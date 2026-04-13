<?php
namespace WPLM;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Camada de segurança centralizada: Criptografia, Auth e Sanitização.
 */
final class Security {

	/**
	 * Retorna a chave de criptografia derivada dos salts do WordPress.
	 */
	private static function get_key(): string {
		$material = wp_salt( 'auth' ) . wp_salt( 'secure_auth' );
		return substr( hash( 'sha256', $material, true ), 0, SODIUM_CRYPTO_SECRETBOX_KEYBYTES );
	}

	/**
	 * Criptografa uma string usando Sodium (preferencial) ou OpenSSL (fallback).
	 */
	public static function encrypt( string $plaintext ): string {
		if ( empty( $plaintext ) ) {
			return '';
		}

		if ( function_exists( 'sodium_crypto_secretbox' ) ) {
			$nonce  = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
			$cipher = sodium_crypto_secretbox( $plaintext, $nonce, self::get_key() );
			sodium_memzero( $plaintext );
			return base64_encode( $nonce . $cipher );
		}

		// Fallback OpenSSL
		$iv     = random_bytes( 16 );
		$key    = hash( 'sha256', wp_salt( 'auth' ) . wp_salt( 'secure_auth' ), true );
		$cipher = openssl_encrypt( $plaintext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );
		return base64_encode( $iv . $cipher );
	}

	/**
	 * Descriptografa uma string.
	 */
	public static function decrypt( string $encoded ): string|false {
		if ( empty( $encoded ) ) {
			return '';
		}

		$decoded = base64_decode( $encoded, true );
		if ( false === $decoded ) {
			return false;
		}

		if ( function_exists( 'sodium_crypto_secretbox_open' ) ) {
			if ( strlen( $decoded ) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ) {
				return false;
			}
			$nonce  = substr( $decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
			$cipher = substr( $decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
			$plain  = sodium_crypto_secretbox_open( $cipher, $nonce, self::get_key() );
			return $plain;
		}

		// Fallback OpenSSL
		if ( strlen( $decoded ) < 16 ) {
			return false;
		}
		$iv     = substr( $decoded, 0, 16 );
		$cipher = substr( $decoded, 16 );
		$key    = hash( 'sha256', wp_salt( 'auth' ) . wp_salt( 'secure_auth' ), true );
		return openssl_decrypt( $cipher, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );
	}

	/**
	 * Verificação obrigatória em 3 camadas para ações sensíveis.
	 */
	public static function authorize( string $nonce_action ): void {
		// 1. Usuário autenticado
		if ( ! is_user_logged_in() ) {
			wp_die( esc_html__( 'Acesso negado: Usuário não autenticado.', 'wp-leads-mailer' ), 403 );
		}

		// 2. Capability customizada
		if ( ! current_user_can( Capabilities::CAPABILITY ) ) {
			wp_die( esc_html__( 'Acesso negado: Permissão insuficiente.', 'wp-leads-mailer' ), 403 );
		}

		// 3. Verificação de Nonce
		$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_key( $_POST['_wpnonce'] ) : '';
		if ( ! wp_verify_nonce( $nonce, $nonce_action ) ) {
			wp_die( esc_html__( 'Acesso negado: Token de segurança inválido.', 'wp-leads-mailer' ), 403 );
		}
	}
}
