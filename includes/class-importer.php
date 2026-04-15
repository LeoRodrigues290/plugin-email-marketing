<?php
namespace WPLM;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Importador de clientes via CSV com processamento em lotes (batching).
 */
class Importer {

	public static function init() {
		// Handler para upload inicial
		add_action( 'wp_ajax_wplm_import_upload', array( __CLASS__, 'handle_upload' ) );
		// Handler para processamento de chunks
		add_action( 'wp_ajax_wplm_import_chunk', array( __CLASS__, 'handle_chunk' ) );
	}

	/**
	 * Recebe o arquivo e salva temporariamente.
	 */
	public static function handle_upload() {
		check_ajax_referer( 'wplm_import_nonce', 'nonce' );

		if ( ! current_user_can( Capabilities::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => 'Não autorizado.' ) );
		}

		if ( ! isset( $_FILES['csv_file'] ) ) {
			wp_send_json_error( array( 'message' => 'Arquivo não enviado.' ) );
		}

		$upload_dir = wp_upload_dir();
		$wplm_dir   = $upload_dir['basedir'] . '/wplm-imports';

		if ( ! file_exists( $wplm_dir ) ) {
			wp_mkdir_p( $wplm_dir );
		}

		$filename = 'import-' . get_current_user_id() . '-' . time() . '.csv';
		$file_path = $wplm_dir . '/' . $filename;

		if ( move_uploaded_file( $_FILES['csv_file']['tmp_name'], $file_path ) ) {
			// Conta total de linhas
			$line_count = 0;
			$handle = fopen( $file_path, 'r' );
			while ( ! feof( $handle ) ) {
				if ( fgets( $handle ) !== false ) {
					$line_count++;
				}
			}
			fclose( $handle );

			wp_send_json_success( array(
				'file_id'    => $filename,
				'total_rows' => max( 0, $line_count - 1 ), // subtrai header
			) );
		}

		wp_send_json_error( array( 'message' => 'Falha ao salvar arquivo temporário.' ) );
	}

	/**
	 * Processa um lote do CSV.
	 */
	public static function handle_chunk() {
		check_ajax_referer( 'wplm_import_nonce', 'nonce' );

		if ( ! current_user_can( Capabilities::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => 'Não autorizado.' ) );
		}

		$filename = isset( $_POST['file_id'] ) ? sanitize_file_name( $_POST['file_id'] ) : '';
		$line_index = isset( $_POST['line_index'] ) ? intval( $_POST['line_index'] ) : 1; // Começa na 1 (pula header)
		
		$upload_dir = wp_upload_dir();
		$file_path  = $upload_dir['basedir'] . '/wplm-imports/' . $filename;

		if ( ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
			wp_send_json_error( array( 'message' => 'Arquivo não encontrado.' ) );
		}

		$handle = fopen( $file_path, 'r' );
		$batch_size = 30;
		$current_line = 0;
		
		// Pula header
		fgetcsv( $handle );
		$current_line++;

		// Pula para a linha desejada
		while ( $current_line < $line_index && ! feof( $handle ) ) {
			fgets( $handle );
			$current_line++;
		}

		$processed = 0;
		$imported = 0;
		$updated = 0;
		$errors = 0;

		while ( $processed < $batch_size && ( $row = fgetcsv( $handle ) ) !== false ) {
			if ( empty( $row ) || count( $row ) < 4 ) {
				$processed++;
				$current_line++;
				continue;
			}

			// Mapeamento baseado no clientes.sql
			// (id, nome, empresa, email, whatsapp, grupo, endereco, telefone, ramal)
			$data = array(
				'nome'     => $row[1] ?? '',
				'empresa'  => $row[2] ?? '',
				'email'    => $row[3] ?? '',
				'whatsapp' => $row[4] ?? '',
				'grupo'    => $row[5] ?? '',
				'endereco' => $row[6] ?? '',
				'telefone' => $row[7] ?? '',
				'ramal'    => $row[8] ?? '',
			);

			if ( is_email( $data['email'] ) ) {
				$res = self::process_client( $data );
				if ( 'imported' === $res ) {
					$imported++;
				} elseif ( 'updated' === $res ) {
					$updated++;
				} else {
					$errors++;
				}
			} else {
				$errors++;
			}

			$processed++;
			$current_line++;
		}

		$is_finished = feof( $handle );
		fclose( $handle );

		if ( $is_finished ) {
			unlink( $file_path ); // Limpa arquivo ao finalizar
		}

		wp_send_json_success( array(
			'next_line'   => $current_line,
			'is_finished' => $is_finished,
			'imported'    => $imported,
			'updated'     => $updated,
			'errors'      => $errors,
		) );
	}

	/**
	 * Insere ou atualiza um cliente.
	 */
	private static function process_client( $data ) {
		global $wpdb;

		// Busca por e-mail nos meta fields
		$existing_id = $wpdb->get_var( $wpdb->prepare(
			"SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'wplm_email' AND meta_value = %s",
			$data['email']
		) );

		if ( $existing_id ) {
			$post_id = $existing_id;
			$status = 'updated';
		} else {
			$post_id = wp_insert_post( array(
				'post_title'  => sanitize_text_field( $data['nome'] ),
				'post_type'   => CPT_Taxonomy::POST_TYPE,
				'post_status' => 'publish',
			) );
			$status = 'imported';
		}

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			return 'error';
		}

		// Metas
		update_post_meta( $post_id, 'wplm_empresa', sanitize_text_field( $data['empresa'] ) );
		update_post_meta( $post_id, 'wplm_email', sanitize_email( $data['email'] ) );
		update_post_meta( $post_id, 'wplm_whatsapp', sanitize_text_field( $data['whatsapp'] ) );
		update_post_meta( $post_id, 'wplm_telefone', sanitize_text_field( $data['telefone'] ) );
		update_post_meta( $post_id, 'wplm_ramal', sanitize_text_field( $data['ramal'] ) );
		update_post_meta( $post_id, 'wplm_endereco', sanitize_text_field( $data['endereco'] ) );

		// Grupo
		if ( ! empty( $data['grupo'] ) ) {
			$group_name = 'Grupo ' . $data['grupo'];
			$term = get_term_by( 'name', $group_name, CPT_Taxonomy::TAXONOMY );
			if ( ! $term ) {
				$term_arr = wp_insert_term( $group_name, CPT_Taxonomy::TAXONOMY );
				$term_id = ! is_wp_error( $term_arr ) ? $term_arr['term_id'] : 0;
			} else {
				$term_id = $term->term_id;
			}

			if ( $term_id ) {
				wp_set_object_terms( $post_id, array( (int) $term_id ), CPT_Taxonomy::TAXONOMY );
			}
		}

		return $status;
	}
}
