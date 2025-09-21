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

	// Disable WordPress updates
	if (sa_get_option('disable_wp_updates', false) && strpos($_SERVER['PHP_SELF'], 'update-core.php') === false) {
		add_filter('automatic_updater_disabled', '__return_true');
		add_filter('auto_update_core', '__return_false');
		add_filter('auto_update_plugin', '__return_false');
		add_filter('auto_update_theme', '__return_false');
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
	}
}

/**
 * WooCommerce Admin Settings
 */
if (class_exists('WooCommerce') && is_admin() && !is_woocommerce_admin_page()) {
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
		return (is_admin() && isset($_GET['page']) && $_GET['page'] === 'xts_patcher') ? true : false;
	});
}

/**
 * Free Shipping Progress Bar
 */
if (class_exists('WooCommerce')) {
	$free_shipping_min_mashhad = (int) sa_get_option('free_shipping_min_mashhad', 0);
	$free_shipping_min_other_cities = (int) sa_get_option('free_shipping_min_other_cities', 0);

	if (sa_get_option('free_shipping_status', false) && ($free_shipping_min_mashhad > 0 || $free_shipping_min_other_cities > 0)) {
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

		if ($min_mashhad <= 0 && $min_others <= 0) {
			return;
		}

		$progress_mashhad = ($min_mashhad > 0) ? min(100, ($cart_total / $min_mashhad) * 100) : 0;
		$progress_others = ($min_others > 0) ? min(100, ($cart_total / $min_others) * 100) : 0;
		$remain_mashhad = max(0, $min_mashhad - $cart_total);
		$remain_others = max(0, $min_others - $cart_total);
		$shipping_is_free = ($cart_total >= $min_mashhad && $cart_total >= $min_others);

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
}

/**
 * Automatic Membership Settings
 */
if (class_exists('WooCommerce')) {
	add_action('wp_footer', function () {
		if (!is_checkout() || is_order_received_page()) {
			return;
		}

		if (sa_get_option('enable_auto_membership', false)) {
		?>
			<script type="text/javascript">
				document.addEventListener('DOMContentLoaded', () => {
					const checkbox = document.getElementById('createaccount');
					if (checkbox && !checkbox.checked) {
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
 * Order Management
 */
if (class_exists('WooCommerce') && sa_get_option('order_convertor_status', false)) {
	add_action('wp', 'sa_setup_order_status_cron');
	add_filter('cron_schedules', 'sa_add_cron_interval');
	add_action('sa_change_pending_orders', 'sa_change_pending_orders');
	add_action('sa_change_failed_orders', 'sa_change_failed_orders');

	function sa_setup_order_status_cron()
	{
		if (!wp_next_scheduled('sa_change_pending_orders')) {
			wp_schedule_event(time(), 'every_15_minutes', 'sa_change_pending_orders');
		}
		if (!wp_next_scheduled('sa_change_failed_orders')) {
			wp_schedule_event(time(), 'every_15_minutes', 'sa_change_failed_orders');
		}
	}

	function sa_add_cron_interval($schedules)
	{
		$schedules['every_15_minutes'] = [
			'interval' => 15 * MINUTE_IN_SECONDS,
			'display'  => esc_html__('Every 15 Minutes', 'safe-assistant'),
		];
		return $schedules;
	}

	function sa_change_pending_orders()
	{
		$start_time = (int) sa_get_option('order_convertor_start_time', 8);
		$end_time = (int) sa_get_option('order_convertor_end_time', 16);
		$fail_hours = (int) sa_get_option('order_to_fail_pending_time', 1);
		$current_hour = (int) current_time('H');

		if ($current_hour < $start_time || $current_hour > $end_time) {
			return;
		}

		$args = [
			'status' => 'pending',
			'date_created' => '<' . strtotime("-{$fail_hours} hours"),
			'limit' => -1,
		];

		$orders = wc_get_orders($args);

		foreach ($orders as $order) {
			if (!is_a($order, 'WC_Order')) {
				continue;
			}

			$user_id = $order->get_user_id();
			if (!$user_id) {
				$order->update_status('failed', esc_html__('Guest order auto-failed after timeout.', 'safe-assistant'));
				continue;
			}

			$product_ids = array_map(function ($item) {
				return $item->get_product_id();
			}, $order->get_items());

			$customer_orders = wc_get_orders([
				'customer_id' => $user_id,
				'status' => ['pending', 'processing', 'completed'],
				'limit' => -1,
				'orderby' => 'date',
				'order' => 'DESC',
			]);

			$newer_similar_order_exists = false;
			foreach ($customer_orders as $co) {
				if ($co->get_id() === $order->get_id() || $co->get_date_created() <= $order->get_date_created()) {
					continue;
				}

				foreach ($co->get_items() as $item) {
					if (in_array($item->get_product_id(), $product_ids, true)) {
						$newer_similar_order_exists = true;
						break 2;
					}
				}
			}

			$status = $newer_similar_order_exists ? 'cancelled' : 'failed';
			$note = $newer_similar_order_exists
				? esc_html__('Order cancelled due to newer similar order.', 'safe-assistant')
				: esc_html__('Order auto-failed after timeout.', 'safe-assistant');
			$order->update_status($status, $note);
		}
	}

	function sa_change_failed_orders()
	{
		$cancel_hours = (int) sa_get_option('order_to_canceled_pending_time', 36);
		$args = [
			'status' => 'failed',
			'date_created' => '<' . (time() - $cancel_hours * HOUR_IN_SECONDS),
			'limit' => -1,
		];
		$orders = wc_get_orders($args);

		foreach ($orders as $order) {
			if (is_a($order, 'WC_Order')) {
				$order->update_status('cancelled', esc_html__('Order auto-cancelled after timeout.', 'safe-assistant'));
			}
		}
	}

	// Show order notes in admin orders table
	if (sa_get_option('show_order_notes_in_admin_table', false)) {
		add_filter('manage_edit-shop_order_columns', function ($columns) {
			$new_columns = [];
			foreach ($columns as $key => $column) {
				$new_columns[$key] = $column;
				if ($key === 'order_status') {
					$new_columns['order_notes'] = esc_html__('Customer Note', 'safe-assistant');
				}
			}
			return $new_columns;
		}, 20);

		add_action('manage_shop_order_posts_custom_column', function ($column, $post_id) {
			if ($column === 'order_notes') {
				$order = wc_get_order($post_id);
				if (is_a($order, 'WC_Order')) {
					echo esc_html($order->get_customer_note());
				}
			}
		}, 10, 2);

		add_filter('manage_woocommerce_page_wc-orders_columns', function ($columns) {
			$columns['order_notes'] = esc_html__('Customer Note', 'safe-assistant');
			return $columns;
		}, 20);

		add_action('manage_woocommerce_page_wc-orders_custom_column', function ($column, $order_id) {
			if ($column === 'order_notes') {
				$order = wc_get_order($order_id);
				if (is_a($order, 'WC_Order')) {
					echo esc_html($order->get_customer_note());
				}
			}
		}, 10, 2);
	}
}

/**
 * Wallet Settings
 */
if (defined('nirweb_wallet')) {
	function nirweb_wallet_expiration_check_by_hour()
	{
		$pattern = sa_get_option('nir_wallet_expire_pattern_sms_hour', '');
		if (!$pattern) {
			return;
		}

		global $wpdb;
		$current_time = current_time('timestamp');

		$users = $wpdb->get_results(
			"SELECT user_id, amount, expire_time 
         FROM {$wpdb->prefix}nirweb_wallet_cashback
         WHERE expire_time > {$current_time}"
		);

		if (!$users) {
			return;
		}

		foreach ($users as $user) {
			$phone = get_user_meta($user->user_id, 'billing_phone', true);
			if (!$phone) {
				continue;
			}

			$user_info = get_userdata($user->user_id);
			$name      = $user_info && $user_info->first_name ? $user_info->first_name : $user_info->display_name;

			$diff_hours = floor(($user->expire_time - $current_time) / HOUR_IN_SECONDS);

			if (function_exists('jdate')) {
				$expire_date = jdate('Y/m/d H:i', $user->expire_time);
			} else {
				$expire_date = date_i18n('Y/m/d H:i', $user->expire_time);
			}

			$pattern_vars = "$name;$diff_hours";

			sa_send_sms_pattern(
				$pattern_vars,
				'09155909469',
				$pattern
			);
		}
	}

	function nirweb_wallet_expiration_check_by_days()
	{
		$expire_days   = (array) sa_get_option('nir_wallet_expire_day_sms', [24]);
		$pattern       = sa_get_option('nir_wallet_expire_pattern_sms', '');
		$pattern_last  = sa_get_option('nir_wallet_expire_last_pattern_sms', '');

		if (!$expire_days || (!$pattern && !$pattern_last)) {
			return;
		}

		global $wpdb;
		$current_time = current_time('timestamp');

		foreach ($expire_days as $days) {
			$days = (int) $days;
			if ($days <= 0) {
				continue;
			}

			$time = $current_time + $days * HOUR_IN_SECONDS;

			$users = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT user_id, amount 
             FROM {$wpdb->prefix}nirweb_wallet_cashback
             WHERE expire_time BETWEEN %d AND %d",
					$time - HOUR_IN_SECONDS,
					$time + HOUR_IN_SECONDS
				)
			);

			if (!$users) {
				continue;
			}

			foreach ($users as $user) {
				$phone     = get_user_meta($user->user_id, 'billing_phone', true);
				if (!$phone) {
					continue;
				}

				$user_info = get_userdata($user->user_id);
				$name      = $user_info && $user_info->first_name ? $user_info->first_name : $user_info->display_name;

				$selected_pattern = ($days === 24 && $pattern_last) ? $pattern_last : $pattern;
				$pattern_vars = ($days === 24 && $pattern_last) ? "$name" : "$name;$days";

				sa_send_sms_pattern(
					$pattern_vars,
					'09155909469',
					$selected_pattern
				);
			}
		}
	}

	$handler_function = sa_get_option('nir_wallet_expire_check_by', 'days') === 'hours'
		? 'nirweb_wallet_expiration_check_by_hour'
		: 'nirweb_wallet_expiration_check_by_days';

	add_action('sa_nir_wallet_expiration_check', $handler_function);

	add_action('csf_options_save', function ($options, $option_name) {

		if ($option_name !== SAFE_ASSISTANT_SLUG . '-settings') {
			return;
		}

		$old_options = get_option($option_name, []);

		$old_time = $old_options['nir_wallet_expire_send_time'] ?? '09';
		$new_time = $options['nir_wallet_expire_send_time'] ?? '09';

		$old_check_by = $old_options['nir_wallet_expire_check_by'] ?? 'days';
		$new_check_by = $options['nir_wallet_expire_check_by'] ?? 'days';

		if ($old_time !== $new_time || $old_check_by !== $new_check_by) {

			wp_clear_scheduled_hook('sa_nir_wallet_expiration_check');

			if (strlen((string)$new_time) === 1) {
				$new_time = "0$new_time";
			}

			$timestamp = strtotime("today $new_time:00");
			if ($timestamp <= time()) {
				$timestamp += DAY_IN_SECONDS;
			}

			wp_schedule_event($timestamp, 'daily', 'sa_nir_wallet_expiration_check');
		}
	}, 10, 2);
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
