<?php
namespace WPLM;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gerenciamento de configurações SMTP e hook no PHPMailer.
 */
class SMTP_Config {

	const OPTION_NAME = 'wplm_smtp_options';

	public static function init() {
		add_action( 'phpmailer_init', array( __CLASS__, 'configure' ) );
	}

	/**
	 * Configura o PHPMailer com os dados salvos.
	 */
	public static function configure( $phpmailer ) {
		$opts = self::get_options();
		
		if ( empty( $opts['host'] ) ) {
			return;
		}

		$password = Security::decrypt( $opts['password'] ?? '' );
		if ( false === $password ) {
			return; // Senha corrompida ou erro na descriptografia
		}

		$phpmailer->isSMTP();
		$phpmailer->Host       = $opts['host'];
		$phpmailer->Port       = (int) ( $opts['port'] ?? 587 );
		$phpmailer->SMTPAuth   = ! empty( $opts['username'] );
		$phpmailer->Username   = $opts['username'] ?? '';
		$phpmailer->Password   = $password;
		$phpmailer->SMTPSecure = $opts['encryption'] === 'ssl' ? 'ssl' : 'tls';
		
		$from_email = ! empty( $opts['from_email'] ) ? $opts['from_email'] : get_option( 'admin_email' );
		$from_name  = ! empty( $opts['from_name'] ) ? $opts['from_name'] : get_option( 'blogname' );
		
		$phpmailer->setFrom( $from_email, $from_name );

		if ( function_exists( 'sodium_memzero' ) ) {
			sodium_memzero( $password );
		}
	}

	/**
	 * Salva as opções SMTP de forma segura.
	 */
	public static function save_options( array $data ): bool {
		$current = self::get_options();
		
		$new_options = array(
			'host'       => sanitize_text_field( $data['smtp_host'] ?? '' ),
			'port'       => absint( $data['smtp_port'] ?? 587 ),
			'username'   => sanitize_text_field( $data['smtp_username'] ?? '' ),
			'from_email' => sanitize_email( $data['smtp_from_email'] ?? '' ),
			'from_name'  => sanitize_text_field( $data['smtp_from_name'] ?? '' ),
			'encryption' => in_array( $data['smtp_encryption'] ?? '', array( 'none', 'ssl', 'tls' ), true ) ? $data['smtp_encryption'] : 'tls',
		);

		// Atualiza a senha apenas se fornecida
		if ( ! empty( $data['smtp_password'] ) ) {
			$new_options['password'] = Security::encrypt( $data['smtp_password'] );
		} else {
			$new_options['password'] = $current['password'] ?? '';
		}

		$updated = update_option( self::OPTION_NAME, $new_options, false );
		
		if ( $updated || serialize( $current ) === serialize( $new_options ) ) {
			Audit_Log::record( 'smtp_updated', 'settings' );
			return true;
		}

		return false;
	}

	/**
	 * Obtém as opções salvas.
	 */
	public static function get_options(): array {
		return get_option( self::OPTION_NAME, array() );
	}
}
