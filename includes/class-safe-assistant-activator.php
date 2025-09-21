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
			$send_time = get_option(SAFE_ASSISTANT_SLUG . '-settings')['nir_wallet_expire_send_time'] ?? '09';
			if (strlen((string)$send_time) === 1) {
				$send_time = "0$send_time";
			}

			$timestamp = strtotime("today $send_time:00");
			if ($timestamp <= time()) {
				$timestamp += DAY_IN_SECONDS;
			}

			if (!wp_next_scheduled('sa_nir_wallet_expiration_check')) {
				wp_schedule_event($timestamp, 'daily', 'sa_nir_wallet_expiration_check');
			}
		}
	}
}
