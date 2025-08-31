<?php

$free_shipping_min_mashhad = sa_get_option('free_shipping_min_mashhad', 0);
$free_shipping_min_other_cities = sa_get_option('free_shipping_min_other_cities', 0);
add_action('wp_footer', 'wp_footer_codes');

if ($free_shipping_min_mashhad + $free_shipping_min_other_cities > 0) {
    add_action('woocommerce_cart_totals_after_order_total', 'render_free_shipping_progress');
    add_action('woocommerce_checkout_before_order_review', 'render_free_shipping_progress');
    add_action('woocommerce_widget_shopping_cart_after_buttons', 'render_free_shipping_progress');
}

if (sa_get_option('remove_email_checkout')) {
    add_filter('woocommerce_checkout_fields', 'elite_remove_woo_checkout_fields', 99);
}

if (sa_get_option('show_order_notes_in_admin_table') && is_admin()) {
    add_filter('manage_woocommerce_page_wc-orders_columns', 'note_shop_order_column', 20); // HPOS
    add_filter('manage_edit-shop_order_columns', 'note_shop_order_column', 20);
    add_action('manage_woocommerce_page_wc-orders_note_column', 'note_shop_order_list_column_content', 10, 2); // HPOS
    add_action('manage_shop_order_posts_note_column', 'note_shop_order_list_column_content', 10, 2);
}

function note_shop_order_column($columns)
{
    $ordered_columns = array();

    foreach ($columns as $key => $column) {
        $ordered_columns[$key] = $column;
        if ('origin' == $key) {
            $ordered_columns['order_notes'] = __('Customer Note', 'safe-assistant');
        }
    }

    return $ordered_columns;
}

function note_shop_order_list_column_content($column, $order)
{
    if (! is_a($order, 'WC_order') && $order > 0) {
        $order = wc_get_order($order);
    }

    if (! is_a($order, 'WC_order')) {
        return;
    }

    if ($column == 'order_notes') {
        $order_notes = $order->get_customer_note();
        echo "<span>$order_notes</span>";
    }
}
function wp_footer_codes()
{
    if (is_checkout() && !is_order_received_page()) {
        if (sa_get_option('enable_auto_membership')) {
            echo <<<HTML
                <script type="text/javascript">
                    document.addEventListener('DOMContentLoaded', function() {
                        var checkbox = document.getElementById('createaccount');
                        if (checkbox && !checkbox.checked) {
                            checkbox.checked = true;
                        }
                    });
                </script>
            HTML;
        }
        if (sa_get_option('hide_membership_option_checkout')) {
            echo <<<HTML
                <style>
                .woocommerce-checkout #createaccount {
                    display: none !important;
                }
            </style>
            HTML;
        }
    }
}

if (sa_get_option('order_convertor_status')) {
    add_action('wp', 'custom_order_status_change_cron_jobs');
    add_filter('cron_schedules', 'custom_cron_interval');
    add_action('change_pending_orders_to_failed', 'change_pending_orders_to_failed_func');
    add_action('change_failed_orders_to_cancelled', 'change_failed_orders_to_cancelled_func');
}

function custom_order_status_change_cron_jobs()
{
    if (! wp_next_scheduled('change_pending_orders_to_failed')) {
        wp_schedule_event(time(), 'every_15_minutes', 'change_pending_orders_to_failed');
    }
    if (! wp_next_scheduled('change_failed_orders_to_cancelled')) {
        wp_schedule_event(time(), 'every_15_minutes', 'change_failed_orders_to_cancelled');
    }
}

function custom_cron_interval($schedules)
{
    $schedules['every_15_minutes'] = array(
        'interval' => 15 * MINUTE_IN_SECONDS,
        'display'  => __('Every 15 Minutes'),
    );
    return $schedules;
}

function change_pending_orders_to_failed_func()
{
    $start_time = sa_get_option('order_failed_time', 9);
    $end_time = sa_get_option('status_change_end_time', 22);
    $current_time = current_time('H');

    if ($current_time >= $start_time && $current_time <= $end_time) {
        $args = array(
            'status' => 'pending',
            'date_created' => '<' . (time() - get_option('pending_to_failed_time', 4) * HOUR_IN_SECONDS),
            'limit' => -1,
        );

        $orders = wc_get_orders($args);

        foreach ($orders as $order) {
            $user_id = $order->get_user_id();
            if (! $user_id) {
                // مهمان‌ها را مستقیم failed کن
                $order->update_status('failed', 'Guest order auto-failed after timeout.');
                continue;
            }

            $product_ids_in_order = array();
            foreach ($order->get_items() as $item) {
                $product_ids_in_order[] = $item->get_product_id();
            }

            // پیدا کردن سفارشات جدیدتر همین کاربر با وضعیت جدیدتر (مثلاً processing یا completed یا حتی pending جدیدتر)
            $customer_orders = wc_get_orders(array(
                'customer_id' => $user_id,
                'status' => array('pending', 'processing', 'completed'),
                'limit' => -1,
                'orderby' => 'date',
                'order' => 'DESC',
            ));

            $newer_similar_order_exists = false;
            foreach ($customer_orders as $co) {
                if ($co->get_id() == $order->get_id()) continue; // همین سفارش رو بررسی نکن

                if ($co->get_date_created() <= $order->get_date_created()) continue; // فقط سفارش‌های جدیدتر

                foreach ($co->get_items() as $item) {
                    if (in_array($item->get_product_id(), $product_ids_in_order)) {
                        $newer_similar_order_exists = true;
                        break 2;
                    }
                }
            }

            if ($newer_similar_order_exists) {
                $order->update_status('cancelled', 'Order cancelled because user placed a newer similar order.');
            } else {
                $order->update_status('failed', 'Order auto-failed after configured time.');
            }
        }
    }
}

