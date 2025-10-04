<?php

/**
 * Handler Class
 *
 * Handles WordPress, WooCommerce, and Wallet settings with optimized performance.
 *
 * @since      1.0.0
 * @package    Safe_Assistant
 * @subpackage Safe_Assistant/includes
 * @author     AlirezaYaghouti <webelitee@gmail.com>
 */

/**
 * WordPress Admin Settings
 */
if (is_admin()) {
	// Remove WordPress logo from admin bar
	if (sa_get_option('remove_wp_logo_admin_bar', false)) {
		add_action('admin_bar_menu', function ($wp_admin_bar) {
			$wp_admin_bar->remove_node('wp-logo');
		}, 999);
	}

	// Disable admin bar for non-admins
	if (sa_get_option('disable_admin_bar', false)) {
		add_filter('show_admin_bar', function ($show) {
			return current_user_can('administrator') ? $show : false;
		});
	}

	// Remove dashboard widgets
	if (sa_get_option('remove_dashboard_widgets', false)) {
		add_action('wp_dashboard_setup', function () {
			remove_meta_box('dashboard_primary', 'dashboard', 'side');
			remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
			remove_meta_box('dashboard_activity', 'dashboard', 'normal');
		});
	}

	// Disable Gutenberg editor
	if (sa_get_option('disable_gutenberg', false)) {
		add_filter('use_block_editor_for_post', '__return_false', 10);
		add_filter('use_block_editor_for_post_type', '__return_false', 10);
	}

	// Disable All WordPress updates
	if (sa_get_option('disable_all_wp_updates', false) && !is_update_page()) {
		add_filter('pre_site_transient_update_plugins', '__return_null');
		add_filter('pre_site_transient_update_themes', '__return_null');
		add_filter('pre_site_transient_update_core', '__return_null');
		remove_action('init', 'wp_schedule_update_checks');
		remove_action('admin_init', '_maybe_update_core');
		remove_action('admin_init', '_maybe_update_plugins');
		remove_action('admin_init', '_maybe_update_themes');
		wp_clear_scheduled_hook('wp_version_check');
		wp_clear_scheduled_hook('wp_update_plugins');
		wp_clear_scheduled_hook('wp_update_themes');
		wp_clear_scheduled_hook('wp_maybe_auto_update');
		add_filter('automatic_updater_disabled', '__return_true');
		add_filter('auto_update_core', '__return_false');
		add_filter('auto_update_plugin', '__return_false');
		add_filter('auto_update_theme', '__return_false');
	}

	// Disable Only Automatic WordPress updates
	if (sa_get_option('disable_auto_wp_updates', false) && !is_update_page()) {
		add_filter('automatic_updater_disabled', '__return_true');
		add_filter('auto_update_core', '__return_false');
		add_filter('auto_update_plugin', '__return_false');
		add_filter('auto_update_theme', '__return_false');
	}
}

/**
 * WooCommerce Admin Settings
 */
if (
	is_woocommerce_activated() &&
	is_admin() &&
	!is_woocommerce_admin_page()
) {
	// Disable WooCommerce Admin
	if (sa_get_option('disable_wc_admin', false)) {
		add_filter('woocommerce_admin_disabled', '__return_true');
		remove_action('admin_notices', 'woocommerce_admin_notices');
		add_action('admin_menu', function () {
			remove_submenu_page('woocommerce', 'wc-addons');
			remove_submenu_page('woocommerce', 'wc-addons&section=helper');
			remove_submenu_page('woocommerce', 'wc-admin&path=/extensions');
		}, 999);
	}

	// Disable WooCommerce Marketing Hub
	if (sa_get_option('disable_wc_marketing_hub', false)) {
		add_filter('woocommerce_allow_marketplace_suggestions', '__return_false', 999);
		add_filter('woocommerce_marketing_menu_items', '__return_empty_array', 999);
		add_filter('woocommerce_admin_features', '__return_empty_array', 90);
		add_action('admin_enqueue_scripts', function () {
			wp_dequeue_script('wc-admin-app');
			wp_dequeue_style('wc-admin-app');
			wp_dequeue_style('wc-onboarding');
		}, 99);
	}

	// Disable WooCommerce Blocks on Frontend
	if (sa_get_option('disable_wc_blocks_frontend', false)) {
		add_action('wp_enqueue_scripts', function () {
			if (!is_admin()) {
				wp_dequeue_style('wc-blocks-style');
				wp_dequeue_script('wc-blocks');
			}
		}, 100);
	}
}

/**
 * Woodmart Patch Checker
 */
if (sa_get_option('disable_woodmart_patch_checker', false)) {
	add_filter('woodmart_load_patches_map_from_server', function ($enabled) {
		return (is_admin() &&
			isset($_GET['page']) &&
			$_GET['page'] === 'xts_patcher') ? true : false;
	});
}

/**
 * Order Management
 */
