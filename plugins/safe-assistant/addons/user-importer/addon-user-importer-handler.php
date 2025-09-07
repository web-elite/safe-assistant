<?php

/**
 * Backend functions for WE User Importer plugin
 *
 * @package ADDON_USER_IMPORTER
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

require_once SAFE_ASSISTANT_DIR . 'lib/jdf.php';

/**
 * Add message to CSV processing logs
 *
 * @param string $message Log message
 * @param string $status Log status (info, success, warning, error)
 * @since 1.0.0
 */
function add_to_csv_log(string $message, string $status = 'info')
{
    $log_entry = [
        'message' => $message,
        'status' => $status,
        'time' => current_time('mysql'),
    ];

    $emoji = match ($status) {
        'info' => 'ℹ️',
        'success' => '✅',
        'warning' => '⚠️',
        'error' => '❌',
    };

    $log_file = WP_CONTENT_DIR . '/csv_import.log';
    $log_line = sprintf('[%s] %s: %s %s', $log_entry['time'], strtoupper($status), $emoji, $message);

    // Log to file using WP_Filesystem
    require_once ABSPATH . 'wp-admin/includes/file.php';
    file_put_contents($log_file, $log_line . "\n", FILE_APPEND | LOCK_EX);

    // Log to debug.log
    error_log($log_line);

    // Store in transient for compatibility
    $logs = get_transient(ADDON_USER_IMPORTER_CRON_EVENT . '_log');
    if (!is_array($logs)) {
        $logs = [];
    }
    $logs[] = $log_entry;
    set_transient(ADDON_USER_IMPORTER_CRON_EVENT . '_log', $logs, HOUR_IN_SECONDS);
}

/**
 * Retrieve CSV processing logs from file
 *
 * @return array List of log lines
 * @since 1.0.0
 */
function get_csv_logs(): array
{
    $log_file = WP_CONTENT_DIR . '/csv_import.log';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    WP_Filesystem();
    global $wp_filesystem;

    if ($wp_filesystem->exists($log_file)) {
        return array_reverse(array_filter(explode("\n", $wp_filesystem->get_contents($log_file)), 'strlen'));
    }
    return [];
}

/**
 * Check if a value is not empty
 *
 * @param mixed $input Value to check
 * @return bool True if filled, false otherwise
 * @since 1.0.0
 */
function filled($input): bool
{
    if (is_null($input)) {
        return false;
    }

    if (is_string($input)) {
        return '' !== trim($input);
    }

    if (is_array($input)) {
        return count($input) > 0;
    }

    return !empty($input);
}

/**
 * Update WooCommerce user address
 *
 * @param int $user_id User ID
 * @param string $state State
 * @param string $city City
 * @return bool Success status
 * @since 1.0.0
 */
function update_user_wc_address(int $user_id, string $state, string $city): bool
{
    if (0 === $user_id || '' === $state || '' === $city) {
        return false;
    }

    update_user_meta($user_id, 'billing_state', sanitize_text_field($state));
    update_user_meta($user_id, 'billing_city', sanitize_text_field($city));
    update_user_meta($user_id, 'shipping_state', sanitize_text_field($state));
    update_user_meta($user_id, 'shipping_city', sanitize_text_field($city));

    global $wpdb;

    $user_info = get_userdata($user_id);
    if (!$user_info) {
        return false;
    }

    $first_name = get_user_meta($user_id, 'first_name', true);
    $last_name = get_user_meta($user_id, 'last_name', true);
    $email = $user_info->user_email;
    $phone = get_user_meta($user_id, 'billing_phone', true);
    $city = get_user_meta($user_id, 'billing_city', true);
    $state = get_user_meta($user_id, 'billing_state', true);
    $postcode = get_user_meta($user_id, 'billing_postcode', true);
    $country = get_user_meta($user_id, 'billing_country', true);

    $existing = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT user_id FROM {$wpdb->prefix}wc_customer_lookup WHERE user_id = %d",
            $user_id
        )
    );

    $data = [
        'user_id' => $user_id,
        'username' => $user_info->user_login,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'email' => $email,
        'date_last_active' => current_time('mysql'),
        'date_registered' => $user_info->user_registered,
        'country' => $country ? $country : '',
        'postcode' => $postcode ? $postcode : '',
        'city' => $city ? $city : '',
        'state' => $state ? $state : '',
    ];

    if ($existing) {
        $updated = $wpdb->update(
            "{$wpdb->prefix}wc_customer_lookup",
            $data,
            ['user_id' => $user_id]
        );
        return $updated !== false;
    } else {
        $inserted = $wpdb->insert(
            "{$wpdb->prefix}wc_customer_lookup",
            $data
        );
        return $inserted !== false;
    }
    return true;
}

