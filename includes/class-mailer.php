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
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title>Newsletter</title>
			<style type="text/css">
				body {
					margin: 0 !important;
					padding: 0 !important;
					width: 100% !important;
					-webkit-text-size-adjust: 100% !important;
					-ms-text-size-adjust: 100% !important;
					-webkit-font-smoothing: antialiased !important;
				}
				table {
					border-spacing: 0;
					border-collapse: collapse;
					mso-table-lspace: 0pt;
					mso-table-rspace: 0pt;
				}
				table td {
					border-collapse: collapse;
				}
				img {
					-ms-interpolation-mode: bicubic;
				}
				@media screen and (max-width: 600px) {
					.responsive-table {
						width: 100% !important;
						max-width: 100% !important;
					}
					.responsive-image {
						width: 100% !important;
						height: auto !important;
						max-width: 100% !important;
					}
					.content-cell {
						padding: 15px !important;
					}
				}
			</style>
		</head>
		<body style="font-family: Arial, Helvetica, sans-serif; line-height: 1.6; color: #333333; background-color: #f9f9f9; margin: 0; padding: 0;">
			<table width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: #f9f9f9; width: 100%; margin: 0; padding: 0;">
				<tr>
					<td align="center" style="padding: 20px 10px;">
						<!--[if mso]>
						<table width="600" border="0" cellpadding="0" cellspacing="0" align="center">
						<tr>
						<td align="center">
						<![endif]-->
						<table border="0" cellpadding="0" cellspacing="0" width="100%" class="responsive-table" style="max-width: 600px; background-color: #ffffff; border: 1px solid #dddddd; margin: 0 auto;">
							<tr>
								<td align="center" class="content-cell" style="padding: 20px 30px;">
									<!-- Header -->
									<table border="0" cellpadding="0" cellspacing="0" width="100%">
										<tr>
											<td align="center" style="padding-bottom: 25px; text-align: center;">
												<!-- Tabela auxiliar para manter a logo centralizada mesmo se o CSS (margin: 0 auto) for removido (Ex: Caixa de Spam) -->
												<table align="center" border="0" cellpadding="0" cellspacing="0" width="200" style="margin: 0 auto;">
													<tr>
														<td align="center">
															<img src="cid:wplm_logo" alt="Logo" width="200" style="display: block; width: 200px; max-width: 200px; height: auto; margin: 0 auto; border: 0; outline: none; text-decoration: none;">
														</td>
													</tr>
												</table>
											</td>
										</tr>
										<tr>
											<td align="center" style="padding-bottom: 40px; text-align: center;">
												<img src="cid:wplm_banner" alt="Banner" width="500" class="responsive-image" style="display: block; margin: 0 auto; border: 0; outline: none; text-decoration: none; width: 100%; max-width: 500px; height: auto;">
											</td>
										</tr>
										<tr>
											<td align="center" style="padding-bottom: 20px; text-align: center;">
												<h2 style="margin: 0; font-family: Arial, Helvetica, sans-serif; font-weight: bold; color: #444444; font-size: 14px; text-transform: none;">Últimas notícias:</h2>
											</td>
										</tr>
										<?php foreach ( $posts as $post ) : ?>
										<tr>
											<td align="center" style="padding-bottom: 15px; text-align: center;">
												<a href="<?php echo esc_url( get_permalink( $post ) ); ?>" style="color: #967841; text-decoration: none; font-weight: 400; font-size: 12px; font-family: Arial, Helvetica, sans-serif;">
													<?php echo esc_html( get_the_title( $post ) ); ?>
												</a>
											</td>
										</tr>
										<?php endforeach; ?>
									</table>
								</td>
							</tr>
						</table>
						<!--[if mso]>
						</td>
						</tr>
						</table>
						<![endif]-->

						<!-- External Footer -->
						<table border="0" cellpadding="0" cellspacing="0" width="100%" class="responsive-table" style="max-width: 600px; margin: 0 auto;">
							<tr>
								<td align="center" style="padding: 20px 20px 40px 20px; text-align: center;">
									<p style="margin: 0; font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #999999; text-align: center;">
										Emmendorfer e Tavares - Advogados Associados © <?php echo date( 'Y' ); ?>
									</p>
								</td>
							</tr>
						</table>
						<!--[if mso]>
						</td>
						</tr>
						</table>
						<![endif]-->
					</td>
				</tr>
			</table>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}
}