if (is_woocommerce_activated()) {

	/**
	 * Free Shipping Progress Bar
	 */
	$free_shipping_min_mashhad = (int) sa_get_option('free_shipping_min_mashhad', 0);
	$free_shipping_min_other_cities = (int) sa_get_option('free_shipping_min_other_cities', 0);

	if (
		sa_get_option('free_shipping_status', false) &&
		($free_shipping_min_mashhad > 0 || $free_shipping_min_other_cities > 0)
	) {
		add_action('woocommerce_cart_totals_after_order_total', 'render_free_shipping_progress');
		add_action('woocommerce_checkout_before_order_review', 'render_free_shipping_progress');
		add_action('woocommerce_widget_shopping_cart_after_buttons', 'render_free_shipping_progress');
	}

	function render_free_shipping_progress()
	{
		if (!WC()->cart) {
			return;
		}

		$cart_total = WC()->cart->get_cart_contents_total() + WC()->cart->get_cart_contents_tax();
		$min_mashhad = (int) sa_get_option('free_shipping_min_mashhad', 0);
		$min_others = (int) sa_get_option('free_shipping_min_other_cities', 0);

		if (
			$min_mashhad <= 0 &&
			$min_others <= 0
		) {
			return;
		}

		$progress_mashhad = ($min_mashhad > 0) ? min(100, ($cart_total / $min_mashhad) * 100) : 0;
		$progress_others = ($min_others > 0) ? min(100, ($cart_total / $min_others) * 100) : 0;
		$remain_mashhad = max(0, $min_mashhad - $cart_total);
		$remain_others = max(0, $min_others - $cart_total);
		$shipping_is_free = ($cart_total >= $min_mashhad &&
			$cart_total >= $min_others);

?>
		<div class="safe-assistant-free-shipping-progress <?php echo $shipping_is_free ? esc_attr('success') : ''; ?>">
			<?php if ($shipping_is_free) : ?>
				<p><?php esc_html_e('Free shipping activated nationwide.', 'safe-assistant'); ?></p>
			<?php else : ?>
				<div class="progress-label">
					<?php if ($cart_total >= $min_mashhad) : ?>
						<?php esc_html_e('Free shipping activated for Mashhad.', 'safe-assistant'); ?>
					<?php else : ?>
						<?php printf(esc_html__('%s Toman left for free shipping in Mashhad.', 'safe-assistant'), number_format($remain_mashhad)); ?>
					<?php endif; ?>
				</div>
				<div class="progress-box">
					<div class="progress-fill" style="width: <?php echo esc_attr($progress_mashhad); ?>%; background: #10b939;"></div>
				</div>
				<div class="progress-label">
					<?php if ($cart_total >= $min_others) : ?>
						<?php esc_html_e('Free shipping activated nationwide.', 'safe-assistant'); ?>
					<?php else : ?>
						<?php printf(esc_html__('%s Toman left for free shipping nationwide.', 'safe-assistant'), number_format($remain_others)); ?>
					<?php endif; ?>
				</div>
				<div class="progress-box">
					<div class="progress-fill" style="width: <?php echo esc_attr($progress_others); ?>%; background: #565656;"></div>
				</div>
				<?php if ($cart_total < $min_mashhad) : ?>
					<div class="progress-labels">
						<span style="color: #005c16;">
							<?php printf(esc_html__('Minimum purchase for free shipping in Mashhad: %s Toman', 'safe-assistant'), number_format($min_mashhad)); ?>
						</span>
					</div>
				<?php endif; ?>
				<?php if ($cart_total < $min_others) : ?>
					<div class="progress-labels">
						<span style="color: #343434;">
							<?php printf(esc_html__('Minimum purchase for free shipping nationwide: %s Toman', 'safe-assistant'), number_format($min_others)); ?>
						</span>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Automatic Membership Settings
	 */
	add_action('wp_footer', function () {
		if (!is_checkout() || is_order_received_page()) {
			return;
		}

		if (sa_get_option('enable_auto_membership', false)) {
		?>
			<script type="text/javascript">
				document.addEventListener('DOMContentLoaded', () => {
					const checkbox = document.getElementById('createaccount');
					if (checkbox &&
						!checkbox.checked) {
						checkbox.checked = true;
					}
				});
			</script>
		<?php
		}

		if (sa_get_option('hide_membership_option_checkout', false)) {
		?>
			<style>
				.woocommerce-checkout #createaccount {
					display: none !important;
				}
			</style>
<?php
		}
	});
}

/**
 * Wallet Settings
 */
