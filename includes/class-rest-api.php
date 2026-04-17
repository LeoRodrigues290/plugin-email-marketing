<?php
namespace WPLM;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Endpoints REST para Select2 e Polling de Progresso.
 */
class REST_API {

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function register_routes() {
		$namespace = 'wplm/v1';

		// 1. Busca de Clientes
		register_rest_route( $namespace, '/clients', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'search_clients' ),
			'permission_callback' => array( __CLASS__, 'check_permission' ),
		) );

		// 2. Busca de Grupos/Taxonomia
		register_rest_route( $namespace, '/groups', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'search_groups' ),
			'permission_callback' => array( __CLASS__, 'check_permission' ),
		) );

		// 3. Busca de Posts (Notícias)
		register_rest_route( $namespace, '/posts', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'search_posts' ),
			'permission_callback' => array( __CLASS__, 'check_permission' ),
		) );

		// 4. Progresso da Campanha
		register_rest_route( $namespace, '/campaign/(?P<id>\d+)/progress', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'get_campaign_progress' ),
			'permission_callback' => array( __CLASS__, 'check_permission' ),
		) );
	}

	/**
	 * Verifica permissão básica para os endpoints.
	 */
	public static function check_permission() {
		return is_user_logged_in() && current_user_can( Capabilities::CAPABILITY );
	}

	/**
	 * Busca clientes por título (Select2 AJAX).
	 */
	public static function search_clients( \WP_REST_Request $request ) {
		$search = sanitize_text_field( $request->get_param( 'q' ) );
		$page   = max( 1, (int) $request->get_param( 'page' ) );

		$query = new \WP_Query( array(
			'post_type'      => CPT_Taxonomy::POST_TYPE,
			'post_status'    => 'publish',
			's'              => $search,
			'posts_per_page' => 20,
			'paged'          => $page,
		) );

		$results = array();
		foreach ( $query->posts as $post ) {
			$results[] = array(
				'id'   => $post->ID,
				'text' => $post->post_title,
			);
		}

		return rest_ensure_response( array(
			'results'    => $results,
			'pagination' => array( 'more' => $query->max_num_pages > $page ),
		) );
	}

	/**
	 * Busca grupos de clientes.
	 */
	public static function search_groups( \WP_REST_Request $request ) {
		$terms = get_terms( array(
			'taxonomy'   => CPT_Taxonomy::TAXONOMY,
			'hide_empty' => false,
			'search'     => sanitize_text_field( $request->get_param( 'q' ) ),
		) );

		$results = array();
		foreach ( $terms as $term ) {
			$results[] = array(
				'id'   => $term->term_id,
				'text' => $term->name,
			);
		}

		return rest_ensure_response( array( 'results' => $results ) );
	}

	/**
	 * Busca posts (notícias).
	 */
	public static function search_posts( \WP_REST_Request $request ) {
		$search = sanitize_text_field( $request->get_param( 'q' ) );
		$page   = max( 1, (int) $request->get_param( 'page' ) );

		$query_args = array(
			'post_type'      => 'noticia',
			'post_status'    => 'publish',
			'orderby'        => 'date',
			'order'          => 'DESC',
			'posts_per_page' => empty( $search ) ? 5 : 20,
			'paged'          => $page,
		);

		if ( ! empty( $search ) ) {
			$query_args['s'] = $search;
		}

		$query = new \WP_Query( $query_args );

		$results = array();
		foreach ( $query->posts as $post ) {
			$results[] = array(
				'id'   => $post->ID,
				'text' => $post->post_title,
			);
		}

		return rest_ensure_response( array(
			'results'    => $results,
			'pagination' => array( 'more' => $query->max_num_pages > $page ),
		) );
	}

	/**
	 * Retorna o progresso em tempo real da campanha.
	 */
	public static function get_campaign_progress( \WP_REST_Request $request ) {
		global $wpdb;
		$id = (int) $request['id'];

		$campaign = $wpdb->get_row( $wpdb->prepare(
			"SELECT total_recipients, sent_count, failed_count, status FROM {$wpdb->prefix}leads_campaigns WHERE id = %d",
			$id
		) );

		if ( ! $campaign ) {
			return new \WP_Error( 'not_found', 'Campanha não encontrada', array( 'status' => 404 ) );
		}

		return rest_ensure_response( array(
			'total'  => (int) $campaign->total_recipients,
			'sent'   => (int) $campaign->sent_count,
			'failed' => (int) $campaign->failed_count,
			'status' => $campaign->status,
		) );
	}
}
