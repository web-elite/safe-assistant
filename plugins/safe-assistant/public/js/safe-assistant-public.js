(function ($) {
	'use strict';

	/**
	 * All of the code for your public-facing JavaScript source
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

	document.addEventListener("DOMContentLoaded", function () {
    if (ssa_vpn_data.enabled !== "yes") return;

    // Send AJAX request to WordPress backend
    jQuery.ajax({
        url: ssa_vpn_data.ajax_url,
        type: 'POST',
        data: {
            action: 'check_vpn_status',
            nonce: ssa_vpn_data.nonce
        },
        success: function(response) {
            if(response.success && response.data.is_vpn) {
                showVpnPopup(ssa_vpn_data.message);
            }
        },
        error: function(xhr, status, error) {
            console.error("VPN check failed", error);
        }
    });

    function showVpnPopup(message) {
        let popup = document.createElement("div");
        popup.innerHTML = `<div class="vpn-popup"><p>${message}</p><button onclick="this.parentElement.style.display='none'">بستن</button></div>`;
        document.body.appendChild(popup);
        document.querySelector(".vpn-popup").style.cssText = `
            position: fixed; 
            top: 20%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #fff;
            padding: 20px;
            border: 1px solid #ccc;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
            z-index: 9999;
        `;
    }
});
})(jQuery);
