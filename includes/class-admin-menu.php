<?php
namespace WPLM;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registro de menus e submenus administrativos.
 */
class Admin_Menu {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menus' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'admin_post_wplm_clear_history', array( __CLASS__, 'handle_clear_history' ) );
	}

	public static function handle_clear_history() {
		Security::authorize( 'wplm_clear_history_' . get_current_user_id() );

		global $wpdb;
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}leads_campaigns" );
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}leads_mailer_logs" );

		Audit_Log::record( 'history_cleared', 'settings', 0 );
		wp_safe_redirect( admin_url( 'admin.php?page=wplm-settings&cleared=1' ) );
		exit;
	}

	public static function register_menus() {
		$cap = Capabilities::CAPABILITY;

		// 1. Menu NOVO: Clientes
		add_menu_page(
			'Clientes',
			'Clientes',
			$cap,
			'edit.php?post_type=' . CPT_Taxonomy::POST_TYPE,
			'',
			'dashicons-groups',
			26
		);

		add_submenu_page(
			'edit.php?post_type=' . CPT_Taxonomy::POST_TYPE,
			'Todos os Clientes',
			'Todos os Clientes',
			$cap,
			'edit.php?post_type=' . CPT_Taxonomy::POST_TYPE
		);

		add_submenu_page(
			'edit.php?post_type=' . CPT_Taxonomy::POST_TYPE,
			'Grupos',
			'Grupos',
			$cap,
			'edit-tags.php?taxonomy=' . CPT_Taxonomy::TAXONOMY . '&post_type=' . CPT_Taxonomy::POST_TYPE
		);

		// 2. Menu Leads (Campanhas)
		add_menu_page(
			'Leads Mailer',
			'Leads',
			$cap,
			'wplm-campaigns',
			array( __CLASS__, 'render_campaign_list' ),
			'dashicons-email-alt',
			25
		);

		add_submenu_page(
			'wplm-campaigns',
			'Dashboard de Campanhas',
			'Campanhas',
			$cap,
			'wplm-campaigns',
			array( __CLASS__, 'render_campaign_list' )
		);

		add_submenu_page(
			'wplm-campaigns',
			'Novo Envio',
			'Novo Envio',
			$cap,
			'wplm-new-campaign',
			array( __CLASS__, 'render_campaign_form' )
		);

		add_submenu_page(
			'wplm-campaigns',
			'Detalhes da Campanha',
			'',
			$cap,
			'wplm-campaign-detail',
			array( __CLASS__, 'render_campaign_detail' )
		);

		add_submenu_page(
			'wplm-campaigns',
			'Configurações SMTP',
			'Configurações',
			$cap,
			'wplm-settings',
			array( __CLASS__, 'render_settings' )
		);
	}

	public static function enqueue_assets( $hook ) {
		$screens = array( 'wplm-', 'wplm_cliente', 'cliente', 'grupo_cliente' );
		$match = false;
		foreach ( $screens as $s ) {
			if ( strpos( $hook, $s ) !== false ) {
				$match = true;
				break;
			}
		}

		if ( ! $match ) {
			return;
		}

		// Select2
		wp_enqueue_style( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0' );
		wp_enqueue_script( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array( 'jquery' ), '4.1.0', true );

		wp_enqueue_style( 'wplm-admin-style', WPLM_URL . 'admin/css/admin-style.css', array(), WPLM_VERSION );
		wp_enqueue_script( 'wplm-admin-script', WPLM_URL . 'admin/js/admin-script.js', array( 'jquery', 'select2' ), time(), true );

		wp_localize_script( 'wplm-admin-script', 'wplm', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'rest_url' => get_rest_url( null, 'wplm/v1/' ),
			'nonce'    => wp_create_nonce( 'wp_rest' ),
		) );
	}

	public static function render_campaign_list() {
		if ( isset( $_GET['action'] ) && 'cancel' === $_GET['action'] && isset( $_GET['id'] ) ) {
			$campaign_id = absint( $_GET['id'] );
			Security::authorize( 'wplm_cancel_campaign_' . $campaign_id );
			
			global $wpdb;
			$wpdb->update(
				$wpdb->prefix . 'leads_campaigns',
				array( 'status' => 'cancelled' ),
				array( 'id' => $campaign_id, 'status' => 'pending' )
			);
			
			Audit_Log::record( 'campaign_cancelled', 'campaign', $campaign_id );
			echo '<div class="updated"><p>Campanha cancelada com sucesso.</p></div>';
		}

		require_once WPLM_PATH . 'admin/views/view-campaign-list.php';
	}

	public static function render_campaign_detail() {
		require_once WPLM_PATH . 'admin/views/view-campaign-detail.php';
	}

	public static function render_campaign_form() {
		require_once WPLM_PATH . 'admin/views/view-campaign-form.php';
	}

	public static function render_settings() {
		if ( isset( $_POST['wplm_save_settings'] ) ) {
			Security::authorize( 'wplm_save_smtp_' . get_current_user_id() );
			SMTP_Config::save_options( $_POST );
			echo '<div class="updated"><p>Configurações salvas com sucesso!</p></div>';
		}

		if ( isset( $_POST['wplm_test_smtp'] ) ) {
			Security::authorize( 'wplm_save_smtp_' . get_current_user_id() );
			
			$admin_email = get_option( 'admin_email' );
			$success = Mailer::send( 
				$admin_email, 
				'Teste de Conexão SMTP - WP Leads Mailer', 
				'<p>Este é um e-mail de teste enviado para validar suas configurações SMTP.</p>' 
			);

			if ( $success ) {
				Audit_Log::record( 'smtp_test_sent', 'settings', 0, array( 'to' => $admin_email ) );
				echo '<div class="updated"><p>E-mail de teste enviado com sucesso para ' . esc_html( $admin_email ) . '!</p></div>';
			} else {
				echo '<div class="error"><p>Falha no envio do e-mail de teste. Verifique suas configurações SMTP.</p></div>';
			}
		}

		require_once WPLM_PATH . 'admin/views/view-settings.php';
	}
}