if (defined('nirweb_wallet')) {
	/**
	 * nirweb_wallet_expiration_check
	 *
	 * @return void
	 */
	function nirweb_wallet_expiration_check()
	{
		sa_log('general', 'info', "Wallet Cron Log", "=== Wallet expiration check started ===");

		$check_by_day  = sa_get_option('nir_wallet_expire_check_by', false);
		$expire_hours  = (array) sa_get_option('nir_wallet_expire_day_sms', []);
		$pattern_hour  = sa_get_option('nir_wallet_expire_pattern_sms_hour', '');
		$pattern_day   = sa_get_option('nir_wallet_expire_pattern_sms', '');
		$pattern_last  = sa_get_option('nir_wallet_expire_last_pattern_sms', '');

		sa_log('general', 'info', "Wallet Cron Log", "check_by_day: " . ($check_by_day ? 'true' : 'false'));
		sa_log('general', 'info', "Wallet Cron Log", "expire_hours: " . implode(',', $expire_hours));
		sa_log('general', 'info', "Wallet Cron Log", "pattern_hour: $pattern_hour");
		sa_log('general', 'info', "Wallet Cron Log", "pattern_day: $pattern_day");
		sa_log('general', 'info', "Wallet Cron Log", "pattern_last: $pattern_last");

		if (!$expire_hours || (!$pattern_hour &&
			!$pattern_day &&
			!$pattern_last)) {
			sa_log('general', 'info', "Wallet Cron Log", "No patterns or expire_hours set. Exiting.");
			return;
		}

		global $wpdb;
		$current_time = current_time('timestamp');
		sa_log('general', 'info', "Wallet Cron Log", "current_time: $current_time (" . date('Y-m-d H:i:s', $current_time) . ")");

		$users = $wpdb->get_results(
			"SELECT user_id, amount, expire_time 
         FROM {$wpdb->prefix}nirweb_wallet_cashback
         WHERE expire_time > {$current_time}"
		);

		if (!$users) {
			sa_log('general', 'info', "Wallet Cron Log", "No users found with remaining expire_time.");
			return;
		}

		foreach ($users as $user) {
			$diff_hours = floor(($user->expire_time - $current_time) / HOUR_IN_SECONDS);

			$include_user = false;
			sort($expire_hours);
			foreach ($expire_hours as $hour) {
				$min = max(1, $hour - 23);
				$max = $hour;
				if (
					$diff_hours >= $min &&
					$diff_hours <= $max
				) {
					$include_user = true;
					break;
				}
			}
			if (!$include_user) continue;

			$phone = get_user_meta($user->user_id, 'billing_phone', true);
			if (!$phone) {
				continue;
			}

			$user_info = get_userdata($user->user_id);
			$name      = $user_info &&
				$user_info->first_name ? $user_info->first_name : $user_info->display_name;
			sa_log('general', 'info', "Wallet Cron Log", "Sending SMS to $name, phone: $phone, diff_hours: $diff_hours");

			if ($check_by_day) {
				if (
					$diff_hours <= 24 &&
					$pattern_last
				) {
					$pattern_vars_day = ['name' => $name];
					sa_log('general', 'info', "Wallet Cron Log", "Using last day pattern: $pattern_last, vars: $pattern_vars_day");
					sa_send_sms_pattern($pattern_vars_day, ($phone), $pattern_last);
				} elseif (
					$diff_hours > 24 &&
					$pattern_day
				) {
					$days_remaining   = ceil($diff_hours / 24);
					$pattern_vars_day = ['name' => $name, 'days' => $days_remaining];
					sa_log('general', 'info', "Wallet Cron Log", "Using day pattern: $pattern_day, vars: $pattern_vars_day");
					sa_send_sms_pattern($pattern_vars_day, ($phone), $pattern_day);
				}
			} else {
				if ($pattern_hour) {
					$pattern_vars_hour = ['name' => $name, 'hours' => $diff_hours];
					sa_log('general', 'info', "Wallet Cron Log", "Using hour pattern: $pattern_hour, vars: $pattern_vars_hour");
					sa_send_sms_pattern($pattern_vars_hour, ($phone), $pattern_hour);
				}
			}
		}

		sa_log('general', 'info', "Wallet Cron Log", "=== Wallet expiration check finished ===");
	}
	add_action('sa_nir_wallet_expiration_check', 'nirweb_wallet_expiration_check');
}

/**
 * Fix ob_end_flush error
 */
remove_action('shutdown', 'wp_ob_end_flush_all', 1);
add_action('shutdown', function () {
	while (@ob_end_flush());
});

/**
 * Replace Custom Maintenance with orginal woprdpress maintenance page
 */
if (sa_get_option('wp_custom_maintenance_status')) {
	sa_create_custom_maintenance_page(sa_get_option('wp_custom_maintenance'));
}

if (
	!empty(sa_get_option('block_external_requests', '')) &&
	!is_update_page()
) {
	add_filter('pre_http_request', function ($pre, $args, $url) {
		$blocked_urls = explode("\n", sa_get_option('block_external_requests', ''));

		foreach ($blocked_urls as $blocked) {
			$blocked = trim($blocked);
			if (!$blocked) continue;
			if (str_starts_with($url, $blocked)) {
				$block_title = esc_html('Blocked by Safe Assistant', 'safe-assistant');
				$block_desc = esc_html("Request to", 'safe-assistant')  . " $url " . esc_html("blocked by Safe Assistant settings.", 'safe-assistant');
				$args['body'] = '<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
    <channel>
        <title>' . $block_title . '</title>
        <link>' . esc_url(site_url()) . '</link>
        <description>' . $block_desc . '</description>
        <item>
            <title>' . $block_title . '</title>
            <link>' . esc_url(site_url()) . '</link>
            <description>' . $block_desc . '</description>
            <pubDate>' . date(DATE_RSS) . '</pubDate>
            <guid>' . esc_url(site_url()) . '</guid>
        </item>
    </channel>
</rss>';
				return array_merge($args, [
					'headers' => ['content-type' => 'application/rss+xml; charset=UTF-8'],
					'timeout' => 0.1,
					'blocking' => 1,
					'reject_unsafe_urls' => 1,
					'response' => ['code' => 200, 'message' => 'Blocked'],
				]);
			}
		}

		return $pre;
	}, 10, 3);
}

