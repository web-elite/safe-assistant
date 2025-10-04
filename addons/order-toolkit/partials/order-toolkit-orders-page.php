<div class="wrap" id="safe-assistant-orders-page">
    <h1><?php _e('Orders Management Pro', 'safe-assistant'); ?></h1>
    <p><?php printf(__('Main City: %s | Orders from this city are managed directly.', 'safe-assistant'), esc_html($main_city)); ?></p>
    <?php

    $status_labels = [
        'pending'    => __('Pending Orders', 'safe-assistant'),
        'on-hold'    => __('On-Hold Orders', 'safe-assistant'),
        'failed'     => __('Failed Orders', 'safe-assistant'),
        'processing' => __('Processing Orders', 'safe-assistant'),
    ];

    foreach ($orders_by_status as $status => $groups):
        $status_key = str_replace('wc-', '', $status);
        $label = $status_labels[$status_key] ?? ucfirst($status_key);
        $total_orders = count($groups['main_city'] ?? []) + count($groups['other_city'] ?? []);
        if ($total_orders === 0) continue;
    ?>
        <div class="order-table-wrapper status-<?php echo esc_attr($status_key); ?>">
            <h2><?php echo sprintf(__('%s (%d orders)', 'safe-assistant'), $label, $total_orders); ?></h2>

            <?php foreach (['main_city' => __('Main City Orders', 'safe-assistant'), 'other_city' => __('Other Cities Orders', 'safe-assistant')] as $group_key => $group_label): ?>
                <?php if (!empty($groups[$group_key])): ?>
                    <h3><?php echo $group_label; ?> (<?php echo count($groups[$group_key]); ?>)</h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th width="5%"><?php _e('Order ID', 'safe-assistant'); ?></th>
                                <th width="7%"><?php _e('Customer Name', 'safe-assistant'); ?></th>
                                <th width="10%"><?php _e('City', 'safe-assistant'); ?></th>
                                <th width="25%"><?php _e('Full Address', 'safe-assistant'); ?></th>
                                <th width="7%"><?php _e('Phone', 'safe-assistant'); ?></th>
                                <th width="45%"><?php _e('Actions', 'safe-assistant'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($groups[$group_key] as $order): ?>
                                <tr>
                                    <td><?php echo $order->get_id(); ?></td>
                                    <td><?php echo esc_html($order->get_formatted_billing_full_name()); ?></td>
                                    <td><?php echo esc_html($order->get_shipping_city() ?: $order->get_billing_city()); ?></td>
                                    <td><?php echo esc_html(preg_replace('/<br\s*\/?>/i', ', ', $order->get_formatted_shipping_address())); ?></td>
                                    <td><?php echo esc_html($order->get_billing_phone()); ?></td>
                                    <td>
                                        <?php if ($status_key === 'failed'): ?>
                                            <span style="color:#cc0000;"><?php _e('Contact support for follow-up', 'safe-assistant'); ?></span>

                                        <?php elseif (($status_key === 'on-hold' || $status_key === 'processing') && $group_key === 'main_city'): ?>
                                            <button type="button" class="button complete-order" data-order-id="<?php echo $order->get_id(); ?>">
                                                <?php _e('Mark as Completed', 'safe-assistant'); ?>
                                            </button>

                                        <?php elseif (($status_key === 'on-hold' || $status_key === 'processing') && $group_key === 'other_city'): ?>
                                            <input type="text" class="tracking_code" id="tracking_<?php echo $order->get_id(); ?>" placeholder="<?php esc_attr_e('Enter tracking code', 'safe-assistant'); ?>" value="<?php echo esc_attr(get_post_meta($order->get_id(), '_tracking_code', true)); ?>">
                                            <button type="button" class="button save-tracking" data-order-id="<?php echo $order->get_id(); ?>">
                                                <?php _e('Save & Complete', 'safe-assistant'); ?>
                                            </button>
                                            <span class="status" style="margin-left:10px;"></span>

                                        <?php else: ?>
                                            <a href="<?php echo get_edit_post_link($order->get_id()); ?>" class="button"><?php _e('Edit Order', 'safe-assistant'); ?></a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>

    <?php if (empty($orders)): ?>
        <p><?php _e('No pending orders found.', 'safe-assistant'); ?></p>
    <?php endif; ?>
</div>

<script>
    jQuery(document).ready(function($) {
        $('.save-tracking').click(function() {
            var orderId = $(this).data('order-id');
            var trackingCode = $('#tracking_' + orderId).val();
            var btn = $(this);
            var statusSpan = btn.siblings('.status');

            if (!trackingCode) {
                alert('<?php echo esc_js(__('Tracking code is required.', 'safe-assistant')); ?>');
                return;
            }

            btn.prop('disabled', true).text('<?php echo esc_js(__('Saving...', 'safe-assistant')); ?>');

            $.post(ajaxurl, {
                action: 'save_order_tracking_ajax',
                order_id: orderId,
                tracking_code: trackingCode,
                nonce: '<?php echo wp_create_nonce('tracking_nonce'); ?>'
            }, function(response) {
                if (response.success) {
                    statusSpan.html('<span style="color:green;"><?php echo esc_js(__('Saved & Completed!', 'safe-assistant')); ?></span>');
                    btn.closest('tr').fadeOut();
                } else {
                    statusSpan.html('<span style="color:red;"><?php echo esc_js(__('Error: ', 'safe-assistant')); ?>' + response.data + '</span>');
                }
                btn.prop('disabled', false).text('<?php echo esc_js(__('Save & Order completion', 'safe-assistant')); ?>');
            });
        });
    });
</script>