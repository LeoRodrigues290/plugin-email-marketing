<?php
namespace WPLM;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Singleton para operações no banco de dados com suporte a transações.
 */
class DB {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Inicia uma transação.
	 */
	public function start_transaction() {
		global $wpdb;
		$wpdb->query( 'START TRANSACTION' );
	}

	/**
	 * Commita uma transação.
	 */
	public function commit() {
		global $wpdb;
		$wpdb->query( 'COMMIT' );
	}

	/**
	 * Rollback de uma transação.
	 */
	public function rollback() {
		global $wpdb;
		$wpdb->query( 'ROLLBACK' );
	}

	/**
	 * Atalho para prepare e query.
	 */
	public function query( $query, ...$args ) {
		global $wpdb;
		if ( empty( $args ) ) {
			return $wpdb->query( $query );
		}
		return $wpdb->query( $wpdb->prepare( $query, ...$args ) );
	}

	/**
	 * Atalho para get_results com prepare.
	 */
	public function get_results( $query, ...$args ) {
		global $wpdb;
		if ( empty( $args ) ) {
			return $wpdb->get_results( $query );
		}
		return $wpdb->get_results( $wpdb->prepare( $query, ...$args ) );
	}

	/**
	 * Atalho para get_row com prepare.
	 */
	public function get_row( $query, ...$args ) {
		global $wpdb;
		if ( empty( $args ) ) {
			return $wpdb->get_row( $query );
		}
		return $wpdb->get_row( $wpdb->prepare( $query, ...$args ) );
	}

	/**
	 * Atalho para get_var com prepare.
	 */
	public function get_var( $query, ...$args ) {
		global $wpdb;
		if ( empty( $args ) ) {
			return $wpdb->get_var( $query );
		}
		return $wpdb->get_var( $wpdb->prepare( $query, ...$args ) );
	}
}
