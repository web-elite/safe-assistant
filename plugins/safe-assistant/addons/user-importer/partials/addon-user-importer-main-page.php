<?php
if (isset($_POST['submit_csv']) && isset($_FILES['csv_file'])) {
    // Verify nonce
    if (!isset($_POST['we_user_importer_nonce']) || !wp_verify_nonce($_POST['we_user_importer_nonce'], 'we_user_importer_upload')) {
        echo '<div class="info-box"><h3>' . esc_html__('❌ Error: Invalid nonce.', 'safe-assistant') . '</h3></div>';
        return;
    }

    // Check user permissions
    if (!current_user_can('manage_options')) {
        echo '<div class="info-box"><h3>' . esc_html__('❌ Error: Insufficient permissions.', 'safe-assistant') . '</h3></div>';
        return;
    }

    // Check if a task is already running
    if (get_transient(WE_USER_IMPORTER_CRON_EVENT . '_task')) {
        echo '<div class="info-box"><h3>' . esc_html__('⚠️ Notice: A process is currently running.', 'safe-assistant') . '</h3><p>' . esc_html__('Please wait until the current process is complete.', 'safe-assistant') . '</p></div>';
        return;
    }

    // Validate file upload
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK || $_FILES['csv_file']['type'] !== 'text/csv') {
        echo '<div class="info-box"><h3>' . esc_html__('❌ Error: Invalid or missing CSV file.', 'safe-assistant') . '</h3></div>';
        return;
    }

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
    set_transient('we_user_importer_form_data', $form_data, 3600);

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
        set_transient(WE_USER_IMPORTER_CRON_EVENT . '_task', $task_data, 3600);
        echo '<div class="info-box"><h3>' . esc_html__('✅ File processing started.', 'safe-assistant') . '</h3><p>' . esc_html__('Your file is being processed in the background. Refresh the page to see updated logs.', 'safe-assistant') . '</p><p> ' .  esc_html__('File Path is:', 'safe-assistant') . $temp_file_path . '</p></div>';
    } else {
        echo '<div class="info-box"><h3>' . esc_html__('❌ Error: Unable to save file.', 'safe-assistant') . '</h3></div>';
    }
}

?>

