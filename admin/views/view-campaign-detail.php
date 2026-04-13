<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$campaign_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
$campaign    = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}leads_campaigns WHERE id = %d", $campaign_id ) );

if ( ! $campaign ) {
	echo '<div class="error"><p>Campanha não encontrada.</p></div>';
	return;
}

// Busca logs desta campanha
$logs = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}leads_mailer_logs WHERE campaign_id = %d ORDER BY id ASC", $campaign_id ) );
?>

<div class="wrap wplm-wrap">
	<h1 class="wp-heading-inline">Detalhes da Campanha #<?php echo esc_html( $campaign->id ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=wplm-campaigns' ) ); ?>" class="page-title-action">Voltar para Lista</a>
	<hr class="wp-header-end">

	<div class="wplm-card">
		<h2><?php echo esc_html( $campaign->subject ); ?></h2>
		<p>
			<strong>Status:</strong> <span class="status-badge status-<?php echo esc_attr( $campaign->status ); ?>"><?php echo esc_html( $campaign->status ); ?></span> |
			<strong>Criada em:</strong> <?php echo esc_html( $campaign->created_at ); ?> |
			<strong>Progresso:</strong> <?php echo esc_html( $campaign->sent_count ); ?> enviados, <?php echo esc_html( $campaign->failed_count ); ?> falhas de um total de <?php echo esc_html( $campaign->total_recipients ); ?>.
		</p>
	</div>

	<div class="wplm-card">
		<h3>Logs Individuais de Envio</h3>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th>Destinatário</th>
					<th>E-mail</th>
					<th>Status</th>
					<th>Erro</th>
					<th>Tentativas</th>
					<th>Enviado em</th>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! empty( $logs ) ) : ?>
					<?php foreach ( $logs as $log ) : ?>
						<tr>
							<td><?php echo esc_html( $log->recipient_name ); ?></td>
							<td><?php echo esc_html( $log->recipient_email ); ?></td>
							<td>
								<span class="status-badge status-<?php echo esc_attr( $log->status ); ?>">
									<?php echo esc_html( $log->status ); ?>
								</span>
							</td>
							<td><?php echo esc_html( $log->error_message ?: '-' ); ?></td>
							<td><?php echo esc_html( $log->attempts ); ?></td>
							<td><?php echo esc_html( $log->sent_at ?: '-' ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr>
						<td colspan="6">Nenhum log encontrado para esta campanha.</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>
