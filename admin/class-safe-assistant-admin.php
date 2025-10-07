<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://webelitee.ir
 * @since      1.0.0
 *
 * @package    Safe_Assistant
 * @subpackage Safe_Assistant/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Safe_Assistant
 * @subpackage Safe_Assistant/admin
 * @author     AlirezaYaghouti <webelitee@gmail.com>
 */
class Safe_Assistant_Admin
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
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		
		// Add AJAX handlers
		add_action('wp_ajax_sa_get_logs_paginated', [$this, 'handle_logs_pagination_ajax']);
	}

	/**
	 * Register the stylesheets for the admin area.
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

		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/safe-assistant-admin.css', array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the admin area.
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

		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/safe-assistant-admin.js', array('jquery'), $this->version, false);
		
		// Localize script for AJAX
		wp_localize_script($this->plugin_name, 'sa_ajax', [
			'ajax_url' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('sa_logs_nonce'),
			'loading_text' => __('Loading...', 'safe-assistant'),
			'error_text' => __('Error loading logs. Please try again.', 'safe-assistant')
		]);
	}

	/**
	 * Handle AJAX request for paginated logs
	 *
	 * @since 1.0.0
	 */
	public function handle_logs_pagination_ajax()
	{
		// Verify nonce
		if (!wp_verify_nonce($_POST['nonce'] ?? '', 'sa_logs_nonce')) {
			wp_die(__('Security check failed', 'safe-assistant'));
		}

		// Check user permissions
		if (!current_user_can('manage_options')) {
			wp_die(__('Insufficient permissions', 'safe-assistant'));
		}

		$type = sanitize_text_field($_POST['type'] ?? '');
		$status = sanitize_text_field($_POST['status'] ?? '');
		$page = intval($_POST['page'] ?? 1);
		$per_page = intval($_POST['per_page'] ?? 20);

		// Ensure empty strings are converted to null for the function
		$type = empty($type) ? null : $type;
		$status = empty($status) ? null : $status;

		// Validate per_page
		if ($per_page < 1 || $per_page > 100) {
			$per_page = 20;
		}

		// Validate page
		if ($page < 1) {
			$page = 1;
		}

		$result = sa_render_logs_paginated($type, $status, $page, $per_page, true);

		wp_send_json($result);
	}
}
