<?php

/**
 * Get Sms Profile Field like gateway, username, password, from, api_key
 *
 * @param  string $field
 * @return string
 */

if (!function_exists('sa_get_sms_profile')) {
    function sa_get_sms_profile(string $field): string
    {
        $gateway = sa_get_option('sms_gateway');
        return ($field == 'gateway') ? $gateway : sa_get_option($gateway . '_sms_' . $field, '');
    }
}

if (!function_exists('sa_validate_sms_profile')) {
    function sa_validate_sms_profile(): bool
    {
        $gateway = sa_get_sms_profile('gateway');
        $result  = (!isset($gateway) || $gateway === '');

        if ($gateway == 'smsir') {
            $apikey = sa_get_sms_profile('api_key');
            $result = (empty($apikey));
        }

        if ($gateway == 'melipayamak') {
            $username = sa_get_sms_profile('username');
            $password = sa_get_sms_profile('password');
            $result   = (empty($username) || empty($password));
        }

        return $result;
    }
}

if (!function_exists('sa_get_sms_gateway_credit')) {
    function sa_get_sms_gateway_credit()
    {
        $gateway = sa_get_sms_profile('gateway');
        $result = ['status' => false, 'credit' => 0, 'message' => ''];
        if ($gateway == 'smsir') {
            $apikey   = sa_get_sms_profile('api_key');
            $curl_options = [
                'CURLOPT_HTTPHEADER' => [
                    'Content-Type: application/json',
                    'X-API-KEY: ' . $apikey
                ],
                'CURLOPT_POST'       => false,
            ];
            $response = json_decode(sa_sms_make_request(sa_get_sms_url('get_credit'), $curl_options ?? [], true));
            $result = [
                'status' => $response['status'] ?? false,
                'credit' => $response['Value'] ?? '',
                'message' => $response['StrRetStatus'] ?? '',
            ];
        }

        if ($gateway == 'melipayamak') {
            $username = sa_get_sms_profile('username');
            $password = sa_get_sms_profile('password');
            $data     = [
                "username" => $username,
                "password" => $password,
            ];
            $curl_options = [
                'CURLOPT_POST'       => true,
                'CURLOPT_POSTFIELDS' => http_build_query($data)
            ];

            $response = json_decode(sa_sms_make_request(sa_get_sms_url('get_credit'), $curl_options ?? []), true);
            $result = [
                'status' => $response['RetStatus'] ?? false,
                'credit' => $response['Value'] ?? '',
                'message' => $response['StrRetStatus'] ?? '',
            ];
        }

        return $result;
    }
}

if (!function_exists('sa_get_sms_status')) {
    function sa_get_sms_status(): string
    {
        $validation_fileds = sa_validate_sms_profile();
        if (!$validation_fileds) {
            return __('Sms profile is invalid', 'safe-assistant');
        }

        $gateway = sa_get_sms_profile('gateway');

        if ($gateway == 'smsir') {
            $apikey   = sa_get_sms_profile('api_key');
            $today    = date("Y-m-d");
            $data     = [
                "page" => 1,
                "count" => 5,
                "from" => "",
                "to" => "",
                "startDate" => $today,
                "endDate" => $today,
                "isRead" => null
            ];
            $curl_options = [
                'CURLOPT_HTTPHEADER' => [
                    'Content-Type: application/json',
                    'X-API-KEY: ' . $apikey
                ],
                'CURLOPT_POST'       => true,
                'CURLOPT_POSTFIELDS' => json_encode($data)
            ];
        }

        if ($gateway == 'melipayamak') {
            $username = sa_get_sms_profile('username');
            $password = sa_get_sms_profile('password');
            $today    = date("Y-m-d");
            $data     = [
                "username" => $username,
                "password" => $password,
                "location" => 2, // 1: inbox, 2: sent
                "from" => "",
                "index" => 0,
                "count" => 5,
                "dateFrom" => $today,
                "dateTo" => $today
            ];
            $curl_options = [
                'CURLOPT_POST'       => true,
                'CURLOPT_POSTFIELDS' => http_build_query($data)
            ];
        }

        return sa_sms_make_request(sa_get_sms_url('get_today_report'), $curl_options ?? []);
    }
}

/**
 * Get SMS Gateway URL for different actions
 *
 * @param  string $type => 'send' | 'pattern' | 'pattern2'
 * @return string
 */
