<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://webelitee.ir
 * @since      1.0.0
 *
 * @package    Safe_Assistant
 * @subpackage Safe_Assistant/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Safe_Assistant
 * @subpackage Safe_Assistant/includes
 * @author     AlirezaYaghouti <webelitee@gmail.com>
 */
class Safe_Assistant_Deactivator
{

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate()
	{
		// Deactivate all addons in the addons directory
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
						if (method_exists($addon_instance, 'deactivator')) {
							$addon_instance->deactivator();
						}
					}
				}
			}
		}
		$timestamp = wp_next_scheduled('sa_nir_wallet_expiration_check');
		if ($timestamp) {
			wp_unschedule_event($timestamp, 'sa_nir_wallet_expiration_check');
		}

		// delete sms log table
		global $wpdb;
		$table = $wpdb->prefix . 'sa_sms_log';
		$wpdb->query("DROP TABLE IF EXISTS $table");
	}
}
