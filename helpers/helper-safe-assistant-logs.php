<?php

if (!function_exists('sa_create_logs_table')) {
    /**
     * Create central logs table
     */
    function sa_create_logs_table()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'sa_logs';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            type VARCHAR(50) NOT NULL DEFAULT 'general',
            status VARCHAR(50) NOT NULL DEFAULT 'info',
            title VARCHAR(255) DEFAULT '',
            message TEXT DEFAULT '',
            result LONGTEXT DEFAULT '',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

if (!function_exists('sa_drop_logs_table')) {
    /**
     * Drop logs table
     */
    function sa_drop_logs_table()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'sa_logs';
        $wpdb->query("DROP TABLE IF EXISTS $table");
    }
}

if (!function_exists('sa_truncate_logs')) {
    /**
     * Clear logs table
     */
    function sa_truncate_logs()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'sa_logs';
        $wpdb->query("TRUNCATE TABLE $table");
    }
}

if (!function_exists('sa_log')) {
    /**
     * Generic log function
     *
     * @param string $type
     * @param string $status
     * @param string $title
     * @param string $message
     * @param mixed  $result
     */
    function sa_log(string $type = 'general', string $status = 'info', string $title = '', string $message = '', $result = '')
    {
        global $wpdb;
        $table = $wpdb->prefix . 'sa_logs';

        if (is_array($result) || is_object($result)) {
            $result = maybe_serialize($result);
        }

        $wpdb->insert(
            $table,
            [
                'type'       => $type,
                'status'     => $status,
                'title'      => $title,
                'message'    => $message,
                'result'     => $result,
                'created_at' => current_time('mysql'),
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s']
        );
    }
}

if (!function_exists('sa_render_logs')) {
    /**
     * Render logs table in HTML
     *
     * @param string|null $type
     * @param string|null $status
     * @param int $limit
     */
    function sa_render_logs(?string $type = null, ?string $status = null, int $limit = 50)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'sa_logs';

        $where = [];
        if ($type)   $where[] = $wpdb->prepare("type = %s", $type);
        if ($status) $where[] = $wpdb->prepare("status = %s", $status);

        $sql = "SELECT * FROM $table";
        if (!empty($where)) $sql .= " WHERE " . implode(' AND ', $where);
        $sql .= " ORDER BY created_at DESC LIMIT %d";

        $rows = $wpdb->get_results($wpdb->prepare($sql, $limit));

        if (!$rows) return '<p>' . esc_html__('No logs found.', 'safe-assistant') . '</p>';

        $html = '<table class="sa-logs-table">';
        $html .= '<thead><tr>
                    <th>' . esc_html__('ID', 'safe-assistant') . '</th>
                    <th>' . esc_html__('Type', 'safe-assistant') . '</th>
                    <th>' . esc_html__('Status', 'safe-assistant') . '</th>
                    <th>' . esc_html__('Title', 'safe-assistant') . '</th>
                    <th>' . esc_html__('Message', 'safe-assistant') . '</th>
                    <th>' . esc_html__('Result', 'safe-assistant') . '</th>
                    <th>' . esc_html__('Created At', 'safe-assistant') . '</th>
                  </tr></thead><tbody>';

        foreach ($rows as $row) {
            $result_display = maybe_unserialize($row->result);
            if (is_array($result_display) || is_object($result_display)) {
                $result_display = '<pre style="white-space:pre-wrap;">' . esc_html(print_r($result_display, true)) . '</pre>';
            } else {
                $result_display = esc_html($result_display);
            }

            $html .= '<tr>
                        <td>' . esc_html($row->id) . '</td>
                        <td>' . esc_html($row->type) . '</td>
                        <td>' . esc_html($row->status) . '</td>
                        <td>' . esc_html($row->title) . '</td>
                        <td>' . esc_html($row->message) . '</td>
                        <td>' . $result_display . '</td>
                        <td>' . esc_html($row->created_at) . '</td>
                      </tr>';
        }

        $html .= '</tbody></table>';
        return $html;
    }
}

if (!function_exists('sa_render_sms_logs')) {
    /**
     * Alias for sa_log
     */
    function sa_render_sms_logs(int $limit = 50)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'sa_logs';
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE type=%s ORDER BY created_at DESC LIMIT %d",
            'sms',
            $limit
        ));

        if (!$rows) return '<p>' . esc_html__('No SMS logs found.', 'safe-assistant') . '</p>';

        $html = '<table class="sa-logs-table">';
        $html .= '<thead><tr>
                <th>' . esc_html__('ID', 'safe-assistant') . '</th>
                <th>' . esc_html__('Status', 'safe-assistant') . '</th>
                <th>' . esc_html__('Title', 'safe-assistant') . '</th>
                <th>' . esc_html__('Message', 'safe-assistant') . '</th>
                <th>' . esc_html__('Request Data', 'safe-assistant') . '</th>
                <th>' . esc_html__('Response Items', 'safe-assistant') . '</th>
                <th>' . esc_html__('Created At', 'safe-assistant') . '</th>
              </tr></thead><tbody>';

        foreach ($rows as $row) {
            $result = maybe_unserialize($row->result);
            $request_data = isset($result['data']) ? '<pre>' . esc_html(print_r($result['data'], true)) . '</pre>' : '';

            $response_display = '';
            if (!empty($result['response'])) {
                $decoded = json_decode($result['response'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $response_display = '<table class="sms-response-table">';
                    foreach ($decoded as $key => $val) {
                        $response_display .= '<tr><td>' . esc_html($key) . '</td><td>' . esc_html(is_array($val) ? print_r($val, true) : $val) . '</td></tr>';
                    }
                    $response_display .= '</table>';
                } else {
                    $response_display = '<pre>' . esc_html($result['response']) . '</pre>';
                }
            }

            $html .= '<tr>
                    <td>' . esc_html($row->id) . '</td>
                    <td class="status-' . esc_attr($row->status) . '">' . esc_html($row->status) . '</td>
                    <td>' . esc_html($row->title) . '</td>
                    <td>' . esc_html($row->message) . '</td>
                    <td>' . $request_data . '</td>
                    <td>' . $response_display . '</td>
                    <td>' . esc_html($row->created_at) . '</td>
                  </tr>';
        }

        $html .= '</tbody></table>';
        return $html;
    }
}
