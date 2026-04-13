<?php
namespace WPLM;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Processamento em lote de e-mails via WP-Cron.
 */
class Cron_Batch {

	public static function init() {
		add_action( 'wp_leads_process_batch', array( __CLASS__, 'process' ) );
	}

	/**
	 * Processa um lote de envios para uma campanha.
	 */
	public static function process( int $campaign_id ) {
		global $wpdb;

		// 1. Verifica se a campanha é válida
		$campaign = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}leads_campaigns WHERE id = %d AND status IN (%s, %s)",
			$campaign_id,
			'pending',
			'processing'
		) );

		if ( ! $campaign ) {
			return;
		}

		// 2. Marca como processando
		$wpdb->update(
			$wpdb->prefix . 'leads_campaigns',
			array( 'status' => 'processing' ),
			array( 'id' => $campaign_id )
		);

		// 3. Busca lote de pendentes
		$batch_size = apply_filters( 'wplm_batch_size', 15 );
		$logs = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}leads_mailer_logs 
			 WHERE campaign_id = %d AND status = %s 
			 ORDER BY id ASC LIMIT %d",
			$campaign_id,
			'pending',
			$batch_size
		) );

		if ( empty( $logs ) ) {
			self::complete_campaign( $campaign_id );
			return;
		}

		// 4. Carrega posts uma vez para evitar múltiplas queries
		$post_ids = json_decode( $campaign->newsletter_ids, true );
		$html_body = Mailer::build_campaign_body( $post_ids );

		// 5. Loop de envio
		$sent_in_this_batch   = 0;
		$failed_in_this_batch = 0;

		foreach ( $logs as $log ) {
			$success = Mailer::send( $log->recipient_email, $campaign->subject, $html_body );

			if ( $success ) {
				$wpdb->update(
					$wpdb->prefix . 'leads_mailer_logs',
					array( 'status' => 'sent', 'sent_at' => current_time( 'mysql' ) ),
					array( 'id' => $log->id )
				);
				$sent_in_this_batch++;
			} else {
				// Retry dinâmico: se falhou, tenta até 2 vezes
				if ( $log->attempts < 1 ) {
					$wpdb->update(
						$wpdb->prefix . 'leads_mailer_logs',
						array( 'attempts' => $log->attempts + 1 ),
						array( 'id' => $log->id )
					);
				} else {
					$wpdb->update(
						$wpdb->prefix . 'leads_mailer_logs',
						array( 'status' => 'failed', 'error_message' => 'PHPMailer error' ),
						array( 'id' => $log->id )
					);
					$failed_in_this_batch++;
				}
			}
		}

		// 6. Atualiza contadores da campanha
		$wpdb->query( $wpdb->prepare(
			"UPDATE {$wpdb->prefix}leads_campaigns 
			 SET sent_count = sent_count + %d, failed_count = failed_count + %d 
			 WHERE id = %d",
			$sent_in_this_batch,
			$failed_in_this_batch,
			$campaign_id
		) );

		// 7. Verifica se ainda há mais para enviar
		$remaining = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}leads_mailer_logs WHERE campaign_id = %d AND status = %s",
			$campaign_id,
			'pending'
		) );

		if ( $remaining > 0 ) {
			wp_schedule_single_event( time() + 60, 'wp_leads_process_batch', array( $campaign_id ) );
		} else {
			self::complete_campaign( $campaign_id );
		}
	}

	/**
	 * Finaliza a campanha e registra no audit log.
	 */
	private static function complete_campaign( int $campaign_id ) {
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'leads_campaigns',
			array( 'status' => 'completed' ),
			array( 'id' => $campaign_id )
		);
		Audit_Log::record( 'campaign_completed', 'campaign', $campaign_id );
	}
}
