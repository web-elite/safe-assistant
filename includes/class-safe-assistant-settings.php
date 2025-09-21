<?php

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Safe_Assistant
 * @subpackage Safe_Assistant/includes
 * @author     AlirezaYaghouti <webelitee@gmail.com>
 */
class Safe_Assistant_Settings
{
	private static $instance = null;

	/**
	 * Database table name for settings
	 *
	 * @var string
	 */
	private $table_name;

	/**
	 * prefix for setting fields
	 *
	 * @var string
	 */
	private $prefix = SAFE_ASSISTANT_SLUG . '-settings';

	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct()
	{
		global $wpdb;
		$this->table_name = $wpdb->prefix . str_replace('-', '_', SAFE_ASSISTANT_SLUG) . '_settings';
	}

	public static function instance()
	{
		if (self::$instance === null) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	public function init()
	{
		$this->create_options();
		$this->handle();
		$this->create_last_options();
	}

	/**
	 * handle_settings
	 * 
	 * handle 
	 *
	 * @return void
	 */
	private function handle()
	{
		include_once SAFE_ASSISTANT_DIR . 'includes/class-safe-assistant-handler.php';
		if (sa_get_option('user_importer_addons')) {
			require_once SAFE_ASSISTANT_DIR . 'addons/user-importer/addon-user-importer.php';
			$user_importer = new Addon_User_Importer();
			$user_importer->activator();
		}
	}

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	private function create_options()
	{
		CSF::createOptions($this->prefix, array(
			'menu_title'              => esc_html__('Safe Assistant', 'safe-assistant'),
			'menu_slug'               => SAFE_ASSISTANT_SLUG . '-menu',
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
			'footer_text'             => '',
			'footer_text_direction'   => is_rtl() ? 'rtl' : 'ltr',
			'database'                => 'options',
			'contextual_help_sidebar' => '',
			'enqueue_webfont'         => false,
			'async_webfont'           => false,
			'output_css'              => true,
		));

		$sections = $this->get_sections();

		foreach ($sections as $key => $value) {
			CSF::createSection($this->prefix, $value);
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
				'id'     => 'wordpress',
				'title'  => esc_html__('WordPress', 'safe-assistant'),
				'icon'   => 'fab fa-wordpress',
			],
			[
				'id'     => 'woocommerce',
				'title'  => esc_html__('WooCommerce', 'safe-assistant'),
				'icon'   => 'fas fa-shopping-cart',
			],
			[
				'id'     => 'wallet',
				'title'  => esc_html__('Wallet', 'safe-assistant'),
				'icon'   => 'fas fa-wallet',
			],
			[
				'parent' => 'wordpress',
				'id'     => 'feature',
				'title'  => esc_html__('Features', 'safe-assistant'),
				'icon'   => 'fas fa-star',
				'fields' => [
					[
						'id'      => 'wp_custom_maintenance_status',
						'type'    => 'switcher',
						'title'   => esc_html__('Active custom maintenance', 'safe-assistant'),
						'default' => false,
						'desc'    => esc_html__('Enable a custom maintenance mode for your site.', 'safe-assistant'),
					],
					[
						'id'       => 'wp_custom_maintenance',
						'type'     => 'code_editor',
						'title'    => 'HTML maintenance content',
						'settings' => [
							'theme'  => 'monokai',
							'mode'   => 'htmlmixed',
						],
						'default'  => '<h1>Site Updating ...</h1>',
						'dependency' => ['wp_custom_maintenance_status', '==', 'true']
					],
				]
			],
			[
				'parent' => 'wordpress',
				'id'     => 'optimization',
				'title'  => esc_html__('Optimization', 'safe-assistant'),
				'icon'   => 'fas fa-cogs',
				'fields' => [
					[
						'id'      => 'disable_woodmart_patch_checker',
						'type'    => 'switcher',
						'title'   => esc_html__('Disable Woodmart Patch Checker', 'safe-assistant'),
						'default' => false,
						'desc'    => esc_html__('Disable Woodmart patch checker on all pages except the Woodmart patch page.', 'safe-assistant'),
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
				],
			],
			[
				'parent' => 'woocommerce',
				'id'     => 'free_shipping',
				'title'  => esc_html__('Free Shipping Settings', 'safe-assistant'),
				'icon'   => 'fas fa-shipping-fast',
				'fields' => [
					[
						'id'      => 'free_shipping_status',
						'type'    => 'switcher',
						'title'   => esc_html__('Enable Free Shipping', 'safe-assistant'),
						'default' => false,
						'desc'    => esc_html__('Enable or disable free shipping for your store.', 'safe-assistant'),
					],
					[
						'id'      => 'free_shipping_min_mashhad',
						'type'    => 'number',
						'attributes' => [
							'min'  => 0,
							'step' => 1000,
						],
						'unit'    => esc_html__('Toman', 'safe-assistant'),
						'title'   => esc_html__('Minimum Purchase (Mashhad)', 'safe-assistant'),
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
						'unit'    => esc_html__('Toman', 'safe-assistant'),
						'title'   => esc_html__('Minimum Purchase (Other Cities)', 'safe-assistant'),
						'default' => 1500000,
						'desc'    => esc_html__('Minimum order amount for free shipping in other cities.', 'safe-assistant'),
					],
				]
			],
			[
				'parent' => 'woocommerce',
				'id'     => 'vpn_checker',
				'title'  => esc_html__('VPN Checker Settings', 'safe-assistant'),
				'icon'   => 'fas fa-shield-alt',
				'fields' => [
					[
						'id'      => 'vpn_checker_status',
						'type'    => 'switcher',
						'title'   => esc_html__('Enable VPN Checker', 'safe-assistant'),
						'default' => false,
						'desc'    => esc_html__('Enable or disable VPN checking for users in checkout page.', 'safe-assistant'),
					],
					[
						'id'      => 'vpn_checker_only_in_checkout',
						'type'    => 'switcher',
						'title'   => esc_html__('Enable VPN Checker Only in Checkout', 'safe-assistant'),
						'default' => true,
						'desc'    => esc_html__('Enable or disable VPN checking for users only in the checkout page.', 'safe-assistant'),
					],
					[
						'id'      => 'vpn_checker_token',
						'type'    => 'text',
						'title'   => esc_html__('VPN Checker Token', 'safe-assistant'),
						'desc'    => esc_html__('Enter your VPN Checker API token. get it from', 'safe-assistant') . ' <a href="https://ipinfo.io/dashboard/token" target="_blank">ipinfo.io/dashboard/token</a>',
					],
					[
						'id'         => 'vpn_checker_type',
						'type'       => 'switcher',
						'title'      => esc_html__('VPN Checker Type', 'safe-assistant'),
						'default'    => false,
						'text_on'    => esc_html__('Check All IPs Not in Iran', 'safe-assistant'),
						'text_off'   => esc_html__('Check Only VPN service', 'safe-assistant'),
						'text_width' => 250,
						'desc'       => esc_html__('Switch on to check only VPN service or switch to off to check all IPs not in Iran', 'safe-assistant') . '<br>' . esc_html__('Note: if you select to check all IPs not in Iran, users from other countries will be affected.', 'safe-assistant'),
					],
					[
						'id'      => 'vpn_checker_title',
						'type'    => 'text',
						'title'   => esc_html__('VPN Checker Title', 'safe-assistant'),
						'default' => esc_html__('VPN Detected', 'safe-assistant'),
						'desc'    => esc_html__('Title to display when VPN is detected.', 'safe-assistant'),
					],
					[
						'id'      => 'vpn_checker_message',
						'type'    => 'textarea',
						'title'   => esc_html__('VPN Checker Message', 'safe-assistant'),
						'default' => esc_html__('You are using a VPN. Please disable it to payment success.', 'safe-assistant'),
						'desc'    => esc_html__('Message to display when VPN is detected.', 'safe-assistant'),
					]
				],
			],
			[
				'parent' => 'woocommerce',
				'id'     => 'automatic_membership',
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
						'title'   => esc_html__('Hide Membership Option on Checkout', 'safe-assistant'),
						'default' => false,
						'desc'    => esc_html__('Hide the membership option on the checkout page.', 'safe-assistant'),
					],
				],
			],
			[
				'parent' => 'woocommerce',
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
			],
			[
				'parent' => 'woocommerce',
				'id'     => 'woocommerce_admin',
				'title'  => esc_html__('WooCommerce Admin Settings', 'safe-assistant'),
				'icon'   => 'fas fa-cog',
				'fields' => [
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
				],
			],
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
				],
			],
			[
				'parent' => 'wallet',
				'id'     => 'nir_wallet',
				'title'  => esc_html__('Nir Wallet', 'safe-assistant'),
				'icon'   => 'fas fa-wallet',
				'fields' => defined('nirweb_wallet') ? [
					[
						'id'          => 'nir_wallet_expire_day_sms',
						'type'        => 'select',
						'title'       => esc_html__('Wallet expiration SMS', 'safe-assistant'),
						'chosen'      => true,
						'multiple'    => true,
						'placeholder' => esc_html__('Select an option', 'safe-assistant'),
						'desc'        => esc_html__('Choose how many days before the user\'s wallet expiration date the SMS will be sent.', 'safe-assistant'),
						'options'     => [
							'24'   => __('1 Day', 'safe-assistant'),
							'48'   => __('2 Days', 'safe-assistant'),
							'72'   => __('3 Days', 'safe-assistant'),
							'96'   => __('4 Days', 'safe-assistant'),
							'120'  => __('5 Days', 'safe-assistant'),
							'144'  => __('6 Days', 'safe-assistant'),
							'168'  => __('7 Days', 'safe-assistant'),
						],
						'default'     => ['24'],
					],
					[
						'id'         => 'nir_wallet_expire_check_by',
						'type'       => 'switcher',
						'title'      => esc_html__('Check Wallet Expiration By', 'safe-assistant'),
						'default'    => true,
						'desc'       => esc_html__('Choose whether to check wallet expiration by days or hours.', 'safe-assistant'),
						'text_on'    => __('Days', 'safe-assistant'),
						'text_off'   => __('Hours', 'safe-assistant'),
						'text_width' => 150,
					],
					[
						'id'      => 'nir_wallet_expire_pattern_sms_hour',
						'type'    => 'text',
						'title'   => esc_html__('SMS Pattern', 'safe-assistant'),
						'desc'    => esc_html__('Enter the SMS pattern for wallet expiration notifications by hour.', 'safe-assistant') . '<br>' . esc_html__('Available placeholders:', 'safe-assistant') . ' {first_name}, {remain_hours}',
						'dependency' => ['nir_wallet_expire_check_by', '==', 'false'],
					],
					[
						'id'      => 'nir_wallet_expire_pattern_sms',
						'type'    => 'text',
						'title'   => esc_html__('SMS Pattern', 'safe-assistant'),
						'desc'    => esc_html__('Enter the SMS pattern for wallet expiration notifications by days.', 'safe-assistant') . '<br>' . esc_html__('Available placeholders:', 'safe-assistant') . ' {first_name}, {remain_days}',
						'dependency' => ['nir_wallet_expire_check_by', '==', 'true'],
					],
					[
						'id'      => 'nir_wallet_expire_last_pattern_sms',
						'type'    => 'text',
						'title'   => esc_html__('SMS Pattern (for last 24h)', 'safe-assistant'),
						'desc'    => esc_html__('Enter the SMS pattern for wallet expiration notifications less than 24 hours.', 'safe-assistant') . '<br>' . esc_html__('Available placeholders:', 'safe-assistant') . ' {first_name}',
						'dependency' => ['nir_wallet_expire_check_by', '==', 'true'],
					],
					[
						'id'      => 'nir_wallet_expire_send_time',
						'type'    => 'number',
						'title'   => esc_html__('Enter the expiration date check time and the time of day the SMS is sent.', 'safe-assistant'),
						'desc'    => esc_html__('In 24-hour format. for example: 9 or 17', 'safe-assistant'),
						'default' => 9,
						'attributes' => [
							'min' => 0,
							'max' => 24,
							'step' => 1,
						],
					],
				] : [
					[
						'type'    => 'notice',
						'style'   => 'warning',
						'content' => esc_html__('Nir Wallet plugin not active or installed', 'safe-assistant'),
					],
				],
			],
		];
		return $sections;
	}

	public function render_logs()
	{
		sa_display_logs('general');
	}

	private function create_last_options()
	{
		CSF::createSection($this->prefix, [
			'title'  => esc_html__('Logs', 'safe-assistant'),
			'icon'   => 'fas fa-file-alt',
			'fields' => [
				[
					'type'     => 'fallback',
					'function'  => [$this, 'render_logs'],
				],
			]
		]);
		CSF::createSection($this->prefix, [
			'title'  => esc_html__('Settings', 'safe-assistant'),
			'icon'   => 'fas fa-cog',
			'fields' => [
				[
					'type'     => 'subheading',
					'content'  => __('SMS Panel Settings', 'safe-assistant'),
				],
				[
					'id'       => 'sms_gateway',
					'type'     => 'select',
					'title'    => __('SMS Gateway', 'safe-assistant'),
					'options'  => [
						'melipayamak' => 'MeliPayamak.Com',
					],
					'default'  => 'melipayamak',
				],
				[
					'id'       => 'sms_username',
					'type'     => 'text',
					'title'    => __('Username', 'safe-assistant'),
				],
				[
					'id'       => 'sms_password',
					'type'     => 'text',
					'title'    => __('Password', 'safe-assistant'),
					'attributes' => [
						'type' => 'password',
					],
				],
				[
					'id'       => 'sms_from_number',
					'type'     => 'text',
					'title'    => __('Sms sender number', 'safe-assistant'),
				]
			]
		]);

		CSF::createSection($this->prefix, [
			'title'  => esc_html__('Backup & Restore', 'safe-assistant'),
			'icon'   => 'fas fa-save',
			'fields' => [
				[
					'type' => 'backup',
				]
			]
		]);
	}
}