if (!function_exists('sa_get_sms_url')) {
    function sa_get_sms_url(string $type): string
    {
        $gateway = sa_get_sms_profile('gateway');

        return match ($gateway) {
            'melipayamak' => match ($type) {
                'send'       => 'http://api.payamak-panel.com/post/Send.asmx/SendSMS',
                'pattern'    => 'http://api.payamak-panel.com/post/Send.asmx/BaseServiceNumber',
                'get_credit' => 'https://rest.payamak-panel.com/api/SendSMS/GetCredit',
                default      => throw new InvalidArgumentException("Unsupported SMS URL type for Melipayamak: $type"),
            },
            'smsir' => match ($type) {
                'send'             => 'https://api.sms.ir/v1/send',
                'pattern'          => 'https://api.sms.ir/v1/send/verify',
                'get_credit'       => 'https://api.sms.ir/v1/credit',
                'get_today_report' => 'https://api.sms.ir/v1/send/pack',
                default            => throw new InvalidArgumentException("Unsupported SMS URL type for SMS.ir: $type"),
            },
            default => throw new InvalidArgumentException("Unsupported SMS Gateway: $gateway"),
        };
    }
}

/**
 * Send SMS with simple mode
 *
 * @param  string|array $to
 * @param  string $text
 * @return bool
 */
if (!function_exists('sa_send_sms')) {
    function sa_send_sms(string|array $to, string $text): bool|string
    {
        if (empty($to) || empty($text)) return false;
        $gateway = sa_get_sms_profile('gateway');

        if ($gateway === 'melipayamak') {
            $data = [
                'username' => sa_get_sms_profile('username'),
                'password' => sa_get_sms_profile('password'),
                'to'       => normalize_mobile_number($to),
                'from'     => sa_get_sms_profile('from'),
                'text'     => $text
            ];
            $curl_options = [
                CURLOPT_POST       => true,
                CURLOPT_POSTFIELDS => http_build_query($data)
            ];
        }

        if ($gateway === 'smsir') {
            $data = [
                'username' => sa_get_sms_profile('username'),
                'password' => sa_get_sms_profile('password'),
                'mobile'   => normalize_mobile_number($to),
                'line'     => sa_get_sms_profile('from'),
                'text'     => $text
            ];
            $curl_options = [
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'x-sms-ir-secure-token: ' . sa_get_sms_profile('api_key')
                ],
                CURLOPT_POST       => true,
                CURLOPT_POSTFIELDS => json_encode($data)
            ];
        }

        return sa_sms_make_request(sa_get_sms_url('send'), $curl_options);
    }
}

/**
 * Send SMS using pattern (base number) with POST
 * @param  array $parameters
 * @param  string $to
 * @param  ?int $templateID
 * @return bool
 */
if (!function_exists('sa_send_sms_pattern')) {

    function sa_send_sms_pattern(array $parameters, string $to, int $templateID): bool|string
    {
        if (empty($to) || empty($parameters) || empty($templateID)) return false;
        $gateway = sa_get_sms_profile('gateway');

        if ($gateway === 'melipayamak') {
            $data = [
                'username' => sa_get_sms_profile('username'),
                'password' => sa_get_sms_profile('password'),
                'to'       => normalize_mobile_number($to),
                'text'     => implode(';', array_values($parameters)),
                'bodyId'   => $templateID,
            ];
            $curl_options = [
                CURLOPT_POST       => true,
                CURLOPT_POSTFIELDS => http_build_query($data)
            ];
        }

        if ($gateway === 'smsir') {
            $data = [
                "mobile"      => normalize_mobile_number($to),
                "templateId"  => $templateID,
                "parameters"  => array_map(
                    fn($k, $v) => ["name" => $k, "value" => $v],
                    array_keys($parameters),
                    $parameters
                ),
            ];
            $curl_options = [
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'x-sms-ir-secure-token: ' . sa_get_sms_profile('api_key')
                ],
                CURLOPT_POST       => true,
                CURLOPT_POSTFIELDS => json_encode($data)
            ];
        }

        return sa_sms_make_request(sa_get_sms_url('pattern'), $curl_options);
    }
}

/**
 * Make HTTP Request to SMS Gateway
 *
 * @param  string $url
 * @param  array $curl_options
 * 
 * @return bool
 */
if (!function_exists('sa_sms_make_request')) {
    function sa_sms_make_request(string $url, array $curl_options_user)
    {
        $curl_options_default = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
        ];

        $normalized = [];
        foreach ($curl_options_user as $key => $val) {
            $const = defined($key) ? constant($key) : $key;
            $normalized[$const] = $val;
        }

        $curl_options = array_replace($curl_options_default, $normalized);
        $handle = curl_init($url);
        curl_setopt_array($handle, $curl_options);
        $response = curl_exec($handle);
        $status   = curl_errno($handle) ? 'error' : 'success';

        sa_log('sms', $status, 'SMS Request', json_encode([
            'url' => $url,
            'options' => $curl_options,
            'response' => $response
        ]));

        if (curl_errno($handle)) {
            error_log('SMS Error: ' . curl_error($handle));
            curl_close($handle);
            return false;
        }

        curl_close($handle);
        return $response;
    }
}