/**
 * Add balance to user's wallet
 *
 * @param int $user_id User ID
 * @param float $add_balance Amount to add
 * @param int|null $expire_timestamp Expiration timestamp
 * @return bool Success status
 * @since 1.0.0
 */
function add_wallet_balance(int $user_id, float $add_balance, ?int $expire_timestamp = null): bool
{
    global $wpdb;

    $existing_balance = get_user_meta($user_id, 'nirweb_wallet_balance', true) ?? 0;
    $new_balance = floatval($existing_balance) + $add_balance;

    update_user_meta($user_id, 'nirweb_wallet_balance', $new_balance);

    $wpdb->insert("{$wpdb->prefix}nirweb_wallet_op", [
        'user_id'      => $user_id,
        'user_created' => 0,
        'amount'       => $add_balance,
        'description'  => __('Wallet charge for in-person buyers', 'we-user-importer'),
        'type_op'      => 'credit',
        'type_v'       => 'register',
        'created'      => current_time('mysql'),
    ]);

    $wpdb->insert("{$wpdb->prefix}nirweb_wallet_cashback", [
        'user_id'      => $user_id,
        'order_id'     => 0,
        'amount'       => $add_balance,
        'expire_time'  => $expire_timestamp,
        'start_time'   => null,
        'status_start' => null,
    ]);

    return true;
}

/**
 * Check if user has ever been charged
 *
 * @param int $user_id User ID
 * @return bool True if charged, false otherwise
 * @since 1.0.0
 */
function user_has_ever_been_charged(int $user_id): bool
{
    global $wpdb;

    $cache_key = 'we_user_charged_' . $user_id;
    $has_been_charged = wp_cache_get($cache_key);
    if (false === $has_been_charged) {
        $has_been_charged = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}nirweb_wallet_op WHERE user_id = %d",
                $user_id
            )
        );
        wp_cache_set($cache_key, $has_been_charged, '', HOUR_IN_SECONDS);
    }

    return 0 < $has_been_charged;
}

/**
 * Standardize phone number for Digits plugin
 *
 * @param string $phone Phone number
 * @return string Standardized phone number
 * @since 1.0.0
 */
function digits_standard_username(string $phone): string
{
    $cleaned = preg_replace('/[^\d+]/', '', $phone);
    $cleaned = ltrim($cleaned, '0');

    if (0 === strpos($cleaned, '+980')) {
        return '+98' . substr($cleaned, 4);
    } elseif (0 === strpos($cleaned, '980')) {
        return '+98' . substr($cleaned, 3);
    } elseif (0 === strpos($cleaned, '+98')) {
        return $cleaned;
    } elseif (0 === strpos($cleaned, '989')) {
        return '+' . $cleaned;
    } elseif (0 === strpos($cleaned, '98')) {
        return '+98' . substr($cleaned, 2);
    } elseif (0 === strpos($cleaned, '9') && 10 === strlen($cleaned)) {
        return '+98' . $cleaned;
    }

    return $phone;
}

/**
 * Clean phone number to raw format
 *
 * @param string $phone Phone number
 * @return string Raw phone number (e.g., 9123456789)
 * @since 1.0.0
 */
function clean_phone_number(string $phone): string
{
    $cleaned = preg_replace('/[^\d+]/', '', $phone);
    $cleaned = ltrim($cleaned, '0');

    if (0 === strpos($cleaned, '+980')) {
        $cleaned = substr($cleaned, 4);
    } elseif (0 === strpos($cleaned, '+98')) {
        $cleaned = substr($cleaned, 3);
    } elseif (0 === strpos($cleaned, '98')) {
        $cleaned = substr($cleaned, 2);
    }

    $cleaned = ltrim($cleaned, '0');

    if (10 === strlen($cleaned) && ctype_digit($cleaned)) {
        return $cleaned;
    }

    return $phone;
}

