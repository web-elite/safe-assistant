<?php
// Get current settings for warning display
$sms_enabled = sa_get_option('user_importer_sms_status', false);
$sms_pattern = sa_get_option('user_importer_sms_pattern', '');
$has_sms_pattern = !empty(trim($sms_pattern));
?>

<div class="postbox">
    <div class="inside">
        <h3><?php esc_html_e('Column Information:', 'safe-assistant'); ?></h3>
        <p>
            <span class="highlight"><?php esc_html_e('Column 1:', 'safe-assistant') ?></span> <?php esc_html_e('Mobile Number', 'safe-assistant'); ?><br>
            <span class="highlight"><?php esc_html_e('Column 2:', 'safe-assistant') ?></span> <?php esc_html_e('Previous Purchase Amount', 'safe-assistant'); ?><br>
            <span class="highlight"><?php esc_html_e('Column 3:', 'safe-assistant') ?></span> <?php esc_html_e('Wallet Credit Percentage', 'safe-assistant'); ?><br>
            <span class="highlight"><?php esc_html_e('Column 4:', 'safe-assistant') ?></span> <?php esc_html_e('Fixed Wallet Credit Amount', 'safe-assistant'); ?><br>
            <span class="highlight"><?php esc_html_e('Column 5:', 'safe-assistant') ?></span> <?php esc_html_e('First Name', 'safe-assistant'); ?><br>
            <span class="highlight"><?php esc_html_e('Column 6:', 'safe-assistant') ?></span> <?php esc_html_e('Last Name', 'safe-assistant'); ?><br>
            <span class="highlight"><?php esc_html_e('Column 7:', 'safe-assistant') ?></span> <?php esc_html_e('State', 'safe-assistant'); ?><br>
            <span class="highlight"><?php esc_html_e('Column 8:', 'safe-assistant') ?></span> <?php esc_html_e('City', 'safe-assistant'); ?><br>
            <span class="highlight"><?php esc_html_e('Column 9:', 'safe-assistant') ?></span> <?php esc_html_e('Purchase Date', 'safe-assistant'); ?><br>
            <span class="highlight"><?php esc_html_e('Column 10:', 'safe-assistant') ?></span> <?php esc_html_e('Expiration Date', 'safe-assistant'); ?><br>
            ℹ️ <?php esc_html_e('Only the first column', 'safe-assistant'); ?> <span class="highlight"><?php esc_html_e('username is required', 'safe-assistant'); ?></span>. <?php esc_html_e('It can be a mobile number or random English characters.', 'safe-assistant'); ?><br>
            ℹ️ <?php esc_html_e('Amounts must be in Toman.', 'safe-assistant'); ?><br>
            ℹ️ <?php esc_html_e('Use the credit percentage field if you want the wallet credit to be based on the previous purchase amount.', 'safe-assistant'); ?><br>
            ℹ️ <?php esc_html_e('Date columns should be in YYYY/MM/DD format, e.g., 1404/05/24.', 'safe-assistant'); ?>
        </p>
        <p><a href="https://docs.google.com/spreadsheets/d/1QR-TApGnGa3V6FeziQcueVWWuqdQSqEz/export?ouid=113885461555545291327&format=csv" class="button" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Download Sample File', 'safe-assistant'); ?></a></p>
    </div>
    <div class="inside">
        <input type="hidden" name="addon_user_importer_action" value="upload_csv">
        <?php wp_nonce_field('addon_user_importer_upload', 'addon_user_importer_nonce'); ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="csv_file"><?php esc_html_e('Select CSV File', 'safe-assistant'); ?></label></th>
                <td><input type="file" name="csv_file" id="csv_file" accept=".csv" required /></td>
            </tr>
            <tr>
                <th scope="row"><label for="if_user_exist_continue"><?php esc_html_e('Add wallet credit if user exists?', 'safe-assistant'); ?></label></th>
                <td><input type="checkbox" name="if_user_exist_continue" id="if_user_exist_continue" value="1" <?php checked($continue_if_exists_value, 1); ?> /></td>
            </tr>
            <tr>
                <th scope="row"><label for="not_only_wallet_first_time"><?php esc_html_e('Add wallet credit for users who already have credit?', 'safe-assistant'); ?></label></th>
                <td><input type="checkbox" name="not_only_wallet_first_time" id="not_only_wallet_first_time" value="1" <?php checked($not_only_wallet_first_time_value, 1); ?> /></td>
            </tr>
            <tr>
                <th scope="row"><label for="min_charge"><?php esc_html_e('Minimum Credit Amount', 'safe-assistant'); ?></label></th>
                <td><input type="number" name="min_charge" id="min_charge" min="0" step="50000" value="<?php echo esc_attr($min_charge_value); ?>" /> <?php esc_html_e('Toman', 'safe-assistant'); ?></td>
            </tr>
            <tr>
                <th scope="row"><label for="expire_date"><?php esc_html_e('Expiration Days', 'safe-assistant'); ?></label></th>
                <td><input type="number" name="expire_date" id="expire_date" value="<?php echo esc_attr($expire_date_value); ?>" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="batch_size"><?php esc_html_e('Batch Size (Rows per Minute)', 'safe-assistant'); ?></label></th>
                <td>
                    <input type="number" name="batch_size" id="batch_size" min="1" max="1000" value="<?php echo esc_attr(sa_get_option('user_importer_batch_size', 20)); ?>" />
                    <p class="description"><?php esc_html_e('Number of CSV rows to process per minute. Higher values process faster but use more server resources.', 'safe-assistant'); ?></p>
                </td>
            </tr>
        </table>

        <!-- Live Settings Status Display -->
        <div id="settings_preview" style="background: #f1f1f1; padding: 15px; margin: 10px 0; border-left: 4px solid #0073aa; display: none;">
            <h4><?php esc_html_e('Current Settings Preview:', 'safe-assistant'); ?></h4>
            <div id="settings_status"></div>
        </div>

        <?php if (!get_transient(ADDON_USER_IMPORTER_SLUG . '_task')) : ?>
            <p class="submit">
                <input type="submit" name="submit_csv" class="button button-primary" value="<?php esc_attr_e('Upload File & Show Actions Preview', 'safe-assistant'); ?>" id="submit_csv_btn" />
            </p>
        <?php else : ?>
            <div class="notice notice-warning inline">
                <p><?php esc_html_e('A process is currently running.', 'safe-assistant'); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    jQuery(document).ready(function($) {
        // Add change listeners to form inputs to update preview
        $('input[name="if_user_exist_continue"], input[name="not_only_wallet_first_time"], input[name="min_charge"], input[name="expire_date"], input[name="batch_size"], input[name="csv_file"]').on('change keyup', function() {
            updateSettingsPreview();
        });

        // Add click handler to submit button
        $('#submit_csv_btn').on('click', function(e) {
            e.preventDefault();
            showUploadWarning();
        });

        // Initialize preview on page load
        updateSettingsPreview();

        function updateSettingsPreview() {
            var previewHtml = [];
            var hasFile = $('#csv_file').val() !== '';
            var continueIfExists = $('#if_user_exist_continue').is(':checked');
            var chargeExistingUsers = $('#not_only_wallet_first_time').is(':checked');
            var minCharge = parseInt($('#min_charge').val()) || 0;
            var expireDays = parseInt($('#expire_date').val()) || 0;
            var batchSize = parseInt($('#batch_size').val()) || 20;

            if (hasFile) {
                previewHtml.push('<span style="color: green;">✓ <?php esc_html_e('File selected', 'safe-assistant'); ?></span>');
            } else {
                previewHtml.push('<span style="color: #999;">○ <?php esc_html_e('No file selected', 'safe-assistant'); ?></span>');
            }

            previewHtml.push('<br>');

            // Charging behavior
            if (continueIfExists) {
                if (chargeExistingUsers) {
                    previewHtml.push('<span style="color: orange;">⚠️ <?php esc_html_e('Will add credit to ALL users (including existing ones)', 'safe-assistant'); ?></span>');
                } else {
                    previewHtml.push('<span style="color: blue;">ℹ️ <?php esc_html_e('Will add credit only to first-time users', 'safe-assistant'); ?></span>');
                }
            } else {
                previewHtml.push('<span style="color: #999;">⏸️ <?php esc_html_e('Will skip charging existing users', 'safe-assistant'); ?></span>');
            }

            if (minCharge > 0) {
                previewHtml.push('<br><span style="color: #333;">💰 <?php esc_html_e('Minimum Credit Amount', 'safe-assistant'); ?>: ' + minCharge.toLocaleString() + ' <?php esc_html_e('Toman', 'safe-assistant'); ?></span>');
            }

            if (expireDays > 0) {
                previewHtml.push('<br><span style="color: #333;">⏰ <?php esc_html_e('Expires in:', 'safe-assistant'); ?> ' + expireDays + ' <?php esc_html_e('days', 'safe-assistant'); ?></span>');
            }

            previewHtml.push('<br><span style="color: #333;">⚡ <?php esc_html_e('Processing:', 'safe-assistant'); ?> ' + batchSize + ' <?php esc_html_e('rows/minute', 'safe-assistant'); ?></span>');

            // SMS Status
            <?php if ($sms_enabled && $has_sms_pattern) : ?>
                previewHtml.push('<br><span style="color: green;">📱 <?php esc_html_e('SMS notifications: ENABLED', 'safe-assistant'); ?></span>');
            <?php elseif ($sms_enabled && !$has_sms_pattern) : ?>
                previewHtml.push('<br><span style="color: red;">📱 <?php esc_html_e('SMS enabled but NO PATTERN set', 'safe-assistant'); ?></span>');
            <?php else : ?>
                previewHtml.push('<br><span style="color: #999;">📱 <?php esc_html_e('SMS notifications: DISABLED', 'safe-assistant'); ?></span>');
            <?php endif; ?>

            $('#settings_status').html(previewHtml.join(''));
            $('#settings_preview').show();
        }

        function showUploadWarning() {
            var warningMessages = [];
            var csvFile = $('#csv_file').val();

            if (!csvFile) {
                alert('<?php esc_html_e('Please select a CSV file first.', 'safe-assistant'); ?>');
                return;
            }

            // Check what actions will be performed
            var continueIfExists = $('#if_user_exist_continue').is(':checked');
            var chargeExistingUsers = $('#not_only_wallet_first_time').is(':checked');
            var minCharge = parseInt($('#min_charge').val()) || 0;
            var expireDays = parseInt($('#expire_date').val()) || 0;
            var batchSize = parseInt($('#batch_size').val()) || 20;

            warningMessages.push('=== <?php esc_html_e('ACTIONS TO BE PERFORMED', 'safe-assistant'); ?> ===');
            warningMessages.push('');

            // User creation/update
            warningMessages.push('👤 <?php esc_html_e('USER MANAGEMENT:', 'safe-assistant'); ?>');
            warningMessages.push('   ✓ <?php esc_html_e('Create new users for phone numbers not in system', 'safe-assistant'); ?>');
            warningMessages.push('   ✓ <?php esc_html_e('Update existing user information (name, location)', 'safe-assistant'); ?>');
            warningMessages.push('   ✓ <?php esc_html_e('Set billing and shipping addresses', 'safe-assistant'); ?>');
            warningMessages.push('   ✓ <?php esc_html_e('Generate unique referral codes', 'safe-assistant'); ?>');
            warningMessages.push('   ✓ <?php esc_html_e('Configure Digits plugin phone authentication', 'safe-assistant'); ?>');
            warningMessages.push('');

            // Wallet credit addition
            warningMessages.push('💰 <?php esc_html_e('WALLET CREDIT ADDITION:', 'safe-assistant'); ?>');
            if (continueIfExists) {
                if (chargeExistingUsers) {
                    warningMessages.push('   ✓ <?php esc_html_e('Add wallet credit for ALL users (new and existing)', 'safe-assistant'); ?>');
                    warningMessages.push('   ⚠️ <?php esc_html_e('WARNING: This will add more balance to users who already have credit!', 'safe-assistant'); ?>');
                } else {
                    warningMessages.push('   ✓ <?php esc_html_e('Add wallet credit ONLY for first-time users', 'safe-assistant'); ?>');
                    warningMessages.push('   ℹ️ <?php esc_html_e('Existing users with previous credit will be skipped', 'safe-assistant'); ?>');
                }
            } else {
                warningMessages.push('   ❌ <?php esc_html_e('Skip wallet credit addition for existing users completely', 'safe-assistant'); ?>');
                warningMessages.push('   ℹ️ <?php esc_html_e('Only new users will get wallet credit', 'safe-assistant'); ?>');
            }

            if (minCharge > 0) {
                warningMessages.push('   • <?php esc_html_e('Minimum Credit Amount', 'safe-assistant'); ?>: ' + minCharge.toLocaleString() + ' <?php esc_html_e('Toman', 'safe-assistant'); ?>');
            }

            if (expireDays > 0) {
                warningMessages.push('   • <?php esc_html_e('Wallet credit will expire after:', 'safe-assistant'); ?> ' + expireDays + ' <?php esc_html_e('days', 'safe-assistant'); ?>');
            } else {
                warningMessages.push('   • <?php esc_html_e('Wallet credit will not expire', 'safe-assistant'); ?>');
            }
            warningMessages.push('');

            // WooCommerce integration
            warningMessages.push('🛒 <?php esc_html_e('WooCommerce Integration:', 'safe-assistant'); ?>');
            warningMessages.push('   • <?php esc_html_e('Update customer lookup table', 'safe-assistant'); ?>');
            warningMessages.push('   • <?php esc_html_e('Set customer capabilities', 'safe-assistant'); ?>');
            warningMessages.push('');

            // SMS notifications
            <?php if ($sms_enabled && $has_sms_pattern) : ?>
                warningMessages.push('📱 <?php esc_html_e('SMS Notifications:', 'safe-assistant'); ?>');
                warningMessages.push('   • ✅ <?php esc_html_e('SMS notifications are ENABLED', 'safe-assistant'); ?>');
                warningMessages.push('   • <?php esc_html_e('SMS will be sent to all processed users', 'safe-assistant'); ?>');
                warningMessages.push('   • <?php esc_html_e('Pattern:', 'safe-assistant'); ?> <?php echo esc_js($sms_pattern); ?>');
            <?php elseif ($sms_enabled && !$has_sms_pattern) : ?>
                warningMessages.push('📱 <?php esc_html_e('SMS Notifications:', 'safe-assistant'); ?>');
                warningMessages.push('   • ⚠️ <?php esc_html_e('SMS is enabled but NO PATTERN is set', 'safe-assistant'); ?>');
                warningMessages.push('   • <?php esc_html_e('No SMS will be sent', 'safe-assistant'); ?>');
            <?php else : ?>
                warningMessages.push('📱 <?php esc_html_e('SMS Notifications:', 'safe-assistant'); ?>');
                warningMessages.push('   • ❌ <?php esc_html_e('SMS notifications are DISABLED', 'safe-assistant'); ?>');
            <?php endif; ?>
            warningMessages.push('');

            // Processing info
            warningMessages.push('⚙️ <?php esc_html_e('PROCESSING INFORMATION:', 'safe-assistant'); ?>');
            warningMessages.push('   🔄 <?php esc_html_e('Process runs automatically in background', 'safe-assistant'); ?>');
            warningMessages.push('   📊 <?php esc_html_e('Check \"Logs\" and \"Results\" tabs for progress updates', 'safe-assistant'); ?>');
            warningMessages.push('   ⏱️ <?php esc_html_e('Processing speed:', 'safe-assistant'); ?> ' + batchSize + ' <?php esc_html_e('rows per minute', 'safe-assistant'); ?>');
            warningMessages.push('   📈 <?php esc_html_e('Real-time statistics will be saved and displayed', 'safe-assistant'); ?>');
            warningMessages.push('   🚫 <?php esc_html_e('Cannot upload new file while processing', 'safe-assistant'); ?>');
            warningMessages.push('');
            warningMessages.push('='.repeat(50));
            warningMessages.push('❓ <?php esc_html_e('CONFIRM: Do you want to start the import process?', 'safe-assistant'); ?>');
            warningMessages.push('   <?php esc_html_e('This action cannot be undone easily!', 'safe-assistant'); ?>');

            var confirmMessage = warningMessages.join('\n');

            if (confirm(confirmMessage)) {
                // User confirmed, submit the form
                $('#submit_csv_btn').off('click').attr('type', 'submit').trigger('click');
            }
        }
    });
</script>