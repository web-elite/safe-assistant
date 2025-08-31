<?php

defined('ABSPATH') || exit;

/**
 * The main class for user importer add-on
 *
 *  
 *
 * @package ADDON_USER_IMPORTER
 * @since 1.0.0
 */

defined('ABSPATH') || exit;
define('ADDON_USER_IMPORTER_SLUG', 'sa-addon-user-importer');
define('ADDON_USER_IMPORTER_CRON_EVENT', 'sa-addon-user-importer');
define('ADDON_USER_IMPORTER_DIR', plugin_dir_path(__FILE__));
define('ADDON_USER_IMPORTER_URL', plugin_dir_url(__FILE__));

class Addon_User_Importer_Main
{
    /**
     * Database table name for settings
     *
     * @var string
     */
    private $table_name;

    function __construct()
    {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'addon-user-importer-settings';
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_ADDON_USER_IMPORTER_save_settings', [$this, 'save_setting_ajax']);
        add_action('wp_ajax_ADDON_USER_IMPORTER_reverse', [$this, 'reverse_ajax']);
        add_action('wp_ajax_ADDON_USER_IMPORTER_reset', [$this, 'reset_actions_ajax']);

        $prefix = SAFE_ASSISTANT_SLUG . '-settings';
        CSF::createSection($prefix, [
            [
                'title'  => __('Addons', 'safe-assistant'),
                'icon'   => 'fas fa-plug',
                'fields' => [
                    [
                        'id'      => 'de',
                        'type'    => 'switcher',
                        'title'   => __('Enable User Importer Addon', 'safe-assistant'),
                        'default' => false,
                        'desc'    => __('Enable or disable User Importer Addon.', 'safe-assistant'),
                    ],
                ]
            ],
        ]);
    }

    public function enqueue_scripts()
    {
        wp_enqueue_scripts(ADDON_USER_IMPORTER_SLUG . '-style', ADDON_USER_IMPORTER_URL . '/css/addon-user-importer-style.css');
        wp_enqueue_script(ADDON_USER_IMPORTER_SLUG . '-tailwindcdn', 'https://cdn.tailwindcss.com', [], null, true);
        wp_add_inline_script(ADDON_USER_IMPORTER_SLUG . '-tailwindcdn', 'tailwind.config={corePlugins:{preflight:false}};', 'before');
    }

    /**
     * fallback function for admin menu content.
     *
     * @since    1.0.0
     */
    public function main_page()
    {
        ob_start();
        $form_data = get_transient('ADDON_USER_IMPORTER_form_data') ?: [];
        $min_charge_value = isset($form_data['min_charge']) ? intval($form_data['min_charge']) : '';
        $expire_date_value = isset($form_data['expire_date']) ? intval($form_data['expire_date']) : '';
        $not_only_wallet_first_time_value = isset($form_data['not_only_wallet_first_time']) ? 1 : '';
        $continue_if_exists_value = isset($form_data['continue_if_exists']) ? 1 : '';

        $sms_username = $this->get_setting('sms_username', '');
        $sms_password = $this->get_setting('sms_password', '');
        $sms_pattern = $this->get_setting('sms_pattern', '');

        global $wp;
        $current_url = home_url(add_query_arg(array(), $wp->request)) . $_SERVER['REQUEST_URI'];

        include_once plugin_dir_path(__FILE__) .
            "/partials/addon-user-importer-main-page.php";
        ob_get_clean();
    }

    /**
     * Create settings table
     *
     * Creates the database table for storing settings if it doesn't exist.
     *
     * @since 1.0.0
     */
    public function create_tables()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $this->table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            setting_key varchar(100) NOT NULL,
            setting_value longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY setting_key (setting_key)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Save a setting
     *
     * Saves or updates a setting in the database, with optional encryption for passwords.
     *
     * @param string $key Setting key
     * @param mixed $value Setting value
     * @return bool True on success, false on failure
     * @since 1.0.0
     */
    public function save_setting(string $key, $value): bool
    {
        global $wpdb;

        if (empty($key)) {
            return false;
        }

        // Sanitize input
        $key = sanitize_text_field($key);
        $value = is_array($value) || is_object($value) ? wp_json_encode($value, JSON_UNESCAPED_UNICODE) : wp_kses_post($value);

        // Encrypt password fields
        if (str_contains($key, 'password')) {
            if (!defined('WE_ENCRYPTE_KEY') || empty(WE_ENCRYPTE_KEY)) {
                add_to_csv_log(__('❌ Error: Encryption key not defined for password.', 'we-user-importer'), 'error');
                return false;
            }
            try {
                $iv = random_bytes(16);
                $encrypted = openssl_encrypt($value, 'AES-256-CBC', WE_ENCRYPTE_KEY, 0, $iv);
                if (false === $encrypted) {
                    add_to_csv_log(__('❌ Error: Failed to encrypt password.', 'we-user-importer'), 'error');
                    return false;
                }
                $value = base64_encode($iv . $encrypted);
            } catch (Exception $e) {
                add_to_csv_log(sprintf(__('❌ Error: Encryption failed: %s', 'we-user-importer'), $e->getMessage()), 'error');
                return false;
            }
        }

        $result = $wpdb->replace(
            $this->table_name,
            [
                'setting_key' => $key,
                'setting_value' => $value
            ],
            ['%s', '%s']
        );

        if (false === $result) {
            add_to_csv_log(sprintf(__('❌ Error: Failed to save setting %s.', 'we-user-importer'), $key), 'error');
        }

        return false !== $result;
    }

