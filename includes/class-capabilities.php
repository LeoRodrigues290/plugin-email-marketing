<?php
namespace WPLM;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gerenciamento de permissões customizadas.
 */
class Capabilities {

	const CAPABILITY = 'wplm_manage';

	/**
	 * Inicializa ganchos relacionados a permissões.
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'add_to_admin' ) );
	}

	/**
	 * Adiciona a capability ao papel de administrador.
	 */
	public static function add_to_admin() {
		$role = get_role( 'administrator' );
		if ( $role && ! $role->has_cap( self::CAPABILITY ) ) {
			$role->add_cap( self::CAPABILITY );
		}
	}

	/**
	 * Remove a capability de todos os papéis (usado no uninstall).
	 */
	public static function remove_all() {
		if ( ! function_exists( 'wp_roles' ) ) {
			return;
		}

		foreach ( wp_roles()->roles as $role_name => $role_data ) {
			$role = get_role( $role_name );
			if ( $role ) {
				$role->remove_cap( self::CAPABILITY );
			}
		}
	}
}
