<?php

/**
 * Fired during plugin activation
 *
 * @link       https://webelitee.ir
 * @since      1.0.0
 *
 * @package    Safe_Assistant
 * @subpackage Safe_Assistant/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Safe_Assistant
 * @subpackage Safe_Assistant/includes
 * @author     𝐀𝐥𝐢𝐫𝐞𝐳𝐚𝐘𝐚𝐠𝐡𝐨𝐮𝐭𝐢 <webelitee@gmail.com>
 */
class Safe_Assistant_Settings
{

	/**
	 * handle_settings
	 * 
	 * handle 
	 *
	 * @return void
	 */
	public function handle()
	{
		if (sa_get_option('user_importer_addons')) {
			require_once SAFE_ASSISTANT_DIR . 'addons/user-importer/addon-user-importer-main.php';
			new Addon_User_Importer_Main();
		}
	}

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public function init()
	{
		$prefix = SAFE_ASSISTANT_SLUG . '-settings';
		CSF::createOptions($prefix, array(
			'menu_title' => esc_html__('Safe Assistant', 'safe-assistant'),
			'menu_slug'  => SAFE_ASSISTANT_SLUG . '-menu',
			'framework_title'         => esc_html__('Safe Assistant', 'safe-assistant'),
			'framework_class'         => '',
			'menu_icon'               => SAFE_ASSISTANT_URL . 'admin/img/menu-icon.webp',
			'menu_position'           => 99,
			'show_bar_menu'           => true,
			'show_sub_menu'           => true,
			'show_in_network'         => true,
			'show_in_customizer'      => false,
			'show_search'             => true,
			'show_reset_all'          => true,
			'show_reset_section'      => true,
			'show_footer'             => true,
			'show_form_warning'       => true,
			'sticky_header'           => true,
			'save_defaults'           => true,
			'ajax_save'               => true,
			'admin_bar_menu_priority' => 50,
			'footer_text' => sprintf(
				esc_html__('%1$s by <a href="https://webelitee.ir" target="_blank" style="color:#555; text-decoration:unset !important;">𝐀𝐥𝐢𝐫𝐞𝐳𝐚𝐘𝐚𝐠𝐡𝐨𝐮𝐭𝐢</a> with %2$s', 'safe-assistant'),
				esc_html__('Created', 'safe-assistant'),
				'<span style="color: #e25555;" title="' . esc_html__('Love', 'safe-assistant') . '">❤</span>'
			),
			'footer_text_direction' => is_rtl() ? 'rtl' : 'ltr',
			'database'                => 'options',
			'contextual_help_sidebar' => '',
			'enqueue_webfont'         => false,
			'async_webfont'           => false,
			'output_css'              => true,
		));

		$sections = $this->get_sections();

		foreach ($sections as $key => $value) {
			CSF::createSection($prefix, $value);
		}
	}