    /**
     * Retrieve a setting
     *
     * Retrieves a setting from the database, with decryption for password fields.
     *
     * @param string $key Setting key
     * @param mixed $default Default value if setting not found
     * @return mixed Setting value or default
     * @since 1.0.0
     */
    public function get_setting(string $key, $default = null)
    {
        global $wpdb;

        $value = $wpdb->get_var(
            $wpdb->prepare("SELECT setting_value FROM {$this->table_name} WHERE setting_key = %s", $key)
        );

        if (null === $value) {
            return $default;
        }

        // Decode JSON if applicable
        $json_value = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $json_value;
        }

        // Decrypt password fields
        if (str_contains($key, 'password')) {
            if (!defined('WE_ENCRYPTE_KEY') || empty(WE_ENCRYPTE_KEY)) {
                add_to_csv_log(__('❌ Error: Encryption key not defined for password decryption.', 'we-user-importer'), 'error');
                return $default;
            }
            try {
                $data = base64_decode($value);
                if (false === $data) {
                    add_to_csv_log(__('❌ Error: Failed to decode password.', 'we-user-importer'), 'error');
                    return $default;
                }
                $iv = substr($data, 0, 16);
                $encrypted = substr($data, 16);
                $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', WE_ENCRYPTE_KEY, 0, $iv);
                if (false === $decrypted) {
                    add_to_csv_log(__('❌ Error: Failed to decrypt password.', 'we-user-importer'), 'error');
                    return $default;
                }
                return $decrypted;
            } catch (Exception $e) {
                add_to_csv_log(sprintf(__('❌ Error: Decryption failed: %s', 'we-user-importer'), $e->getMessage()), 'error');
                return $default;
            }
        }

