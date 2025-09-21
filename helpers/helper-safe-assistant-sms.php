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
        sa_log_sms($recipients, $text, $response ? 'success' : 'failed', (string)$response);
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
        sa_log_sms($to, $textArgs, $response ? 'success' : 'failed', (string)$response);
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
}

if (!function_exists('sa_log_sms')) {
    function sa_log_sms(string $recipient, string $message, string $status, string $response = '')
    {
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'sa_sms_log',
            [
                'recipient'  => $recipient,
                'message'    => $message,
                'status'     => $status,
                'response'   => $response,
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s', '%s', '%s']
        );
    }
}

if (!function_exists('sa_render_sms_logs')) {
    function sa_render_sms_logs(int $limit = 50)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'sa_sms_log';
        $rows = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC LIMIT $limit");

        if (!$rows) {
            return '<p>No SMS logs found.</p>';
        }

        $html = '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse:collapse;width:100%">';
        $html .= '<thead><tr>
                    <th>ID</th>
                    <th>Recipient</th>
                    <th>Message</th>
                    <th>Status</th>
                    <th>Response</th>
                    <th>Created At</th>
                  </tr></thead><tbody>';

        foreach ($rows as $row) {
            $html .= '<tr>
                        <td>' . esc_html($row->id) . '</td>
                        <td>' . esc_html($row->recipient) . '</td>
                        <td>' . esc_html($row->message) . '</td>
                        <td>' . esc_html($row->status) . '</td>
                        <td><pre style="white-space:pre-wrap;">' . esc_html($row->response) . '</pre></td>
                        <td>' . esc_html($row->created_at) . '</td>
                      </tr>';
        }

        $html .= '</tbody></table>';

        return $html;
    }
}

if (!function_exists('sa_truncate_sms_log_table')) {
    function sa_truncate_sms_log_table()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'sa_sms_log';
        $wpdb->query("TRUNCATE TABLE $table");
    }
}
