<?php
namespace WPLM;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Controle de frequência de criação de campanhas.
 */
class Rate_Limiter {

	const MAX_ACTIVE_CAMPAIGNS = 2;
	const MIN_INTERVAL_SECONDS = 30;

	/**
	 * Verifica se o usuário pode criar uma nova campanha.
	 */
	public static function can_create_campaign( int $user_id ): bool {
		// 1. Verifica intervalo individual (transient lock)
		$lock_key = 'wplm_user_lock_' . $user_id;
		if ( get_transient( $lock_key ) ) {
			return false;
		}

		// 2. Verifica limite global de campanhas em processamento
		global $wpdb;
		$active = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}leads_campaigns WHERE status IN (%s, %s)",
			'pending',
			'processing'
		) );

		return $active < self::MAX_ACTIVE_CAMPAIGNS;
	}

	/**
	 * Define o lock para o usuário após criação da campanha.
	 */
	public static function set_user_lock( int $user_id ): void {
		set_transient( 'wplm_user_lock_' . $user_id, 1, self::MIN_INTERVAL_SECONDS );
	}
}
