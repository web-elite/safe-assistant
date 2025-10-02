<?php

/**
 * Backend functions for WE User Importer plugin
 *
 * @package ADDON_USER_IMPORTER
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

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
        'description'  => __('Wallet charge for in-person buyers', 'safe-assistant'),
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
    if (!get_transient(ADDON_USER_IMPORTER_SLUG . '_task') || get_transient(ADDON_USER_IMPORTER_SLUG . '_running')) {
        return;
    }
    $type = ADDON_USER_IMPORTER_SLUG;
    try {
        set_transient(ADDON_USER_IMPORTER_SLUG . '_running', true);
        sa_log($type, 'info', __('🚀 Starting cron job.', 'safe-assistant'), '', current_time('mysql'));
        $task_data = get_transient(ADDON_USER_IMPORTER_SLUG . '_task');
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
            sa_log($type, 'error', sprintf(__('CSV file not found or unreadable: %s', 'safe-assistant'), $file_path));
            delete_transient(ADDON_USER_IMPORTER_SLUG . '_task');
            delete_transient(ADDON_USER_IMPORTER_SLUG . '_running');
            return;
        }

        $handle = fopen($file_path, 'r');
        if (!$handle) {
            sa_log($type, 'error', __('Unable to open CSV file.', 'safe-assistant'));
            delete_transient(ADDON_USER_IMPORTER_SLUG . '_task');
            delete_transient(ADDON_USER_IMPORTER_SLUG . '_running');
            return;
        }

        $row_count = 0;
        while ($row_count < $offset && !feof($handle)) {
            if (false === fgetcsv($handle)) {
                sa_log($type, 'warning', __('Error reading CSV row.', 'safe-assistant'));
                break;
            }
            $row_count++;
        }

        $processed_count = 0;
        while (is_resource($handle) && ($data = fgetcsv($handle)) !== false && $processed_count < $chunk_size) {
            $processed_count++;
            $user_id = null;
            $phone_number = '';
            $amount = 0;
            $percent_charge = 0;
            $fixed_charge = 0;
            $first_name = '';
            $last_name = '';
            $state = '';
            $city = '';
            $buy_date = '';
            $expire_date_input = '';
            $charge = 0;

            $phone_number = isset($data[0]) ? sanitize_text_field(trim($data[0])) : '';
            $amount = isset($data[1]) ? trim($data[1]) : 0;
            $percent_charge = isset($data[2]) ? trim($data[2]) : 0;
            $fixed_charge = isset($data[3]) ? trim($data[3]) : 0;
            $first_name = isset($data[4]) ? sanitize_text_field(trim($data[4])) : '';
            $last_name = isset($data[5]) ? sanitize_text_field(trim($data[5])) : '';
            $state = isset($data[6]) ? sanitize_text_field(trim($data[6])) : '';
            $city = isset($data[7]) ? sanitize_text_field(trim($data[7])) : '';
            $buy_date = isset($data[8]) ? sanitize_text_field(trim($data[8])) : '';
            $buy_date_persian = convert_english_to_persian_numbers($buy_date);
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
                    sa_log($type, 'warning', sprintf(__('Invalid expiration date format in row %d.', 'safe-assistant'), $offset + $processed_count));
                }
            } elseif ($expire_days > 0 && function_exists('jdate')) {
                $wallet_timestamp = time() + ($expire_days * DAY_IN_SECONDS);
                $persian_expire_date = jdate('Y/m/d', $wallet_timestamp);
            }

            if ('' === $phone_number) {
                sa_log($type, 'warning', sprintf(__('Phone number empty in row %d.', 'safe-assistant'), $offset + $processed_count));
                continue;
            }

            if (strlen($phone_number) > 14 || strlen($phone_number) < 10) {
                sa_log($type, 'warning', sprintf(__('Invalid phone number %s in row %d.', 'safe-assistant'), $phone_number, $offset + $processed_count));
                continue;
            }

            $digits_phone_number = digits_standard_username($phone_number);
            sa_log($type, 'info', sprintf(__('Phone Number is %s and digits number is %s', 'safe-assistant'), $phone_number, $digits_phone_number));
            $user_id = find_user_by_phone($phone_number);
            $cleaned_number = clean_phone_number($phone_number);

            if (!is_null($user_id)) {
                sa_log($type, 'info', sprintf(__('User %s already exists (ID: %d).', 'safe-assistant'), $digits_phone_number, $user_id));
            } else {
                $password = wp_generate_password(12, false);
                $email = $digits_phone_number . '@' . parse_url(get_site_url(), PHP_URL_HOST);
                $user_id = wp_create_user($digits_phone_number, $password, $email);

                if (is_wp_error($user_id)) {
                    sa_log($type, 'error', sprintf(__('Failed to create user %s: %s', 'safe-assistant'), $digits_phone_number, $user_id->get_error_message()));
                    continue;
                }
                update_user_meta($user_id, 'billing_email', sanitize_email($email));
                sa_log($type, 'success', sprintf(__('User %s created (ID: %d).', 'safe-assistant'), $digits_phone_number, $user_id));
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
            sa_log($type, 'success', sprintf(__('User %s details updated.', 'safe-assistant'), $digits_phone_number));
            if ((user_has_ever_been_charged($user_id) && $not_only_wallet_first_time) || !user_has_ever_been_charged($user_id)) {
                if (!$continue_if_exists) {
                    sa_log($type, 'info', sprintf(__('Info: User %s wallet not charged due to settings.', 'safe-assistant'), $digits_phone_number));
                    continue;
                }
                if (add_wallet_balance($user_id, $charge, $wallet_timestamp)) {
                    sa_log($type, 'success', sprintf(__('Wallet charged for user %s with amount %d.', 'safe-assistant'), $digits_phone_number, $charge));
                } else {
                    sa_log($type, 'error', sprintf(__('Failed to charge wallet for user %s.', 'safe-assistant'), $digits_phone_number));
                }
            }

            if (sa_filled($state) && sa_filled($city)) {
                if (update_user_wc_address($user_id, $state, $city)) {
                    sa_log($type, 'success', sprintf(__('Address updated for user %s.', 'safe-assistant'), $digits_phone_number));
                } else {
                    sa_log($type, 'error', sprintf(__('Failed to update address for user %s.', 'safe-assistant'), $digits_phone_number));
                }
            } else {
                sa_log($type, 'warning', sprintf(__('State or city empty for user %s.', 'safe-assistant'), $digits_phone_number));
            }

            if (sa_get_option('user_importer_sms_status')) {
                if (sa_send_sms_pattern([
                    "buy_date" => $buy_date_persian,
                    "charge_amount"   => $charge,
                    "expire_date" => $persian_expire_date
                ], $cleaned_number, sa_get_option('user_importer_sms_pattern'))) {
                    sa_log($type, 'success', sprintf(__('Sms Send to %s Success!', 'safe-assistant'), $cleaned_number));
                } else {
                    sa_log($type, 'error', sprintf(__('Sms Send to %s failed!', 'safe-assistant'), $cleaned_number));
                }
            }
        }

        $is_eof = is_resource($handle) && feof($handle);
        fclose($handle);

        if ($is_eof) {
            sa_log($type, 'success', __('File processing completed.', 'safe-assistant'));
            delete_transient(ADDON_USER_IMPORTER_SLUG . '_task');
            delete_transient(ADDON_USER_IMPORTER_SLUG . '_running');
            $wp_filesystem->delete($file_path);
        } else {
            $new_offset = $offset + $processed_count;
            $task_data['offset'] = $new_offset;
            set_transient(ADDON_USER_IMPORTER_SLUG . '_task', $task_data, HOUR_IN_SECONDS);
            delete_transient(ADDON_USER_IMPORTER_SLUG . '_running');
            sa_log($type, 'info', sprintf(__('Processed %d rows, new offset: %d.', 'safe-assistant'), $processed_count, $new_offset));
        }
    } catch (Throwable $th) {
        sa_log($type, 'error', sprintf(__('File processing failed: %s', 'safe-assistant'), $th->getMessage()));
        if (is_resource($handle)) {
            fclose($handle);
        }
        delete_transient(ADDON_USER_IMPORTER_SLUG . '_task');
        delete_transient(ADDON_USER_IMPORTER_SLUG . '_running');
    }
}

add_filter('cron_schedules', function ($schedules) {
    $schedules['every_minute'] = array(
        'interval' => 60,
        'display'  => __('Every Minute (60s)', 'safe-assistant')
    );
    return $schedules;
});

add_action(ADDON_USER_IMPORTER_SLUG, 'my_csv_cron_handler');
if (!wp_next_scheduled(ADDON_USER_IMPORTER_SLUG)) {
    wp_schedule_event(time(), 'every_minute', ADDON_USER_IMPORTER_SLUG);
}
