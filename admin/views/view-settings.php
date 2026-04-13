<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$opts = \WPLM\SMTP_Config::get_options();
?>

<div class="wrap wplm-wrap">
	<h1>Configurações SMTP</h1>

	<form method="post" action="">
		<?php wp_nonce_field( 'wplm_save_smtp_' . get_current_user_id() ); ?>
		
		<div class="wplm-card">
			<table class="form-table wplm-form-table">
				<tr>
					<th><label for="smtp_host">Servidor SMTP</label></th>
					<td>
						<input name="smtp_host" type="text" id="smtp_host" value="<?php echo esc_attr( $opts['host'] ?? '' ); ?>" class="regular-text" required>
						<p class="description">Ex: smtp.gmail.com ou mail.seudominio.com</p>
					</td>
				</tr>

				<tr>
					<th><label for="smtp_port">Porta</label></th>
					<td>
						<input name="smtp_port" type="number" id="smtp_port" value="<?php echo esc_attr( $opts['port'] ?? 587 ); ?>" class="small-text" required>
					</td>
				</tr>

				<tr>
					<th><label for="smtp_encryption">Criptografia</label></th>
					<td>
						<select name="smtp_encryption" id="smtp_encryption">
							<option value="none" <?php selected( $opts['encryption'] ?? 'tls', 'none' ); ?>>Nenhuma</option>
							<option value="ssl" <?php selected( $opts['encryption'] ?? 'tls', 'ssl' ); ?>>SSL</option>
							<option value="tls" <?php selected( $opts['encryption'] ?? 'tls', 'tls' ); ?>>TLS</option>
						</select>
					</td>
				</tr>

				<tr>
					<th><label for="smtp_username">Usuário (E-mail)</label></th>
					<td>
						<input name="smtp_username" type="text" id="smtp_username" value="<?php echo esc_attr( $opts['username'] ?? '' ); ?>" class="regular-text">
					</td>
				</tr>

				<tr>
					<th><label for="smtp_password">Senha SMTP</label></th>
					<td>
						<input name="smtp_password" type="password" id="smtp_password" value="" class="regular-text" placeholder="Deixe em branco para manter a atual" autocomplete="new-password">
					</td>
				</tr>

				<tr class="separator">
					<th colspan="2"><hr></th>
				</tr>

				<tr>
					<th><label for="smtp_from_email">E-mail de Remetente</label></th>
					<td>
						<input name="smtp_from_email" type="email" id="smtp_from_email" value="<?php echo esc_attr( $opts['from_email'] ?? '' ); ?>" class="regular-text" required>
					</td>
				</tr>

				<tr>
					<th><label for="smtp_from_name">Nome do Remetente</label></th>
					<td>
						<input name="smtp_from_name" type="text" id="smtp_from_name" value="<?php echo esc_attr( $opts['from_name'] ?? '' ); ?>" class="regular-text" required>
					</td>
				</tr>
			</table>

			<p class="submit">
				<input type="submit" name="wplm_save_settings" id="submit" class="button button-primary" value="Salvar Configurações">
				<input type="submit" name="wplm_test_smtp" id="test_smtp" class="button button-secondary" value="Testar Conexão">
			</p>
		</div>
	</form>
</div>
