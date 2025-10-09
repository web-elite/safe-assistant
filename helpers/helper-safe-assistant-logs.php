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

if (!function_exists('sa_get_logs_count')) {
    /**
     * Get total count of logs
     *
     * @param string|null $type
     * @param string|null $status
     * @return int
     */
    function sa_get_logs_count(?string $type = null, ?string $status = null): int
    {
        global $wpdb;
        $table = $wpdb->prefix . 'sa_logs';

        $where = [];
        if ($type)   $where[] = $wpdb->prepare("type = %s", $type);
        if ($status) $where[] = $wpdb->prepare("status = %s", $status);

        $sql = "SELECT COUNT(*) FROM $table";
        if (!empty($where)) $sql .= " WHERE " . implode(' AND ', $where);

        return (int) $wpdb->get_var($sql);
    }
}

if (!function_exists('sa_get_logs_paginated')) {
    /**
     * Get paginated logs
     *
     * @param string|null $type
     * @param string|null $status
     * @param int $page
     * @param int $per_page
     * @return array
     */
    function sa_get_logs_paginated(?string $type = null, ?string $status = null, int $page = 1, int $per_page = 20): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'sa_logs';

        $where = [];
        if ($type)   $where[] = $wpdb->prepare("type = %s", $type);
        if ($status) $where[] = $wpdb->prepare("status = %s", $status);

        $offset = ($page - 1) * $per_page;
        
        $sql = "SELECT * FROM $table";
        if (!empty($where)) $sql .= " WHERE " . implode(' AND ', $where);
        $sql .= " ORDER BY created_at DESC LIMIT %d OFFSET %d";

        $logs = $wpdb->get_results($wpdb->prepare($sql, $per_page, $offset));
        $total_count = sa_get_logs_count($type, $status);
        $total_pages = ceil($total_count / $per_page);

        return [
            'logs' => $logs,
            'total_count' => $total_count,
            'total_pages' => $total_pages,
            'current_page' => $page,
            'per_page' => $per_page,
            'has_next' => $page < $total_pages,
            'has_prev' => $page > 1
        ];
    }
}

if (!function_exists('sa_render_logs_paginated')) {
    /**
     * Render logs with AJAX pagination
     *
     * @param string|null $type
     * @param string|null $status
     * @param int $page
     * @param int $per_page
     * @param bool $ajax_mode
     * @return string|array
     */
    function sa_render_logs_paginated(?string $type = null, ?string $status = null, int $page = 1, int $per_page = 20, bool $ajax_mode = false)
    {
        $result = sa_get_logs_paginated($type, $status, $page, $per_page);
        $logs = $result['logs'];

        if (!$logs) {
            $no_logs_message = '<div class="sa-logs-empty"><p>' . esc_html__('No logs found.', 'safe-assistant') . '</p></div>';
            
            if ($ajax_mode) {
                return [
                    'success' => true,
                    'html' => $no_logs_message,
                    'pagination' => '',
                    'total_count' => 0,
                    'current_page' => 1,
                    'total_pages' => 0
                ];
            }
            return $no_logs_message;
        }

        // Generate logs table HTML
        $html = '<div class="sa-logs-container">';
        $html .= '<table class="sa-logs-table widefat fixed striped">';
        $html .= '<thead><tr>
                    <th class="column-id">' . esc_html__('ID', 'safe-assistant') . '</th>
                    <th class="column-type">' . esc_html__('Type', 'safe-assistant') . '</th>
                    <th class="column-status">' . esc_html__('Status', 'safe-assistant') . '</th>
                    <th class="column-title">' . esc_html__('Title', 'safe-assistant') . '</th>
                    <th class="column-message">' . esc_html__('Message', 'safe-assistant') . '</th>
                    <th class="column-result">' . esc_html__('Result', 'safe-assistant') . '</th>
                    <th class="column-date">' . esc_html__('Created At', 'safe-assistant') . '</th>
                  </tr></thead><tbody>';

        foreach ($logs as $row) {
            $result_display = maybe_unserialize($row->result);
            if (is_array($result_display) || is_object($result_display)) {
                $result_display = '<details><summary>' . esc_html__('View Details', 'safe-assistant') . '</summary><pre class="sa-log-result">' . esc_html(print_r($result_display, true)) . '</pre></details>';
            } else {
                $result_display = esc_html($result_display);
            }

            $status_class = 'status-' . esc_attr($row->status);
            
            $html .= '<tr class="' . $status_class . '">
                        <td class="column-id">' . esc_html($row->id) . '</td>
                        <td class="column-type">' . esc_html($row->type) . '</td>
                        <td class="column-status"><span class="status-badge ' . $status_class . '">' . esc_html($row->status) . '</span></td>
                        <td class="column-title">' . esc_html($row->title) . '</td>
                        <td class="column-message">' . esc_html($row->message) . '</td>
                        <td class="column-result">' . $result_display . '</td>
                        <td class="column-date">' . esc_html($row->created_at) . '</td>
                      </tr>';
        }

        $html .= '</tbody></table>';
        
        // Generate pagination HTML
        $pagination_html = sa_generate_logs_pagination($result);
        
        $html .= '</div>';

        if ($ajax_mode) {
            return [
                'success' => true,
                'html' => $html,
                'pagination' => $pagination_html,
                'total_count' => $result['total_count'],
                'current_page' => $result['current_page'],
                'total_pages' => $result['total_pages']
            ];
        }

        return $html . $pagination_html;
    }
}

if (!function_exists('sa_generate_logs_pagination')) {
    /**
     * Generate pagination HTML for logs
     *
     * @param array $result
     * @return string
     */
    function sa_generate_logs_pagination(array $result): string
    {
        if ($result['total_pages'] <= 1) {
            return '';
        }

        $current_page = $result['current_page'];
        $total_pages = $result['total_pages'];
        $total_count = $result['total_count'];

        $html = '<div class="sa-logs-pagination">';
        $html .= '<div class="sa-logs-info">';
        $html .= sprintf(
            esc_html__('Showing page %d of %d (%d total logs)', 'safe-assistant'),
            $current_page,
            $total_pages,
            $total_count
        );
        $html .= '</div>';
        
        $html .= '<div class="sa-pagination-buttons">';

        // First page
        if ($current_page > 1) {
            $html .= '<button class="button sa-page-btn" data-page="1">« ' . esc_html__('First', 'safe-assistant') . '</button>';
            $html .= '<button class="button sa-page-btn" data-page="' . ($current_page - 1) . '">‹ ' . esc_html__('Previous', 'safe-assistant') . '</button>';
        }

        // Page numbers
        $start = max(1, $current_page - 2);
        $end = min($total_pages, $current_page + 2);

        if ($start > 1) {
            $html .= '<span class="sa-pagination-dots">...</span>';
        }

        for ($i = $start; $i <= $end; $i++) {
            $active_class = ($i == $current_page) ? 'button-primary' : 'button';
            $html .= '<button class="' . $active_class . ' sa-page-btn" data-page="' . $i . '">' . $i . '</button>';
        }

        if ($end < $total_pages) {
            $html .= '<span class="sa-pagination-dots">...</span>';
        }

        // Last page
        if ($current_page < $total_pages) {
            $html .= '<button class="button sa-page-btn" data-page="' . ($current_page + 1) . '">' . esc_html__('Next', 'safe-assistant') . ' ›</button>';
            $html .= '<button class="button sa-page-btn" data-page="' . $total_pages . '">' . esc_html__('Last', 'safe-assistant') . ' »</button>';
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }
}
