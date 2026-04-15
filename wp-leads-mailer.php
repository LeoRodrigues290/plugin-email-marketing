<?php
/**
 * Plugin Name: WP Leads Mailer
 * Description: Sistema avançado de envio de e-mails para leads com segurança Sodium, processamento em lote e log de auditoria.
 * Version: 3.0.0
 * Author: InkyDigital
 * Text Domain: wp-leads-mailer
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define constantes do plugin
define( 'WPLM_VERSION', '3.0.0' );
define( 'WPLM_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPLM_URL', plugin_dir_url( __FILE__ ) );
define( 'WPLM_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Autoloader manual simples para as classes do plugin
 */
spl_autoload_register( function ( $class ) {
	$prefix = 'WPLM\\';
	$base_dir = WPLM_PATH . 'includes/';

	$len = strlen( $prefix );
	if ( strncmp( $prefix, $class, $len ) !== 0 ) {
		return;
	}

	$relative_class = substr( $class, $len );
	$file = $base_dir . 'class-' . str_replace( '_', '-', strtolower( $relative_class ) ) . '.php';

	if ( file_exists( $file ) ) {
		require $file;
	}
} );

/**
 * Inicialização do Plugin
 */
function wplm_init() {
	// Requisitos de ativação
	register_activation_hook( __FILE__, array( 'WPLM\\Activator', 'activate' ) );
	register_deactivation_hook( __FILE__, array( 'WPLM\\Activator', 'deactivate' ) );

	// Carrega as funcionalidades se os requisitos forem atendidos
	if ( WPLM\Activator::check_requirements( false ) ) {
		WPLM\Capabilities::init();
		WPLM\CPT_Taxonomy::init();
		WPLM\SMTP_Config::init();
		WPLM\Campaign_Handler::init();
		WPLM\Cron_Batch::init();
		WPLM\REST_API::init();
		WPLM\Admin_Menu::init();
		WPLM\Importer::init();
	}
}
add_action( 'plugins_loaded', 'wplm_init' );
