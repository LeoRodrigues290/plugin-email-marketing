<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$opts = \WPLM\SMTP_Config::get_options();
?>

<div class="wrap wplm-wrap">
	<h1>Configurações SMTP</h1>

	<?php if ( isset( $_GET['cleared'] ) ) : ?>
		<div class="updated"><p>Histórico de campanhas e logs limpo com sucesso!</p></div>
	<?php endif; ?>

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

	<hr>

	<div class="wplm-card">
		<h3>Importar Clientes (CSV)</h3>
		<p>Suba um arquivo CSV para importar ou atualizar seus contatos. O arquivo deve seguir a ordem do seu antigo banco de dados.</p>
		
		<form id="wplm-import-form">
			<input type="file" id="wplm-csv-file" accept=".csv" required>
			<button type="submit" class="button button-primary">Enviar e Importar CSV</button>
			<?php wp_nonce_field( 'wplm_import_nonce', 'wplm_import_nonce_field' ); ?>
		</form>

		<div id="wplm-import-progress-container" style="display:none; margin-top:20px;">
			<div class="wplm-progress-bar">
				<div id="wplm-import-progress-fill" class="wplm-progress-bar-fill" style="width: 0%;"></div>
			</div>
			<p id="wplm-import-status">Preparando...</p>
			<div id="wplm-import-results" style="margin-top:10px; font-size:13px; color:#666;"></div>
		</div>
	</div>

	<hr>

	<div class="wplm-card wplm-card-danger">
		<h3 style="color: #d63638;">Zona de Perigo</h3>
		<p>Estas ações são permanentes e não podem ser desfeitas.</p>
		
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('Tem certeza que deseja apagar TODO o histórico? Esta ação é irreversível.');">
			<input type="hidden" name="action" value="wplm_clear_history">
			<?php wp_nonce_field( 'wplm_clear_history_' . get_current_user_id() ); ?>
			<button type="submit" class="button button-link-delete">Limpar Histórico de Campanhas</button>
			<p class="description">Isso removerá preventivamente todos os registros da lista de campanhas e logs de envio.</p>
		</form>
	</div>
</div>
