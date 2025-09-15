<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://webelitee.ir
 * @since      1.0.0
 *
 * @package    Safe_Assistant
 * @subpackage Safe_Assistant/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Safe_Assistant
 * @subpackage Safe_Assistant/public
 * @author     𝐀𝐥𝐢𝐫𝐞𝐳𝐚𝐘𝐚𝐠𝐡𝐨𝐮𝐭𝐢 <webelitee@gmail.com>
 */
class Safe_Assistant_Public
{

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Safe_Assistant_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Safe_Assistant_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/safe-assistant-public.css', array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Safe_Assistant_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Safe_Assistant_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/safe-assistant-public.js', array('jquery'), $this->version, false);
		$only_checkout = sa_get_option('vpn_checker_only_in_checkout', true);
		$vpn_checker_status = sa_get_option('vpn_checker_status', false);
		if ($only_checkout && !is_checkout()) {
			$vpn_checker_status = false;
		}

		wp_localize_script($this->plugin_name, 'sa_vars', [
			'enable_auto_membership'            => sa_get_option('enable_auto_membership'),
			'hide_membership_option_checkout'   => sa_get_option('hide_membership_option_checkout', false),
			'vpn_checker_url'                   => 'https://ipinfo.io/json?token=' . sa_get_option('vpn_checker_token'),
			'vpn_checker_status'                => (bool) $vpn_checker_status,
			'vpn_checker_type'                  => (bool) sa_get_option('vpn_checker_type', false),
			'vpn_checker_message'               => sa_get_option('vpn_checker_message'),
			'vpn_checker_title'                 => sa_get_option('vpn_checker_title'),
		]);
	}
}