<div id="<?php echo esc_attr(ADDON_USER_IMPORTER_SLUG); ?>" class="aui-container">

    <!-- تب‌ها -->
    <div class="mb-4 border-b border-gray-200">
        <ul class="flex flex-wrap -mb-px" id="aui-tabs" role="tablist">
            <li class="me-2" role="presentation">
                <button type="button" data-tab="import"
                    class="aui-tab inline-block p-3 border-b-2 rounded-t-lg font-medium border-transparent hover:text-gray-600 hover:border-gray-300 active"
                    role="tab" aria-controls="aui-panel-import" aria-selected="true">
                    <?php esc_html_e('Import', 'safe-assistant'); ?>
                </button>
            </li>
            <li class="me-2" role="presentation">
                <button type="button" data-tab="settings"
                    class="aui-tab inline-block p-3 border-b-2 rounded-t-lg font-medium border-transparent hover:text-gray-600 hover:border-gray-300"
                    role="tab" aria-controls="aui-panel-settings" aria-selected="false">
                    <?php esc_html_e('Settings', 'safe-assistant'); ?>
                </button>
            </li>
            <li role="presentation">
                <button type="button" data-tab="reports"
                    class="aui-tab inline-block p-3 border-b-2 rounded-t-lg font-medium border-transparent hover:text-gray-600 hover:border-gray-300"
                    role="tab" aria-controls="aui-panel-reports" aria-selected="false">
                    <?php esc_html_e('Reports & Logs', 'safe-assistant'); ?>
                </button>
            </li>
        </ul>
    </div>

    <!-- پانل درون‌ریزی -->
    <div id="aui-panel-import" class="aui-panel block">
        <div class="info-box rounded-md border border-gray-200 bg-white p-4 shadow-sm mb-4">
            <h3><?php esc_html_e('Column Information:', 'safe-assistant'); ?></h3>
            <p><?php _e('ℹ️ Only the first column username is required. Amounts in Toman. Dates in YYYY/MM/DD.', 'safe-assistant'); ?></p>
            <a id="downloadCsv" href="https://docs.google.com/spreadsheets/d/1QR-TApGnGa3V6FeziQcueVWWuqdQSqEz/export?ouid=113885461555545291327&format=csv"
                class="btn bg-blue-500 text-white px-3 py-1 rounded" target="_blank" rel="noopener noreferrer">
                <?php esc_html_e('Download Sample File', 'safe-assistant'); ?>
            </a>
        </div>

        <form method="post" enctype="multipart/form-data" class="bg-white p-4 border rounded-md shadow-sm">
            <?php wp_nonce_field('ADDON_USER_IMPORTER_upload', 'ADDON_USER_IMPORTER_nonce'); ?>
            <div class="form-group mb-3">
                <label for="csv_file"><?php esc_html_e('Select CSV File:', 'safe-assistant'); ?></label>
                <input type="file" name="csv_file" id="csv_file" accept=".csv" required class="block mt-1">
            </div>
            <div class="form-group mb-3">
                <input type="checkbox" name="if_user_exist_continue" id="if_user_exist_continue" value="1" <?php checked($continue_if_exists_value, 1); ?>>
                <label for="if_user_exist_continue"><?php esc_html_e('Charge account if user already exists?', 'safe-assistant'); ?></label>
            </div>
            <div class="form-group mb-3">
                <input type="checkbox" name="not_only_wallet_first_time" id="not_only_wallet_first_time" value="1" <?php checked($not_only_wallet_first_time_value, 1); ?>>
                <label for="not_only_wallet_first_time"><?php esc_html_e('Charge wallet for users with previous charges?', 'safe-assistant'); ?></label>
            </div>
            <div class="form-group mb-3">
                <label for="min_charge"><?php esc_html_e('Minimum Charge Amount:', 'safe-assistant'); ?></label>
                <input type="number" name="min_charge" id="min_charge" min="0" step="50000"
                    value="<?php echo esc_attr($min_charge_value); ?>" class="ml-2">
                <span><?php esc_html_e('Toman', 'safe-assistant'); ?></span>
            </div>
            <div class="form-group mb-3">
                <label for="expire_date"><?php esc_html_e('Expiration Days:', 'safe-assistant'); ?></label>
                <input type="number" name="expire_date" id="expire_date" value="<?php echo esc_attr($expire_date_value); ?>" class="ml-2">
            </div>
            <?php if (!get_transient(ADDON_USER_IMPORTER_CRON_EVENT . '_task')): ?>
                <input type="submit" name="submit_csv" value="<?php esc_attr_e('Upload File', 'safe-assistant'); ?>" class="bg-blue-600 text-white px-4 py-2 rounded">
            <?php else: ?>
                <div class="info-box bg-yellow-100 p-3 rounded"><?php esc_html_e('⚠️ A process is currently running.', 'safe-assistant'); ?></div>
            <?php endif; ?>
        </form>
    </div>

    <!-- پانل تنظیمات -->
    <div id="aui-panel-settings" class="aui-panel hidden">
        <form id="ADDON_USER_IMPORTER_settings_form" method="post" class="bg-white p-4 border rounded-md shadow-sm">
            <?php wp_nonce_field('ADDON_USER_IMPORTER_settings', 'ADDON_USER_IMPORTER_settings_nonce'); ?>
            <h3 class="mb-3"><?php esc_html_e('SMS Panel Settings', 'safe-assistant'); ?></h3>
            <div class="form-group mb-3">
                <input type="checkbox" name="sms_status" id="sms_status" value="1" <?php checked($setting->get_setting('sms_status'), 1); ?>>
                <label for="sms_status"><?php esc_html_e('Sms Sending is Active?', 'safe-assistant'); ?></label>
            </div>
            <div class="form-group mb-3">
                <label for="sms_gateway"><?php esc_html_e('SMS Gateway', 'safe-assistant'); ?></label>
                <select name="sms_gateway" id="sms_gateway" class="ml-2">
                    <option value="melipayamak" <?php selected($setting->get_setting('sms_gateway', 'melipayamak'), 'melipayamak'); ?>>Melipayamak</option>
                </select>
            </div>
            <div class="form-group mb-3">
                <label for="sms_username"><?php esc_html_e('Username', 'safe-assistant'); ?></label>
                <input type="text" name="sms_username" value="<?php echo esc_attr($sms_username); ?>" id="sms_username" class="ml-2">
            </div>
            <div class="form-group mb-3">
                <label for="sms_password"><?php esc_html_e('Password', 'safe-assistant'); ?></label>
                <input type="password" name="sms_password" value="<?php echo esc_attr($sms_password); ?>" id="sms_password" class="ml-2">
            </div>
            <div class="form-group mb-3">
                <label for="sms_pattern"><?php esc_html_e('Pattern Code', 'safe-assistant'); ?></label>
                <input type="text" name="sms_pattern" value="<?php echo esc_attr($sms_pattern); ?>" id="sms_pattern" class="ml-2">
            </div>
            <input type="submit" name="submit_settings" value="<?php esc_attr_e('Save', 'safe-assistant'); ?>" class="bg-blue-600 text-white px-4 py-2 rounded">
        </form>
        <form id="ADDON_USER_IMPORTER_reverse_form" method="post" class="bg-white p-4 border rounded-md shadow-sm mb-4">
            <?php wp_nonce_field('ADDON_USER_IMPORTER_reverse', 'ADDON_USER_IMPORTER_reverse_nonce'); ?>
            <h3 class="mb-3"><?php esc_html_e('Reset Section', 'safe-assistant'); ?></h3>
            <div class="mb-2">
                <input type="submit" name="submit_reverse" value="<?php esc_attr_e('Revert All Operations', 'safe-assistant'); ?>" class="bg-red-500 text-white px-3 py-1 rounded">
            </div>
            <div class="mb-2">
                <input type="submit" name="submit_reset_factory" value="<?php esc_attr_e('Reset Logs and Ongoing Tasks', 'safe-assistant'); ?>" class="bg-red-500 text-white px-3 py-1 rounded">
            </div>
            <div class="mb-2">
                <a href="<?= $current_url ?>&clear_log=1" class="bg-red-500 text-white px-3 py-1 rounded"><?php esc_html_e('Clear Logs', 'safe-assistant'); ?></a>
            </div>
            <div class="mb-2">
                <a href="<?= $current_url ?>&force_stop=1" class="bg-red-500 text-white px-3 py-1 rounded"><?php esc_html_e('Force Stop Working', 'safe-assistant'); ?></a>
            </div>
        </form>
    </div>

    <!-- پانل گزارشات -->
    <div id="aui-panel-reports" class="aui-panel hidden">
        <?php
        $file_logs = get_csv_logs();
        if (!empty($file_logs)) {
            echo '<div class="info-box bg-white p-4 mt-4 border rounded">
            <h3>' . esc_html__('📝 Logs:', 'safe-assistant') . '</h3>
            <ul>';
            foreach ($file_logs as $log_line) echo '<li>' . esc_html($log_line) . '</li>';
            echo '</ul>
        </div>';
        }
        ?>
    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tabs = document.querySelectorAll('#aui-tabs .aui-tab');
        const panels = {
            import: document.getElementById('aui-panel-import'),
            settings: document.getElementById('aui-panel-settings'),
            reports: document.getElementById('aui-panel-reports')
        };

        function activate(tabKey) {
            Object.values(panels).forEach(p => p.classList.add('hidden'));
            panels[tabKey].classList.remove('hidden');
            tabs.forEach(btn => btn.classList.remove('active', 'border-blue-500', 'text-blue-600'));
            const activeBtn = document.querySelector(`.aui-tab[data-tab="${tabKey}"]`);
            activeBtn.classList.add('active', 'border-blue-500', 'text-blue-600');
        }
        tabs.forEach(btn => btn.addEventListener('click', () => activate(btn.dataset.tab)));
        activate('import');
    });
</script>