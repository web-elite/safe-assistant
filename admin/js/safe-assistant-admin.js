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
			
			// Find the active logs container (either main plugin or addon)
			let container = $('#sa-logs-container');
			if (!container.length || !container.is(':visible')) {
				container = $('.sa-logs-container:visible').first();
			}
			
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
		
		// Helper function to get current filter values from the active logs context
		function getCurrentFilters() {
			// Find the active logs wrapper (either main plugin or addon)
			const mainWrapper = $('#sa-logs-wrapper');
			const addonWrapper = $('.sa-logs-wrapper').not('#sa-logs-wrapper');
			
			let typeVal = '', statusVal = '', perPageVal = 20;
			
			if (mainWrapper.length && mainWrapper.is(':visible')) {
				// Main plugin logs
				typeVal = $('#sa-logs-type').val() || '';
				statusVal = $('#sa-logs-status').val() || '';
				perPageVal = parseInt($('#sa-logs-per-page').val(), 10) || 20;
			} else if (addonWrapper.length && addonWrapper.is(':visible')) {
				// Addon logs - use class-based selectors within the visible wrapper
				const activeWrapper = addonWrapper.filter(':visible').first();
				typeVal = activeWrapper.find('.sa-logs-type').val() || '';
				statusVal = activeWrapper.find('.sa-logs-status').val() || '';
				perPageVal = parseInt(activeWrapper.find('.sa-logs-per-page').val(), 10) || 20;
			}
			
			return { type: typeVal, status: statusVal, per_page: perPageVal };
		}

		// Handle pagination clicks
		$(document).on('click', '.sa-page-btn', function(e) {
			e.preventDefault();
			const page = $(this).data('page');
			const filters = getCurrentFilters();
			
			loadLogs(page, filters.type, filters.status, filters.per_page);
			
			// Scroll to top of logs (find the active wrapper)
			const activeWrapper = $('.sa-logs-wrapper:visible, #sa-logs-wrapper:visible').first();
			if (activeWrapper.length) {
				activeWrapper[0].scrollIntoView({ behavior: 'smooth' });
			}
		});
		
		// Handle filter changes (delegated) - support both ID and class selectors
		$(document).on('change', '#sa-logs-type, #sa-logs-status, #sa-logs-per-page, .sa-logs-type, .sa-logs-status, .sa-logs-per-page', function() {
			const filters = getCurrentFilters();
			loadLogs(1, filters.type, filters.status, filters.per_page);
		});

		// Handle refresh button (delegated) - support both ID and class selectors
		$(document).on('click', '#sa-logs-refresh, .sa-logs-refresh', function() {
			const filters = getCurrentFilters();
			loadLogs(1, filters.type, filters.status, filters.per_page);
		});
		
		// Handle details toggle
		$(document).on('click', 'details summary', function() {
			$(this).parent().toggleClass('open');
		});
		
		// Auto-refresh logs every 30 seconds if on logs page
		if ($('#sa-logs-wrapper, .sa-logs-wrapper').length > 0) {
			setInterval(function() {
				const activeWrapper = $('.sa-logs-wrapper:visible, #sa-logs-wrapper:visible');
				if (!isLoading && activeWrapper.length > 0) {
					const filters = getCurrentFilters();
					const currentPage = $('.sa-page-btn.button-primary').data('page') || 1;
					
					loadLogs(currentPage, filters.type, filters.status, filters.per_page);
				}
			}, 30000); // 30 seconds
		}
	});

})(jQuery);
