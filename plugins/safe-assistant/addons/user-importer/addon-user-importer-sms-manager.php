<?php

class Addon_User_Importer_SMS_Manager
{
    protected string $panel;
    protected string $username;
    protected string $password;
    protected string $pattern;
    protected string $from;
    protected string $url_send_sms;
    protected string $url_send_pattern;

    public function __construct() {
        $setting = new Addon_User_Importer_Settings();
        $this->panel = $setting->get_setting('sms_gateway');
        $this->username = $setting->get_setting('sms_username');
        $this->password = $setting->get_setting('sms_password');
        $this->pattern = $setting->get_setting('sms_pattern');
        $this->from     = '';

        if ($this->panel === 'melipayamak') {
            $this->url_send_sms      = 'https://rest.payamak-panel.com/api/SendSMS/SendSMS';
            $this->url_send_pattern  = 'https://rest.payamak-panel.com/api/SendSMS/BaseServiceNumber';
            $this->url_send_pattern2 = 'http://api.payamak-panel.com/post/Send.asmx/SendByBaseNumber2';
        } else {
            throw new InvalidArgumentException("Unsupported SMS panel: {$this->panel}");
        }
    }

    /**
     * Send plain SMS
     */
    public function sendSMS(string|array $to, string $text): bool|string
    {
        if (empty($to) || empty($text)) return false;

        $recipients = is_array($to) ? implode(',', $to) : $to;

        $data = [
            'username' => $this->username,
            'password' => $this->password,
            'to'       => $recipients,
            'from'     => $this->from,
            'text'     => $text
        ];

        return $this->makeRequest($this->url_send_sms, $data);
    }
    
    /**
     * Send SMS using pattern (base number)
     */
    public function sendPatternSMS(string|array $textArgs, string $to, ?int $bodyId = null): bool|string
    {
        if (empty($to) || empty($textArgs)) return false;
        $data = [
            'username' => $this->username,
            'password' => $this->password,
            'text'     => $textArgs,
            'to'       => $this->normalize_mobile_number($to),
            'bodyId'   => $this->pattern ?? $bodyId,
        ];

        return $this->makeRequest($this->url_send_pattern2, $data, 'GET');
    }

    /**
     * Make cURL POST request
     */
    protected function makeRequest(string $url, array $data, string $method = 'POST'): bool|string
    {
        $curl_options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
        ];
        switch ($method) {
            case 'POST':
                $curl_options[CURLOPT_POST] = true;
                $curl_options[CURLOPT_POSTFIELDS] = http_build_query($data);
                break;
            case 'GET':
                $url = $url . '?' . http_build_query($data);
                break;
            default:
                return "Method Unsupported!";
                break;
        }
        $handle = curl_init($url);
        curl_setopt_array($handle, $curl_options);
        $response = curl_exec($handle);

        if (curl_errno($handle)) {
            error_log('SMS Error: ' . curl_error($handle));
            return false;
        }
        curl_close($handle);
        return $response;
    }

    /**
     * Normalize mobile number to Persian format starting with ۰۹
     *
     * @param string $mobile
     * @return string|null Returns normalized number or null if invalid
     */
    public function normalize_mobile_number(string $mobile): ?string
    {
        // Remove spaces, dashes, parentheses, and non-digit chars (but keep +)
        $mobile = trim($mobile);
        $mobile = str_replace([' ', '-', '(', ')'], '', $mobile);

        // Convert Arabic digits to English
        $mobile = preg_replace_callback('/[۰-۹]/u', function ($matches) {
            return mb_ord($matches[0]) - 1776;
        }, $mobile);

        // Replace Persian/Arabic "۰" with 0
        $mobile = str_replace(['۰', '٠'], '0', $mobile);

        // Remove leading "+" or "00"
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
        // Validate format
        if (!preg_match('/^09\d{9}$/', $mobile)) {
            return $mobile;
        }

        // Convert English digits to Persian
        $mobile = preg_replace_callback('/\d/', function ($matches) {
            return mb_chr($matches[0] + 1776);
        }, $mobile);

        $mobile = preg_replace_callback('/[۰-۹]/u', function ($matches) {
            return mb_ord($matches[0]) - 1776;
        }, $mobile);

        return $mobile;
    }
}
