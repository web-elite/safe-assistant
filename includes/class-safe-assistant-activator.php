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
 * @author     AlirezaYaghouti <webelitee@gmail.com>
 */
class Safe_Assistant_Activator
{

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate()
	{
		// Activate all addons in the addons directory
		if (defined('SAFE_ASSISTANT_DIR')) {
			$addons_dir = SAFE_ASSISTANT_DIR . 'addons/';
			$addon_folders = glob($addons_dir . '*', GLOB_ONLYDIR);

			foreach ($addon_folders as $folder) {
				$addon_name = basename($folder);
				$addon_file = $folder . '/addon-' . $addon_name . '.php';

				if (file_exists($addon_file)) {
					require_once $addon_file;
					$class_name = 'Addon_' . str_replace('-', '_', ucwords($addon_name, '-'));
					if (class_exists($class_name)) {
						$addon_instance = new $class_name();
						if (method_exists($addon_instance, 'activator')) {
							$addon_instance->activator();
						}
					}
				}
			}
		}

		if (defined('nirweb_wallet')) {
			$settings = get_option(SAFE_ASSISTANT_SLUG . '-settings', []);
			$send_hour = isset($settings['nir_wallet_expire_send_time']) ? (int)$settings['nir_wallet_expire_send_time'] : 9;

			$year  = gmdate('Y');
			$month = gmdate('n');
			$day   = gmdate('j');

			$timestamp = mktime($send_hour, 0, 0, $month, $day, $year);
			$timestamp = $timestamp - (get_option('gmt_offset') * HOUR_IN_SECONDS);

			if ($timestamp <= current_time('timestamp')) {
				$timestamp += DAY_IN_SECONDS;
			}

			if (!wp_next_scheduled('sa_nir_wallet_expiration_check')) {
				wp_schedule_event($timestamp, 'daily', 'sa_nir_wallet_expiration_check');
			}
		}

		// create sms log table 
		global $wpdb;
		$table = $wpdb->prefix . 'sa_sms_log';
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        recipient VARCHAR(50) NOT NULL,
        message TEXT NOT NULL,
        response TEXT NULL,
        status VARCHAR(20) NOT NULL,
        created_at DATETIME NOT NULL,
        PRIMARY KEY (id)
    ) $charset;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}
}
