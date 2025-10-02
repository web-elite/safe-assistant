<?php

if (! function_exists('sa_get_option')) {
    /**
     * Get an option from Codestar options array.
     *
     * @param string $option   Option key
     * @param mixed  $default  Default value
     * @return mixed
     */
    function sa_get_option($option = '', $default = null)
    {
        $options = get_option(SAFE_ASSISTANT_SETTING_ID);
        return (isset($options[$option])) ? $options[$option] : $default;
    }
}

if (! function_exists('get_current_url')) {
    /**
     * get_current_url
     *
     * @return string
     */
    function get_current_url(): string
    {
        $protocol = 'http';
        if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
            $protocol = 'https';
        }
        $url = $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        return $url;
    }
}

if (!function_exists('get_log_file_path')) {
    /**
     * get_log_file_path
     *
     * @param  mixed $type options: main|revert
     * @return string
     */
    function get_log_file_path(string $type): string
    {
        $log_file_name = $type == 'main' ? 'import.log' : 'revert.log';
        $log_file_path = SAFE_ASSISTANT_DIR . "logs/$log_file_name.log";
        if (!file_exists($log_file_path)) {
            $log_file = fopen($log_file_path, 'w');
            fclose($log_file);
        }
        return $log_file_path;
    }
}

if (!function_exists('normalize_mobile_number')) {
    /**
     * Normalize mobile number to Persian format starting with 09 (English digits only)
     *
     * @param string $mobile
     * @return string|null Returns normalized number or null if invalid
     */
    function normalize_mobile_number(string $mobile): ?string
    {
        $mobile = trim($mobile);
        $mobile = str_replace([' ', '-', '(', ')'], '', $mobile);

        // Convert Persian/Arabic digits to English
        $mobile = preg_replace_callback('/[۰-۹]/u', function ($matches) {
            return mb_ord($matches[0]) - 1776;
        }, $mobile);

        // Normalize prefixes
        if (str_starts_with($mobile, '+98')) {
            $mobile = '0' . substr($mobile, 3);
        } elseif (str_starts_with($mobile, '0098')) {
            $mobile = '0' . substr($mobile, 4);
        } elseif (str_starts_with($mobile, '098')) {
            $mobile = '0' . substr($mobile, 3);
        } elseif (str_starts_with($mobile, '98')) {
            $mobile = '0' . substr($mobile, 2);
        } elseif (str_starts_with($mobile, '9')) {
            $mobile = '0' . $mobile;
        }

        // Validate
        if (!preg_match('/^09\d{9}$/', $mobile)) {
            sa_log('sms', 'error', "Invalid mobile number format: $mobile", 'Normalization failed');
            return $mobile;
        }

        return $mobile;
    }
}

/**
 * Check if current page is a WooCommerce page
 */
function is_woocommerce_page()
{
    return function_exists('is_woocommerce') && (
        is_woocommerce() || is_cart() || is_checkout() || is_account_page()
    );
}

/**
 * Check if current page is a WooCommerce admin page
 */
function is_woocommerce_admin_page()
{
    if (!is_admin()) {
        return false;
    }

    $screen = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
    $post_type = isset($_GET['post_type']) ? sanitize_text_field(wp_unslash($_GET['post_type'])) : get_post_type();

    $allowed_pages = [
        'wc-admin',
        'wc-settings',
        'wc-orders',
        'wc-reports',
        'wc-status',
        'wc-addons',
        'novin-signature-admin',
        'novin-advance-shipping'
    ];
    $allowed_post_types = ['product', 'shop_order', 'shop_coupon', 'wooi'];

    return in_array($screen, $allowed_pages, true) || in_array($post_type, $allowed_post_types, true);
}

if (!function_exists('sa_filled')) {
    /**
     * Check if a value is not empty
     *
     * @param mixed $input Value to check
     * @return bool True if filled, false otherwise
     * @since 1.0.0
     */
    function sa_filled($input): bool
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
}

/**
 * Set a custom WordPress maintenance page using your raw HTML.
 * Override default WordPress maintenance page with your custom HTML.
 */

if (!function_exists('sa_create_custom_maintenance_page')) {
    /**
     * Write wp-content/maintenance.php with your HTML (served with 503 status).
     *
     * @param string $html        Your full HTML markup (head/body…).
     * @param int    $retry_after Seconds for Retry-After header (SEO-friendly).
     * @return bool               True on success.
     */
    function sa_create_custom_maintenance_page($html)
    {
        if (!defined('WP_CONTENT_DIR')) return;

        $target = WP_CONTENT_DIR . '/maintenance.php';

        $php = <<<PHP
<?php
if (!headers_sent()) {
    @header((\$_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1') . ' 503 Service Unavailable', true, 503);
    @header('Content-Type: text/html; charset=UTF-8');
    @header('Retry-After: 600'); // SEO-friendly
}
echo <<<'HTML'
{$html}
HTML;
exit;
PHP;

        file_put_contents($target, $php);
    }
}

if (! function_exists('is_update_page')) {
    function is_update_page(): bool
    {
        global $pagenow;
        $allowed_pages = [
            'update-core.php',
            'plugins.php',
            'plugin-install.php',
            'themes.php',
            'theme-install.php',
        ];

        return in_array($pagenow, $allowed_pages, true);
    }
}

/**
 * Check if WooCommerce is activated
 */
if (! function_exists('is_woocommerce_activated')) {
    function is_woocommerce_activated(): bool
    {
        return is_plugin_active('woocommerce/woocommerce.php');
    }
}
