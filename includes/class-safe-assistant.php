<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://webelitee.ir
 * @since      1.0.0
 *
 * @package    Safe_Assistant
 * @subpackage Safe_Assistant/includes
 */

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Safe_Assistant
 * @subpackage Safe_Assistant/includes
 * @author     𝐀𝐥𝐢𝐫𝐞𝐳𝐚𝐘𝐚𝐠𝐡𝐨𝐮𝐭𝐢 <webelitee@gmail.com>
 */
class Safe_Assistant
{

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Safe_Assistant_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct()
	{
		if (defined('SAFE_ASSISTANT_VERSION')) {
			$this->version = SAFE_ASSISTANT_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		if (defined('SAFE_ASSISTANT_VERSION')) {
			$this->plugin_name = SAFE_ASSISTANT_SLUG;
		} else {
			$this->plugin_name = 'safe-assistant';
		}

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->loader->add_action('plugins_loaded', $this, 'init_settings');
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Safe_Assistant_Loader. Orchestrates the hooks of the plugin.
	 * - Safe_Assistant_i18n. Defines internationalization functionality.
	 * - Safe_Assistant_Admin. Defines all hooks for the admin area.
	 * - Safe_Assistant_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies()
	{
		/**
		 * The file responsible for providing date and time jalali functions.
		 */
		require_once SAFE_ASSISTANT_DIR . 'lib/jdf.php';

		/**
		 * The file responsible for providing sms functions.
		 */
		require_once SAFE_ASSISTANT_DIR . 'helpers/helper-safe-assistant-sms.php';

		/**
		 * The file responsible for providing general helper functions.
		 */
		require_once SAFE_ASSISTANT_DIR . 'helpers/helper-safe-assistant-general.php';

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once SAFE_ASSISTANT_DIR . 'includes/class-safe-assistant-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once SAFE_ASSISTANT_DIR . 'includes/class-safe-assistant-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once SAFE_ASSISTANT_DIR . 'admin/class-safe-assistant-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once SAFE_ASSISTANT_DIR . 'public/class-safe-assistant-public.php';

		/**
		 * The class responsible for checking for plugin updates.
		 */
		require_once SAFE_ASSISTANT_DIR . 'lib/we-update-checker/we-update-checker.php';

		/**
		 * The class responsible for managing plugin setting.
		 */
		require_once SAFE_ASSISTANT_DIR . 'lib/codestar-framework-master/codestar-framework.php';

		$this->loader = new Safe_Assistant_Loader();
	}

	public function init_settings()
	{
		if (class_exists(WE_Updater::class, false)) {
			$updater = new WE_Updater(SAFE_ASSISTANT_DIR . 'safe-assistant.php', [
				'slug'     => SAFE_ASSISTANT_SLUG,
				'source'   => 'json',
				'json_url' => 'https://raw.githubusercontent.com/web-elite/safe-assistant/master/info.json',
			]);
			$updater->init();
		} else {
			add_action('admin_notices', function () {
				echo '<div class="notice notice-error"><p>'
					. esc_html__('Safe Assistant: Update Checker not found.', SAFE_ASSISTANT_SLUG)
					. '</p></div>';
			});
			error_log('Safe Assistant => Update Checker not found');
		}

		if (class_exists('CSF')) {
			require_once SAFE_ASSISTANT_DIR . 'includes/class-safe-assistant-settings.php';
			$settings = Safe_Assistant_Settings::instance();
			$settings->init();
		} else {
			add_action('admin_notices', function () {
				echo '<div class="notice notice-error"><p>'
					. esc_html__('Safe Assistant: Codestar Framework not found.', 'safe-assistant')
					. '</p></div>';
			});
			error_log('Safe Assistant => Codestar Framework not found');
		}
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Safe_Assistant_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale()
	{

		$plugin_i18n = new Safe_Assistant_i18n();
		$this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks()
	{

		$plugin_admin = new Safe_Assistant_Admin($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks()
	{

		$plugin_public = new Safe_Assistant_Public($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run()
	{
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name()
	{
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Safe_Assistant_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader()
	{
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version()
	{
		return $this->version;
	}
}