	/**
	 * get_sections
	 *
	 * @return array
	 */
	private function get_sections(): array
	{

		$sections = [
			[
				'id'     => 'addons',
				'title'  => esc_html__('Addons', 'safe-assistant'),
				'icon'   => 'fas fa-plug',
				'fields' => [
					[
						'id'      => 'user_importer_addons',
						'type'    => 'switcher',
						'title'   => esc_html__('Enable User Importer Addon', 'safe-assistant'),
						'default' => false,
						'desc'    => esc_html__('Enable or disable User Importer Addon.', 'safe-assistant'),
					],
				]
			],
			[
				'title'  => esc_html__('Optimization', 'safe-assistant'),
				'icon'   => 'fas fa-cogs',
				'fields' => [
					[
						'id' => 'disbale_woodmart_patch_cheker',
						'type' => 'swticher',
						'title'   => esc_html__('Disable Woodmart Patch Checker', 'safe-assistant'),
						'default' => false,
						'desc'    => esc_html__('disbale woodmart patch cheker in all pages exclude woodmart patch page', 'safe-assistant'),
					],
					[
						'id'      => 'disable_admin_bar',
						'type'    => 'switcher',
						'title'   => esc_html__('Disable Admin Bar', 'safe-assistant'),
						'default' => false,
						'desc'    => esc_html__('Hide the WordPress admin bar for all users except administrators.', 'safe-assistant'),
					],
					[
						'id'      => 'remove_wp_logo_admin_bar',
						'type'    => 'switcher',
						'title'   => esc_html__('Remove WordPress Logo from Admin Bar', 'safe-assistant'),
						'default' => false,
						'desc'    => esc_html__('Remove the WordPress logo from the top admin bar.', 'safe-assistant'),
					],
					[
						'id'      => 'remove_dashboard_widgets',
						'type'    => 'switcher',
						'title'   => esc_html__('Remove Dashboard Widgets', 'safe-assistant'),
						'default' => false,
						'desc'    => esc_html__('Remove default widgets from the WordPress dashboard.', 'safe-assistant'),
					],
					[
						'id'      => 'disable_wp_updates',
						'type'    => 'switcher',
						'title'   => esc_html__('Disable All WordPress Updates', 'safe-assistant'),
						'default' => false,
						'desc'    => esc_html__('Disable core, plugin, and theme updates.', 'safe-assistant'),
					],
					[
						'id'      => 'disable_gutenberg',
						'type'    => 'switcher',
						'title'   => esc_html__('Disable Gutenberg Editor', 'safe-assistant'),
						'default' => false,
						'desc'    => esc_html__('Disable the Gutenberg block editor and use Classic Editor.', 'safe-assistant'),
					],
					[
						'id'      => 'disable_wc_admin',
						'type'    => 'switcher',
						'title'   => esc_html__('Disable WooCommerce Admin', 'safe-assistant'),
						'default' => false,
						'desc'    => esc_html__('Disable the WooCommerce Admin dashboard.', 'safe-assistant'),
					],
					[
						'id'      => 'disable_wc_marketing_hub',
						'type'    => 'switcher',
						'title'   => esc_html__('Disable WooCommerce Marketing Hub', 'safe-assistant'),
						'default' => false,
						'desc'    => esc_html__('Disable the Marketing Hub in WooCommerce.', 'safe-assistant'),
					],
					[
						'id'      => 'disable_wc_blocks_frontend',
						'type'    => 'switcher',
						'title'   => esc_html__('Disable WooCommerce Blocks on Frontend', 'safe-assistant'),
						'default' => false,
						'desc'    => esc_html__('Disable WooCommerce Blocks scripts/styles on frontend.', 'safe-assistant'),
					],
				]
			],
			[
				'title'  => esc_html__('Tools', 'safe-assistant'),
				'icon'   => 'fas fa-tools',
				'fields' => []
			],
			[
				'title'  => esc_html__('Free Shipping Settings', 'safe-assistant'),
				'icon'   => 'fas fa-shipping-fast',
				'fields' => [
					[
						'id'      => 'free_shipping_min_mashhad',
						'type'    => 'number',
						'attributes' => [
							'min'  => 0,
							'step' => 1000,
						],
						'unit'        => esc_html__('Toman', 'safe-assistant'),
						'title'   => esc_html__('Minimum Purchase', 'safe-assistant'),
						'default' => 1000000,
						'desc'    => esc_html__('Minimum order amount for free shipping in Mashhad.', 'safe-assistant'),
					],
					[
						'id'      => 'free_shipping_min_other_cities',
						'type'    => 'number',
						'attributes' => [
							'min'  => 0,
							'step' => 1000,
						],
						'unit'        => esc_html__('Toman', 'safe-assistant'),
						'title'   => esc_html__('Minimum Purchase', 'safe-assistant'),
						'default' => 1500000,
						'desc'    => esc_html__('Minimum order amount for free shipping in other cities.', 'safe-assistant'),
					],
				]
			],
			[
				'title'  => esc_html__('Automatic Membership Settings', 'safe-assistant'),
				'icon'   => 'fas fa-user-plus',
				'fields' => [
					[
						'id'      => 'enable_auto_membership',
						'type'    => 'switcher',
						'title'   => esc_html__('Enable Automatic Membership', 'safe-assistant'),
						'default' => false,
						'desc'    => esc_html__('Enable or disable automatic user membership on checkout.', 'safe-assistant'),
					],
					[
						'id'      => 'hide_membership_option_checkout',
						'type'    => 'switcher',
						'title'   => esc_html__('Hide Membership Option on Checkout Page', 'safe-assistant'),
						'default' => false,
						'desc'    => esc_html__('Hide the membership option on the checkout page.', 'safe-assistant'),
					],
				]
			],
			[
				'title'  => esc_html__('Order Management', 'safe-assistant'),
				'icon'   => 'fas fa-clipboard-list',
				'fields' => [
					[
						'id'      => 'show_order_notes_in_admin_table',
						'type'    => 'switcher',
						'title'   => esc_html__('Show Order Notes in Admin Orders Table', 'safe-assistant'),
						'default' => false,
						'desc'    => esc_html__('Enable displaying order notes in the admin orders table.', 'safe-assistant'),
					],
					[
						'id'      => 'order_convertor_status',
						'type'    => 'switcher',
						'title'   => esc_html__('Convert pending orders to fail order', 'safe-assistant'),
						'default' => true,
						'desc'    => esc_html__('Enable automatic conversion of pending orders to failed status', 'safe-assistant'),
					],
					[
						'id'      => 'order_to_fail_pending_time',
						'type'    => 'number',
						'title'   => esc_html__('Time to set order as Failed (hours)', 'safe-assistant'),
						'default' => 1,
						'desc'    => esc_html__('Set how many hours after creation a pending order should be marked as Failed', 'safe-assistant'),
					],
					[
						'id'      => 'order_to_canceled_pending_time',
						'type'    => 'number',
						'title'   => esc_html__('Time to set order as Cancelled (hours)', 'safe-assistant'),
						'default' => 36,
						'desc'    => esc_html__('Set how many hours after creation a pending order should be marked as Cancelled', 'safe-assistant'),
					],
					[
						'id'      => 'order_convertor_start_time',
						'type'    => 'number',
						'title'   => esc_html__('Start time for Failed status check (hour)', 'safe-assistant'),
						'default' => 8,
						'desc'    => esc_html__('Set the hour (24h format) when the system should start checking for orders to mark as Failed', 'safe-assistant'),
					],
					[
						'id'      => 'order_convertor_end_time',
						'type'    => 'number',
						'title'   => esc_html__('End time for Failed status check (hour)', 'safe-assistant'),
						'default' => 16,
						'desc'    => esc_html__('Set the hour (24h format) when the system should stop checking for orders to mark as Failed', 'safe-assistant'),
					],
				]
			],
			[
				'title'  => esc_html__('Backup & Restore', 'safe-assistant'),
				'icon'   => 'fas fa-save',
				'fields' => [
					[
						'type' => 'backup',
					]
				]
			]
		];

		return $sections;
	}
}
