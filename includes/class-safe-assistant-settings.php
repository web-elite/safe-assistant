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
			require_once SAFE_ASSISTANT_DIR . 'addons/user-importer/user-importer.php';
			$user_importer = new Addon_User_Importer();
			$user_importer->activator();
		}

		if (sa_get_option('social_order_management_addons')) {
			require_once SAFE_ASSISTANT_DIR . 'addons/social-order-manager/social-order-manager.php';
			$social_order_management = new Addon_Social_Order_Manager();
			$social_order_management->activator();
		}

		if (sa_get_option('order_toolkit_addons')) {
			require_once SAFE_ASSISTANT_DIR . 'addons/order-toolkit/order-toolkit.php';
			$order_toolkit = new Addon_Order_Toolkit();
			$order_toolkit->activator();
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
		CSF::createOptions(SAFE_ASSISTANT_SETTING_ID, array(
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
			CSF::createSection(SAFE_ASSISTANT_SETTING_ID, $value);
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
				'id'     => 'custom_code',
				'title'  => esc_html__('Custom Code', 'safe-assistant'),
				'icon'   => 'fas fa-code',
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
						'id'      => 'disable_all_wp_updates',
						'type'    => 'switcher',
						'title'   => esc_html__('Disable All WordPress Updates', 'safe-assistant'),
						'default' => false,
						'desc'    => "⚠️ " . esc_html__('Disable core, plugin, and theme updates.', 'safe-assistant'),
					],
					[
						'id'      => 'disable_auto_wp_updates',
						'type'    => 'switcher',
						'title'   => esc_html__('Disable Automatic WordPress Updates', 'safe-assistant'),
						'default' => false,
						'desc'    => esc_html__('Disable only automatic updates for WordPress core, plugins, and themes. Manual updates will still be available.', 'safe-assistant'),
					],
					[
						'id'      => 'disable_gutenberg',
						'type'    => 'switcher',
						'title'   => esc_html__('Disable Gutenberg Editor', 'safe-assistant'),
						'default' => false,
						'desc'    => esc_html__('Disable the Gutenberg block editor and use Classic Editor.', 'safe-assistant'),
					],
					[
						'id'      => 'block_external_requests',
						'type'    => 'textarea',
						'title'   => esc_html__('Block External Requests', 'safe-assistant'),
						'default' => 'https://my.elementor.com/api/v2/info',
						'desc'    => esc_html__('List external URLs to block (one per line).', 'safe-assistant'),
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
					[
						'id'      => 'free_shipping_activated_nationwide_text',
						'type'    => 'text',
						'title'   => esc_html__('Activated Nationwide Text', 'safe-assistant'),
						'default' => esc_html__('Free shipping activated nationwide.', 'safe-assistant'),
						'desc'    => esc_html__('Text to display when free shipping is activated nationwide.', 'safe-assistant'),
					],
					[
						'id'      => 'free_shipping_activated_mashhad_text',
						'type'    => 'text',
						'title'   => esc_html__('Activated Mashhad Text', 'safe-assistant'),
						'default' => esc_html__('Free shipping activated for Mashhad.', 'safe-assistant'),
						'desc'    => esc_html__('Text to display when free shipping is activated for Mashhad.', 'safe-assistant'),
					],
					[
						'id'      => 'free_shipping_remaining_mashhad_text',
						'type'    => 'text',
						'title'   => esc_html__('Remaining Mashhad Text', 'safe-assistant'),
						'default' => esc_html__('%s Toman left for free shipping in Mashhad.', 'safe-assistant'),
						'desc'    => esc_html__('Text to display remaining amount for free shipping in Mashhad. Use %s for the amount.', 'safe-assistant'),
					],
					[
						'id'      => 'free_shipping_remaining_other_text',
						'type'    => 'text',
						'title'   => esc_html__('Remaining Other Cities Text', 'safe-assistant'),
						'default' => esc_html__('%s Toman left for free shipping nationwide.', 'safe-assistant'),
						'desc'    => esc_html__('Text to display remaining amount for free shipping in other cities. Use %s for the amount.', 'safe-assistant'),
					],
					[
						'id'      => 'free_shipping_min_mashhad_text',
						'type'    => 'text',
						'title'   => esc_html__('Minimum Mashhad Text', 'safe-assistant'),
						'default' => esc_html__('Minimum purchase for free shipping in Mashhad: %s Toman', 'safe-assistant'),
						'desc'    => esc_html__('Text to display minimum purchase for free shipping in Mashhad. Use %s for the amount.', 'safe-assistant'),
					],
					[
						'id'      => 'free_shipping_min_other_text',
						'type'    => 'text',
						'title'   => esc_html__('Minimum Other Cities Text', 'safe-assistant'),
						'default' => esc_html__('Minimum purchase for free shipping nationwide: %s Toman', 'safe-assistant'),
						'desc'    => esc_html__('Text to display minimum purchase for free shipping in other cities. Use %s for the amount.', 'safe-assistant'),
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
					]
				],
			],
			[
				'parent' => 'woocommerce',
				'id'     => 'woocommerce_search_optimization',
				'title'  => esc_html__('WooCommerce Search Optimization', 'safe-assistant'),
				'icon'   => 'fas fa-search',
				'fields' => [
					[
						'id'      => 'improvements_in_product_admin_search_status',
						'type'    => 'switcher',
						'title'   => esc_html__('Improve Admin Product Search', 'safe-assistant'),
						'default' => false,
						'desc'    => esc_html__('Optimize admin product search to search only in product title, product ID, and category names (faster and more accurate results)', 'safe-assistant'),
					],
					[
						'id'      => 'improvements_in_order_admin_search_status',
						'type'    => 'switcher',
						'title'   => esc_html__('Improve Admin Order Search', 'safe-assistant'),
						'default' => false,
						'desc'    => esc_html__('Optimize admin order search to search only in order ID, customer name, customer email, customer username (faster and more accurate results)', 'safe-assistant'),
					],
					[
						'id'      => 'improvements_in_product_front_search_status',
						'type'    => 'switcher',
						'title'   => esc_html__('Improve Product Frontend Search', 'safe-assistant'),
						'default' => false,
						'desc'    => esc_html__('Optimize product search to search only in product title, product ID, and category names (faster and more accurate results)', 'safe-assistant'),
					],
				],
			],
			[
				'parent' => 'woocommerce',
				'id'     => 'woocommerce_checkout',
				'title'  => esc_html__('WooCommerce Checkout Settings', 'safe-assistant'),
				'icon'   => 'fas fa-dolly-flatbed',
				'fields' => [
					[
						'id'     => 'checkout_account_fields_accordion',
						'type'   => 'accordion',
						'title'  => esc_html__('Account Fields Editor', 'safe-assistant'),
						'accordions' => [
							[
								'title'  => esc_html__('Account Username', 'safe-assistant'),
								'fields' => [
									[
										'id'      => 'account_username_enabled',
										'type'    => 'switcher',
										'title'   => esc_html__('Enable Field', 'safe-assistant'),
										'default' => true,
									],
									[
										'id'      => 'account_username_required',
										'type'    => 'switcher',
										'title'   => esc_html__('Is Required?', 'safe-assistant'),
										'default' => true,
									],
									[
										'id'      => 'account_username_label',
										'type'    => 'text',
										'title'   => esc_html__('Field Label', 'safe-assistant'),
										'default' => esc_html__('First name', 'woocommerce'),
									],
									[
										'id'      => 'account_username_placeholder',
										'type'    => 'text',
										'title'   => esc_html__('Placeholder', 'safe-assistant'),
									],
									[
										'id'      => 'account_username_default',
										'type'    => 'text',
										'title'   => esc_html__('Default Value', 'safe-assistant'),
									],
								],
							],
							[
								'title'  => esc_html__('Account Password', 'safe-assistant'),
								'fields' => [
									[
										'id'      => 'account_password_enabled',
										'type'    => 'switcher',
										'title'   => esc_html__('Enable Field', 'safe-assistant'),
										'default' => true,
									],
									[
										'id'      => 'account_password_required',
										'type'    => 'switcher',
										'title'   => esc_html__('Is Required?', 'safe-assistant'),
										'default' => true,
									],
									[
										'id'      => 'account_password_label',
										'type'    => 'text',
										'title'   => esc_html__('Field Label', 'safe-assistant'),
										'default' => esc_html__('First name', 'woocommerce'),
									],
									[
										'id'      => 'account_password_placeholder',
										'type'    => 'text',
										'title'   => esc_html__('Placeholder', 'safe-assistant'),
									],
									[
										'id'      => 'account_password_default',
										'type'    => 'text',
										'title'   => esc_html__('Default Value', 'safe-assistant'),
									],
								],
							],
							[
								'title'  => esc_html__('Account Password Confirmation', 'safe-assistant'),
								'fields' => [
									[
										'id'      => 'account_password-2_enabled',
										'type'    => 'switcher',
										'title'   => esc_html__('Enable Field', 'safe-assistant'),
										'default' => true,
									],
									[
										'id'      => 'account_password-2_required',
										'type'    => 'switcher',
										'title'   => esc_html__('Is Required?', 'safe-assistant'),
										'default' => true,
									],
									[
										'id'      => 'account_password-2_label',
										'type'    => 'text',
										'title'   => esc_html__('Field Label', 'safe-assistant'),
										'default' => esc_html__('First name', 'woocommerce'),
									],
									[
										'id'      => 'account_password-2_placeholder',
										'type'    => 'text',
										'title'   => esc_html__('Placeholder', 'safe-assistant'),
									],
									[
										'id'      => 'account_password-2_default',
										'type'    => 'text',
										'title'   => esc_html__('Default Value', 'safe-assistant'),
									],
								],
							],
						],
					],
					[
						'id'     => 'checkout_billing_fields_accordion',
						'type'   => 'accordion',
						'title'  => esc_html__('Billing Fields Editor', 'safe-assistant'),
						'accordions' => [
							// First Name
							[
								'title'  => esc_html__('Billing First Name', 'safe-assistant'),
								'fields' => [
									[
										'id'      => 'billing_first_name_enabled',
										'type'    => 'switcher',
										'title'   => esc_html__('Enable Field', 'safe-assistant'),
										'default' => true,
									],
									[
										'id'      => 'billing_first_name_required',
										'type'    => 'switcher',
										'title'   => esc_html__('Is Required?', 'safe-assistant'),
										'default' => true,
									],
									[
										'id'      => 'billing_first_name_label',
										'type'    => 'text',
										'title'   => esc_html__('Field Label', 'safe-assistant'),
										'default' => esc_html__('First name', 'woocommerce'),
									],
									[
										'id'      => 'billing_first_name_placeholder',
										'type'    => 'text',
										'title'   => esc_html__('Placeholder', 'safe-assistant'),
									],
									[
										'id'      => 'billing_first_name_default',
										'type'    => 'text',
										'title'   => esc_html__('Default Value', 'safe-assistant'),
									],
								],
							],

							// Last Name
							[
								'title'  => esc_html__('Billing Last Name', 'safe-assistant'),
								'fields' => [
									[
										'id'      => 'billing_last_name_enabled',
										'type'    => 'switcher',
										'title'   => esc_html__('Enable Field', 'safe-assistant'),
										'default' => true,
									],
									[
										'id'      => 'billing_last_name_required',
										'type'    => 'switcher',
										'title'   => esc_html__('Is Required?', 'safe-assistant'),
										'default' => true,
									],
									[
										'id'      => 'billing_last_name_label',
										'type'    => 'text',
										'title'   => esc_html__('Field Label', 'safe-assistant'),
										'default' => esc_html__('Last name', 'woocommerce'),
									],
									[
										'id'      => 'billing_last_name_placeholder',
										'type'    => 'text',
										'title'   => esc_html__('Placeholder', 'safe-assistant'),
									],
									[
										'id'      => 'billing_last_name_default',
										'type'    => 'text',
										'title'   => esc_html__('Default Value', 'safe-assistant'),
									],
								],
							],

							// Company
							[
								'title'  => esc_html__('Billing Company', 'safe-assistant'),
								'fields' => [
									[
										'id'      => 'billing_company_enabled',
										'type'    => 'switcher',
										'title'   => esc_html__('Enable Field', 'safe-assistant'),
										'default' => true,
									],
									[
										'id'      => 'billing_company_required',
										'type'    => 'switcher',
										'title'   => esc_html__('Is Required?', 'safe-assistant'),
										'default' => false,
									],
									[
										'id'      => 'billing_company_label',
										'type'    => 'text',
										'title'   => esc_html__('Field Label', 'safe-assistant'),
										'default' => esc_html__('Company name', 'woocommerce'),
									],
									[
										'id'      => 'billing_company_placeholder',
										'type'    => 'text',
										'title'   => esc_html__('Placeholder', 'safe-assistant'),
									],
									[
										'id'      => 'billing_company_default',
										'type'    => 'text',
										'title'   => esc_html__('Default Value', 'safe-assistant'),
									],
								],
							],

							// Country
							[
								'title'  => esc_html__('Billing Country', 'safe-assistant'),
								'fields' => [
									[
										'id'      => 'billing_country_enabled',
										'type'    => 'switcher',
										'title'   => esc_html__('Enable Field', 'safe-assistant'),
										'default' => true,
									],
									[
										'id'      => 'billing_country_required',
										'type'    => 'switcher',
										'title'   => esc_html__('Is Required?', 'safe-assistant'),
										'default' => true,
									],
									[
										'id'      => 'billing_country_label',
										'type'    => 'text',
										'title'   => esc_html__('Field Label', 'safe-assistant'),
										'default' => esc_html__('Country / Region', 'woocommerce'),
									],
								],
							],

							// Address 1
							[
								'title'  => esc_html__('Billing Address 1', 'safe-assistant'),
								'fields' => [
									[
										'id'      => 'billing_address_1_enabled',
										'type'    => 'switcher',
										'title'   => esc_html__('Enable Field', 'safe-assistant'),
										'default' => true,
									],
									[
										'id'      => 'billing_address_1_required',
										'type'    => 'switcher',
										'title'   => esc_html__('Is Required?', 'safe-assistant'),
										'default' => true,
									],
									[
										'id'      => 'billing_address_1_label',
										'type'    => 'text',
										'title'   => esc_html__('Field Label', 'safe-assistant'),
										'default' => esc_html__('Street address', 'woocommerce'),
									],
									[
										'id'      => 'billing_address_1_placeholder',
										'type'    => 'text',
										'title'   => esc_html__('Placeholder', 'safe-assistant'),
									],
									[
										'id'      => 'billing_address_1_default',
										'type'    => 'text',
										'title'   => esc_html__('Default Value', 'safe-assistant'),
									],
								],
							],

							// Address 2
							[
								'title'  => esc_html__('Billing Address 2', 'safe-assistant'),
								'fields' => [
									[
										'id'      => 'billing_address_2_enabled',
										'type'    => 'switcher',
										'title'   => esc_html__('Enable Field', 'safe-assistant'),
										'default' => true,
									],
									[
										'id'      => 'billing_address_2_required',
										'type'    => 'switcher',
										'title'   => esc_html__('Is Required?', 'safe-assistant'),
										'default' => false,
									],
									[
										'id'      => 'billing_address_2_label',
										'type'    => 'text',
										'title'   => esc_html__('Field Label', 'safe-assistant'),
										'default' => esc_html__('Apartment, suite, unit, etc. (optional)', 'woocommerce'),
									],
									[
										'id'      => 'billing_address_2_placeholder',
										'type'    => 'text',
										'title'   => esc_html__('Placeholder', 'safe-assistant'),
									],
									[
										'id'      => 'billing_address_2_default',
										'type'    => 'text',
										'title'   => esc_html__('Default Value', 'safe-assistant'),
									],
								],
							],

							// City
							[
								'title'  => esc_html__('Billing City', 'safe-assistant'),
								'fields' => [
									[
										'id'      => 'billing_city_enabled',
										'type'    => 'switcher',
										'title'   => esc_html__('Enable Field', 'safe-assistant'),
										'default' => true,
									],
									[
										'id'      => 'billing_city_required',
										'type'    => 'switcher',
										'title'   => esc_html__('Is Required?', 'safe-assistant'),
										'default' => true,
									],
									[
										'id'      => 'billing_city_label',
										'type'    => 'text',
										'title'   => esc_html__('Field Label', 'safe-assistant'),
										'default' => esc_html__('Town / City', 'woocommerce'),
									],
									[
										'id'      => 'billing_city_placeholder',
										'type'    => 'text',
										'title'   => esc_html__('Placeholder', 'safe-assistant'),
									],
									[
										'id'      => 'billing_city_default',
										'type'    => 'text',
										'title'   => esc_html__('Default Value', 'safe-assistant'),
									],
								],
							],

							// State
							[
								'title'  => esc_html__('Billing State', 'safe-assistant'),
								'fields' => [
									[
										'id'      => 'billing_state_enabled',
										'type'    => 'switcher',
										'title'   => esc_html__('Enable Field', 'safe-assistant'),
										'default' => true,
									],
									[
										'id'      => 'billing_state_required',
										'type'    => 'switcher',
										'title'   => esc_html__('Is Required?', 'safe-assistant'),
										'default' => true,
									],
									[
										'id'      => 'billing_state_label',
										'type'    => 'text',
										'title'   => esc_html__('Field Label', 'safe-assistant'),
										'default' => esc_html__('State / County', 'woocommerce'),
									],
								],
							],

							// Postcode
							[
								'title'  => esc_html__('Billing Postcode', 'safe-assistant'),
								'fields' => [
									[
										'id'      => 'billing_postcode_enabled',
										'type'    => 'switcher',
										'title'   => esc_html__('Enable Field', 'safe-assistant'),
										'default' => true,
									],
									[
										'id'      => 'billing_postcode_required',
										'type'    => 'switcher',
										'title'   => esc_html__('Is Required?', 'safe-assistant'),
										'default' => true,
									],
									[
										'id'      => 'billing_postcode_label',
										'type'    => 'text',
										'title'   => esc_html__('Field Label', 'safe-assistant'),
										'default' => esc_html__('Postcode / ZIP', 'woocommerce'),
									],
									[
										'id'      => 'billing_postcode_placeholder',
										'type'    => 'text',
										'title'   => esc_html__('Placeholder', 'safe-assistant'),
									],
									[
										'id'      => 'billing_postcode_default',
										'type'    => 'text',
										'title'   => esc_html__('Default Value', 'safe-assistant'),
									],
								],
							],

							// Phone
							[
								'title'  => esc_html__('Billing Phone', 'safe-assistant'),
								'fields' => [
									[
										'id'      => 'billing_phone_enabled',
										'type'    => 'switcher',
										'title'   => esc_html__('Enable Field', 'safe-assistant'),
										'default' => true,
									],
									[
										'id'      => 'billing_phone_required',
										'type'    => 'switcher',
										'title'   => esc_html__('Is Required?', 'safe-assistant'),
										'default' => true,
									],
									[
										'id'      => 'billing_phone_label',
										'type'    => 'text',
										'title'   => esc_html__('Field Label', 'safe-assistant'),
										'default' => esc_html__('Phone', 'woocommerce'),
									],
									[
										'id'      => 'billing_phone_placeholder',
										'type'    => 'text',
										'title'   => esc_html__('Placeholder', 'safe-assistant'),
									],
									[
										'id'      => 'billing_phone_default',
										'type'    => 'text',
										'title'   => esc_html__('Default Value', 'safe-assistant'),
									],
								],
							],

							// Email
							[
								'title'  => esc_html__('Billing Email', 'safe-assistant'),
								'fields' => [
									[
										'id'      => 'billing_email_enabled',
										'type'    => 'switcher',
										'title'   => esc_html__('Enable Field', 'safe-assistant'),
										'default' => true,
									],
									[
										'id'      => 'billing_email_required',
										'type'    => 'switcher',
										'title'   => esc_html__('Is Required?', 'safe-assistant'),
										'default' => true,
									],
									[
										'id'      => 'billing_email_label',
										'type'    => 'text',
										'title'   => esc_html__('Field Label', 'safe-assistant'),
										'default' => esc_html__('Email address', 'woocommerce'),
									],
									[
										'id'      => 'billing_email_placeholder',
										'type'    => 'text',
										'title'   => esc_html__('Placeholder', 'safe-assistant'),
									],
									[
										'id'      => 'billing_email_default',
										'type'    => 'text',
										'title'   => esc_html__('Default Value', 'safe-assistant'),
									],
								],
							],
						],
					],
					// End of Billing Fields

					// Start of Shipping Fields
					[
						'id'     => 'checkout_shipping_fields_accordion',
						'type'   => 'accordion',
						'title'  => esc_html__('Shipping Fields Editor', 'safe-assistant'),
						'accordions' => [
							// First Name
							[
								'title'  => esc_html__('Shipping First Name', 'safe-assistant'),
								'fields' => [
									[
										'id'      => 'shipping_first_name_enabled',
										'type'    => 'switcher',
										'title'   => esc_html__('Enable Field', 'safe-assistant'),
										'default' => true,
									],
									[
										'id'      => 'shipping_first_name_required',
										'type'    => 'switcher',
										'title'   => esc_html__('Is Required?', 'safe-assistant'),
										'default' => true,
									],
									[
										'id'      => 'shipping_first_name_label',
										'type'    => 'text',
										'title'   => esc_html__('Field Label', 'safe-assistant'),
										'default' => esc_html__('First name', 'woocommerce'),
									],
									[
										'id'      => 'shipping_first_name_placeholder',
										'type'    => 'text',
										'title'   => esc_html__('Placeholder', 'safe-assistant'),
									],
									[
										'id'      => 'shipping_first_name_default',
										'type'    => 'text',
										'title'   => esc_html__('Default Value', 'safe-assistant'),
									],
								],
							],

							// Last Name
							[
								'title'  => esc_html__('Shipping Last Name', 'safe-assistant'),
								'fields' => [
									[
										'id'      => 'shipping_last_name_enabled',
										'type'    => 'switcher',
										'title'   => esc_html__('Enable Field', 'safe-assistant'),
										'default' => true,
									],
									[
										'id'      => 'shipping_last_name_required',
										'type'    => 'switcher',
										'title'   => esc_html__('Is Required?', 'safe-assistant'),
										'default' => true,
									],
									[
										'id'      => 'shipping_last_name_label',
										'type'    => 'text',
										'title'   => esc_html__('Field Label', 'safe-assistant'),
										'default' => esc_html__('Last name', 'woocommerce'),
									],
									[
										'id'      => 'shipping_last_name_placeholder',
										'type'    => 'text',
										'title'   => esc_html__('Placeholder', 'safe-assistant'),
									],
									[
										'id'      => 'shipping_last_name_default',
										'type'    => 'text',
										'title'   => esc_html__('Default Value', 'safe-assistant'),
									],
								],
							],

							// Company
							[
								'title'  => esc_html__('Shipping Company', 'safe-assistant'),
								'fields' => [
									[
										'id'      => 'shipping_company_enabled',
										'type'    => 'switcher',
										'title'   => esc_html__('Enable Field', 'safe-assistant'),
										'default' => true,
									],
									[
										'id'      => 'shipping_company_required',
										'type'    => 'switcher',
										'title'   => esc_html__('Is Required?', 'safe-assistant'),
										'default' => false,
									],
									[
										'id'      => 'shipping_company_label',
										'type'    => 'text',
										'title'   => esc_html__('Field Label', 'safe-assistant'),
										'default' => esc_html__('Company name', 'woocommerce'),
									],
									[
										'id'      => 'shipping_company_placeholder',
										'type'    => 'text',
										'title'   => esc_html__('Placeholder', 'safe-assistant'),
									],
									[
										'id'      => 'shipping_company_default',
										'type'    => 'text',
										'title'   => esc_html__('Default Value', 'safe-assistant'),
									],
								],
							],

							// Country
							[
								'title'  => esc_html__('Shipping Country', 'safe-assistant'),
								'fields' => [
									[
										'id'      => 'shipping_country_enabled',
										'type'    => 'switcher',
										'title'   => esc_html__('Enable Field', 'safe-assistant'),
										'default' => true,
									],
									[
										'id'      => 'shipping_country_required',
										'type'    => 'switcher',
										'title'   => esc_html__('Is Required?', 'safe-assistant'),
										'default' => true,
									],
									[
										'id'      => 'shipping_country_label',
										'type'    => 'text',
										'title'   => esc_html__('Field Label', 'safe-assistant'),
										'default' => esc_html__('Country / Region', 'woocommerce'),
									],
								],
							],

							// Address 1
							[
								'title'  => esc_html__('Shipping Address 1', 'safe-assistant'),
								'fields' => [
									[
										'id'      => 'shipping_address_1_enabled',
										'type'    => 'switcher',
										'title'   => esc_html__('Enable Field', 'safe-assistant'),
										'default' => true,
									],
									[
										'id'      => 'shipping_address_1_required',
										'type'    => 'switcher',
										'title'   => esc_html__('Is Required?', 'safe-assistant'),
										'default' => true,
									],
									[
										'id'      => 'shipping_address_1_label',
										'type'    => 'text',
										'title'   => esc_html__('Field Label', 'safe-assistant'),
										'default' => esc_html__('Street address', 'woocommerce'),
									],
									[
										'id'      => 'shipping_address_1_placeholder',
										'type'    => 'text',
										'title'   => esc_html__('Placeholder', 'safe-assistant'),
									],
									[
										'id'      => 'shipping_address_1_default',
										'type'    => 'text',
										'title'   => esc_html__('Default Value', 'safe-assistant'),
									],
								],
							],

							// Address 2
							[
								'title'  => esc_html__('Shipping Address 2', 'safe-assistant'),
								'fields' => [
									[
										'id'      => 'shipping_address_2_enabled',
										'type'    => 'switcher',
										'title'   => esc_html__('Enable Field', 'safe-assistant'),
										'default' => true,
									],
									[
										'id'      => 'shipping_address_2_required',
										'type'    => 'switcher',
										'title'   => esc_html__('Is Required?', 'safe-assistant'),
										'default' => false,
									],
									[
										'id'      => 'shipping_address_2_label',
										'type'    => 'text',
										'title'   => esc_html__('Field Label', 'safe-assistant'),
										'default' => esc_html__('Apartment, suite, unit, etc. (optional)', 'woocommerce'),
									],
									[
										'id'      => 'shipping_address_2_placeholder',
										'type'    => 'text',
										'title'   => esc_html__('Placeholder', 'safe-assistant'),
									],
									[
										'id'      => 'shipping_address_2_default',
										'type'    => 'text',
										'title'   => esc_html__('Default Value', 'safe-assistant'),
									],
								],
							],

							// City
							[
								'title'  => esc_html__('Shipping City', 'safe-assistant'),
								'fields' => [
									[
										'id'      => 'shipping_city_enabled',
										'type'    => 'switcher',
										'title'   => esc_html__('Enable Field', 'safe-assistant'),
										'default' => true,
									],
									[
										'id'      => 'shipping_city_required',
										'type'    => 'switcher',
										'title'   => esc_html__('Is Required?', 'safe-assistant'),
										'default' => true,
									],
									[
										'id'      => 'shipping_city_label',
										'type'    => 'text',
										'title'   => esc_html__('Field Label', 'safe-assistant'),
										'default' => esc_html__('Town / City', 'woocommerce'),
									],
									[
										'id'      => 'shipping_city_placeholder',
										'type'    => 'text',
										'title'   => esc_html__('Placeholder', 'safe-assistant'),
									],
									[
										'id'      => 'shipping_city_default',
										'type'    => 'text',
										'title'   => esc_html__('Default Value', 'safe-assistant'),
									],
								],
							],

							// State
							[
								'title'  => esc_html__('Shipping State', 'safe-assistant'),
								'fields' => [
									[
										'id'      => 'shipping_state_enabled',
										'type'    => 'switcher',
										'title'   => esc_html__('Enable Field', 'safe-assistant'),
										'default' => true,
									],
									[
										'id'      => 'shipping_state_required',
										'type'    => 'switcher',
										'title'   => esc_html__('Is Required?', 'safe-assistant'),
										'default' => true,
									],
									[
										'id'      => 'shipping_state_label',
										'type'    => 'text',
										'title'   => esc_html__('Field Label', 'safe-assistant'),
										'default' => esc_html__('State / County', 'woocommerce'),
									],
								],
							],

							// Postcode
							[
								'title'  => esc_html__('Shipping Postcode', 'safe-assistant'),
								'fields' => [
									[
										'id'      => 'shipping_postcode_enabled',
										'type'    => 'switcher',
										'title'   => esc_html__('Enable Field', 'safe-assistant'),
										'default' => true,
									],
									[
										'id'      => 'shipping_postcode_required',
										'type'    => 'switcher',
										'title'   => esc_html__('Is Required?', 'safe-assistant'),
										'default' => true,
									],
									[
										'id'      => 'shipping_postcode_label',
										'type'    => 'text',
										'title'   => esc_html__('Field Label', 'safe-assistant'),
										'default' => esc_html__('Postcode / ZIP', 'woocommerce'),
									],
									[
										'id'      => 'shipping_postcode_placeholder',
										'type'    => 'text',
										'title'   => esc_html__('Placeholder', 'safe-assistant'),
									],
									[
										'id'      => 'shipping_postcode_default',
										'type'    => 'text',
										'title'   => esc_html__('Default Value', 'safe-assistant'),
									],
								],
							],

							// Phone
							[
								'title'  => esc_html__('Shipping Phone', 'safe-assistant'),
								'fields' => [
									[
										'id'      => 'shipping_phone_enabled',
										'type'    => 'switcher',
										'title'   => esc_html__('Enable Field', 'safe-assistant'),
										'default' => true,
									],
									[
										'id'      => 'shipping_phone_required',
										'type'    => 'switcher',
										'title'   => esc_html__('Is Required?', 'safe-assistant'),
										'default' => true,
									],
									[
										'id'      => 'shipping_phone_label',
										'type'    => 'text',
										'title'   => esc_html__('Field Label', 'safe-assistant'),
										'default' => esc_html__('Phone', 'woocommerce'),
									],
									[
										'id'      => 'shipping_phone_placeholder',
										'type'    => 'text',
										'title'   => esc_html__('Placeholder', 'safe-assistant'),
									],
									[
										'id'      => 'shipping_phone_default',
										'type'    => 'text',
										'title'   => esc_html__('Default Value', 'safe-assistant'),
									],
								],
							],

							// Email
							[
								'title'  => esc_html__('Shipping Email', 'safe-assistant'),
								'fields' => [
									[
										'id'      => 'shipping_email_enabled',
										'type'    => 'switcher',
										'title'   => esc_html__('Enable Field', 'safe-assistant'),
										'default' => true,
									],
									[
										'id'      => 'shipping_email_required',
										'type'    => 'switcher',
										'title'   => esc_html__('Is Required?', 'safe-assistant'),
										'default' => true,
									],
									[
										'id'      => 'shipping_email_label',
										'type'    => 'text',
										'title'   => esc_html__('Field Label', 'safe-assistant'),
										'default' => esc_html__('Email address', 'woocommerce'),
									],
									[
										'id'      => 'shipping_email_placeholder',
										'type'    => 'text',
										'title'   => esc_html__('Placeholder', 'safe-assistant'),
									],
									[
										'id'      => 'shipping_email_default',
										'type'    => 'text',
										'title'   => esc_html__('Default Value', 'safe-assistant'),
									],
								],
							],
						],
					],
					// Start of Order Fields
					[
						'id'     => 'checkout_order_fields_accordion',
						'type'   => 'accordion',
						'title'  => esc_html__('Order Fields Editor', 'safe-assistant'),
						'accordions' => [
							// Order Comments
							[
								'title'  => esc_html__('Order Comments', 'safe-assistant'),
								'fields' => [
									[
										'id'      => 'order_comments_enabled',
										'type'    => 'switcher',
										'title'   => esc_html__('Enable Field', 'safe-assistant'),
										'default' => true,
									],
									[
										'id'      => 'order_comments_required',
										'type'    => 'switcher',
										'title'   => esc_html__('Is Required?', 'safe-assistant'),
										'default' => false,
									],
									[
										'id'      => 'order_comments_label',
										'type'    => 'text',
										'title'   => esc_html__('Field Label', 'safe-assistant'),
										'default' => esc_html__('Order notes', 'woocommerce'),
									],
									[
										'id'      => 'order_comments_placeholder',
										'type'    => 'text',
										'title'   => esc_html__('Placeholder', 'safe-assistant'),
										'default' => esc_html__('Notes about your order, e.g. special notes for delivery.', 'woocommerce'),
									],
									[
										'id'      => 'order_comments_default',
										'type'    => 'text',
										'title'   => esc_html__('Default Value', 'safe-assistant'),
									],
								],
							],
						],
					],
					// End of Order Fields
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
						'desc'    => esc_html__('you can import users from excel file and users imported in your site will be automatically and charge wallet with percentage or fixed amount and more.', 'safe-assistant'),
					],
					[
						'id'      => 'social_order_management_addons',
						'type'    => 'switcher',
						'title'   => esc_html__('Enable Social Order Management Addon', 'safe-assistant'),
						'default' => false,
						'desc'    => esc_html__('Management Woocommerce orders via Social Media Platforms like WhatsApp or Telegram.', 'safe-assistant'),
					],
					[
						'id'      => 'order_toolkit_addons',
						'type'    => 'switcher',
						'title'   => esc_html__('Enable Order Toolkit Addon', 'safe-assistant'),
						'default' => false,
						'desc'    => esc_html__('Toolkit for managing and processing orders. you can print invoices, add tracking information, update order statuses easily and more.', 'safe-assistant'),
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
						'desc'    => esc_html__('Enter the SMS pattern for wallet expiration notifications by hour.', 'safe-assistant') . '<br>'
							. __('first parameter is name of user', 'safe-assistant') . '<code>name</code>' . '<br>'
							. __('second parameter is remaining hours', 'safe-assistant') . '<code>hours</code>' . '<br>',
						'dependency' => ['nir_wallet_expire_check_by', '==', 'false'],
					],
					[
						'id'      => 'nir_wallet_expire_pattern_sms',
						'type'    => 'text',
						'title'   => esc_html__('SMS Pattern', 'safe-assistant'),
						'desc'    => esc_html__('Enter the SMS pattern for wallet expiration notifications by days.', 'safe-assistant') . '<br>'
							. __('first parameter is name of user', 'safe-assistant') . '<code>name</code>' . '<br>'
							. __('second parameter is remaining days', 'safe-assistant') . '<code>days</code>' . '<br>',
						'dependency' => ['nir_wallet_expire_check_by', '==', 'true'],
					],
					[
						'id'      => 'nir_wallet_expire_last_pattern_sms',
						'type'    => 'text',
						'title'   => esc_html__('SMS Pattern (for last 24h)', 'safe-assistant'),
						'desc'    => esc_html__('Enter the SMS pattern for wallet expiration notifications less than 24 hours.', 'safe-assistant') . '<br>'
							. __('only available parameter is name of user', 'safe-assistant') . '<code>name</code>' . '<br>',
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
			[
				'parent' => 'custom_code',
				'id'     => 'wordpress_admin',
				'title'  => esc_html__('Wordpress Admin', 'safe-assistant'),
				'icon'   => 'fab fa-wordpress',
				'fields' => [
					[
						'id'       => 'javascript_admin',
						'type'     => 'code_editor',
						'title'    => esc_html__('Custom javascript for wordpress admin dashboard', 'safe-assistant'),
						'settings' => [
							'theme'  => 'monokai',
							'mode'   => 'javascript',
						],
					],
					[
						'id'       => 'css_admin',
						'type'     => 'code_editor',
						'title'    => esc_html__('Custom css for wordpress admin dashboard', 'safe-assistant'),
						'settings' => [
							'theme'  => 'mbo',
							'mode'   => 'css',
						],
					],
				]
			],
			[
				'parent' => 'custom_code',
				'id'     => 'wordpress_admin',
				'title'  => esc_html__('Frontend', 'safe-assistant'),
				'icon'   => 'fas fa-file-code',
				'fields' => [
					[
						'id'       => 'javascript_front',
						'type'     => 'code_editor',
						'title'    => esc_html__('Custom javascript for frontend', 'safe-assistant'),
						'settings' => [
							'theme'  => 'monokai',
							'mode'   => 'javascript',
						],
					],
					[
						'id'       => 'css_front',
						'type'     => 'code_editor',
						'title'    => esc_html__('Custom css for frontend', 'safe-assistant'),
						'settings' => [
							'theme'  => 'mbo',
							'mode'   => 'css',
						],
					],
				]
			],
		];
		return $sections;
	}

	public function render_logs()
	{
		echo '<div id="sa-logs-wrapper">';
		echo '<div class="sa-logs-filters">';
		echo '<label for="sa-logs-type">' . esc_html__('Type:', 'safe-assistant') . '</label>';
		echo '<select id="sa-logs-type">';
		echo '<option value="">' . esc_html__('All Types', 'safe-assistant') . '</option>';

		// Get available types
		global $wpdb;
		$table = $wpdb->prefix . 'sa_logs';
		$types = $wpdb->get_col("SELECT DISTINCT type FROM $table WHERE type != '' ORDER BY type");
		foreach ($types as $type) {
			echo '<option value="' . esc_attr($type) . '">' . esc_html($type) . '</option>';
		}

		echo '</select>';
		echo '<label for="sa-logs-status">' . esc_html__('Status:', 'safe-assistant') . '</label>';
		echo '<select id="sa-logs-status">';
		echo '<option value="">' . esc_html__('All Statuses', 'safe-assistant') . '</option>';
		echo '<option value="info">' . esc_html__('Info', 'safe-assistant') . '</option>';
		echo '<option value="success">' . esc_html__('Success', 'safe-assistant') . '</option>';
		echo '<option value="warning">' . esc_html__('Warning', 'safe-assistant') . '</option>';
		echo '<option value="error">' . esc_html__('Error', 'safe-assistant') . '</option>';
		echo '</select>';

		echo '<label for="sa-logs-per-page">' . esc_html__('Per Page:', 'safe-assistant') . '</label>';
		echo '<select id="sa-logs-per-page">';
		echo '<option value="10">10</option>';
		echo '<option value="20" selected>20</option>';
		echo '<option value="50">50</option>';
		echo '<option value="100">100</option>';
		echo '</select>';

		echo '<button type="button" class="button" id="sa-logs-refresh">' . esc_html__('Refresh', 'safe-assistant') . '</button>';
		echo '</div>';

		echo '<div id="sa-logs-container">';
		echo sa_render_logs_paginated();
		echo '</div>';
		echo '</div>';
	}

	private function create_last_options()
	{
		CSF::createSection(
			SAFE_ASSISTANT_SETTING_ID,
			[
				'id'     => 'logs',
				'title'  => esc_html__('Logs', 'safe-assistant'),
				'icon'   => 'fas fa-file-alt',
				'fields' => [
					[
						'type'     => 'callback',
						'function'  => [$this, 'render_logs'],
					],
				]

			]
		);

		CSF::createSection(SAFE_ASSISTANT_SETTING_ID, [
			'title'  => esc_html__('Settings', 'safe-assistant'),
			'icon'   => 'fas fa-cog',
			'fields' => [
				[
					'type'    => 'notice',
					'style'   => 'warning',
					'content' => __('Please note when changing SMS gateway settings, you may need to update other settings such as patterns.', 'safe-assistant'),
				],
				[
					'id'       => 'sms_gateway',
					'type'     => 'select',
					'title'    => __('SMS Gateway', 'safe-assistant'),
					'options'  => [
						'melipayamak' => 'MeliPayamak.Com',
						'smsir'       => 'Sms.ir',
					],
					'default'  => 'melipayamak',
				],
				// === MeliPayamak credentials ===
				[
					'id'       => 'melipayamak_sms_username',
					'type'     => 'text',
					'title'    => __('MeliPayamak Username', 'safe-assistant'),
					'dependency' => ['sms_gateway', '==', 'melipayamak'],
				],
				[
					'id'       => 'melipayamak_sms_password',
					'type'     => 'text',
					'title'    => __('MeliPayamak Password', 'safe-assistant'),
					'attributes' => [
						'type' => 'password',
					],
					'dependency' => ['sms_gateway', '==', 'melipayamak'],
				],
				[
					'id'       => 'melipayamak_sms_from',
					'type'     => 'text',
					'title'    => __('Sms sender number', 'safe-assistant'),
					'dependency' => ['sms_gateway', '==', 'melipayamak'],
				],
				// === Sms.ir credentials ===
				[
					'id'       => 'smsir_sms_api_key',
					'type'     => 'text',
					'title'    => __('SMS.ir API Key', 'safe-assistant'),
					'dependency' => ['sms_gateway', '==', 'smsir'],
				],
				[
					'id'       => 'smsir_sms_from',
					'type'     => 'text',
					'title'    => __('Sms sender number', 'safe-assistant'),
					'dependency' => ['sms_gateway', '==', 'smsir'],
				],
				[
					'type'     => 'callback',
					'function' => [$this, 'sms_profile_status'],
				]
			]
		]);

		CSF::createSection(SAFE_ASSISTANT_SETTING_ID, [
			'title'  => esc_html__('Backup & Restore', 'safe-assistant'),
			'icon'   => 'fas fa-save',
			'fields' => [
				[
					'type' => 'backup',
				]
			]
		]);
	}

	public function sms_profile_status()
	{
		try {
			$sms_result = sa_get_sms_gateway_credit();
			if (!empty($sms_result) && isset($sms_result['status']) && $sms_result['status'] == 1) {
				echo "<div class='panel-status'>";
				echo "<div class='circle-status green pulse'></div>";
				echo __('Panel is Connected', 'safe-assistant');
				echo "</div>";
				echo "<div class='sms-credit-box'>";
				echo "<div class='credit-text'>";
				echo __('Your Panel Credit:', 'safe-assistant');
				echo "<strong>";
				echo number_format($sms_result['credit']);
				echo "</strong><br>";
				echo "</div></div>";
			} else {
				echo "<div class='panel-status'>";
				echo "<div class='circle-status red pulse'></div>";
				echo __('Panel is Connected', 'safe-assistant');
				echo "</div>";
				echo "<div class='sms-credit-box'>";
				__('Panel is Not Connected', 'safe-assistant');
				echo "<div class='credit-text'>";
				echo __('Unable to fetch credit.', 'safe-assistant');
				echo __('Error:', 'safe-assistant');
				print_r($sms_result['message']);
				echo "</div></div>";
			}
		} catch (\Throwable $th) {
			echo "<div class='panel-status'>";
			echo "<div class='circle-status red pulse'></div>";
			echo __('Panel is Connected', 'safe-assistant');
			echo "</div>";
			echo "<div class='sms-credit-box'>";
			__('Panel is Not Connected', 'safe-assistant');
			echo "<div class='credit-text'>";
			echo __('Unable to fetch credit.', 'safe-assistant');
			echo __('Error:', 'safe-assistant');
			echo $th->getMessage();
			echo "</div></div>";
		}
	}
}
