<?php
if (class_exists('Debug_Bar_Panel') && ! class_exists('WE_Update_Checker_Debug_Panel')) {

	class WE_Update_Checker_Debug_Panel extends Debug_Bar_Panel
	{

		public function init()
		{
			$this->title(__('Safe Assistant Updater Logs', 'safe-assistant'));
		}

		public function render()
		{
			echo '<div style="padding:10px;">';
			if (empty(WE_Update_Checker_Logger::$logs)) {
				echo '<p>No updater logs yet.</p>';
			} else {
				echo '<pre style="white-space:pre-wrap;">' . esc_html(implode("\n", WE_Update_Checker_Logger::$logs)) . '</pre>';
			}
			echo '</div>';
		}
	}
}

class WE_Update_Checker_Logger
{
	public static $logs = [];

	public static function log($message, $context = [])
	{
		$line = '[' . date('Y-m-d H:i:s') . '] ' . $message;
		if (! empty($context)) {
			$line .= ' | ' . wp_json_encode($context);
		}
		self::$logs[] = $line;

		if (! class_exists('Debug_Bar')) {
			error_log($line);
		}
	}
}

add_filter('debug_bar_panels', function ($panels) {
	if (class_exists('WE_Update_Checker_Debug_Panel')) {
		$panels[] = new WE_Update_Checker_Debug_Panel();
	}
	return $panels;
});
