<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://webelitee.ir
 * @since             1.0.0
 * @package           Safe_Assistant
 *
 * @wordpress-plugin
 * Plugin Name:       Safe Assistant
 * Plugin URI:        https://iranqq.com
 * Description:       Safe Assistant Sales assistant and necessary and efficient tools
 * Version:           1.6.5
 * Requires at least: 5.2
 * Requires PHP:	  7.4
 * Author:            AlirezaYaghouti
 * Author URI:        https://webelitee.ir/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       safe-assistant
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (! defined('WPINC')) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 */
define('SAFE_ASSISTANT_VERSION', '1.6.5');
define('SAFE_ASSISTANT_NAME', 'Safe Assistant');
define('SAFE_ASSISTANT_SLUG', 'safe-assistant');
define('SAFE_ASSISTANT_DIR', plugin_dir_path(__FILE__));
define('SAFE_ASSISTANT_URL', plugin_dir_url(__FILE__));
define('SAFE_ASSISTANT_SETTING_ID', 'safe-assistant-settings');

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-safe-assistant-activator.php
 */
function activate_safe_assistant()
{
	require_once SAFE_ASSISTANT_DIR . 'includes/class-safe-assistant-activator.php';
	Safe_Assistant_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-safe-assistant-deactivator.php
 */
function deactivate_safe_assistant()
{
	require_once SAFE_ASSISTANT_DIR . 'includes/class-safe-assistant-deactivator.php';
	Safe_Assistant_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_safe_assistant');
register_deactivation_hook(__FILE__, 'deactivate_safe_assistant');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require SAFE_ASSISTANT_DIR . 'includes/class-safe-assistant.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_safe_assistant()
{
	$plugin = new Safe_Assistant();
	$plugin->run();
}
run_safe_assistant();