        return $value;
    }

    /**
     * Delete a setting
     *
     * Deletes a setting from the database.
     *
     * @param string $key Setting key
     * @return bool True on success, false on failure
     * @since 1.0.0
     */
    public function delete_setting(string $key): bool
    {
        global $wpdb;

        $result = $wpdb->delete(
            $this->table_name,
            ['setting_key' => sanitize_text_field($key)],
            ['%s']
        );

        if (false === $result) {
            add_to_csv_log(sprintf(__('❌ Error: Failed to delete setting %s.', 'we-user-importer'), $key), 'error');
        }

        return false !== $result;
    }

    /**
     * Get all settings
     *
     * Retrieves all settings from the database.
     *
     * @return array Associative array of settings
     * @since 1.0.0
     */
    public function get_all_settings(): array
    {
        global $wpdb;

        $results = $wpdb->get_results("SELECT setting_key, setting_value FROM {$this->table_name}", ARRAY_A);
        $settings = [];

        foreach ($results as $row) {
            $value = $row['setting_value'];
            $json_value = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $settings[$row['setting_key']] = $json_value;
            } elseif (str_contains($row['setting_key'], 'password')) {
                $settings[$row['setting_key']] = $this->get_setting($row['setting_key'], '');
            } else {
                $settings[$row['setting_key']] = $value;
            }
        }

        return $settings;
    }

    /**
     * Drop settings table
     *
     * Drops the settings database table.
     *
     * @since 1.0.0
     */
    public function drop_tables()
    {
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$this->table_name}");
        add_to_csv_log(__('✅ Success: Settings table dropped.', 'we-user-importer'), 'success');
    }

    /**
     * Reverse all plugin actions
     *
     * reverse wallet balance for the WE User Importer plugin.
     *
     * @since 1.0.0
     * @return bool True on success, false on failure
     */
    public function revert_wallet_charges(): bool
    {
        global $wpdb;

        function log_revert_action(string $message, string $status = 'info')
        {
            $log_file = WP_CONTENT_DIR . '/wallet_revert.log';
            $log_line = sprintf("[%s] %s: %s\n", current_time('mysql'), strtoupper($status), $message);
            file_put_contents($log_file, $log_line, FILE_APPEND | LOCK_EX);
        }

        $transactions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}nirweb_wallet_op 
             WHERE description = %s AND type_op = %s AND type_v = %s",
                "شارژ کیف پول برای خریداران حضوری",
                "credit",
                "register"
            )
        );

        if (empty($transactions)) {
            log_revert_action("❌ هیچ تراکنشی با توضیحات 'شارژ کیف پول برای خریداران حضوری' یافت نشد.", "warning");
            return false;
        }

        $reverted_count = 0;

        foreach ($transactions as $transaction) {
            $user_id = $transaction->user_id;
            $amount = floatval($transaction->amount);

            $current_balance = get_user_meta($user_id, 'nirweb_wallet_balance', true);
            $current_balance = $current_balance ? floatval($current_balance) : 0;

            if ($current_balance < $amount) {
                log_revert_action("❌ موجودی کاربر $user_id ($current_balance) برای کسر $amount کافی نیست.", "error");
                continue;
            }

            $new_balance = $current_balance - $amount;
            update_user_meta($user_id, 'nirweb_wallet_balance', $new_balance);

            $wpdb->insert($wpdb->prefix . "nirweb_wallet_op", [
                "user_id"      => $user_id,
                "user_created" => 0,
                "amount"       => -$amount,
                "description"  => "بازگشت شارژ حساب برای خریداران حضوری",
                "type_op"      => "debit",
                "type_v"       => "revert",
                "created"      => current_time("mysql"),
            ]);

            $wpdb->delete(
                $wpdb->prefix . "nirweb_wallet_cashback",
                [
                    "user_id" => $user_id,
                    "amount"  => $amount,
                    "order_id" => 0,
                ]
            );

            log_revert_action("✅ شارژ $amount از کیف پول کاربر $user_id کسر شد و تراکنش معکوس ثبت شد.", "success");
            $reverted_count++;
        }

        log_revert_action("🎉 عملیات بازگشت برای $reverted_count تراکنش با موفقیت انجام شد.", "success");
        return true;
    }

    /**
     * Reset all plugin data to factory settings
     *
     * Deletes logs, transients, and scheduled cron events for the WE User Importer plugin.
     *
     * @since 1.0.0
     * @return bool True on success, false on failure
     */
    public function reset_factory_settings(): bool
    {
        // Check user permissions
        if (!current_user_can('manage_options')) {
            add_to_csv_log(__('❌ Error: Insufficient permissions to reset plugin settings.', 'we-user-importer'), 'error');
            return false;
        }

        // Verify nonce if called via AJAX
        if (isset($_POST['nonce']) && !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'ADDON_USER_IMPORTER_reset')) {
            add_to_csv_log(__('❌ Error: Invalid nonce for reset action.', 'we-user-importer'), 'error');
            return false;
        }

        // Initialize WP_Filesystem
        require_once ABSPATH . 'wp-admin/includes/file.php';
        WP_Filesystem();
        global $wp_filesystem;

        // Delete log file
        $log_file = WP_CONTENT_DIR . '/csv_import.log';
        if ($wp_filesystem->exists($log_file)) {
            if (!$wp_filesystem->delete($log_file)) {
                add_to_csv_log(sprintf(__('❌ Error: Failed to delete log file: %s', 'we-user-importer'), $log_file), 'error');
                return false;
            }
            add_to_csv_log(__('✅ Success: Log file deleted.', 'we-user-importer'), 'success');
        }

        // Delete transients
        $transients = [
            ADDON_USER_IMPORTER_CRON_EVENT . '_task',
            'ADDON_USER_IMPORTER_form_data',
        ];

        foreach ($transients as $transient) {
            if (delete_transient($transient)) {
                add_to_csv_log(sprintf(__('✅ Success: Transient %s deleted.', 'we-user-importer'), $transient), 'success');
            }
        }

        // Clear scheduled cron events
        $timestamp = wp_next_scheduled(ADDON_USER_IMPORTER_CRON_EVENT);
        if ($timestamp) {
            wp_unschedule_event($timestamp, ADDON_USER_IMPORTER_CRON_EVENT);
            add_to_csv_log(__('✅ Success: Scheduled cron event cleared.', 'we-user-importer'), 'success');
        }

        add_to_csv_log(__('🎉 Success: Factory reset completed.', 'we-user-importer'), 'success');
        return true;
    }

    function save_setting_ajax()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions.', 'we-user-importer')]);
        }

        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'ADDON_USER_IMPORTER_settings')) {
            wp_send_json_error(['message' => __('Invalid nonce.', 'we-user-importer')]);
        }

        $fields = ['sms_gateway', 'sms_username', 'sms_password', 'sms_pattern', 'sms_status'];

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $this->save_setting($field, sanitize_text_field(wp_unslash($_POST[$field])));
            }
        }

        wp_send_json_success(['message' => __('Settings saved successfully.', 'we-user-importer')]);
    }

    function reverse_ajax()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions.', 'we-user-importer')]);
        }

        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'ADDON_USER_IMPORTER_reverse')) {
            wp_send_json_error(['message' => __('Invalid nonce.', 'we-user-importer')]);
        }

        $result = $this->revert_wallet_charges();
        if ($result) {
            wp_send_json_success(['message' => __('All operations reverted successfully.', 'we-user-importer')]);
        } else {
            wp_send_json_error(['message' => __('Failed to revert operations.', 'we-user-importer')]);
        }
    }

    function reset_actions_ajax()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions.', 'we-user-importer')]);
        }

        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'ADDON_USER_IMPORTER_reset')) {
            wp_send_json_error(['message' => __('Invalid nonce.', 'we-user-importer')]);
        }

        $result = $this->reset_factory_settings();
        if ($result) {
            wp_send_json_success(['message' => __('Reset Actions successfully.', 'we-user-importer')]);
        } else {
            wp_send_json_error(['message' => __('Failed to reset actions.', 'we-user-importer')]);
        }
    }
}
