<?php

/**
 * The main class for order toolkit add-on
 *
 * @package ADDON_ORDER_TOOLKIT
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

class Addon_Order_Toolkit
{
    /**
     * __construct
     * 
     * Instantiate the class
     *
     * @return void
     */
    function __construct()
    {
        $this->set_variables();
        $this->load_dependencies();
        $this->create_setting();
    }

    /**
     * load_dependencies
     *
     * load dependencies for this class
     * 
     * @return void
     */
    private function load_dependencies()
    {
        require_once ADDON_ORDER_TOOLKIT_DIR . ADDON_ORDER_TOOLKIT_SLUG . '-handler.php';
    }

    /**
     * set_variables
     *
     * set variables for this class
     * 
     * @return void
     */
    public function set_variables()
    {
        define('ADDON_ORDER_TOOLKIT_NAME', 'order_toolkit');
        define('ADDON_ORDER_TOOLKIT_SLUG', 'order-toolkit');
        define('ADDON_ORDER_TOOLKIT_DIR', plugin_dir_path(__FILE__));
        define('ADDON_ORDER_TOOLKIT_URL', plugin_dir_url(__FILE__));
    }

    /**
     * activator
     * 
     * When plugin is activated, this function will be called.
     *
     * @return void
     */
    public function activator() {}

    /**
     * deactivator
     *
     * When plugin is deactivated, this function will be called.
     * 
     * @return void
     */
    public function deactivator() {}

    /**
     * create_setting
     * 
     * create setting for this plugin
     *
     * @return void
     */
    public function create_setting()
    {
        CSF::createSection(SAFE_ASSISTANT_SETTING_ID, [
            'id'     => 'order_toolkit_addons',
            'title'  => __('Order Toolkit', 'safe-assistant'),
            'icon'   => 'fas fa-shopping-cart',
        ]);

        // Main Section
        CSF::createSection(SAFE_ASSISTANT_SETTING_ID, [
            'parent' => 'order_toolkit_addons',
            'id'     => 'order_management_pro',
            'title'  => esc_html__('Orders Management Pro', 'safe-assistant'),
            'icon'   => 'fas fa-clipboard-list',
            'fields' => [
                [
                    'id'      => 'order_management_pro_status',
                    'type'    => 'switcher',
                    'default' => false,
                    'title'   => esc_html__('Enable Post Tracking', 'safe-assistant'),
                    'desc'    => esc_html__('Enable post tracking for orders.', 'safe-assistant'),
                ],
                [
                    'id'      => 'order_management_pro_main_city',
                    'type'    => 'text',
                    'title'   => esc_html__('Your shop location', 'safe-assistant'),
                    'desc'    => esc_html__('Enter the original value of the city field on the checkout page here. For example, Mashhad', 'safe-assistant'),
                ],
                [
                    'id'      => 'order_management_pro_sms_status',
                    'type'    => 'switcher',
                    'title'   => esc_html__('Enable SMS Notifications', 'safe-assistant'),
                    'default' => true,
                    'desc'    => esc_html__('Enable automatic sending of SMS after order completion with "Post tracking code".', 'safe-assistant'),
                ],
                [
                    'id'      => 'order_management_pro_sms_pattern',
                    'type'    => 'text',
                    'title'   => esc_html__('Sms pattern for after order completion', 'safe-assistant'),
                    'default' => true,
                    'desc'    => esc_html__('Sms pattern must includes below parameters:', 'safe-assistant') . '<br>'
                        . __('first parameter is name of user', 'safe-assistant') . '<code>name</code>' . '<br>'
                        . __('second parameter is tracking code', 'safe-assistant') . '<code>code</code>' . '<br>',
                ],
            ],
        ]);

        CSF::createSection(SAFE_ASSISTANT_SETTING_ID, [
            'parent' => 'order_toolkit_addons',
            'id'     => 'order_management',
            'title'  => esc_html__('Order Management', 'safe-assistant'),
            'icon'   => 'fas fa-clipboard-list',
            'fields' => [
                [
                    'id'      => 'show_order_notes_in_admin_table',
                    'type'    => 'switcher',
                    'title'   => esc_html__('Display order notes in the orders table', 'safe-assistant'),
                    'default' => false,
                    'desc'    => esc_html__('Enable displaying order notes in the admin orders table.', 'safe-assistant'),
                ],
                [
                    'id'      => 'order_convertor_status',
                    'type'    => 'switcher',
                    'title'   => esc_html__('Convert Pending Orders to Failed', 'safe-assistant'),
                    'default' => true,
                    'desc'    => esc_html__('Enable automatic conversion of pending orders to failed status.', 'safe-assistant'),
                ],
                [
                    'id'      => 'order_to_fail_pending_time',
                    'type'    => 'number',
                    'title'   => esc_html__('Time to Mark Order as Failed (Hours)', 'safe-assistant'),
                    'default' => 1,
                    'desc'    => esc_html__('Set how many hours after creation a pending order should be marked as failed.', 'safe-assistant'),
                ],
                [
                    'id'      => 'order_to_canceled_pending_time',
                    'type'    => 'number',
                    'title'   => esc_html__('Time to Mark Order as Cancelled (Hours)', 'safe-assistant'),
                    'default' => 36,
                    'desc'    => esc_html__('Set how many hours after creation a pending order should be marked as cancelled.', 'safe-assistant'),
                ],
                [
                    'id'      => 'order_convertor_start_time',
                    'type'    => 'number',
                    'title'   => esc_html__('Start Time for Failed Status Check (Hour)', 'safe-assistant'),
                    'default' => 8,
                    'desc'    => esc_html__('Set the hour (24h format) when the system starts checking for orders to mark as failed.', 'safe-assistant'),
                ],
                [
                    'id'      => 'order_convertor_end_time',
                    'type'    => 'number',
                    'title'   => esc_html__('End Time for Failed Status Check (Hour)', 'safe-assistant'),
                    'default' => 16,
                    'desc'    => esc_html__('Set the hour (24h format) when the system stops checking for orders to mark as failed.', 'safe-assistant'),
                ],
            ],
        ]);
    }
}
