<?php
namespace WPLM;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registro de CPT 'cliente' e taxonomia 'grupo_cliente'.
 */
class CPT_Taxonomy {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_taxonomy' ) );
		add_action( 'init', array( __CLASS__, 'register_cpt' ) );
	}

	public static function register_taxonomy() {
		register_taxonomy( 'grupo_cliente', 'cliente', array(
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
		register_post_type( 'cliente', array(
			'labels' => array(
				'name'               => 'Clientes',
				'singular_name'      => 'Cliente',
				'menu_name'          => 'Leads', // Aparecerá sob o menu Leads no futuro
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
			'rewrite'             => array( 'slug' => 'cliente' ),
			'capability_type'     => 'post',
			'has_archive'         => false,
			'hierarchical'        => false,
			'menu_position'       => null,
			'supports'            => array( 'title', 'editor' ), // Título (Nome) e Editor (Obs)
			'show_in_rest'        => true,
		) );
	}
}
