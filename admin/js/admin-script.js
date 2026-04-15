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

	// 7. Importador de Clientes (Batching)
	$('#wplm-import-form').on('submit', function(e) {
		e.preventDefault();
		var fileInput = $('#wplm-csv-file')[0];
		if (!fileInput.files.length) {
			alert('Por favor, selecione um arquivo CSV primeiro.');
			return;
		}

		var formData = new FormData();
		formData.append('action', 'wplm_import_upload');
		formData.append('csv_file', fileInput.files[0]);
		formData.append('nonce', $('#wplm_import_nonce_field').val());

		startImport(formData);
	});

	function startImport(formData) {
		$('#wplm-import-form').find('button').prop('disabled', true);
		$('#wplm-import-progress-container').show();
		$('#wplm-import-status').text('Preparando importação...');

		$.ajax({
			url: wplm.ajax_url,
			method: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			success: function(response) {
				if (response.success) {
					processImportChunk(response.data.file_id, 0, response.data.total_rows, 0, 0, 0);
				} else {
					alert('Erro: ' + response.data.message);
					resetImportForm();
				}
			},
			error: function() {
				alert('Erro de comunicação com o servidor. Verifique os logs.');
				resetImportForm();
			}
		});
	}

	function processImportChunk(fileId, lineIndex, total, imported, updated, errors) {
		$.ajax({
			url: wplm.ajax_url,
			method: 'POST',
			data: {
				action: 'wplm_import_chunk',
				file_id: fileId,
				line_index: lineIndex,
				nonce: $('#wplm_import_nonce_field').val()
			},
			success: function(response) {
				if (response.success) {
					var data = response.data;
					var currentImported = imported + data.imported;
					var currentUpdated = updated + data.updated;
					var currentErrors = errors + data.errors;
					
					var percent = Math.min(100, Math.round((data.next_line / total) * 100));
					$('#wplm-import-progress-fill').css('width', percent + '%');
					$('#wplm-import-status').text('Processando... ' + percent + '%');
					$('#wplm-import-results').html(
						'<strong>Sucesso:</strong> ' + currentImported + ' novos, ' + currentUpdated + ' atualizados. ' +
						'<strong>Falhas:</strong> ' + currentErrors
					);

					if (!data.is_finished) {
						processImportChunk(fileId, data.next_line, total, currentImported, currentUpdated, currentErrors);
					} else {
						$('#wplm-import-status').text('Importação Concluída!');
						$('#wplm-import-form').find('button').text('Finalizado');
					}
				} else {
					$('#wplm-import-status').text('Erro no processamento.');
					alert('Erro no chunk: ' + response.data.message);
				}
			}
		});
	}

	function resetImportForm() {
		$('#wplm-import-form').find('button').prop('disabled', false).text('Iniciar Importação');
		$('#wplm-import-progress-container').hide();
	}

});
