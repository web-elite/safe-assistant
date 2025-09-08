<?php

/**
 * The main class for user importer add-on
 *
 * @package ADDON_USER_IMPORTER
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

class Addon_User_Importer
{
    /**
     * __construct
     * 
     * Instantiate the class
     *
     * @return void
     */
    function __construct()
    {
        $this->set_variables();
        $this->load_dependencies();
        $this->create_setting();
        add_action('wp_ajax_addon_user_importer_save_settings', [$this, 'save_setting_ajax']);
        add_action('wp_ajax_addon_user_importer_reverse', [$this, 'reverse_ajax']);
        add_action('wp_ajax_addon_user_importer_reset', [$this, 'reset_actions_ajax']);
    }

    /**
     * load_dependencies
     *
     * load dependencies for this class
     * 
     * @return void
     */
    private function load_dependencies()
    {
        require_once ADDON_USER_IMPORTER_DIR . 'addon-user-importer-handler.php';
    }

    /**
     * set_variables
     *
     * set variables for this class
     * 
     * @return void
     */
    public function set_variables()
    {
        define('ADDON_USER_IMPORTER_SLUG', 'sa-addon-user-importer');
        define('ADDON_USER_IMPORTER_CRON_EVENT', 'sa-addon-user-importer');
        define('ADDON_USER_IMPORTER_DIR', plugin_dir_path(__FILE__));
        define('ADDON_USER_IMPORTER_URL', plugin_dir_url(__FILE__));
        define('ADDONS_USER_IMPORTER_KEY', hash('sha256', ADDON_USER_IMPORTER_SLUG));
    }

    /**
     * activator
     * 
     * When plugin is activated, this function will be called.
     *
     * @return void
     */
    public function activator() {}

    /**
     * deactivator
     *
     * When plugin is deactivated, this function will be called.
     * 
     * @return void
     */
    public function deactivator()
    {
        delete_transient(ADDON_USER_IMPORTER_CRON_EVENT . '_task');
        wp_clear_scheduled_hook(ADDON_USER_IMPORTER_CRON_EVENT);
    }

    /**
     * create_setting
     * 
     * create setting for this plugin
     *
     * @return void
     */
    public function create_setting()
    {
        $prefix = SAFE_ASSISTANT_SLUG . '-settings';
        CSF::createSection($prefix, [
            'id'     => 'user_importer_addon',
            'title'  => __('User Importer', 'safe-assistant'),
            'icon'   => 'fas fa-user-plus',
        ]);

        // Main Section
        CSF::createSection($prefix, [
            'parent' => 'user_importer_addon',
            'title'  => __('Import', 'safe-assistant'),
            'icon'   => 'fas fa-file-alt',
            'fields' => [
                [
                    'type'     => 'callback',
                    'function'  => [$this, 'main_section_handler'],
                ],
            ],
        ]);

        // Log Section
        CSF::createSection($prefix, [
            'parent' => 'user_importer_addon',
            'title'  => __('Logs', 'safe-assistant'),
            'icon'   => 'fas fa-file-alt',
            'fields' => [
                [
                    'type'     => 'callback',
                    'function'  => [$this, 'display_logs'],
                ],
            ],
        ]);

        // Settings Section
        CSF::createSection($prefix, [
            'parent' => 'user_importer_addon',
            'title'  => __('Settings', 'safe-assistant'),
            'icon'   => 'fas fa-cog',
            'fields' => [
                [
                    'id'      => 'user_importer_sms_status',
                    'type'    => 'switcher',
                    'title'   => __('Sms Status', 'safe-assistant'),
                ],
                [
                    'id'      => 'user_importer_sms_pattern',
                    'type'    => 'text',
                    'title'   => __('Sms Pattern', 'safe-assistant'),
                ],
                [
                    'type'     => 'callback',
                    'function'  => [$this, 'settings_section_handler'],
                ],
            ],
        ]);
    }

    public function display_logs()
    {
        sa_display_logs(ADDON_USER_IMPORTER_SLUG);
    }

    public function settings_section_handler()
    {
        $reset_section_title = esc_html__('Reset Section', 'safe-assistant');
        $revert_button_text = esc_attr__('Revert All Operations', 'safe-assistant');
        $reset_button_text = esc_attr__('Reset Logs and Ongoing Tasks', 'safe-assistant');
        $force_stop_button_text = esc_html__('Force Stop Working', 'safe-assistant');

        $reverse_nonce = wp_create_nonce('addon_user_importer_reverse');
        $reset_nonce = wp_create_nonce('addon_user_importer_reset');
        $force_stop_url = esc_url(add_query_arg('force_stop', 1));

        echo <<<HTML
        <div class="postbox">
            <div class="inside">
            <h3>{$reset_section_title}</h3>

            <form id="addon_user_importer_reverse_form" method="post" class="addon-user-importer-action-form">
                <input type="hidden" name="action" value="addon_user_importer_reverse">
                <input type="hidden" name="nonce" value="{$reverse_nonce}">
                <p>
                <input type="submit" name="submit_reverse" class="button button-secondary" value="{$revert_button_text}" />
                <span class="spinner"></span>
                </p>
            </form>

            <form id="addon_user_importer_reset_form" method="post" class="addon-user-importer-action-form">
                <input type="hidden" name="action" value="addon_user_importer_reset">
                <input type="hidden" name="nonce" value="{$reset_nonce}">
                <p>
                <input type="submit" name="submit_reset_factory" class="button button-secondary" value="{$reset_button_text}" />
                <span class="spinner"></span>
                </p>
            </form>

            <p>
                <a href="{$force_stop_url}" class="button button-secondary">{$force_stop_button_text}</a>
            </p>
            </div>
        </div>
        <script>
            jQuery(document).ready(function($) {
                $('.addon-user-importer-action-form').on('submit', function(e) {
                    e.preventDefault();

                    var form = $(this);
                    var submitButton = form.find('input[type="submit"]');
                    var spinner = form.find('.spinner');

                    submitButton.prop('disabled', true);
                    spinner.addClass('is-active'); 

                    $.post(ajaxurl, form.serialize(), function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                    }).always(function() {
                    submitButton.prop('disabled', false);
                    spinner.removeClass('is-active');
                    });
                });
            });
        </script>
        HTML;
    }

    public function main_section_handler()
    {
        $continue_if_exists_value         = isset(get_transient('addon_user_importer_form_data')['continue_if_exists']) ? 1 : 0;
        $not_only_wallet_first_time_value = isset(get_transient('addon_user_importer_form_data')['not_only_wallet_first_time']) ? 1 : 0;
        $min_charge_value                 = isset(get_transient('addon_user_importer_form_data')['min_charge']) ? intval(get_transient('addon_user_importer_form_data')['min_charge']) : 0;
        $expire_date_value                = isset(get_transient('addon_user_importer_form_data')['expire_date']) ? intval(get_transient('addon_user_importer_form_data')['expire_date']) : 0;
        $sms_gateway                      = sa_get_option('sms_gateway', 'melipayamak');
        $sms_status                       = sa_get_option('sms_status', 0);
        $sms_username                     = sa_get_option('sms_username', '');
        $sms_password                     = sa_get_option('sms_password', '');
        $sms_pattern                      = sa_get_option('sms_pattern', '');
        $current_url = get_current_url();
        echo ob_get_clean();
        if (isset($_POST['submit_csv']) && isset($_FILES['csv_file'])) {
            $has_error = false;

            // Verify nonce
            if (!isset($_POST['addon_user_importer_nonce']) || !wp_verify_nonce($_POST['addon_user_importer_nonce'], 'addon_user_importer_upload')) {
                echo '<div class="error notice"><p><strong>' . esc_html__('Error:', 'safe-assistant') . '</strong> ' . esc_html__('Invalid nonce.', 'safe-assistant') . '</p></div>';
                $has_error = true;
            }

            // Check user permissions
            if (!current_user_can('manage_options')) {
                echo '<div class="error notice"><p><strong>' . esc_html__('Error:', 'safe-assistant') . '</strong> ' . esc_html__('Insufficient permissions.', 'safe-assistant') . '</p></div>';
                $has_error = true;
            }

            // Check if a task is already running
            if (get_transient(ADDON_USER_IMPORTER_CRON_EVENT . '_task')) {
                echo '<div class="notice notice-warning"><p><strong>' . esc_html__('Notice:', 'safe-assistant') . '</strong> ' . esc_html__('A process is currently running. Please wait until it completes.', 'safe-assistant') . '</p></div>';
                $has_error = true;
            }

            // Validate file upload
            if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK || $_FILES['csv_file']['type'] !== 'text/csv') {
                echo '<div class="error notice"><p><strong>' . esc_html__('Error:', 'safe-assistant') . '</strong> ' . esc_html__('Invalid or missing CSV file.', 'safe-assistant') . '</p></div>';
                $has_error = true;
            }

            if (!$has_error) {
                // Sanitize form inputs
                $continue_if_exists = isset($_POST['if_user_exist_continue']) ? 1 : 0;
                $not_only_wallet_first_time = isset($_POST['not_only_wallet_first_time']) ? 1 : 0;
                $min_charge = isset($_POST['min_charge']) ? intval($_POST['min_charge']) : 0;
                $expire_date = isset($_POST['expire_date']) ? intval($_POST['expire_date']) : 0;

                // Save form data to transient
                $form_data = [
                    'continue_if_exists' => $continue_if_exists,
                    'not_only_wallet_first_time' => $not_only_wallet_first_time,
                    'min_charge' => $min_charge,
                    'expire_date' => $expire_date
                ];
                set_transient('addon_user_importer_form_data', $form_data, 3600);

                // Use WP_Filesystem for file handling
                require_once ABSPATH . 'wp-admin/includes/file.php';
                WP_Filesystem();
                global $wp_filesystem;

                $upload_dir = wp_upload_dir();
                $temp_file_path = $upload_dir['path'] . '/' . uniqid('csv_') . '.csv';

                if ($wp_filesystem->move($_FILES['csv_file']['tmp_name'], $temp_file_path)) {
                    $task_data = [
                        'file_path' => $temp_file_path,
                        'offset' => 0,
                        'form_data' => $form_data
                    ];
                    set_transient(ADDON_USER_IMPORTER_CRON_EVENT . '_task', $task_data, 3600);
                    echo '<div class="updated notice"><p><strong>' . esc_html__('Success:', 'safe-assistant') . '</strong> ' . esc_html__('File processing started. Refresh the page to see updated logs.', 'safe-assistant') . '</p><p>' . esc_html__('File Path:', 'safe-assistant') . ' ' . esc_html($temp_file_path) . '</p></div>';
                } else {
                    echo '<div class="error notice"><p><strong>' . esc_html__('Error:', 'safe-assistant') . '</strong> ' . esc_html__('Unable to save file.', 'safe-assistant') . '</p></div>';
                }
            }
        }

        include_once 'partials/addon-user-importer-main-section.php';
    }

    /**
     * Reverse all plugin actions
     *
     * reverse wallet balance for the User Importer Addon.
     *
     * @since 1.0.0
     * @return bool True on success, false on failure
     */
    public function revert_wallet_charges(): bool
    {
        global $wpdb;

        function log_revert_action(string $message, string $status = 'info')
        {
            $log_file = get_log_file_path('revert');
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
     * Deletes logs, transients, and scheduled cron events for the User Importer Addon.
     *
     * @since 1.0.0
     * @return bool True on success, false on failure
     */
    public function reset_factory_settings(): bool
    {
        // Check user permissions
        if (!current_user_can('manage_options')) {
            sa_add_log(__('Insufficient permissions to reset plugin settings.', 'safe-assistant'), 'error', 'system');
            return false;
        }

        // Verify nonce if called via AJAX
        if (isset($_POST['nonce']) && !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'addon_user_importer_reset')) {
            sa_add_log(__('Invalid nonce for reset action.', 'safe-assistant'), 'error', 'system');
            return false;
        }

        // Initialize WP_Filesystem
        require_once ABSPATH . 'wp-admin/includes/file.php';
        WP_Filesystem();
        global $wp_filesystem;

        // Delete log file
        sa_clear_log(ADDON_USER_IMPORTER_SLUG);

        // Delete transients
        $transients = [
            ADDON_USER_IMPORTER_CRON_EVENT . '_task',
            'addon_user_importer_form_data',
        ];

        foreach ($transients as $transient) {
            if (delete_transient($transient)) {
                sa_add_log(sprintf(__('Transient %s deleted.', 'safe-assistant'), $transient), 'success', 'system');
            }
        }

        // Clear scheduled cron events
        $timestamp = wp_next_scheduled(ADDON_USER_IMPORTER_CRON_EVENT);
        if ($timestamp) {
            wp_unschedule_event($timestamp, ADDON_USER_IMPORTER_CRON_EVENT);
            sa_add_log(__('Scheduled cron event cleared.', 'safe-assistant'), 'success', 'system');
        }

        sa_add_log(__('Factory reset completed.', 'safe-assistant'), 'success', 'system');
        return true;
    }

    function reverse_ajax()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions.', 'safe-assistant')]);
        }

        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'addon_user_importer_reverse')) {
            wp_send_json_error(['message' => __('Invalid nonce.', 'safe-assistant')]);
        }

        $result = $this->revert_wallet_charges();
        if ($result) {
            wp_send_json_success(['message' => __('All operations reverted successfully.', 'safe-assistant')]);
        } else {
            wp_send_json_error(['message' => __('Failed to revert operations.', 'safe-assistant')]);
        }
    }

    function reset_actions_ajax()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions.', 'safe-assistant')]);
        }

        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'addon_user_importer_reset')) {
            wp_send_json_error(['message' => __('Invalid nonce.', 'safe-assistant')]);
        }

        $result = $this->reset_factory_settings();
        if ($result) {
            wp_send_json_success(['message' => __('Reset Actions successfully.', 'safe-assistant')]);
        } else {
            wp_send_json_error(['message' => __('Failed to reset actions.', 'safe-assistant')]);
        }
    }
}
