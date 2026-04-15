<?php
namespace WPLM;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Processamento do formulário de nova campanha.
 */
class Campaign_Handler {

	public static function init() {
		add_action( 'admin_post_wplm_create_campaign', array( __CLASS__, 'handle_submission' ) );
	}

	/**
	 * Processa o envio do formulário.
	 */
	public static function handle_submission() {
		$user_id = get_current_user_id();
		Security::authorize( 'wplm_create_campaign_' . $user_id );

		// 1. Rate Limiting
		if ( ! Rate_Limiter::can_create_campaign( $user_id ) ) {
			wp_die( esc_html__( 'Aguarde antes de criar outra campanha ou há muitas campanhas em andamento.', 'wp-leads-mailer' ), 429 );
		}

		// 2. Parse & Validate
		$data   = self::parse_input( $_POST );
		$errors = self::validate( $data );

		if ( ! empty( $errors ) ) {
			wp_die( implode( '<br>', array_map( 'esc_html', $errors ) ) );
		}

		// 3. Coletar destinatários
		$recipients = self::collect_recipients( $data );
		if ( empty( $recipients ) ) {
			wp_die( esc_html__( 'Nenhum destinatário válido encontrado para os critérios selecionados.', 'wp-leads-mailer' ) );
		}

		// 4. Inserção Atômica
		$campaign_id = self::create_campaign_transaction( $data, $recipients );

		if ( $campaign_id ) {
			Rate_Limiter::set_user_lock( $user_id );
			Audit_Log::record( 'campaign_created', 'campaign', $campaign_id, $data );
			
			// Agenda o processamento inicial
			wp_schedule_single_event( time(), 'wp_leads_process_batch', array( $campaign_id ) );

			wp_safe_redirect( admin_url( 'admin.php?page=wplm-campaigns&created=1' ) );
			exit;
		}

		wp_die( esc_html__( 'Erro ao criar a campanha. Tente novamente.', 'wp-leads-mailer' ) );
	}

	/**
	 * Filtra e sanitiza os inputs.
	 */
	private static function parse_input( array $post ): array {
		$allowed_keys = array( 'subject', 'recipient_type', 'group_id', 'client_ids', 'post_ids' );
		$data = array();

		foreach ( $allowed_keys as $key ) {
			if ( isset( $post[ $key ] ) ) {
				if ( is_array( $post[ $key ] ) ) {
					$data[ $key ] = array_map( 'absint', $post[ $key ] );
				} else {
					$data[ $key ] = sanitize_text_field( $post[ $key ] );
				}
			}
		}

		$data['group_id'] = absint( $data['group_id'] ?? 0 );
		$data['subject']  = substr( $data['subject'] ?? '', 0, 200 );

		return $data;
	}

	/**
	 * Valida os dados da campanha.
	 */
	private static function validate( array $data ): array {
		$errors = array();

		if ( empty( $data['subject'] ) ) {
			$errors[] = 'O assunto é obrigatório.';
		}

		if ( ! in_array( $data['recipient_type'] ?? '', array( 'group', 'clients' ), true ) ) {
			$errors[] = 'Tipo de destinatário inválido.';
		}

		if ( 'group' === $data['recipient_type'] && empty( $data['group_id'] ) ) {
			$errors[] = 'Selecione um grupo.';
		}

		if ( 'clients' === $data['recipient_type'] && empty( $data['client_ids'] ) ) {
			$errors[] = 'Selecione ao menos um cliente.';
		}

		if ( empty( $data['post_ids'] ) ) {
			$errors[] = 'Selecione as notícias para o envio.';
		}

		return $errors;
	}

	/**
	 * Busca emails e nomes dos destinatários.
	 */
	private static function collect_recipients( array $data ): array {
		$recipients = array();
		$args = array(
			'post_type'      => CPT_Taxonomy::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		);

		if ( 'group' === $data['recipient_type'] ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => CPT_Taxonomy::TAXONOMY,
					'field'    => 'term_id',
					'terms'    => $data['group_id'],
				),
			);
		} else {
			$args['post__in'] = $data['client_ids'];
		}

		$client_ids = get_posts( $args );

		foreach ( $client_ids as $id ) {
			// Busca o e-mail no meta field customizado
			$email = get_post_meta( $id, 'wplm_email', true );
			if ( ! is_email( $email ) ) {
				continue;
			}
			$recipients[] = array(
				'email' => $email,
				'name'  => get_the_title( $id ),
			);
		}

		return $recipients;
	}

	/**
	 * Realiza a inserção atômica no banco.
	 */
	private static function create_campaign_transaction( array $data, array $recipients ): int {
		global $wpdb;
		$db = DB::get_instance();
		$db->start_transaction();

		try {
			// 1. Inserir Campanha
			$wpdb->insert(
				$wpdb->prefix . 'leads_campaigns',
				array(
					'created_by'       => get_current_user_id(),
					'subject'          => $data['subject'],
					'newsletter_ids'   => wp_json_encode( $data['post_ids'] ),
					'recipient_type'   => $data['recipient_type'],
					'recipient_data'   => wp_json_encode( 'group' === $data['recipient_type'] ? $data['group_id'] : $data['client_ids'] ),
					'total_recipients' => count( $recipients ),
					'status'           => 'pending',
				),
				array( '%d', '%s', '%s', '%s', '%s', '%d', '%s' )
			);

			$campaign_id = $wpdb->insert_id;

			// 2. Inserir Logs de Envio
			foreach ( $recipients as $recipient ) {
				$wpdb->insert(
					$wpdb->prefix . 'leads_mailer_logs',
					array(
						'campaign_id'     => $campaign_id,
						'recipient_email' => $recipient['email'],
						'recipient_name'  => $recipient['name'],
						'status'          => 'pending',
					),
					array( '%d', '%s', '%s', '%s' )
				);
			}

			$db->commit();
			return (int) $campaign_id;
		} catch ( \Throwable $e ) {
			$db->rollback();
			error_log( 'WPLM Error: ' . $e->getMessage() );
			return 0;
		}
	}
}
