(function ($) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	function showAssistantNotice() {
		const notice = document.getElementById('safe-assistant-notice');

		notice.classList.add('show');
		notice.classList.remove('hide');

		setTimeout(() => {
			hideAssistantNotice();
		}, 5000);
	}

	function hideAssistantNotice() {
		const notice = document.getElementById('safe-assistant-notice');

		notice.classList.add('hide');
		notice.classList.remove('show');

		setTimeout(() => {
			notice.remove();
		}, 500);
	}

	document.addEventListener("DOMContentLoaded", function () {
		const notice = document.getElementById("safe-assistant-notice");
		if (notice) {
			notice.style.transform = "translateX(100%)";
			notice.style.transition = "transform 0.5s ease-in-out";

			setTimeout(() => {
				notice.style.transform = "translateX(0)";
			}, 100);

			setTimeout(() => {
				notice.style.transform = "translateX(100%)";
				setTimeout(() => {
					notice.remove();
				}, 500);
			}, 5000);
		}
	});

	// AJAX Logs Pagination
	$(document).ready(function() {
		let isLoading = false;
		
		function loadLogs(page = 1, type = '', status = '', per_page = 20) {
			if (isLoading) return;
			
			isLoading = true;
			const container = $('#sa-logs-container');
			
			// Show loading indicator
			container.html('<div class="sa-logs-loading"><p><span class="spinner is-active"></span> ' + sa_ajax.loading_text + '</p></div>');
			
			$.ajax({
				url: sa_ajax.ajax_url,
				type: 'POST',
				data: {
					action: 'sa_get_logs_paginated',
					nonce: sa_ajax.nonce,
					page: page,
					type: type,
					status: status,
					per_page: per_page
				},
				success: function(response) {
					if (response.success) {
						container.html(response.html + response.pagination);
					} else {
						container.html('<div class="notice notice-error"><p>' + sa_ajax.error_text + '</p></div>');
					}
				},
				error: function() {
					container.html('<div class="notice notice-error"><p>' + sa_ajax.error_text + '</p></div>');
				},
				complete: function() {
					isLoading = false;
				}
			});
		}
		
		// Handle pagination clicks
		$(document).on('click', '.sa-page-btn', function(e) {
			e.preventDefault();
			const page = $(this).data('page');
			const type = $('#sa-logs-type').val();
			const status = $('#sa-logs-status').val();
			const per_page = $('#sa-logs-per-page').val();
			
			loadLogs(page, type, status, per_page);
			
			// Scroll to top of logs
			$('#sa-logs-wrapper')[0].scrollIntoView({ behavior: 'smooth' });
		});
		
		// Handle filter changes
		$('#sa-logs-type, #sa-logs-status, #sa-logs-per-page').on('change', function() {
			const type = $('#sa-logs-type').val();
			const status = $('#sa-logs-status').val();
			const per_page = $('#sa-logs-per-page').val();
			
			loadLogs(1, type, status, per_page);
		});
		
		// Handle refresh button
		$('#sa-logs-refresh').on('click', function() {
			const type = $('#sa-logs-type').val();
			const status = $('#sa-logs-status').val();
			const per_page = $('#sa-logs-per-page').val();
			
			loadLogs(1, type, status, per_page);
		});
		
		// Handle details toggle
		$(document).on('click', 'details summary', function() {
			$(this).parent().toggleClass('open');
		});
		
		// Auto-refresh logs every 30 seconds if on logs page
		if ($('#sa-logs-wrapper').length > 0) {
			setInterval(function() {
				if (!isLoading && $('#sa-logs-wrapper:visible').length > 0) {
					const type = $('#sa-logs-type').val();
					const status = $('#sa-logs-status').val();
					const per_page = $('#sa-logs-per-page').val();
					const currentPage = $('.sa-page-btn.button-primary').data('page') || 1;
					
					loadLogs(currentPage, type, status, per_page);
				}
			}, 30000); // 30 seconds
		}
	});

})(jQuery);
