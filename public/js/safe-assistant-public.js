(function ($) {
    'use strict';

    document.addEventListener("DOMContentLoaded", function () {

        if (sa_vars.enable_auto_membership) {
            var checkbox = document.getElementById('createaccount');
            if (checkbox && !checkbox.checked) {
                checkbox.checked = true;
            }

            var createAccountSection = document.getElementsByClassName('woocommerce-account-fields')[0];
            if (sa_vars.hide_membership_option_checkout && createAccountSection) {
                createAccountSection.style.display = 'none';
            }
        }

        if (sa_vars.vpn_checker_result > 0) {
            function showVpnPopup(title, message) {
                let popup = document.createElement("div");
                popup.innerHTML = `
                <div class="vpn-popup-overlay">
  <div class="vpn-popup">
    <div class="vpn-popup-header">
      <h2>${title}</h2>
      <button class="vpn-popup-close" onclick="this.closest('.vpn-popup-overlay').remove()">
        &times;
      </button>
    </div>
    <div class="vpn-popup-body">
      <p>${message}</p>
    </div>
  </div>
</div>
`;
                document.body.appendChild(popup);
            }
            showVpnPopup(sa_vars.vpn_checker_title, sa_vars.vpn_checker_message);
        }

    });

})(jQuery);
