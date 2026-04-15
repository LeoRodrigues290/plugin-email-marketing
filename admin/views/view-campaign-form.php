<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap wplm-wrap">
	<h1>Novo Envio de E-mails</h1>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wplm-form-campaign">
		<input type="hidden" name="action" value="wplm_create_campaign">
		<?php wp_nonce_field( 'wplm_create_campaign_' . get_current_user_id() ); ?>

		<div class="wplm-card">
			<table class="form-table wplm-form-table">
				<tr>
					<th><label for="subject">Assunto do E-mail</label></th>
					<td>
						<input name="subject" type="text" id="subject" value="" class="widefat" required maxlength="200">
						<p class="description">Este será o assunto exibido na caixa de entrada do lead.</p>
					</td>
				</tr>

				<tr>
					<th>Destinatários</th>
					<td>
						<label style="margin-right: 20px;">
							<input type="radio" name="recipient_type" value="group" checked> Grupo de Clientes
						</label>
						<label>
							<input type="radio" name="recipient_type" value="clients"> Clientes Específicos
						</label>
					</td>
				</tr>

				<tr class="row-group">
					<th><label for="group_id">Selecionar Grupo</label></th>
					<td>
						<select name="group_id" id="group_id" class="wplm-select-groups wplm-select2-full" style="width: 100%"></select>
						<p class="description">O e-mail será enviado para todos os clientes deste grupo.</p>
					</td>
				</tr>

				<tr class="row-clients" style="display: none;">
					<th><label for="client_ids">Selecionar Clientes</label></th>
					<td>
						<select name="client_ids[]" id="client_ids" class="wplm-select-clients wplm-select2-full" multiple style="width: 100%"></select>
						<p class="description">Busque e selecione os clientes individualmente.</p>
					</td>
				</tr>

				<tr>
					<th><label for="post_ids">Conteúdo (Notícias)</label></th>
					<td>
						<select name="post_ids[]" id="post_ids" class="wplm-select-posts wplm-select2-full" multiple style="width: 100%"></select>
						<p class="description">Selecione os posts que serão incluídos no corpo do e-mail.</p>
					</td>
				</tr>
			</table>

			<p class="submit">
				<input type="submit" name="submit" id="submit" class="button button-primary" value="Enviar">
				<span class="spinner spinner-inline"></span>
			</p>
		</div>
	</form>
</div>
