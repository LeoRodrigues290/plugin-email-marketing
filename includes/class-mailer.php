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
	 * Envia um e-mail individual.
	 */
	public static function send( string $to, string $subject, string $html_body ): bool {
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		
		// O WP usará automaticamente as configurações SMTP se o hook phpmailer_init estiver funcionando
		return wp_mail( $to, $subject, $html_body, $headers );
	}

	/**
	 * Constrói o corpo do e-mail a partir dos posts selecionados.
	 */
	public static function build_campaign_body( array $post_ids ): string {
		if ( empty( $post_ids ) ) {
			return '';
		}

		$posts = get_posts( array(
			'post__in'       => $post_ids,
			'orderby'        => 'post__in',
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 20,
		) );

		ob_start();
		?>
		<!DOCTYPE html>
		<html lang="pt-BR">
		<head>
			<meta charset="UTF-8">
			<style>
				body { font-family: sans-serif; line-height: 1.6; color: #333; background-color: #f9f9f9; margin: 0; padding: 0; }
				.container { max-width: 600px; margin: 20px auto; background-color: #fff; padding: 20px; border: 1px solid #ddd; }
				.header { text-align: center; margin-bottom: 30px; }
				.header img { display: block; margin: 0 auto 10px; max-width: 100%; height: auto; }
				.section-title { text-align: center; font-weight: bold; color: #000; margin: 30px 0 20px; text-transform: uppercase; font-size: 18px; }
				.news-list { list-style: none; padding: 0; margin: 0; text-align: center; }
				.news-item { margin-bottom: 15px; }
				.news-item a { color: #967841; text-decoration: none; font-weight: bold; font-size: 16px; }
				.news-item a:hover { text-decoration: underline; }
				.footer { font-size: 12px; color: #777; margin-top: 40px; text-align: center; border-top: 1px solid #eee; padding-top: 20px; }
			</style>
		</head>
		<body>
			<div class="container">
				<div class="header">
					<img src="<?php echo esc_url( WPLM_URL . 'assets/logo.png' ); ?>" alt="Logo">
					<img src="<?php echo esc_url( WPLM_URL . 'assets/banner.png' ); ?>" alt="Banner">
				</div>

				<div class="section-title">Últimas Notícias:</div>

				<div class="news-list">
					<?php foreach ( $posts as $post ) : ?>
						<div class="news-item">
							<a href="<?php echo esc_url( get_permalink( $post ) ); ?>">
								<?php echo esc_html( get_the_title( $post ) ); ?>
							</a>
						</div>
					<?php endforeach; ?>
				</div>

				<div class="footer">
					<p>Você recebeu este e-mail porque faz parte da nossa lista de contatos.</p>
				</div>
			</div>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}
}
