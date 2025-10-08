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
        add_action('wp_ajax_clear_user_importer_results', [$this, 'clear_results_ajax']);
        add_action('wp_ajax_export_user_importer_results', [$this, 'export_results_ajax']);
        add_action('admin_init', [$this, 'submit_csv_ajax']);
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
        require_once ADDON_USER_IMPORTER_DIR . ADDON_USER_IMPORTER_SLUG . '-handler.php';
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
        define('ADDON_USER_IMPORTER_SLUG', 'user-importer');
        define('ADDON_USER_IMPORTER_NAME', 'user_importer');
        define('ADDON_USER_IMPORTER_DIR', plugin_dir_path(__FILE__));
        define('ADDON_USER_IMPORTER_URL', plugin_dir_url(__FILE__));
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
        delete_transient(ADDON_USER_IMPORTER_SLUG . '_task');
        wp_clear_scheduled_hook(ADDON_USER_IMPORTER_SLUG);
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
        CSF::createSection(SAFE_ASSISTANT_SETTING_ID, [
            'id'     => 'user_importer_addon',
            'title'  => __('User Importer', 'safe-assistant'),
            'icon'   => 'fas fa-user-plus',
        ]);

        // Main Section
        CSF::createSection(SAFE_ASSISTANT_SETTING_ID, [
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

        // Results Section
        CSF::createSection(SAFE_ASSISTANT_SETTING_ID, [
            'parent' => 'user_importer_addon',
            'title'  => __('Results', 'safe-assistant'),
            'icon'   => 'fas fa-chart-bar',
            'fields' => [
                [
                    'type'     => 'callback',
                    'function'  => [$this, 'display_results'],
                ],
            ],
        ]);

        // Log Section
        CSF::createSection(SAFE_ASSISTANT_SETTING_ID, [
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
        CSF::createSection(SAFE_ASSISTANT_SETTING_ID, [
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
                    'desc'    => __('Enter the SMS pattern ID to use for sending messages.', 'safe-assistant') . '<br>'
                        . __('Make sure the pattern includes below parameters:', 'safe-assistant') . '<br>'
                        . __('first parameter is buy date', 'safe-assistant') . ' (<code>buy_date</code>)' . '<br>'
                        . __('second parameter is charge amount', 'safe-assistant') . ' (<code>charge_amount</code>)' . '<br>'
                        . __('third parameter is expire date.', 'safe-assistant') . ' (<code>expire_date</code>)' . '<br>',
                ],
                [
                    'id'      => 'user_importer_batch_size',
                    'type'    => 'number',
                    'title'   => __('Batch Size', 'safe-assistant'),
                    'desc'    => __('Number of rows to process per minute (default: 20)', 'safe-assistant'),
                    'default' => 20,
                    'unit'    => __('rows/minute', 'safe-assistant'),
                    'min'     => 1,
                    'max'     => 1000,
                ],
                [
                    'id'      => 'user_importer_save_results',
                    'type'    => 'switcher',
                    'title'   => __('Save Processing Results', 'safe-assistant'),
                    'desc'    => __('Save detailed processing statistics for review', 'safe-assistant'),
                    'default' => true,
                ],
                [
                    'type'     => 'callback',
                    'function'  => [$this, 'settings_section_handler'],
                ],
            ],
        ]);
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

        include_once ADDON_USER_IMPORTER_DIR . 'partials/user-importer-settings-section.php';
    }

    public function main_section_handler()
    {
        $continue_if_exists_value         = isset(get_transient('addon_user_importer_form_data')['continue_if_exists']) ? 1 : 0;
        $not_only_wallet_first_time_value = isset(get_transient('addon_user_importer_form_data')['not_only_wallet_first_time']) ? 1 : 0;
        $min_charge_value                 = isset(get_transient('addon_user_importer_form_data')['min_charge']) ? intval(get_transient('addon_user_importer_form_data')['min_charge']) : 0;
        $expire_date_value                = isset(get_transient('addon_user_importer_form_data')['expire_date']) ? intval(get_transient('addon_user_importer_form_data')['expire_date']) : 0;
        $current_url = get_current_url();
        echo ob_get_clean();
        include_once ADDON_USER_IMPORTER_DIR . 'partials/user-importer-main-section.php';
    }

    public function display_results()
    {
        $results = get_option('user_importer_results', []);
        $current_task = get_transient(ADDON_USER_IMPORTER_SLUG . '_task');
        $is_running = get_transient(ADDON_USER_IMPORTER_SLUG . '_running');

        echo '<div class="user-importer-results-wrapper">';
        
        // Current Status
        echo '<div class="postbox">';
        echo '<h3 class="hndle">' . esc_html__('Current Processing Status', 'safe-assistant') . '</h3>';
        echo '<div class="inside">';
        
        if ($current_task) {
            $progress = 0;
            if (isset($current_task['total_rows']) && $current_task['total_rows'] > 0) {
                $progress = ($current_task['offset'] / $current_task['total_rows']) * 100;
            }
            
            echo '<div class="processing-status processing">';
            echo '<div class="status-indicator">';
            echo '<span class="circle-status pulse ' . ($is_running ? 'green' : 'yellow') . '"></span>';
            echo '<strong>' . ($is_running ? esc_html__('Processing...', 'safe-assistant') : esc_html__('Queued', 'safe-assistant')) . '</strong>';
            echo '</div>';
            echo '<div class="progress-info">';
            echo '<p>' . sprintf(esc_html__('Progress: %d/%d rows (%.1f%%)', 'safe-assistant'), 
                $current_task['offset'], 
                $current_task['total_rows'] ?? 0, 
                $progress) . '</p>';
            if (isset($current_task['file_path'])) {
                echo '<p><small>' . esc_html__('File:', 'safe-assistant') . ' ' . esc_html(basename($current_task['file_path'])) . '</small></p>';
            }
            echo '</div>';
            echo '</div>';
        } else {
            echo '<div class="processing-status idle">';
            echo '<div class="status-indicator">';
            echo '<span class="circle-status gray"></span>';
            echo '<strong>' . esc_html__('Idle', 'safe-assistant') . '</strong>';
            echo '</div>';
            echo '<p>' . esc_html__('No processing task running', 'safe-assistant') . '</p>';
            echo '</div>';
        }
        
        echo '</div>';
        echo '</div>';

        // Processing History
        if (!empty($results)) {
            echo '<div class="postbox">';
            echo '<h3 class="hndle">' . esc_html__('Processing History', 'safe-assistant') . '</h3>';
            echo '<div class="inside">';
            
            echo '<div class="results-controls">';
            echo '<button type="button" class="button" id="clear-results">' . esc_html__('Clear History', 'safe-assistant') . '</button>';
            echo '<button type="button" class="button" id="export-results">' . esc_html__('Export Results', 'safe-assistant') . '</button>';
            echo '</div>';

            // Sort results by date (newest first)
            usort($results, function($a, $b) {
                return strtotime($b['completed_at']) - strtotime($a['completed_at']);
            });

            echo '<div class="results-grid">';
            foreach (array_slice($results, 0, 10) as $index => $result) {
                $this->render_result_card($result, $index);
            }
            echo '</div>';
            
            if (count($results) > 10) {
                echo '<p class="show-more-results">';
                echo '<button type="button" class="button" id="show-all-results">' . 
                     sprintf(esc_html__('Show all %d results', 'safe-assistant'), count($results)) . '</button>';
                echo '</p>';
            }
            
            echo '</div>';
            echo '</div>';
        } else {
            echo '<div class="postbox">';
            echo '<h3 class="hndle">' . esc_html__('Processing History', 'safe-assistant') . '</h3>';
            echo '<div class="inside">';
            echo '<div class="no-results">';
            echo '<p>' . esc_html__('No processing results available yet.', 'safe-assistant') . '</p>';
            echo '<p><em>' . esc_html__('Results will appear here after completing CSV imports.', 'safe-assistant') . '</em></p>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }
        
        echo '</div>';

        // Add JavaScript for interactive features
        $this->add_results_javascript();
    }

    private function render_result_card($result, $index)
    {
        $status_class = $result['status'] ?? 'completed';
        $success_rate = 0;
        if (isset($result['stats']['total_processed']) && $result['stats']['total_processed'] > 0) {
            $success_rate = (($result['stats']['users_created'] + $result['stats']['users_updated']) / $result['stats']['total_processed']) * 100;
        }

        echo '<div class="result-card ' . esc_attr($status_class) . '">';
        echo '<div class="result-header">';
        echo '<h4>' . sprintf(esc_html__('Import #%d', 'safe-assistant'), $index + 1) . '</h4>';
        echo '<span class="result-date">' . esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($result['started_at']))) . '</span>';
        echo '</div>';
        
        echo '<div class="result-stats">';
        echo '<div class="stat-item">';
        echo '<span class="stat-label">' . esc_html__('Total Rows:', 'safe-assistant') . '</span>';
        echo '<span class="stat-value">' . esc_html($result['stats']['total_processed'] ?? 0) . '</span>';
        echo '</div>';
        
        echo '<div class="stat-item">';
        echo '<span class="stat-label">' . esc_html__('Users Created:', 'safe-assistant') . '</span>';
        echo '<span class="stat-value success">' . esc_html($result['stats']['users_created'] ?? 0) . '</span>';
        echo '</div>';
        
        echo '<div class="stat-item">';
        echo '<span class="stat-label">' . esc_html__('Users Updated:', 'safe-assistant') . '</span>';
        echo '<span class="stat-value info">' . esc_html($result['stats']['users_updated'] ?? 0) . '</span>';
        echo '</div>';
        
        echo '<div class="stat-item">';
        echo '<span class="stat-label">' . esc_html__('Wallets Charged:', 'safe-assistant') . '</span>';
        echo '<span class="stat-value warning">' . esc_html($result['stats']['wallets_charged'] ?? 0) . '</span>';
        echo '</div>';
        
        echo '<div class="stat-item">';
        echo '<span class="stat-label">' . esc_html__('SMS Sent:', 'safe-assistant') . '</span>';
        echo '<span class="stat-value info">' . esc_html($result['stats']['sms_sent'] ?? 0) . '</span>';
        echo '</div>';
        
        echo '<div class="stat-item">';
        echo '<span class="stat-label">' . esc_html__('Errors:', 'safe-assistant') . '</span>';
        echo '<span class="stat-value error">' . esc_html($result['stats']['errors'] ?? 0) . '</span>';
        echo '</div>';
        
        echo '</div>';
        
        echo '<div class="result-progress">';
        echo '<div class="progress-bar">';
        echo '<div class="progress-fill" style="width: ' . esc_attr($success_rate) . '%"></div>';
        echo '</div>';
        echo '<span class="progress-text">' . sprintf(esc_html__('Success Rate: %.1f%%', 'safe-assistant'), $success_rate) . '</span>';
        echo '</div>';
        
        if (isset($result['duration'])) {
            echo '<div class="result-footer">';
            echo '<small>' . sprintf(esc_html__('Duration: %s', 'safe-assistant'), $this->format_duration($result['duration'])) . '</small>';
            echo '</div>';
        }
        
        echo '</div>';
    }

    private function format_duration($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;
        
        if ($hours > 0) {
            return sprintf('%dh %dm %ds', $hours, $minutes, $seconds);
        } elseif ($minutes > 0) {
            return sprintf('%dm %ds', $minutes, $seconds);
        } else {
            return sprintf('%ds', $seconds);
        }
    }

    private function add_results_javascript()
    {
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Clear results
            $('#clear-results').on('click', function() {
                if (confirm('<?php echo esc_js(__('Are you sure you want to clear all processing history?', 'safe-assistant')); ?>')) {
                    $.post(ajaxurl, {
                        action: 'clear_user_importer_results',
                        nonce: '<?php echo wp_create_nonce('clear_results_nonce'); ?>'
                    }, function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('<?php echo esc_js(__('Failed to clear results', 'safe-assistant')); ?>');
                        }
                    });
                }
            });
            
            // Export results
            $('#export-results').on('click', function() {
                window.open('<?php echo admin_url('admin-ajax.php?action=export_user_importer_results&nonce=' . wp_create_nonce('export_results_nonce')); ?>');
            });
            
            // Show all results
            $('#show-all-results').on('click', function() {
                $('.result-card:hidden').show();
                $(this).hide();
            });
        });
        </script>
        <?php
    }

    public function display_logs()
    {
        echo '<div id="sa-logs-wrapper">';
        echo '<div class="sa-logs-filters">';
        echo '<label for="sa-logs-status">' . esc_html__('Status:', 'safe-assistant') . '</label>';
        echo '<select id="sa-logs-status">';
        echo '<option value="">' . esc_html__('All Statuses', 'safe-assistant') . '</option>';
        echo '<option value="info">' . esc_html__('Info', 'safe-assistant') . '</option>';
        echo '<option value="success">' . esc_html__('Success', 'safe-assistant') . '</option>';
        echo '<option value="warning">' . esc_html__('Warning', 'safe-assistant') . '</option>';
        echo '<option value="error">' . esc_html__('Error', 'safe-assistant') . '</option>';
        echo '</select>';
        
        echo '<label for="sa-logs-per-page">' . esc_html__('Per Page:', 'safe-assistant') . '</label>';
        echo '<select id="sa-logs-per-page">';
        echo '<option value="10">10</option>';
        echo '<option value="20" selected>20</option>';
        echo '<option value="50">50</option>';
        echo '<option value="100">100</option>';
        echo '</select>';
        
        echo '<button type="button" class="button" id="sa-logs-refresh">' . esc_html__('Refresh', 'safe-assistant') . '</button>';
        echo '</div>';
        
        echo '<div id="sa-logs-container">';
        echo sa_render_logs_paginated(ADDON_USER_IMPORTER_SLUG);
        echo '</div>';
        echo '</div>';
    }

    public function submit_csv_ajax()
    {
        if (isset($_POST['addon_user_importer_action']) && $_POST['addon_user_importer_action'] === 'upload_csv' && isset($_FILES['csv_file'])) {
            $has_error = false;

            // Verify nonce
            if (!isset($_POST['addon_user_importer_nonce']) || !wp_verify_nonce($_POST['addon_user_importer_nonce'], 'addon_user_importer_upload')) {
                $result = ([
                    'message' => esc_html__('Error:', 'safe-assistant') . ' '
                        . esc_html__('Invalid nonce.', 'safe-assistant')
                ]);
                $has_error = true;
            }

            // Check user permissions
            if (!current_user_can('manage_options')) {
                $result = ([
                    'message' => esc_html__('Error:', 'safe-assistant') . ' '
                        . esc_html__('Insufficient permissions.', 'safe-assistant')
                ]);
                $has_error = true;
            }

            // Check if a task is already running
            if (get_transient(ADDON_USER_IMPORTER_SLUG . '_task')) {
                $result = ([
                    'message' => esc_html__('Error:', 'safe-assistant') . ' '
                        . esc_html__('A process is currently running. Please wait until it completes.', 'safe-assistant')
                ]);
                $has_error = true;
            }

            // Validate file upload
            if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK || $_FILES['csv_file']['type'] !== 'text/csv') {
                $result = ([
                    'message' => esc_html__('Error:', 'safe-assistant') . ' '
                        . esc_html__('Invalid or missing CSV file.', 'safe-assistant')
                ]);
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
                set_transient('addon_user_importer_form_data', $form_data);

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
                    set_transient(ADDON_USER_IMPORTER_SLUG . '_task', $task_data, 12 * HOUR_IN_SECONDS);
                    $result = ([
                        'title'   => esc_html__('User import is in progress in the background.', 'safe-assistant'),
                        'message' => esc_html__('File Path:', 'safe-assistant') . ' '
                            . esc_html($temp_file_path),
                        'status'  => 'success',
                    ]);
                } else {
                    $result = ([
                        'message' => esc_html__('Error:', 'safe-assistant') . ' '
                            . esc_html__('Unable to save file.', 'safe-assistant')
                    ]);
                }
            }

            if (isset($result['message'])) {
                sa_create_notif($result['message'], $result['title'] ?? 'خطا', $result['status'] ?? 'error');
            }
        }
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

        $transactions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}nirweb_wallet_op 
             WHERE description = %s AND type_op = %s AND type_v = %s",
                __("Wallet charge for in-person buyers", 'safe-assistant'),
                "credit",
                "register"
            )
        );

        if (empty($transactions)) {
            sa_log(
                ADDON_USER_IMPORTER_SLUG,
                'warning',
                __('Revert Wallet Charges', 'safe-assistant'),
                __('No transactions found for refund process', 'safe-assistant')
            );
            return false;
        }

        $reverted_count = 0;

        foreach ($transactions as $transaction) {
            $user_id = $transaction->user_id;
            $amount = floatval($transaction->amount);

            $current_balance = get_user_meta($user_id, 'nirweb_wallet_balance', true);
            $current_balance = $current_balance ? floatval($current_balance) : 0;

            if ($current_balance < $amount) {
                sa_log(
                    ADDON_USER_IMPORTER_SLUG,
                    'error',
                    __('Revert Wallet Charges', 'safe-assistant'),
                    sprintf(
                        __('Insufficient balance for user %1$s. Current: %2$s, Required: %3$s', 'safe-assistant'),
                        $user_id,
                        $current_balance,
                        $amount
                    )
                );
                continue;
            }

            $new_balance = $current_balance - $amount;
            update_user_meta($user_id, 'nirweb_wallet_balance', $new_balance);

            $wpdb->insert($wpdb->prefix . "nirweb_wallet_op", [
                "user_id"      => $user_id,
                "user_created" => 0,
                "amount"       => -$amount,
                "description"  => __("Refund of incorrect wallet charge", 'safe-assistant'),
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

            sa_log(
                ADDON_USER_IMPORTER_SLUG,
                'success',
                __('Revert Wallet Charges', 'safe-assistant'),
                sprintf(
                    __('Amount %1$s deducted from user %2$s wallet. Reverse transaction recorded.', 'safe-assistant'),
                    $amount,
                    $user_id
                )
            );
            $reverted_count++;
        }

        sa_log(
            ADDON_USER_IMPORTER_SLUG,
            'success',
            __('Revert Wallet Charges', 'safe-assistant'),
            sprintf(
                __('Refund process completed successfully. %d transactions processed.', 'safe-assistant'),
                $reverted_count
            )
        );

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
            sa_log(ADDON_USER_IMPORTER_SLUG, 'error', __('Insufficient permissions to reset plugin settings.', 'safe-assistant'));
            return false;
        }

        // Verify nonce if called via AJAX
        if (isset($_POST['nonce']) && !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'addon_user_importer_reset')) {
            sa_log(ADDON_USER_IMPORTER_SLUG, 'error', __('Invalid nonce for reset action.', 'safe-assistant'));
            return false;
        }

        // Initialize WP_Filesystem
        require_once ABSPATH . 'wp-admin/includes/file.php';
        WP_Filesystem();
        global $wp_filesystem;

        // Delete transients
        $transients = [
            ADDON_USER_IMPORTER_SLUG . '_task',
            ADDON_USER_IMPORTER_SLUG . '_running',
            'addon_user_importer_form_data',
        ];

        foreach ($transients as $transient) {
            if (delete_transient($transient)) {
                sa_log(ADDON_USER_IMPORTER_SLUG, 'success', sprintf(__('Transient %s deleted.', 'safe-assistant'), $transient));
            }
        }

        // Clear scheduled cron events
        $timestamp = wp_next_scheduled(ADDON_USER_IMPORTER_SLUG);
        if ($timestamp) {
            wp_unschedule_event($timestamp, ADDON_USER_IMPORTER_SLUG);
            sa_log(ADDON_USER_IMPORTER_SLUG, 'success', __('Scheduled cron event cleared.', 'safe-assistant'));
        }

        sa_log(ADDON_USER_IMPORTER_SLUG, 'success', __('Factory reset completed.', 'safe-assistant'));
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

    function clear_results_ajax()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions.', 'safe-assistant')]);
        }

        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'clear_results_nonce')) {
            wp_send_json_error(['message' => __('Invalid nonce.', 'safe-assistant')]);
        }

        delete_option('user_importer_results');
        sa_log(ADDON_USER_IMPORTER_SLUG, 'info', __('Processing results history cleared by user', 'safe-assistant'));
        
        wp_send_json_success(['message' => __('Results history cleared successfully.', 'safe-assistant')]);
    }

    function export_results_ajax()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'safe-assistant'));
        }

        if (!isset($_GET['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['nonce'])), 'export_results_nonce')) {
            wp_die(__('Invalid nonce.', 'safe-assistant'));
        }

        $results = get_option('user_importer_results', []);
        
        if (empty($results)) {
            wp_die(__('No results to export.', 'safe-assistant'));
        }

        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=user-importer-results-' . date('Y-m-d-H-i-s') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Create file pointer
        $output = fopen('php://output', 'w');

        // Add BOM for UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // CSV headers
        fputcsv($output, [
            __('Import ID', 'safe-assistant'),
            __('Started At', 'safe-assistant'),
            __('Completed At', 'safe-assistant'),
            __('Duration (seconds)', 'safe-assistant'),
            __('Status', 'safe-assistant'),
            __('Total Processed', 'safe-assistant'),
            __('Users Created', 'safe-assistant'),
            __('Users Updated', 'safe-assistant'),
            __('Wallets Charged', 'safe-assistant'),
            __('SMS Sent', 'safe-assistant'),
            __('Errors', 'safe-assistant'),
            __('Success Rate (%)', 'safe-assistant'),
        ]);

        // Add data rows
        foreach ($results as $index => $result) {
            $success_rate = 0;
            if (isset($result['stats']['total_processed']) && $result['stats']['total_processed'] > 0) {
                $success_rate = round((($result['stats']['users_created'] + $result['stats']['users_updated']) / $result['stats']['total_processed']) * 100, 2);
            }

            fputcsv($output, [
                $index + 1,
                $result['started_at'],
                $result['completed_at'] ?? '',
                $result['duration'] ?? 0,
                $result['status'] ?? 'completed',
                $result['stats']['total_processed'] ?? 0,
                $result['stats']['users_created'] ?? 0,
                $result['stats']['users_updated'] ?? 0,
                $result['stats']['wallets_charged'] ?? 0,
                $result['stats']['sms_sent'] ?? 0,
                $result['stats']['errors'] ?? 0,
                $success_rate,
            ]);
        }

        fclose($output);
        exit;
    }
}
