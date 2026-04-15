<?php
namespace WPLM;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registro de CPT 'wplm_cliente' e taxonomia 'grupo_cliente'.
 */
class CPT_Taxonomy {

	const POST_TYPE = 'wplm_cliente';
	const TAXONOMY  = 'grupo_cliente';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_taxonomy' ) );
		add_action( 'init', array( __CLASS__, 'register_cpt' ) );
		
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post', array( __CLASS__, 'save_meta_fields' ) );

		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( __CLASS__, 'add_custom_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'render_custom_columns' ), 10, 2 );
	}

	public static function register_taxonomy() {
		register_taxonomy( self::TAXONOMY, self::POST_TYPE, array(
			'labels' => array(
				'name'              => 'Grupos de Clientes',
				'singular_name'     => 'Grupo de Cliente',
				'search_items'      => 'Buscar Grupos',
				'all_items'         => 'Todos os Grupos',
				'parent_item'       => 'Grupo Pai',
				'parent_item_colon' => 'Grupo Pai:',
				'edit_item'         => 'Editar Grupo',
				'update_item'       => 'Atualizar Grupo',
				'add_new_item'      => 'Adicionar Novo Grupo',
				'new_item_name'     => 'Nome do Novo Grupo',
				'menu_name'         => 'Grupos',
			),
			'hierarchical'      => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'grupo-cliente' ),
			'show_in_rest'      => true,
		) );
	}

	public static function register_cpt() {
		register_post_type( self::POST_TYPE, array(
			'labels' => array(
				'name'               => 'Clientes',
				'singular_name'      => 'Cliente',
				'menu_name'          => 'Clientes',
				'name_admin_bar'     => 'Cliente',
				'add_new'            => 'Adicionar Novo',
				'add_new_item'       => 'Adicionar Novo Cliente',
				'new_item'           => 'Novo Cliente',
				'edit_item'          => 'Editar Cliente',
				'view_item'          => 'Ver Cliente',
				'all_items'          => 'Todos os Clientes',
				'search_items'       => 'Buscar Clientes',
				'parent_item_colon'  => 'Clientes Pai:',
				'not_found'          => 'Nenhum cliente encontrado.',
				'not_found_in_trash' => 'Nenhum cliente encontrado na lixeira.',
			),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => false, // Será adicionado via Admin_Menu
			'query_var'           => true,
			'rewrite'             => array( 'slug' => 'clientes' ),
			'capability_type'     => 'post',
			'has_archive'         => false,
			'hierarchical'        => false,
			'supports'            => array( 'title' ),
			'show_in_rest'        => true,
		) );
	}

	public static function add_meta_boxes() {
		add_meta_box(
			'wplm_cliente_details',
			'Detalhes do Cliente',
			array( __CLASS__, 'render_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	public static function render_meta_box( $post ) {
		$fields = array(
			'wplm_empresa'   => 'Empresa',
			'wplm_email'     => 'Email',
			'wplm_whatsapp'  => 'WhatsApp',
			'wplm_telefone'  => 'Telefone',
			'wplm_ramal'     => 'Ramal',
			'wplm_endereco'  => 'Endereço',
		);

		foreach ( $fields as $key => $label ) {
			$value = get_post_meta( $post->ID, $key, true );
			echo '<p>';
			echo '<label for="' . esc_attr( $key ) . '" style="display:block; font-weight:bold; margin-bottom:5px;">' . esc_html( $label ) . '</label>';
			echo '<input type="text" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" class="widefat">';
			echo '</p>';
		}
		wp_nonce_field( 'wplm_save_cliente_meta', 'wplm_cliente_meta_nonce' );
	}

	public static function save_meta_fields( $post_id ) {
		if ( ! isset( $_POST['wplm_cliente_meta_nonce'] ) || ! wp_verify_nonce( $_POST['wplm_cliente_meta_nonce'], 'wplm_save_cliente_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( isset( $_POST['post_type'] ) && self::POST_TYPE === $_POST['post_type'] ) {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}
		}

		$fields = array( 'wplm_empresa', 'wplm_email', 'wplm_whatsapp', 'wplm_telefone', 'wplm_ramal', 'wplm_endereco' );
		foreach ( $fields as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				update_post_meta( $post_id, $key, sanitize_text_field( $_POST[ $key ] ) );
			}
		}
	}

	public static function add_custom_columns( $columns ) {
		$new_columns = array(
			'cb'            => $columns['cb'],
			'title'         => 'Nome',
			'taxonomy-grupo_cliente' => $columns['taxonomy-grupo_cliente'],
			'wplm_empresa'  => 'Empresa',
			'wplm_email'    => 'Email',
			'wplm_whatsapp' => 'WhatsApp',
			'date'          => $columns['date'],
		);
		return $new_columns;
	}

	public static function render_custom_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'wplm_empresa':
				echo esc_html( get_post_meta( $post_id, 'wplm_empresa', true ) ?: '-' );
				break;
			case 'wplm_email':
				echo esc_html( get_post_meta( $post_id, 'wplm_email', true ) ?: '-' );
				break;
			case 'wplm_whatsapp':
				echo esc_html( get_post_meta( $post_id, 'wplm_whatsapp', true ) ?: '-' );
				break;
		}
	}
}
