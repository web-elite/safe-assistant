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

})(jQuery);
