<div class="postbox">
    <div class="inside">
        <h3><?= $reset_section_title ?></h3>

        <form id="addon_user_importer_reverse_form" method="post" class="addon-user-importer-action-form">
            <input type="hidden" name="action" value="addon_user_importer_reverse">
            <input type="hidden" name="nonce" value="<?= $reverse_nonce ?>">
            <p>
                <input type="submit" name="submit_reverse" class="button button-secondary" value="<?= $revert_button_text ?>" />
                <span class="spinner"></span>
            </p>
        </form>

        <form id="addon_user_importer_reset_form" method="post" class="addon-user-importer-action-form">
            <input type="hidden" name="action" value="addon_user_importer_reset">
            <input type="hidden" name="nonce" value="<?= $reset_nonce ?>">
            <p>
                <input type="submit" name="submit_reset_factory" class="button button-secondary" value="<?= $reset_button_text ?>" />
                <span class="spinner"></span>
            </p>
        </form>

        <p>
            <a href="<?= $force_stop_url ?>" class="button button-secondary"><?= $force_stop_button_text ?></a>
        </p>
    </div>
</div>
<script>
    jQuery(document).ready(function($) {
        $('.addon-user-importer-action-form').on('submit', function(e) {
            e.preventDefault();

            var form = $(this);
            var submitButton = form.find('input[type="submit"]');
            var spinner = form.find('.spinner');

            submitButton.prop('disabled', true);
            spinner.addClass('is-active');

            $.post(ajaxurl, form.serialize(), function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            }).always(function() {
                submitButton.prop('disabled', false);
                spinner.removeClass('is-active');
            });
        });
    });
</script>