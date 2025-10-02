<div class="postbox">
    <div class="inside">
        <h3><?php esc_html_e('Column Information:', 'safe-assistant'); ?></h3>
        <p>
            <span class="highlight"><?php esc_html_e('Column 1:', 'safe-assistant') ?></span> <?php esc_html_e('Mobile Number', 'safe-assistant'); ?><br>
            <span class="highlight"><?php esc_html_e('Column 2:', 'safe-assistant') ?></span> <?php esc_html_e('Previous Purchase Amount', 'safe-assistant'); ?><br>
            <span class="highlight"><?php esc_html_e('Column 3:', 'safe-assistant') ?></span> <?php esc_html_e('Charge Percentage', 'safe-assistant'); ?><br>
            <span class="highlight"><?php esc_html_e('Column 4:', 'safe-assistant') ?></span> <?php esc_html_e('Fixed Charge Amount', 'safe-assistant'); ?><br>
            <span class="highlight"><?php esc_html_e('Column 5:', 'safe-assistant') ?></span> <?php esc_html_e('First Name', 'safe-assistant'); ?><br>
            <span class="highlight"><?php esc_html_e('Column 6:', 'safe-assistant') ?></span> <?php esc_html_e('Last Name', 'safe-assistant'); ?><br>
            <span class="highlight"><?php esc_html_e('Column 7:', 'safe-assistant') ?></span> <?php esc_html_e('State', 'safe-assistant'); ?><br>
            <span class="highlight"><?php esc_html_e('Column 8:', 'safe-assistant') ?></span> <?php esc_html_e('City', 'safe-assistant'); ?><br>
            <span class="highlight"><?php esc_html_e('Column 9:', 'safe-assistant') ?></span> <?php esc_html_e('Purchase Date', 'safe-assistant'); ?><br>
            <span class="highlight"><?php esc_html_e('Column 10:', 'safe-assistant') ?></span> <?php esc_html_e('Expiration Date', 'safe-assistant'); ?><br>
            ℹ️ <?php esc_html_e('Only the first column', 'safe-assistant'); ?> <span class="highlight"><?php esc_html_e('username is required', 'safe-assistant'); ?></span>. <?php esc_html_e('It can be a mobile number or random English characters.', 'safe-assistant'); ?><br>
            ℹ️ <?php esc_html_e('Amounts must be in Toman.', 'safe-assistant'); ?><br>
            ℹ️ <?php esc_html_e('Use the charge percentage field if you want the wallet charge to be based on the previous purchase amount.', 'safe-assistant'); ?><br>
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
                <th scope="row"><label for="if_user_exist_continue"><?php esc_html_e('Charge account if user exists?', 'safe-assistant'); ?></label></th>
                <td><input type="checkbox" name="if_user_exist_continue" id="if_user_exist_continue" value="1" <?php checked($continue_if_exists_value, 1); ?> /></td>
            </tr>
            <tr>
                <th scope="row"><label for="not_only_wallet_first_time"><?php esc_html_e('Charge wallet for users with previous charges?', 'safe-assistant'); ?></label></th>
                <td><input type="checkbox" name="not_only_wallet_first_time" id="not_only_wallet_first_time" value="1" <?php checked($not_only_wallet_first_time_value, 1); ?> /></td>
            </tr>
            <tr>
                <th scope="row"><label for="min_charge"><?php esc_html_e('Minimum Charge Amount', 'safe-assistant'); ?></label></th>
                <td><input type="number" name="min_charge" id="min_charge" min="0" step="50000" value="<?php echo esc_attr($min_charge_value); ?>" /> <?php esc_html_e('Toman', 'safe-assistant'); ?></td>
            </tr>
            <tr>
                <th scope="row"><label for="expire_date"><?php esc_html_e('Expiration Days', 'safe-assistant'); ?></label></th>
                <td><input type="number" name="expire_date" id="expire_date" value="<?php echo esc_attr($expire_date_value); ?>" /></td>
            </tr>
        </table>
        <?php if (!get_transient(ADDON_USER_IMPORTER_SLUG . '_task')) : ?>
            <p class="submit">
                <input type="submit" name="submit_csv" class="button button-primary" value="<?php esc_attr_e('Upload File', 'safe-assistant'); ?>" />
            </p>
        <?php else : ?>
            <div class="notice notice-warning inline">
                <p><?php esc_html_e('A process is currently running.', 'safe-assistant'); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>