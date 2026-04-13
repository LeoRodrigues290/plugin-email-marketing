<?php
/**
 * Elimina todos os dados do plugin na desinstalação.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// 1. Remover Tabelas
$tables = array(
	$wpdb->prefix . 'leads_campaigns',
	$wpdb->prefix . 'leads_mailer_logs',
	$wpdb->prefix . 'leads_audit_log',
);

foreach ( $tables as $table ) {
	$wpdb->query( "DROP TABLE IF EXISTS $table" );
}

// 2. Remover Opções
$wpdb->query( $wpdb->prepare(
	"DELETE FROM $wpdb->options WHERE option_name LIKE %s",
	'wplm_%'
) );

// 3. Remover Transients
$wpdb->query( $wpdb->prepare(
	"DELETE FROM $wpdb->options WHERE option_name LIKE %s OR option_name LIKE %s",
	'_transient_wplm_%',
	'_transient_timeout_wplm_%'
) );

// 4. Remover Capabilities
if ( function_exists( 'wp_roles' ) ) {
	foreach ( wp_roles()->roles as $role_name => $role_data ) {
		$role = get_role( $role_name );
		if ( $role ) {
			$role->remove_cap( 'wplm_manage' );
		}
	}
}

// 5. Limpar agendamentos de Cron
wp_clear_scheduled_hook( 'wp_leads_process_batch' );
