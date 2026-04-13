<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

// Busca campanhas recentes
$campaigns = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}leads_campaigns ORDER BY created_at DESC LIMIT 50" );
?>

<div class="wrap wplm-wrap">
	<h1 class="wp-heading-inline">Campanhas de Leads</h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=wplm-new-campaign' ) ); ?>" class="page-title-action">Novo Envio</a>
	<hr class="wp-header-end">

	<div class="wplm-card">
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th style="width: 50px;">ID</th>
					<th>Assunto</th>
					<th>Destinatários</th>
					<th>Enviados/Falhas</th>
					<th>Status</th>
					<th>Data</th>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! empty( $campaigns ) ) : ?>
					<?php foreach ( $campaigns as $camp ) : ?>
						<tr>
							<td><?php echo esc_html( $camp->id ); ?></td>
							<td>
								<strong><?php echo esc_html( $camp->subject ); ?></strong>
								<div class="row-actions">
									<span class="view">
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=wplm-campaign-detail&id=' . $camp->id ) ); ?>">Ver Detalhes</a> |
									</span>
									<?php if ( 'pending' === $camp->status ) : ?>
										<span class="trash">
											<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wplm-campaigns&action=cancel&id=' . $camp->id ), 'wplm_cancel_campaign_' . $camp->id ) ); ?>" class="submitdelete" onclick="return confirm('Tem certeza que deseja cancelar esta campanha?');">Cancelar</a>
										</span>
									<?php endif; ?>
								</div>
							</td>
							<td><?php echo esc_html( 'group' === $camp->recipient_type ? 'Grupo' : 'Clientes Selecionados' ); ?></td>
							<td>
								<?php echo esc_html( $camp->sent_count . ' / ' . $camp->failed_count ); ?>
								<?php if ( in_array( $camp->status, array( 'pending', 'processing' ), true ) ) : ?>
									<div class="wplm-progress-bar-container" 
										 data-campaign-id="<?php echo esc_attr( $camp->id ); ?>" 
										 data-status="<?php echo esc_attr( $camp->status ); ?>">
										<div class="wplm-progress-bar-fill" style="width: <?php echo esc_attr( ( $camp->total_recipients > 0 ) ? round( ( ( $camp->sent_count + $camp->failed_count ) / $camp->total_recipients ) * 100 ) : 0 ); ?>%;"></div>
										<div class="wplm-progress-text">Processando...</div>
									</div>
								<?php endif; ?>
							</td>
							<td>
								<span class="status-badge status-<?php echo esc_attr( $camp->status ); ?>">
									<?php echo esc_html( strtoupper( $camp->status ) ); ?>
								</span>
							</td>
							<td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $camp->created_at ) ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr>
						<td colspan="6">Nenhum envio encontrado.</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>