/**
 * Find user by phone number
 *
 * @param string $phone Phone number
 * @return int|null User ID or null if not found
 * @since 1.0.0
 */
function find_user_by_phone(string $phone): ?int
{
    $user_id = null;
    $cleaned_number = clean_phone_number($phone);
    global $wpdb;

    $user_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}users WHERE user_login LIKE %s",
            '%' . $wpdb->esc_like($cleaned_number) . '%'
        )
    );
    $user_id = $user_id ? (int) $user_id : null;

    if (is_null($user_id)) {
        $user_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT user_id FROM {$wpdb->prefix}usermeta 
         WHERE meta_key = 'digits_phone_no' 
         AND meta_value LIKE %s",
                '%' . $wpdb->esc_like($cleaned_number) . '%'
            )
        );
        $user_id = $user_id ? (int) $user_id : null;
    }

    return $user_id;
}

/**
 * generateRefCode
 *
 * @param  mixed $user_id
 * @return string
 */
function generateRefCode(int $user_id): string
{
    $user_id_str = (string)$user_id;
    $len = strlen($user_id_str);

    if ($len >= 5) {
        return substr($user_id_str, 0, 5);
    }

    $random_digits_count = 5 - $len;

    $min = (int) str_pad('1', $random_digits_count, '0', STR_PAD_RIGHT);
    $max = (int) str_pad('9', $random_digits_count, '9', STR_PAD_RIGHT);
    $random_number = rand($min, $max);

    return $user_id_str . $random_number;
}

/**
 * Handle CSV processing via cron
 *
 * @since 1.0.0
 */
