(function ($) {
    'use strict';

    document.addEventListener("DOMContentLoaded", function () {
        if (!sa_vars.vpn_checker_status) return;

        fetch(sa_vars.vpn_checker_url)
            .then(response => response.json())
            .then(data => {
                let isVpn = false;

                if (sa_vars.vpn_checker_type) {
                    if (data.country && data.country !== "IR") {
                        isVpn = true;
                    }
                } else {
                    if (data.privacy && (data.privacy.vpn || data.privacy.proxy || data.privacy.tor)) {
                        isVpn = true;
                    }
                }

                if (isVpn) {
                    showVpnPopup(sa_vars.vpn_checker_title, sa_vars.vpn_checker_message);
                }
            })
            .catch(error => console.error("VPN check failed", error));

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

    });
})(jQuery);