add_filter('woocommerce_checkout_fields', 'safe_assistant_custom_checkout_fields');

function safe_assistant_custom_checkout_fields($fields)
{
	if (isset($fields['account']['account_username'])) {
		if (!sa_get_option('checkout_account_fields_accordion')['account_username_enabled']) {
			unset($fields['account']['account_username']);
		} else {
			$fields['account']['account_username']['required'] = (bool) sa_get_option('checkout_account_fields_accordion')['account_username_required'];
			if (!empty(sa_get_option('checkout_account_fields_accordion')['account_username_label'])) {
				$fields['account']['account_username']['label'] = sanitize_text_field(sa_get_option('checkout_account_fields_accordion')['account_username_label']);
			}
			if (sa_get_option('checkout_account_fields_accordion')['account_username_default'] !== '') {
				$fields['account']['account_username']['placeholder'] = sanitize_text_field(sa_get_option('checkout_account_fields_accordion')['account_username_placeholder']);
				$fields['account']['account_username']['default'] = sanitize_text_field(sa_get_option('checkout_account_fields_accordion')['account_username_default']);
			}
		}
	}

	if (isset($fields['account']['account_username'])) {
		if (!sa_get_option('checkout_account_fields_accordion')['account_first_name_enabled']) {
			unset($fields['account']['account_first_name']);
		} else {
			$fields['account']['account_first_name']['required'] = (bool) sa_get_option('checkout_account_fields_accordion')['account_first_name_required'];
			if (!empty(sa_get_option('checkout_account_fields_accordion')['account_first_name_label'])) {
				$fields['account']['account_first_name']['label'] = sanitize_text_field(sa_get_option('checkout_account_fields_accordion')['account_first_name_label']);
			}
			if (sa_get_option('checkout_account_fields_accordion')['account_first_name_default'] !== '') {
				$fields['account']['account_first_name']['placeholder'] = sanitize_text_field(sa_get_option('checkout_account_fields_accordion')['account_first_name_placeholder']);
				$fields['account']['account_first_name']['default'] = sanitize_text_field(sa_get_option('checkout_account_fields_accordion')['account_first_name_default']);
			}
		}
	}

	if (isset($fields['account']['account_password'])) {
		if (!sa_get_option('checkout_account_fields_accordion')['account_password_enabled']) {
			unset($fields['account']['account_password']);
		} else {
			$fields['account']['account_password']['required'] = (bool) sa_get_option('checkout_account_fields_accordion')['account_password_required'];
			if (!empty(sa_get_option('checkout_account_fields_accordion')['account_password_label'])) {
				$fields['account']['account_password']['label'] = sanitize_text_field(sa_get_option('checkout_account_fields_accordion')['account_password_label']);
			}
			if (sa_get_option('checkout_account_fields_accordion')['account_password_default'] !== '') {
				$fields['account']['account_password']['placeholder'] = sanitize_text_field(sa_get_option('checkout_account_fields_accordion')['account_password_placeholder']);
				$fields['account']['account_password']['default'] = sanitize_text_field(sa_get_option('checkout_account_fields_accordion')['account_password_default']);
			}
		}
	}
	if (isset($fields['billing']['billing_account_password-2'])) {
		if (!sa_get_option('checkout_billing_fields_accordion')['billing_account_password-2_enabled']) {
			unset($fields['billing']['billing_account_password-2']);
		} else {
			$fields['billing']['billing_account_password-2']['required'] = (bool) sa_get_option('checkout_billing_fields_accordion')['billing_account_password-2_required'];
			if (!empty(sa_get_option('checkout_billing_fields_accordion')['billing_account_password-2_label'])) {
				$fields['billing']['billing_account_password-2']['label'] = sanitize_text_field(sa_get_option('checkout_billing_fields_accordion')['billing_account_password-2_label']);
			}
			if (sa_get_option('checkout_billing_fields_accordion')['billing_account_password-2_default'] !== '') {
				$fields['billing']['billing_account_password-2']['placeholder'] = sanitize_text_field(sa_get_option('checkout_billing_fields_accordion')['billing_account_password-2_placeholder']);
				$fields['billing']['billing_account_password-2']['default'] = sanitize_text_field(sa_get_option('checkout_billing_fields_accordion')['billing_account_password-2_default']);
			}
		}
	}

	if (isset($fields['billing']['billing_last_name'])) {
		if (!sa_get_option('checkout_billing_fields_accordion')['billing_last_name_enabled']) {
			unset($fields['billing']['billing_last_name']);
		} else {
			$fields['billing']['billing_last_name']['required'] = (bool) sa_get_option('checkout_billing_fields_accordion')['billing_last_name_required'];
			if (!empty(sa_get_option('checkout_billing_fields_accordion')['billing_last_name_label'])) {
				$fields['billing']['billing_last_name']['label'] = sanitize_text_field(sa_get_option('checkout_billing_fields_accordion')['billing_last_name_label']);
			}
			if (sa_get_option('checkout_billing_fields_accordion')['billing_last_name_default'] !== '') {
				$fields['billing']['billing_last_name']['placeholder'] = sanitize_text_field(sa_get_option('checkout_billing_fields_accordion')['billing_last_name_placeholder']);
				$fields['billing']['billing_last_name']['default'] = sanitize_text_field(sa_get_option('checkout_billing_fields_accordion')['billing_last_name_default']);
			}
		}
	}

	if (isset($fields['billing']['billing_company'])) {
		if (!sa_get_option('checkout_billing_fields_accordion')['billing_company_enabled']) {
			unset($fields['billing']['billing_company']);
		} else {
			$fields['billing']['billing_company']['required'] = (bool) sa_get_option('checkout_billing_fields_accordion')['billing_company_required'];
			if (!empty(sa_get_option('checkout_billing_fields_accordion')['billing_company_label'])) {
				$fields['billing']['billing_company']['label'] = sanitize_text_field(sa_get_option('checkout_billing_fields_accordion')['billing_company_label']);
			}
			if (sa_get_option('checkout_billing_fields_accordion')['billing_company_default'] !== '') {
				$fields['billing']['billing_company']['placeholder'] = sanitize_text_field(sa_get_option('checkout_billing_fields_accordion')['billing_company_placeholder']);
				$fields['billing']['billing_company']['default'] = sanitize_text_field(sa_get_option('checkout_billing_fields_accordion')['billing_company_default']);
			}
		}
	}

	if (isset($fields['billing']['billing_country'])) {
		if (!sa_get_option('checkout_billing_fields_accordion')['billing_country_enabled']) {
			unset($fields['billing']['billing_country']);
		} else {
			$fields['billing']['billing_country']['required'] = (bool) sa_get_option('checkout_billing_fields_accordion')['billing_country_required'];
			if (!empty(sa_get_option('checkout_billing_fields_accordion')['billing_country_label'])) {
				$fields['billing']['billing_country']['label'] = sanitize_text_field(sa_get_option('checkout_billing_fields_accordion')['billing_country_label']);
			}
		}
	}

	if (isset($fields['billing']['billing_address_1'])) {
		if (!sa_get_option('checkout_billing_fields_accordion')['billing_address_1_enabled']) {
			unset($fields['billing']['billing_address_1']);
		} else {
			$fields['billing']['billing_address_1']['required'] = (bool) sa_get_option('checkout_billing_fields_accordion')['billing_address_1_required'];
			if (!empty(sa_get_option('checkout_billing_fields_accordion')['billing_address_1_label'])) {
				$fields['billing']['billing_address_1']['label'] = sanitize_text_field(sa_get_option('checkout_billing_fields_accordion')['billing_address_1_label']);
			}
			if (sa_get_option('checkout_billing_fields_accordion')['billing_address_1_default'] !== '') {
				$fields['billing']['billing_address_1']['placeholder'] = sanitize_text_field(sa_get_option('checkout_billing_fields_accordion')['billing_address_1_placeholder']);
				$fields['billing']['billing_address_1']['default'] = sanitize_text_field(sa_get_option('checkout_billing_fields_accordion')['billing_address_1_default']);
			}
		}
	}

	if (isset($fields['billing']['billing_address_2'])) {
		if (!sa_get_option('checkout_billing_fields_accordion')['billing_address_2_enabled']) {
			unset($fields['billing']['billing_address_2']);
		} else {
			$fields['billing']['billing_address_2']['required'] = (bool) sa_get_option('checkout_billing_fields_accordion')['billing_address_2_required'];
			if (!empty(sa_get_option('checkout_billing_fields_accordion')['billing_address_2_label'])) {
				$fields['billing']['billing_address_2']['label'] = sanitize_text_field(sa_get_option('checkout_billing_fields_accordion')['billing_address_2_label']);
			}
			if (sa_get_option('checkout_billing_fields_accordion')['billing_address_2_default'] !== '') {
				$fields['billing']['billing_address_2']['placeholder'] = sanitize_text_field(sa_get_option('checkout_billing_fields_accordion')['billing_address_2_placeholder']);
				$fields['billing']['billing_address_2']['default'] = sanitize_text_field(sa_get_option('checkout_billing_fields_accordion')['billing_address_2_default']);
			}
		}
	}

	if (isset($fields['billing']['billing_city'])) {
		if (!sa_get_option('checkout_billing_fields_accordion')['billing_city_enabled']) {
			unset($fields['billing']['billing_city']);
		} else {
			$fields['billing']['billing_city']['required'] = (bool) sa_get_option('checkout_billing_fields_accordion')['billing_city_required'];
			if (!empty(sa_get_option('checkout_billing_fields_accordion')['billing_city_label'])) {
				$fields['billing']['billing_city']['label'] = sanitize_text_field(sa_get_option('checkout_billing_fields_accordion')['billing_city_label']);
			}
			if (sa_get_option('checkout_billing_fields_accordion')['billing_city_default'] !== '') {
				$fields['billing']['billing_city']['placeholder'] = sanitize_text_field(sa_get_option('checkout_billing_fields_accordion')['billing_city_placeholder']);
				$fields['billing']['billing_city']['default'] = sanitize_text_field(sa_get_option('checkout_billing_fields_accordion')['billing_city_default']);
			}
		}
	}

	if (isset($fields['billing']['billing_state'])) {
		if (!sa_get_option('checkout_billing_fields_accordion')['billing_state_enabled']) {
			unset($fields['billing']['billing_state']);
		} else {
			$fields['billing']['billing_state']['required'] = (bool) sa_get_option('checkout_billing_fields_accordion')['billing_state_required'];
			if (!empty(sa_get_option('checkout_billing_fields_accordion')['billing_state_label'])) {
				$fields['billing']['billing_state']['label'] = sanitize_text_field(sa_get_option('checkout_billing_fields_accordion')['billing_state_label']);
			}
		}
	}

	if (isset($fields['billing']['billing_postcode'])) {
		if (!sa_get_option('checkout_billing_fields_accordion')['billing_postcode_enabled']) {
			unset($fields['billing']['billing_postcode']);
		} else {
			$fields['billing']['billing_postcode']['required'] = (bool) sa_get_option('checkout_billing_fields_accordion')['billing_postcode_required'];
			if (!empty(sa_get_option('checkout_billing_fields_accordion')['billing_postcode_label'])) {
				$fields['billing']['billing_postcode']['label'] = sanitize_text_field(sa_get_option('checkout_billing_fields_accordion')['billing_postcode_label']);
			}
			if (sa_get_option('checkout_billing_fields_accordion')['billing_postcode_default'] !== '') {
				$fields['billing']['billing_postcode']['placeholder'] = sanitize_text_field(sa_get_option('checkout_billing_fields_accordion')['billing_postcode_placeholder']);
				$fields['billing']['billing_postcode']['default'] = sanitize_text_field(sa_get_option('checkout_billing_fields_accordion')['billing_postcode_default']);
			}
		}
	}

	if (isset($fields['billing']['billing_phone'])) {
		if (!sa_get_option('checkout_billing_fields_accordion')['billing_phone_enabled']) {
			unset($fields['billing']['billing_phone']);
		} else {
			$fields['billing']['billing_phone']['required'] = (bool) sa_get_option('checkout_billing_fields_accordion')['billing_phone_required'];
			if (!empty(sa_get_option('checkout_billing_fields_accordion')['billing_phone_label'])) {
				$fields['billing']['billing_phone']['label'] = sanitize_text_field(sa_get_option('checkout_billing_fields_accordion')['billing_phone_label']);
			}
			if (sa_get_option('checkout_billing_fields_accordion')['billing_phone_default'] !== '') {
				$fields['billing']['billing_phone']['placeholder'] = sanitize_text_field(sa_get_option('checkout_billing_fields_accordion')['billing_phone_placeholder']);
				$fields['billing']['billing_phone']['default'] = sanitize_text_field(sa_get_option('checkout_billing_fields_accordion')['billing_phone_default']);
			}
		}
	}

	if (isset($fields['billing']['billing_email'])) {
		if (!sa_get_option('checkout_billing_fields_accordion')['billing_email_enabled']) {
			unset($fields['billing']['billing_email']);
		} else {
			$fields['billing']['billing_email']['required'] = (bool) sa_get_option('checkout_billing_fields_accordion')['billing_email_required'];
			if (!empty(sa_get_option('checkout_billing_fields_accordion')['billing_email_label'])) {
				$fields['billing']['billing_email']['label'] = sanitize_text_field(sa_get_option('checkout_billing_fields_accordion')['billing_email_label']);
			}
			if (sa_get_option('checkout_billing_fields_accordion')['billing_email_default'] !== '') {
				$fields['billing']['billing_email']['placeholder'] = sanitize_text_field(sa_get_option('checkout_billing_fields_accordion')['billing_email_placeholder']);
				$fields['billing']['billing_email']['default'] = sanitize_text_field(sa_get_option('checkout_billing_fields_accordion')['billing_email_default']);
			}
		}
	}

	if (isset($fields['shipping']['shipping_first_name'])) {
		if (!sa_get_option('checkout_shipping_fields_accordion')['shipping_first_name_enabled']) {
			unset($fields['shipping']['shipping_first_name']);
		} else {
			$fields['shipping']['shipping_first_name']['required'] = (bool) sa_get_option('checkout_shipping_fields_accordion')['shipping_first_name_required'];
			if (!empty(sa_get_option('checkout_shipping_fields_accordion')['shipping_first_name_label'])) {
				$fields['shipping']['shipping_first_name']['label'] = sanitize_text_field(sa_get_option('checkout_shipping_fields_accordion')['shipping_first_name_label']);
			}
			if (sa_get_option('checkout_shipping_fields_accordion')['shipping_first_name_default'] !== '') {
				$fields['shipping']['shipping_first_name']['placeholder'] = sanitize_text_field(sa_get_option('checkout_shipping_fields_accordion')['shipping_first_name_placeholder']);
				$fields['shipping']['shipping_first_name']['default'] = sanitize_text_field(sa_get_option('checkout_shipping_fields_accordion')['shipping_first_name_default']);
			}
		}
	}

	if (isset($fields['shipping']['shipping_last_name'])) {
		if (!sa_get_option('checkout_shipping_fields_accordion')['shipping_last_name_enabled']) {
			unset($fields['shipping']['shipping_last_name']);
		} else {
			$fields['shipping']['shipping_last_name']['required'] = (bool) sa_get_option('checkout_shipping_fields_accordion')['shipping_last_name_required'];
			if (!empty(sa_get_option('checkout_shipping_fields_accordion')['shipping_last_name_label'])) {
				$fields['shipping']['shipping_last_name']['label'] = sanitize_text_field(sa_get_option('checkout_shipping_fields_accordion')['shipping_last_name_label']);
			}
			if (sa_get_option('checkout_shipping_fields_accordion')['shipping_last_name_default'] !== '') {
				$fields['shipping']['shipping_last_name']['placeholder'] = sanitize_text_field(sa_get_option('checkout_shipping_fields_accordion')['shipping_last_name_placeholder']);
				$fields['shipping']['shipping_last_name']['default'] = sanitize_text_field(sa_get_option('checkout_shipping_fields_accordion')['shipping_last_name_default']);
			}
		}
	}

	if (isset($fields['shipping']['shipping_company'])) {
		if (!sa_get_option('checkout_shipping_fields_accordion')['shipping_company_enabled']) {
			unset($fields['shipping']['shipping_company']);
		} else {
			$fields['shipping']['shipping_company']['required'] = (bool) sa_get_option('checkout_shipping_fields_accordion')['shipping_company_required'];
			if (!empty(sa_get_option('checkout_shipping_fields_accordion')['shipping_company_label'])) {
				$fields['shipping']['shipping_company']['label'] = sanitize_text_field(sa_get_option('checkout_shipping_fields_accordion')['shipping_company_label']);
			}
			if (sa_get_option('checkout_shipping_fields_accordion')['shipping_company_default'] !== '') {
				$fields['shipping']['shipping_company']['placeholder'] = sanitize_text_field(sa_get_option('checkout_shipping_fields_accordion')['shipping_company_placeholder']);
				$fields['shipping']['shipping_company']['default'] = sanitize_text_field(sa_get_option('checkout_shipping_fields_accordion')['shipping_company_default']);
			}
		}
	}

	if (isset($fields['shipping']['shipping_country'])) {
		if (!sa_get_option('checkout_shipping_fields_accordion')['shipping_country_enabled']) {
			unset($fields['shipping']['shipping_country']);
		} else {
			$fields['shipping']['shipping_country']['required'] = (bool) sa_get_option('checkout_shipping_fields_accordion')['shipping_country_required'];
			if (!empty(sa_get_option('checkout_shipping_fields_accordion')['shipping_country_label'])) {
				$fields['shipping']['shipping_country']['label'] = sanitize_text_field(sa_get_option('checkout_shipping_fields_accordion')['shipping_country_label']);
			}
		}
	}

	if (isset($fields['shipping']['shipping_address_1'])) {
		if (!sa_get_option('checkout_shipping_fields_accordion')['shipping_address_1_enabled']) {
			unset($fields['shipping']['shipping_address_1']);
		} else {
			$fields['shipping']['shipping_address_1']['required'] = (bool) sa_get_option('checkout_shipping_fields_accordion')['shipping_address_1_required'];
			if (!empty(sa_get_option('checkout_shipping_fields_accordion')['shipping_address_1_label'])) {
				$fields['shipping']['shipping_address_1']['label'] = sanitize_text_field(sa_get_option('checkout_shipping_fields_accordion')['shipping_address_1_label']);
			}
			if (sa_get_option('checkout_shipping_fields_accordion')['shipping_address_1_default'] !== '') {
				$fields['shipping']['shipping_address_1']['placeholder'] = sanitize_text_field(sa_get_option('checkout_shipping_fields_accordion')['shipping_address_1_placeholder']);
				$fields['shipping']['shipping_address_1']['default'] = sanitize_text_field(sa_get_option('checkout_shipping_fields_accordion')['shipping_address_1_default']);
			}
		}
	}

	if (isset($fields['shipping']['shipping_address_2'])) {
		if (!sa_get_option('checkout_shipping_fields_accordion')['shipping_address_2_enabled']) {
			unset($fields['shipping']['shipping_address_2']);
		} else {
			$fields['shipping']['shipping_address_2']['required'] = (bool) sa_get_option('checkout_shipping_fields_accordion')['shipping_address_2_required'];
			if (!empty(sa_get_option('checkout_shipping_fields_accordion')['shipping_address_2_label'])) {
				$fields['shipping']['shipping_address_2']['label'] = sanitize_text_field(sa_get_option('checkout_shipping_fields_accordion')['shipping_address_2_label']);
			}
			if (sa_get_option('checkout_shipping_fields_accordion')['shipping_address_2_default'] !== '') {
				$fields['shipping']['shipping_address_2']['placeholder'] = sanitize_text_field(sa_get_option('checkout_shipping_fields_accordion')['shipping_address_2_placeholder']);
				$fields['shipping']['shipping_address_2']['default'] = sanitize_text_field(sa_get_option('checkout_shipping_fields_accordion')['shipping_address_2_default']);
			}
		}
	}

	if (isset($fields['shipping']['shipping_city'])) {
		if (!sa_get_option('checkout_shipping_fields_accordion')['shipping_city_enabled']) {
			unset($fields['shipping']['shipping_city']);
		} else {
			$fields['shipping']['shipping_city']['required'] = (bool) sa_get_option('checkout_shipping_fields_accordion')['shipping_city_required'];
			if (!empty(sa_get_option('checkout_shipping_fields_accordion')['shipping_city_label'])) {
				$fields['shipping']['shipping_city']['label'] = sanitize_text_field(sa_get_option('checkout_shipping_fields_accordion')['shipping_city_label']);
			}
			if (sa_get_option('checkout_shipping_fields_accordion')['shipping_city_default'] !== '') {
				$fields['shipping']['shipping_city']['placeholder'] = sanitize_text_field(sa_get_option('checkout_shipping_fields_accordion')['shipping_city_placeholder']);
				$fields['shipping']['shipping_city']['default'] = sanitize_text_field(sa_get_option('checkout_shipping_fields_accordion')['shipping_city_default']);
			}
		}
	}

	if (isset($fields['shipping']['shipping_state'])) {
		if (!sa_get_option('checkout_shipping_fields_accordion')['shipping_state_enabled']) {
			unset($fields['shipping']['shipping_state']);
		} else {
			$fields['shipping']['shipping_state']['required'] = (bool) sa_get_option('checkout_shipping_fields_accordion')['shipping_state_required'];
			if (!empty(sa_get_option('checkout_shipping_fields_accordion')['shipping_state_label'])) {
				$fields['shipping']['shipping_state']['label'] = sanitize_text_field(sa_get_option('checkout_shipping_fields_accordion')['shipping_state_label']);
			}
		}
	}

	if (isset($fields['shipping']['shipping_postcode'])) {
		if (!sa_get_option('checkout_shipping_fields_accordion')['shipping_postcode_enabled']) {
			unset($fields['shipping']['shipping_postcode']);
		} else {
			$fields['shipping']['shipping_postcode']['required'] = (bool) sa_get_option('checkout_shipping_fields_accordion')['shipping_postcode_required'];
			if (!empty(sa_get_option('checkout_shipping_fields_accordion')['shipping_postcode_label'])) {
				$fields['shipping']['shipping_postcode']['label'] = sanitize_text_field(sa_get_option('checkout_shipping_fields_accordion')['shipping_postcode_label']);
			}
			if (sa_get_option('checkout_shipping_fields_accordion')['shipping_postcode_default'] !== '') {
				$fields['shipping']['shipping_postcode']['placeholder'] = sanitize_text_field(sa_get_option('checkout_shipping_fields_accordion')['shipping_postcode_placeholder']);
				$fields['shipping']['shipping_postcode']['default'] = sanitize_text_field(sa_get_option('checkout_shipping_fields_accordion')['shipping_postcode_default']);
			}
		}
	}

	if (isset($fields['order']['order_comments'])) {
		if (!sa_get_option('checkout_order_fields_accordion')['order_comments_enabled']) {
			unset($fields['order']['order_comments']);
		} else {
			$fields['order']['order_comments']['required'] = (bool) sa_get_option('checkout_order_fields_accordion')['order_comments_required'];
			if (!empty(sa_get_option('checkout_order_fields_accordion')['order_comments_label'])) {
				$fields['order']['order_comments']['label'] = sanitize_text_field(sa_get_option('checkout_order_fields_accordion')['order_comments_label']);
			}
			if (sa_get_option('checkout_order_fields_accordion')['order_comments_default'] !== '') {
				$fields['order']['order_comments']['placeholder'] = sanitize_text_field(sa_get_option('checkout_order_fields_accordion')['order_comments_placeholder']);
				$fields['order']['order_comments']['default'] = sanitize_text_field(sa_get_option('checkout_order_fields_accordion')['order_comments_default']);
			}
		}
	}

	return $fields;
}

add_filter('woocommerce_checkout_required_field_notice', function ($message, $field_key) {
	if ($field_key === 'billing_email' && !sa_get_option('checkout_billing_fields_accordion')['billing_email_enabled']) {
		return '';
	}

	if ($field_key === 'billing_phone' && !sa_get_option('checkout_billing_fields_accordion')['billing_phone_enabled']) {
		return '';
	}

	return $message;
}, 10, 2);

function user_have_vpn(): bool
{
	if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$user_ip = strtok($_SERVER['HTTP_X_FORWARDED_FOR'], ',');
	} else {
		$user_ip = $_SERVER['REMOTE_ADDR'];
	}
	$url = "http://ip-api.com/json/{$user_ip}?fields=country";
	$response = wp_remote_get($url);

	if (is_wp_error($response)) {
		sa_log('general', 'info', "VPN Checker", 'HTTP Error: ' . $response->get_error_message());
		return false;
	}

	$data = wp_remote_retrieve_body($response);
	$response = json_decode($data);

	if (isset($response->error)) {
		sa_log('general', 'info', "VPN Checker", 'API Error: ' . $response->error);
		return false;
	}

	if ($response && isset($response->country) && $response->country !== 'Iran') {
		return true;
	}

	return false;
}
