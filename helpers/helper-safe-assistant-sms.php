<?php

if (!function_exists('sa_get_sms_url')) {
    function sa_get_sms_url(string $type): string
    {
        $base_url = 'https://rest.payamak-panel.com/api/SendSMS/';
        return match ($type) {
            'send' => $base_url . 'SendSMS',
            'pattern' => $base_url . 'BaseServiceNumber',
            'pattern2' => 'http://api.payamak-panel.com/post/Send.asmx/SendByBaseNumber2',
            default => throw new InvalidArgumentException("Unsupported SMS URL type: $type"),
        };
    }
}

if (!function_exists('sa_send_sms')) {
    /**
     * Send plain SMS
     */
    function sa_send_sms(string|array $to, string $text): bool|string
    {
        if (empty($to) || empty($text)) return false;

        $recipients = is_array($to) ? implode(',', $to) : $to;

        $data = [
            'username' => sa_get_option('sms_username'),
            'password' => sa_get_option('sms_password'),
            'to'       => $recipients,
            'from'     => sa_get_option('sms_from'),
            'text'     => $text
        ];
        $response = sa_sms_make_request(sa_get_sms_url('send'), $data);
        return $response;
    }
}

if (!function_exists('sa_send_sms_pattern')) {
    /**
     * Send SMS using pattern (base number)
     */
    function sa_send_sms_pattern(string|array $textArgs, string $to, ?int $bodyId = null): bool|string
    {
        if (empty($to) || empty($textArgs)) return false;
        $data = [
            'username' => sa_get_option('sms_username'),
            'password' => sa_get_option('sms_password'),
            'text'     => $textArgs,
            'to'       => normalize_mobile_number($to),
            'bodyId'   => $bodyId,
        ];
        $response = sa_sms_make_request(sa_get_sms_url('pattern2'), $data, 'GET');
        return $response;
    }
}
if (!function_exists('sa_sms_make_request')) {
    /**
     * Make cURL POST request
     */
    function sa_sms_make_request(string $url, array $data, string $method = 'POST'): bool|string
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
                sa_log('sms', 'error', "Unsupported method $method", 'SMS Request failed');
                return "Method Unsupported!";
        }

        $handle   = curl_init($url);
        curl_setopt_array($handle, $curl_options);
        $response = curl_exec($handle);
        $status   = curl_errno($handle) ? 'error' : 'success';

        sa_log('sms', $status, 'SMS Request', $response, ['url' => $url, 'data' => $data, 'method' => $method]);

        if (curl_errno($handle)) {
            error_log('SMS Error: ' . curl_error($handle));
            curl_close($handle);
            return false;
        }

        curl_close($handle);
        return $response;
    }
}
