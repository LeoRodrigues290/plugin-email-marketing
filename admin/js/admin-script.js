jQuery(document).ready(function($) {

	// 1. Inicializar Select2 para Clientes
	$('.wplm-select-clients').select2({
		ajax: {
			url: wplm.rest_url + 'clients',
			dataType: 'json',
			delay: 250,
			beforeSend: function(xhr) {
				xhr.setRequestHeader('X-WP-Nonce', wplm.nonce);
			},
			data: function(params) {
				return {
					q: params.term,
					page: params.page || 1
				};
			},
			processResults: function(data, params) {
				params.page = params.page || 1;
				return {
					results: data.results,
					pagination: {
						more: data.pagination.more
					}
				};
			},
			cache: true
		},
		placeholder: 'Buscar clientes...',
		minimumInputLength: 2,
		templateSelection: function(data) {
			return data.text || data.id;
		}
	});

	// 2. Inicializar Select2 para Grupos
	$('.wplm-select-groups').select2({
		ajax: {
			url: wplm.rest_url + 'groups',
			dataType: 'json',
			delay: 250,
			beforeSend: function(xhr) {
				xhr.setRequestHeader('X-WP-Nonce', wplm.nonce);
			},
			data: function(params) {
				return { q: params.term };
			},
			processResults: function(data) {
				return { results: data.results };
			},
			cache: true
		},
		placeholder: 'Selecionar grupo...',
		minimumInputLength: 0
	});

	// 3. Inicializar Select2 para Posts
	$('.wplm-select-posts').select2({
		ajax: {
			url: wplm.rest_url + 'posts',
			dataType: 'json',
			delay: 250,
			beforeSend: function(xhr) {
				xhr.setRequestHeader('X-WP-Nonce', wplm.nonce);
			},
			data: function(params) {
				return {
					q: params.term,
					page: params.page || 1
				};
			},
			processResults: function(data, params) {
				params.page = params.page || 1;
				return {
					results: data.results,
					pagination: {
						more: data.pagination.more
					}
				};
			},
			cache: true
		},
		placeholder: 'Buscar notícias...',
		minimumInputLength: 1,
		multiple: true
	});

	// 4. Alternância de campos baseado no tipo de destinatário
	$('input[name="recipient_type"]').on('change', function() {
		var type = $(this).val();
		if (type === 'group') {
			$('.row-group').show();
			$('.row-clients').hide();
		} else {
			$('.row-group').hide();
			$('.row-clients').show();
		}
	});

	// 5. Polling de progresso para campanhas ativas
	$('.wplm-progress-bar-container').each(function() {
		var container = $(this);
		var campaignId = container.data('campaign-id');
		var status = container.data('status');

		if (status === 'processing' || status === 'pending') {
			pollProgress(campaignId, container);
		}
	});

	function pollProgress(id, container) {
		$.ajax({
			url: wplm.rest_url + 'campaign/' + id + '/progress',
			method: 'GET',
			beforeSend: function(xhr) {
				xhr.setRequestHeader('X-WP-Nonce', wplm.nonce);
			},
			success: function(data) {
				var percent = 0;
				if (data.total > 0) {
					percent = Math.round(((data.sent + data.failed) / data.total) * 100);
				}

				container.find('.wplm-progress-bar-fill').css('width', percent + '%');
				container.find('.wplm-progress-text').text(percent + '% (' + (data.sent + data.failed) + '/' + data.total + ')');

				if (data.status === 'processing' || data.status === 'pending') {
					setTimeout(function() {
						pollProgress(id, container);
					}, 5000);
				} else {
					location.reload(); // Recarrega para atualizar status final
				}
			}
		});
	}

	// 6. Spinner no submit
	$('.wplm-form-campaign').on('submit', function() {
		var btn = $(this).find('input[type="submit"]');
		btn.prop('disabled', true);
		$(this).addClass('is-active');
	});

});
