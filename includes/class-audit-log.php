<?php
namespace WPLM;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registra ações administrativas de forma imutável.
 */
class Audit_Log {

	/**
	 * Grava um novo registro de auditoria.
	 */
	public static function record( string $action, string $object_type = '', int $object_id = 0, array $metadata = [] ): void {
		global $wpdb;

		// Remove campos sensíveis
		$sensitive_keys = array( 'password', 'smtp_password', '_wpnonce', 'key', 'token' );
		foreach ( $sensitive_keys as $key ) {
			if ( isset( $metadata[ $key ] ) ) {
				unset( $metadata[ $key ] );
			}
		}

		$wpdb->insert(
			$wpdb->prefix . 'leads_audit_log',
			array(
				'user_id'     => get_current_user_id(),
				'action'      => sanitize_key( $action ),
				'object_type' => sanitize_key( $object_type ),
				'object_id'   => absint( $object_id ),
				'metadata'    => wp_json_encode( $metadata ),
				'ip_address'  => self::get_client_ip(),
				'user_agent'  => substr( (string) ( $_SERVER['HTTP_USER_AGENT'] ?? '' ), 0, 255 ),
			),
			array( '%d', '%s', '%s', '%d', '%s', '%s', '%s' )
		);
	}

	/**
	 * Obtém o IP do cliente de forma segura.
	 */
	private static function get_client_ip(): string {
		$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
		return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '0.0.0.0';
	}
}
