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
		global $wpdb;

		if ( isset( $_GET['action'] ) && isset( $_GET['id'] ) ) {
			$campaign_id = absint( $_GET['id'] );

			if ( 'cancel' === $_GET['action'] ) {
				Security::authorize( 'wplm_cancel_campaign_' . $campaign_id );
				
				$wpdb->query( $wpdb->prepare(
					"UPDATE {$wpdb->prefix}leads_campaigns SET status = 'cancelled' WHERE id = %d AND status IN ('pending', 'processing')",
					$campaign_id
				) );
				
				Audit_Log::record( 'campaign_cancelled', 'campaign', $campaign_id );
				echo '<div class="updated"><p>Campanha cancelada com sucesso.</p></div>';
			} elseif ( 'retry' === $_GET['action'] ) {
				Security::authorize( 'wplm_retry_campaign_' . $campaign_id );
				
				if ( Campaign_Handler::retry_campaign( $campaign_id ) ) {
					echo '<div class="updated"><p>Campanha duplicada e adicionada à fila de envio com sucesso.</p></div>';
				} else {
					echo '<div class="error"><p>Erro ao tentar reenviar a campanha.</p></div>';
				}
			}
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
			try {
				SMTP_Config::save_options( $_POST );
				echo '<div class="updated"><p>Configurações salvas com sucesso!</p></div>';
			} catch ( \Throwable $e ) {
				echo '<div class="error"><p><strong>Erro Crítico ao Salvar:</strong> ' . esc_html( $e->getMessage() ) . '</p><p>Verifique a versão do PHP ou módulos instalados no novo servidor.</p></div>';
			}
		}

		if ( isset( $_POST['wplm_test_smtp'] ) ) {
			Security::authorize( 'wplm_save_smtp_' . get_current_user_id() );
			
			// Salva as configurações antes de testar para usar os dados preenchidos no momento
			try {
				SMTP_Config::save_options( $_POST );
			} catch ( \Throwable $e ) {
				echo '<div class="error"><p><strong>Erro Crítico ao Salvar Senha:</strong> ' . esc_html( $e->getMessage() ) . '</p></div>';
				return; // Aborta o teste se falhou ao salvar
			}
			
			// Busca as 3 últimas notícias para o teste
			$test_posts = get_posts( array(
				'post_type'      => 'noticia',
				'post_status'    => 'publish',
				'posts_per_page' => 3,
				'fields'         => 'ids',
			) );

			$html_body = Mailer::build_campaign_body( $test_posts );
			
			// Fallback caso não existam notícias
			if ( empty( $html_body ) ) {
				$html_body = '<h1>Teste de Conexão SMTP</h1><p>E-mail enviado com sucesso, porém nenhuma notícia do tipo "noticia" foi encontrada para compor o template real.</p>';
			}

			$admin_email = get_option( 'admin_email' );

			// Hook temporário para capturar erros detalhados do wp_mail() / PHPMailer
			global $wplm_last_mail_error;
			$wplm_last_mail_error = null;
			$error_logger = function( $wp_error ) {
				global $wplm_last_mail_error;
				$wplm_last_mail_error = $wp_error;
			};
			add_action( 'wp_mail_failed', $error_logger );

			// Hook temporário para capturar log detalhado do SMTP (Debug)
			global $wplm_smtp_debug;
			$wplm_smtp_debug = '';
			$debug_logger = function( $phpmailer ) {
				$phpmailer->SMTPDebug = 3; // Log completo de conexão e dados
				$phpmailer->Debugoutput = function( $str, $level ) {
					global $wplm_smtp_debug;
					$wplm_smtp_debug .= esc_html( $str ) . "\n";
				};
			};
			add_action( 'phpmailer_init', $debug_logger, 999 );

			$success = Mailer::send( 
				$admin_email, 
				'Teste de Conexão SMTP - WP Leads Mailer', 
				$html_body
			);

			remove_action( 'wp_mail_failed', $error_logger );
			remove_action( 'phpmailer_init', $debug_logger, 999 );

			if ( $success ) {
				Audit_Log::record( 'smtp_test_sent', 'settings', 0, array( 'to' => $admin_email ) );
				echo '<div class="updated" style="padding-bottom: 10px;">';
				echo '<p><strong>E-mail de teste processado pelo WordPress com sucesso para ' . esc_html( $admin_email ) . '!</strong></p>';
				echo '<p>Se o e-mail não chegar na sua caixa de entrada, verifique o log de conexão abaixo para ver se o servidor SMTP bloqueou silenciosamente ou se está usando a função padrão de mail() do servidor em vez do SMTP.</p>';
				if ( ! empty( $wplm_smtp_debug ) ) {
					echo '<p><strong>Log de Conexão SMTP (Sucesso):</strong></p>';
					echo '<pre style="background: #fff; padding: 10px; border: 1px solid #ccc; max-width: 100%; overflow: auto; max-height: 300px;">' . wp_kses_post( $wplm_smtp_debug ) . '</pre>';
				} else {
					echo '<p style="color:#d63638;"><strong>AVISO:</strong> O log SMTP está vazio! Isso indica que as configurações SMTP (Host ou Senha) não foram carregadas corretamente e o WordPress tentou enviar usando a função de e-mail padrão do servidor de hospedagem (que geralmente não funciona).</p>';
				}
				echo '</div>';
			} else {
				$error_msg = 'Falha desconhecida no wp_mail().';
				if ( is_wp_error( $wplm_last_mail_error ) ) {
					$error_msg = $wplm_last_mail_error->get_error_message();
				}
				
				echo '<div class="error" style="padding-bottom: 10px;">';
				echo '<p><strong>Falha no envio do e-mail de teste.</strong> Verifique suas configurações SMTP.</p>';
				echo '<p><strong>Motivo / Log de Erro:</strong> <code style="color: #d63638;">' . esc_html( $error_msg ) . '</code></p>';
				
				if ( ! empty( $wplm_smtp_debug ) ) {
					echo '<p><strong>Log de Conexão SMTP (Falha):</strong></p>';
					echo '<pre style="background: #fff; padding: 10px; border: 1px solid #ccc; max-width: 100%; overflow: auto; max-height: 300px;">' . wp_kses_post( $wplm_smtp_debug ) . '</pre>';
				}
				
				if ( is_wp_error( $wplm_last_mail_error ) && $wplm_last_mail_error->get_error_data() ) {
					echo '<p><strong>Detalhes Técnicos:</strong></p>';
					echo '<pre style="background: #fff; padding: 10px; border: 1px solid #ccc; overflow: auto;">' . esc_html( print_r( $wplm_last_mail_error->get_error_data(), true ) ) . '</pre>';
				}
				echo '</div>';
			}
		}

		require_once WPLM_PATH . 'admin/views/view-settings.php';
	}
}