function my_csv_cron_handler()
{
    if (!get_transient(ADDON_USER_IMPORTER_CRON_EVENT . '_task') || get_transient(ADDON_USER_IMPORTER_CRON_EVENT . '_running')) {
        return;
    }

    try {
        set_transient(ADDON_USER_IMPORTER_CRON_EVENT . '_running', true);
        add_to_csv_log(__('🚀 Starting cron job.', 'we-user-importer') . ' ' . current_time('mysql'), 'info');

        $task_data = get_transient(ADDON_USER_IMPORTER_CRON_EVENT . '_task');
        $file_path = $task_data['file_path'];
        $offset = intval($task_data['offset']);
        $form_data = $task_data['form_data'];
        $continue_if_exists = (bool) $form_data['continue_if_exists'];
        $not_only_wallet_first_time = (bool) $form_data['not_only_wallet_first_time'];
        $min_charge = floatval($form_data['min_charge']);
        $expire_days = intval($form_data['expire_date']);

        $chunk_size = 20;

        require_once ABSPATH . 'wp-admin/includes/file.php';
        WP_Filesystem();
        global $wp_filesystem;

        if (!$wp_filesystem->exists($file_path) || !$wp_filesystem->is_readable($file_path)) {
            add_to_csv_log(sprintf(__('CSV file not found or unreadable: %s', 'we-user-importer'), $file_path), 'error');
            delete_transient(ADDON_USER_IMPORTER_CRON_EVENT . '_task');
            delete_transient(ADDON_USER_IMPORTER_CRON_EVENT . '_running');
            return;
        }

        $handle = fopen($file_path, 'r');
        if (!$handle) {
            add_to_csv_log(__('Unable to open CSV file.', 'we-user-importer'), 'error');
            delete_transient(ADDON_USER_IMPORTER_CRON_EVENT . '_task');
            delete_transient(ADDON_USER_IMPORTER_CRON_EVENT . '_running');
            return;
        }

        $row_count = 0;
        while ($row_count < $offset && !feof($handle)) {
            if (false === fgetcsv($handle)) {
                add_to_csv_log(__('Error reading CSV row.', 'we-user-importer'), 'warning');
                break;
            }
            $row_count++;
        }

        $processed_count = 0;
        while (is_resource($handle) && ($data = fgetcsv($handle)) !== false && $processed_count < $chunk_size) {
            $processed_count++;
            $user_id = null;
            $phone_number = '';
            $amount = 0.0;
            $percent_charge = 0.0;
            $fixed_charge = 0.0;
            $first_name = '';
            $last_name = '';
            $state = '';
            $city = '';
            $buy_date = '';
            $expire_date_input = '';
            $charge = 0.0;

            $phone_number = isset($data[0]) ? sanitize_text_field(trim($data[0])) : '';
            $amount = isset($data[1]) ? floatval(trim($data[1])) : 0.0;
            $percent_charge = isset($data[2]) ? floatval(trim($data[2])) : 0.0;
            $fixed_charge = isset($data[3]) ? floatval(trim($data[3])) : 0.0;
            $first_name = isset($data[4]) ? sanitize_text_field(trim($data[4])) : '';
            $last_name = isset($data[5]) ? sanitize_text_field(trim($data[5])) : '';
            $state = isset($data[6]) ? sanitize_text_field(trim($data[6])) : '';
            $city = isset($data[7]) ? sanitize_text_field(trim($data[7])) : '';
            $buy_date = isset($data[8]) ? sanitize_text_field(trim($data[8])) : '';
            $expire_date_input = isset($data[9]) ? sanitize_text_field(trim($data[9])) : '';

            $charge = $percent_charge > 0 ? ($percent_charge / 100) * $amount : $fixed_charge;
            $charge = $charge > 0 ? $charge : $min_charge;

            $wallet_timestamp = 0;
            $persian_expire_date = '';

            if (!empty($expire_date_input)) {
                $date_parts = explode('/', $expire_date_input);
                if (count($date_parts) === 3 && function_exists('jmktime')) {
                    $wallet_timestamp = jmktime(0, 0, 0, $date_parts[1], $date_parts[2], $date_parts[0]);
                    $persian_expire_date = $expire_date_input;
                } else {
                    add_to_csv_log(sprintf(__('Invalid expiration date format in row %d.', 'we-user-importer'), $offset + $processed_count), 'warning');
                }
            } elseif ($expire_days > 0 && function_exists('jdate')) {
                $wallet_timestamp = time() + ($expire_days * DAY_IN_SECONDS);
                $persian_expire_date = jdate('Y/m/d', $wallet_timestamp);
            }

            if ('' === $phone_number) {
                add_to_csv_log(sprintf(__('Phone number empty in row %d.', 'we-user-importer'), $offset + $processed_count), 'warning');
                continue;
            }

            if (strlen($phone_number) > 14 || strlen($phone_number) < 10) {
                add_to_csv_log(sprintf(__('Invalid phone number %s in row %d.', 'we-user-importer'), $phone_number, $offset + $processed_count), 'warning');
                continue;
            }

            $digits_phone_number = digits_standard_username($phone_number);
            add_to_csv_log(sprintf(__('Phone Number is %s and digits number is %s', 'we-user-importer'), $phone_number, $digits_phone_number), 'info');
            $user_id = find_user_by_phone($phone_number);
            $cleaned_number = clean_phone_number($phone_number);

            if (!is_null($user_id)) {
                add_to_csv_log(sprintf(__('User %s already exists (ID: %d).', 'we-user-importer'), $digits_phone_number, $user_id), 'info');
            } else {
                $password = wp_generate_password(12, false);
                $email = $digits_phone_number . '@' . parse_url(get_site_url(), PHP_URL_HOST);
                $user_id = wp_create_user($digits_phone_number, $password, $email);

                if (is_wp_error($user_id)) {
                    add_to_csv_log(sprintf(__('Failed to create user %s: %s', 'we-user-importer'), $digits_phone_number, $user_id->get_error_message()), 'error');
                    continue;
                }
                update_user_meta($user_id, 'billing_email', sanitize_email($email));
                add_to_csv_log(sprintf(__('User %s created (ID: %d).', 'we-user-importer'), $digits_phone_number, $user_id), 'success');
            }

            wp_update_user([
                'ID'         => $user_id,
                'first_name' => $first_name,
                'last_name'  => $last_name,
            ]);
            $ref_code = generateRefCode($user_id);
            update_user_meta($user_id, 'billing_state', $state);
            update_user_meta($user_id, 'billing_city', $city);
            update_user_meta($user_id, 'billing_phone', $digits_phone_number);
            update_user_meta($user_id, 'shipping_state', $state);
            update_user_meta($user_id, 'shipping_city', $city);
            update_user_meta($user_id, 'shipping_phone', $digits_phone_number);
            update_user_meta($user_id, 'digits_phone', $digits_phone_number);
            update_user_meta($user_id, 'digits_phone_no', $cleaned_number);
            update_user_meta($user_id, 'digits_form_data',  serialize([]));
            update_user_meta($user_id, 'billing_country', 'IR');
            update_user_meta($user_id, 'digt_countrycode', '+98');
            update_user_meta($user_id, 'wp_capabilities', serialize(['customer' => true]));
            update_user_meta($user_id, 'nirwallet_referral_code', $ref_code);
            add_to_csv_log(sprintf(__('User %s details updated.', 'we-user-importer'), $digits_phone_number), 'success');

            if ((user_has_ever_been_charged($user_id) && $not_only_wallet_first_time) || !user_has_ever_been_charged($user_id)) {
                if (!$continue_if_exists) {
                    add_to_csv_log(sprintf(
                        __('Info: User %s wallet not charged due to settings.', 'we-user-importer'),
                        $digits_phone_number
                    ), 'info');
                    continue;
                }
                if (add_wallet_balance($user_id, $charge, $wallet_timestamp)) {
                    add_to_csv_log(sprintf(__('Wallet charged for user %s with amount %s.', 'we-user-importer'), $digits_phone_number, $charge), 'success');
                } else {
                    add_to_csv_log(sprintf(__('Failed to charge wallet for user %s.', 'we-user-importer'), $digits_phone_number), 'error');
                }
            }

            if (filled($state) && filled($city)) {
                if (update_user_wc_address($user_id, $state, $city)) {
                    add_to_csv_log(sprintf(__('Address updated for user %s.', 'we-user-importer'), $digits_phone_number), 'success');
                } else {
                    add_to_csv_log(sprintf(__('Failed to update address for user %s.', 'we-user-importer'), $digits_phone_number), 'error');
                }
            } else {
                add_to_csv_log(sprintf(__('State or city empty for user %s.', 'we-user-importer'), $digits_phone_number), 'warning');
            }

            if (sa_get_option('user_importer_sms_status')) {
                if (send_sms_pattern(" $buy_date;$charge;$persian_expire_date", $cleaned_number, sa_get_option('user_importer_sms_pattern'))) {
                    add_to_csv_log(__('Sms Send to $cleaned_number Success!', 'we-user-importer'), "success");
                } else {
                    add_to_csv_log(__('Sms Send to $cleaned_number failed!', 'we-user-importer'), "error");
                }
            }
        }

        $is_eof = is_resource($handle) && feof($handle);
        fclose($handle);

        if ($is_eof) {
            add_to_csv_log(__('File processing completed.', 'we-user-importer'), 'success');
            delete_transient(ADDON_USER_IMPORTER_CRON_EVENT . '_task');
            delete_transient(ADDON_USER_IMPORTER_CRON_EVENT . '_running');
            $wp_filesystem->delete($file_path);
        } else {
            $new_offset = $offset + $processed_count;
            $task_data['offset'] = $new_offset;
            set_transient(ADDON_USER_IMPORTER_CRON_EVENT . '_task', $task_data, HOUR_IN_SECONDS);
            delete_transient(ADDON_USER_IMPORTER_CRON_EVENT . '_running');
            add_to_csv_log(sprintf(__('Processed %d rows, new offset: %d.', 'we-user-importer'), $processed_count, $new_offset), 'info');
        }
    } catch (Throwable $th) {
        add_to_csv_log(sprintf(__('File processing failed: %s', 'we-user-importer'), $th->getMessage()), 'error');
        if (is_resource($handle)) {
            fclose($handle);
        }
        delete_transient(ADDON_USER_IMPORTER_CRON_EVENT . '_task');
        delete_transient(ADDON_USER_IMPORTER_CRON_EVENT . '_running');
    }
}

add_filter('cron_schedules', function ($schedules) {
    $schedules['every_minute'] = array(
        'interval' => 60,
        'display'  => __('Every Minute (60s)', 'we-user-importer')
    );
    return $schedules;
});

add_action(ADDON_USER_IMPORTER_CRON_EVENT, 'my_csv_cron_handler');
if (!wp_next_scheduled(ADDON_USER_IMPORTER_CRON_EVENT)) {
    wp_schedule_event(time(), 'every_minute', ADDON_USER_IMPORTER_CRON_EVENT);
}
