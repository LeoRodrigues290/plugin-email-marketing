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
				body { font-family: sans-serif; line-height: 1.6; color: #333; }
				.container { max-width: 600px; margin: 0 auto; padding: 20px; }
				.post { margin-bottom: 30px; border-bottom: 1px solid #eee; padding-bottom: 20px; }
				.post h2 { margin-top: 0; }
				.post a { color: #0073aa; text-decoration: none; font-weight: bold; }
				.footer { font-size: 12px; color: #777; margin-top: 40px; text-align: center; }
			</style>
		</head>
		<body>
			<div class="container">
				<div class="header">
					<img src="https://nplace.it/clientes/tavares_site/wp-content/uploads/2025/10/Grupo-de-mascara-2-e1760199450381.png" alt="Header" style="max-width: 100%; height: auto; margin-bottom: 20px;">
				</div>
				<?php foreach ( $posts as $post ) : ?>
					<div class="post">
						<h2><?php echo esc_html( get_the_title( $post ) ); ?></h2>
						<?php if ( has_post_thumbnail( $post ) ) : ?>
							<p><?php echo get_the_post_thumbnail( $post, 'medium' ); ?></p>
						<?php endif; ?>
						<p><?php echo wp_kses_post( wp_trim_words( $post->post_content, 40 ) ); ?></p>
						<p><a href="<?php echo esc_url( get_permalink( $post ) ); ?>">Leia mais...</a></p>
					</div>
				<?php endforeach; ?>
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
