<?php

if (sa_get_option('order_convertor_status', false)) {
    add_action('wp', 'sa_setup_order_status_cron');
    add_filter('cron_schedules', 'sa_add_cron_interval');
    add_action('sa_change_pending_orders', 'sa_change_pending_orders');
    add_action('sa_change_failed_orders', 'sa_change_failed_orders');
}

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
            /**
             * @disregard P1013 Undefined method
             */
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
                /**
                 * @disregard P1013 Undefined method
                 */
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

/**
 * Order With Tracking code functionality
 */

if (sa_get_option('order_management_pro_status', false)) {
    add_action('admin_menu', 'add_tracking_admin_page');
    add_action('wp_ajax_save_order_tracking_ajax', 'handle_save_tracking_ajax');
}
// Ajax handler for saving tracking code
function handle_save_tracking_ajax()
{
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tracking_nonce')) {
        wp_die(__('Security check failed.', 'safe-assistant'));
    }

    if (!current_user_can('manage_woocommerce')) {
        wp_die(__('You do not have permission.', 'safe-assistant'));
    }

    $order_id      = intval($_POST['order_id']);
    $tracking_code = sanitize_text_field($_POST['tracking_code']);

    if (empty($tracking_code)) {
        wp_send_json_error(__('Tracking code is empty.', 'safe-assistant'));
    }

    $order = wc_get_order($order_id);

    if (!$order) {
        wp_send_json_error(__('Invalid order.', 'safe-assistant'));
    }

    // Save tracking code
    update_post_meta($order_id, '_tracking_code', $tracking_code);

    /**
     * @disregard P1010 Undefined function
     */
    if (function_exists('PWSMS')) remove_action('woocommerce_order_status_changed', [PWSMS()->orders, 'send_order_sms'], 99);

    if ($order->get_status() !== 'completed') {
        $order->update_status(
            'completed',
            sprintf(__('Order completed with tracking code %s.', 'safe-assistant'), $tracking_code)
        );
    }

    /**
     * @disregard P1010 Undefined function
     */
    if (function_exists('PWSMS')) add_action('woocommerce_order_status_changed', [PWSMS()->orders, 'send_order_sms'], 99);

    // Add order note
    $order->add_order_note(sprintf(__('Tracking code saved: %s', 'safe-assistant'), $tracking_code));

    if (sa_get_option('order_management_pro_sms_status', false) && !empty($order->get_billing_phone())) {

        sa_send_sms_pattern([
            'name' => $order->get_billing_first_name() ?: $order->get_billing_last_name() ?: $order->get_billing_company() ?: __('Customer', 'safe-assistant'),
            'code' => $tracking_code,
        ], $order->get_billing_phone(), sa_get_option('order_management_pro_sms_pattern', ''));
    }
    wp_send_json_success(__('Saved successfully.', 'safe-assistant'));
}

// Add submenu page in WooCommerce
function add_tracking_admin_page()
{
    add_submenu_page(
        'woocommerce',
        __('Orders Management Pro', 'safe-assistant'),
        __('Orders Management Pro', 'safe-assistant'),
        'manage_woocommerce',
        'tracking-orders',
        'render_tracking_admin_page'
    );
}

// Render tracking page (loads partial)
function render_tracking_admin_page()
{
    if (!current_user_can('manage_woocommerce')) {
        wp_die(__('You do not have permission.', 'safe-assistant'));
    }

    $main_city = sa_get_option('order_management_pro_main_city');

    // Get orders by status
    $statuses = ['wc-processing', 'wc-on-hold', 'wc-failed'];
    $orders = wc_get_orders([
        'status'  => $statuses,
        'limit'   => -1,
        'orderby' => 'date',
        'order'   => 'DESC',
    ]);

    // Group orders by status + main/other city
    $orders_by_status = [];
    foreach ($statuses as $status) {
        $orders_by_status[$status] = [
            'main_city'  => [],
            'other_city' => [],
        ];
    }

    foreach ($orders as $order) {
        $status     = $order->get_status(); // pending, on-hold, failed
        $order_city = $order->get_shipping_city() ?: $order->get_billing_city();

        if ($order_city === $main_city) {
            $orders_by_status[$status]['main_city'][] = $order;
        } else {
            $orders_by_status[$status]['other_city'][] = $order;
        }
    }

    include_once ADDON_ORDER_TOOLKIT_DIR . 'partials/order-toolkit-orders-page.php';
}
