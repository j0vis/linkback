/* LinkBack Admin Scripts */
(function ($) {
	'use strict';

	$(document).ready(function () {
		// Select all checkbox
		$('#cb-select-all-1').on('change', function () {
			$('input[name="link_ids[]"]').prop('checked', $(this).prop('checked'));
		});

		// AJAX verify inline
		$('.linkback-admin').on('click', '.linkback-ajax-verify', function (e) {
			e.preventDefault();
			var $btn = $(this);
			var id = $btn.data('id');
			if (!id || $btn.hasClass('spinning')) {
				return;
			}
			$btn.addClass('spinning').text(linkback_ajax.strings?.verifying || 'Verifying...');

			$.post(linkback_ajax.ajax_url, {
				action: 'linkback_ajax_verify',
				nonce: linkback_ajax.nonce,
				id: id
			}, function (response) {
				$btn.removeClass('spinning');
				if (response.success) {
					$btn.text(linkback_ajax.strings?.verified || 'Verified');
					var $row = $btn.closest('tr');
					$row.find('.linkback-status-ok, .linkback-status-grace, .linkback-status-missing').replaceWith(
						'<span class="linkback-status linkback-status-ok">OK</span>'
					);
					$row.find('.linkback-last-checked').text(response.data.checked);
				} else {
					$btn.text(linkback_ajax.strings?.failed || 'Failed');
				}
			}).fail(function () {
				$btn.removeClass('spinning').text(linkback_ajax.strings?.error || 'Error');
			});
		});
	});
})(jQuery);