// تغییر وضعیت سفارشات به "لغو شده" پس از مدت زمان تنظیم شده در صورت "نا موفق"
function change_failed_orders_to_cancelled_func()
{
    $args = array(
        'status' => 'failed',
        'date_created' => '<' . (time() - get_option('failed_to_cancelled_time', 24) * HOUR_IN_SECONDS),
        'limit' => -1,
    );

    $orders = wc_get_orders($args);
    foreach ($orders as $order) {
        $order->update_status('cancelled', 'Order status changed to cancelled after configured time.');
    }
}

function render_free_shipping_progress()
{
    if (! class_exists('WC_Cart') || ! WC()->cart) {
        return;
    }

    $cart_total = WC()->cart->get_cart_contents_total() + WC()->cart->get_cart_contents_tax();

    $min_mashhad = intval(sa_get_option('free_shipping_min_mashhad', 0));
    $min_others  = intval(sa_get_option('free_shipping_min_other_cities', 0));

    if ($min_mashhad <= 0 && $min_others <= 0) {
        return;
    }

    $progress_mashhad = ($min_mashhad > 0) ? min(100, ($cart_total / $min_mashhad) * 100) : 0;
    $progress_others  = ($min_others > 0) ? min(100, ($cart_total / $min_others) * 100) : 0;

    $remain_mashhad = max(0, $min_mashhad - $cart_total);
    $remain_others  = max(0, $min_others - $cart_total);

    $shipping_is_free = ($cart_total >= $min_mashhad && $cart_total >= $min_others);
?>

    <div class="safe-assistant-free-shipping-progress <?= ($shipping_is_free) ? esc_attr('success') : ''; ?>">
        <?php if ($shipping_is_free): ?>
            <?= "ارسال رایگان در کل کشور فعال شد."; ?>
        <?php else: ?>
            <div class="progress-label">
                <?= ($cart_total >= $min_mashhad) ?  "ارسال رایگان در مشهد فعال شد." : $remain_mashhad . " تومان دیگر تا ارسال رایگان در مشهد برای شما"; ?>
            </div>
            <div class="progress-box">
                <div class="progress-fill" style="width:<?php echo esc_attr($progress_mashhad); ?>%; background: #10b939;"></div>
            </div>

            <!-- نوار پیشرفت کل کشور -->
            <div class="progress-label">
                <?= ($cart_total >= $min_others) ?  "ارسال رایگان در کل کشور فعال شد." : $remain_others . " تومان دیگر تا ارسال رایگان در کل کشور برای شما"; ?>
            </div>
            <div class="progress-box">
                <div class="progress-fill" style="width:<?php echo esc_attr($progress_others); ?>%; background: #565656;"></div>
            </div>

            <!-- پیام وضعیت -->
            <?php if ($cart_total < $min_mashhad): ?>
                <div class="progress-labels">
                    <span style="color: #005c16;">حداقل خرید برای ارسال رایگان به مشهد:
                        <?php echo number_format($min_mashhad); ?>
                        تومان
                    </span>
                </div>
            <?php endif ?>
            <?php if ($cart_total < $min_others): ?>
                <div class="progress-labels">
                    <span style="color: #343434;">حداقل خرید برای ارسال رایگان به کل کشور:
                        <?php echo number_format($min_others); ?>
                        تومان
                    </span>
                </div>
            <?php endif ?>
        <?php endif ?>
    </div>
<?php
}

/**
 * Billing Checkout Fields
   billing_first_name
   billing_last_name
   billing_company
   billing_address_1
   billing_address_2
   billing_city
   billing_postcode
   billing_country
   billing_state
   billing_email
   billing_phone
 * Shipping Checkout Fields
   shipping_first_name
   shipping_last_name
   shipping_company
   shipping_address_1
   shipping_address_2
   shipping_city
   shipping_postcode
   shipping_country
   shipping_state
 * Account Checkout Fields
   account_username
   account_password
   account_password-2
 * Order Checkout Fields
   order_comments
 */

function elite_remove_woo_checkout_fields($fields)
{
    unset($fields['billing']['billing_email']);
    return $fields;
}

// Fix: ob_end_flush error
remove_action("shutdown", "wp_ob_end_flush_all", 1);
add_action("shutdown", function () {
    while (@ob_end_flush());
});
