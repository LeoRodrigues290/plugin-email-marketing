<?php
namespace WPLM;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Montagem e despacho de e-mails.
 */
class Mailer {

	/**
	 * Envia um e-mail individual com suporte a imagens embutidas (CID).
	 */
	public static function send( string $to, string $subject, string $html_body ): bool {
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		// Hook temporário para anexar imagens CID
		$embed_callback = function( $phpmailer ) {
			$logo_path   = WPLM_PATH . 'assets/logo.png';
			$banner_path = WPLM_PATH . 'assets/banner.png';

			if ( file_exists( $logo_path ) ) {
				$phpmailer->AddEmbeddedImage( $logo_path, 'wplm_logo', 'logo.png' );
			}
			if ( file_exists( $banner_path ) ) {
				$phpmailer->AddEmbeddedImage( $banner_path, 'wplm_banner', 'banner.png' );
			}
		};

		add_action( 'phpmailer_init', $embed_callback );
		$result = wp_mail( $to, $subject, $html_body, $headers );
		remove_action( 'phpmailer_init', $embed_callback );

		return $result;
	}

	/**
	 * Constrói o corpo do e-mail com estilos inline e referências CID.
	 */
	public static function build_campaign_body( array $post_ids ): string {
		if ( empty( $post_ids ) ) {
			return '';
		}

		$posts = get_posts( array(
			'post__in'       => $post_ids,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'post_type'      => 'noticia',
			'post_status'    => 'publish',
			'posts_per_page' => 20,
		) );

		ob_start();
		?>
		<!DOCTYPE html>
		<html lang="pt-BR">
		<head>
			<meta charset="UTF-8">
		</head>
		<body style="font-family: sans-serif; line-height: 1.6; color: #333; background-color: #f9f9f9; margin: 0; padding: 0;">
			<div class="container" style="max-width: 600px; margin: 20px auto; background-color: #fff; padding: 20px; border: 1px solid #ddd;">
				<div class="header" style="text-align: center; margin-bottom: 30px;">
					<img src="cid:wplm_logo" alt="Logo" width="200" style="display: block; margin: 0 auto 10px; width: 200px; height: auto;">
					<img src="cid:wplm_banner" alt="Banner" width="500" style="display: block; margin: 0 auto 10px; width: 500px; height: auto;">
				</div>

				<div class="section-title" style="text-align: center; font-weight: bold; color: #000; margin: 30px 0 20px; text-transform: uppercase; font-size: 18px;">Últimas Notícias:</div>

				<div class="news-list" style="list-style: none; padding: 0; margin: 0; text-align: center;">
					<?php foreach ( $posts as $post ) : ?>
						<div class="news-item" style="margin-bottom: 15px;">
							<a href="<?php echo esc_url( get_permalink( $post ) ); ?>" style="color: #967841; text-decoration: none; font-weight: bold; font-size: 16px;">
								<?php echo esc_html( get_the_title( $post ) ); ?>
							</a>
						</div>
					<?php endforeach; ?>
				</div>

				<div class="footer" style="font-size: 12px; color: #777; margin-top: 40px; text-align: center; border-top: 1px solid #eee; padding-top: 20px;">
					<p>Você recebeu este e-mail porque faz parte da nossa lista de contatos.</p>
				</div>
			</div>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}
}
