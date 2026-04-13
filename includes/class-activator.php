<?php
namespace WPLM;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lógica de ativação e requisitos.
 */
class Activator {

	/**
	 * Executado ao ativar o plugin.
	 */
	public static function activate() {
		if ( ! self::check_requirements( true ) ) {
			return;
		}

		self::create_tables();
		Capabilities::add_to_admin();
		flush_rewrite_rules();
	}

	/**
	 * Executado ao desativar o plugin.
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'wp_leads_process_batch' );
	}

	/**
	 * Verifica requisitos técnicos.
	 */
	public static function check_requirements( $display_notice = true ) {
		$errors = array();

		if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
			$errors[] = 'O plugin requer PHP 7.4 ou superior.';
		}

		if ( version_compare( get_bloginfo( 'version' ), '6.0', '<' ) ) {
			$errors[] = 'O plugin requer WordPress 6.0 ou superior.';
		}

		if ( ! function_exists( 'sodium_crypto_secretbox' ) && ! function_exists( 'openssl_encrypt' ) ) {
			$errors[] = 'O servidor requer a extensão Sodium ou OpenSSL para criptografia.';
		}

		if ( ! empty( $errors ) ) {
			if ( $display_notice ) {
				add_action( 'admin_notices', function () use ( $errors ) {
					echo '<div class="error"><p>' . implode( '<br>', array_map( 'esc_html', $errors ) ) . '</p></div>';
				} );
				deactivate_plugins( WPLM_BASENAME );
			}
			return false;
		}

		return true;
	}

	/**
	 * Cria as tabelas do banco de dados.
	 */
	private static function create_tables() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// 1. Campanhas
		$sql_campaigns = "CREATE TABLE {$wpdb->prefix}leads_campaigns (
			id bigint(20) UNSIGNED AUTO_INCREMENT,
			created_by bigint(20) UNSIGNED NOT NULL,
			subject varchar(200) NOT NULL,
			newsletter_ids text NOT NULL,
			recipient_type enum('group','clients') NOT NULL,
			recipient_data text NOT NULL,
			total_recipients int(11) UNSIGNED DEFAULT 0,
			sent_count int(11) UNSIGNED DEFAULT 0,
			failed_count int(11) UNSIGNED DEFAULT 0,
			status enum('pending','processing','completed','cancelled','failed') NOT NULL DEFAULT 'pending',
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY status (status),
			KEY created_by (created_by)
		) $charset_collate;";

		// 2. Logs de envio
		$sql_logs = "CREATE TABLE {$wpdb->prefix}leads_mailer_logs (
			id bigint(20) UNSIGNED AUTO_INCREMENT,
			campaign_id bigint(20) UNSIGNED NOT NULL,
			recipient_email varchar(254) NOT NULL,
			recipient_name varchar(255) DEFAULT '',
			status enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
			error_message text DEFAULT NULL,
			attempts tinyint(3) UNSIGNED DEFAULT 0,
			sent_at datetime DEFAULT NULL,
			PRIMARY KEY (id),
			KEY campaign_id (campaign_id),
			KEY status (status),
			KEY campaign_status (campaign_id, status)
		) $charset_collate;";

		// 3. Log de Auditoria
		$sql_audit = "CREATE TABLE {$wpdb->prefix}leads_audit_log (
			id bigint(20) UNSIGNED AUTO_INCREMENT,
			user_id bigint(20) UNSIGNED NOT NULL,
			action varchar(100) NOT NULL,
			object_type varchar(50) DEFAULT '',
			object_id bigint(20) UNSIGNED DEFAULT 0,
			metadata longtext DEFAULT '',
			ip_address varchar(45) NOT NULL,
			user_agent varchar(255) DEFAULT '',
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY action (action),
			KEY created_at (created_at)
		) $charset_collate;";

		dbDelta( $sql_campaigns );
		dbDelta( $sql_logs );
		dbDelta( $sql_audit );
	}
}
