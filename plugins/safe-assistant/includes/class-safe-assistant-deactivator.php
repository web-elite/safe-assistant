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
 * @author     𝐀𝐥𝐢𝐫𝐞𝐳𝐚𝐘𝐚𝐠𝐡𝐨𝐮𝐭𝐢 <webelitee@gmail.com>
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
		include_once SAFE_ASSISTANT_DIR . 'addons/user-importer/addon-user-importer-settings.php';
		$settings = new Addon_User_Importer_Settings();
		$settings->drop_tables();
		delete_transient(ADDON_USER_IMPORTER_CRON_EVENT . '_task');
		wp_clear_scheduled_hook(ADDON_USER_IMPORTER_CRON_EVENT);
	}
}
