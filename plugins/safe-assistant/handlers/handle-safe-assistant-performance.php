<?php

function is_woocommerce_page()
{
    return function_exists("is_woocommerce") &&
        (is_woocommerce() || is_cart() || is_checkout() || is_account_page());
}

function is_woocommerce_admin_page()
{
    $screen = isset($_GET["page"]) ? $_GET["page"] : "";
    $post_type = isset($_GET["post_type"])
        ? $_GET["post_type"]
        : get_post_type();

    $allowed_pages = [
        "wc-admin",
        "wc-settings",
        "wc-orders",
        "wc-reports",
        "wc-status",
        "wc-addons",
        "novin-signature-admin",
        "novin-advance-shipping",
    ];

    $allowed_post_types = ["product", "shop_order", "shop_coupon", "wooi"];

    if ((is_admin()) && (
            in_array($screen, $allowed_pages) ||
            in_array($post_type, $allowed_post_types))
    ) {
        return true;
    }

    return false;
}

if (is_admin()) {
    if (sa_get_option("remove_wp_logo_admin_bar")) {
        add_action(
            "admin_bar_menu",
            function ($wp_admin_bar) {
                $wp_admin_bar->remove_node("wp-logo");
            },
            999
        );
    }

    if (sa_get_option("disable_admin_bar")) {
        add_filter("show_admin_bar", "__return_false");
    }

    if (sa_get_option("remove_dashboard_widgets")) {
        add_action("wp_dashboard_setup", function () {
            remove_meta_box("dashboard_primary", "dashboard", "side");
            remove_meta_box("dashboard_quick_press", "dashboard", "side");
            remove_meta_box("dashboard_activity", "dashboard", "normal");
        });
    }

    if (sa_get_option("disable_gutenberg")) {
        add_filter("use_block_editor_for_post", "__return_false", 10);
        add_filter("use_block_editor_for_post_type", "__return_false");
        add_filter("gutenberg_can_edit_post_type", "__return_false");
    }

    if (sa_get_option("disable_wp_updates")) {
        if (strpos($_SERVER["PHP_SELF"], "update-core.php") === false) {
            add_filter("automatic_updater_disabled", "__return_true");
            add_filter("auto_update_core", "__return_false");
            add_filter("auto_update_plugin", "__return_false");
            add_filter("auto_update_theme", "__return_false");
            add_filter("pre_site_transient_update_plugins", "__return_null");
            add_filter("pre_site_transient_update_themes", "__return_null");
            add_filter("pre_site_transient_update_core", "__return_null");
            add_filter("pre_transient_update_plugins", "__return_null");
            add_filter("pre_transient_update_themes", "__return_null");
            add_filter("pre_transient_update_core", "__return_null");
            remove_action("init", "wp_schedule_update_checks");
            remove_action("admin_init", "_maybe_update_core");
            remove_action("admin_init", "_maybe_update_plugins");
            remove_action("admin_init", "_maybe_update_themes");
            remove_action("wp_version_check", "wp_version_check");
            wp_clear_scheduled_hook("wp_version_check");
            wp_clear_scheduled_hook("wp_update_plugins");
            wp_clear_scheduled_hook("wp_update_themes");
            wp_clear_scheduled_hook("wp_maybe_auto_update");
        }
    }
}

if (!is_woocommerce_admin_page() && is_admin()) {
    if (sa_get_option("disable_wc_admin")) {
        add_filter("woocommerce_admin_disabled", "__return_true");
        remove_action("admin_notices", "woocommerce_admin_notices");
        add_action("admin_menu", "kgmwcbloat_remove_extensions_submenu", 999);
        function kgmwcbloat_remove_extensions_submenu()
        {
            remove_submenu_page("woocommerce", "wc-addons");
            remove_submenu_page("woocommerce", "wc-addons&section=helper");
            remove_submenu_page("woocommerce", "wc-admin&path=/extensions");
        }
        //add_action("widgets_init", "kgmwcbloat_wc_widgets_remove", 99);
        function kgmwcbloat_wc_widgets_remove()
        {
            unregister_widget("WC_Widget_Cart");
            unregister_widget("WC_Widget_Products");
            unregister_widget("WC_Widget_Layered_Nav");
            unregister_widget("WC_Widget_Price_Filter");
            unregister_widget("WC_Widget_Rating_Filter");
            unregister_widget("WC_Widget_Recent_Reviews");
            unregister_widget("WC_Widget_Product_Search");
            unregister_widget("WC_Widget_Recently_Viewed");
            unregister_widget("WC_Widget_Product_Tag_Cloud");
            unregister_widget("WC_Widget_Product_Categories");
            unregister_widget("WC_Widget_Top_Rated_Products");
            unregister_widget("WC_Widget_Layered_Nav_Filters");
        }
    }
    if (sa_get_option("disable_wc_marketing_hub")) {
        add_filter(
            "woocommerce_allow_marketplace_suggestions",
            "__return_false",
            999
        );

        /**
         * Disable all WooCommerce admin features
         * @first filter seems in deprecation
         * @second filter seems in deprecation
         */
        add_filter("woocommerce_admin_disabled", "__return_true");
        add_filter("woocommerce_marketing_menu_items", "__return_empty_array");
        add_filter(
            "woocommerce_admin_features",
            function (array $features): array {
                $features = [];
                return $features;
            },
            90
        );

        /**
         * Disable report text
         */
        add_action("admin_head", "kgmwcbloat_remove_reports_text");
        function kgmwcbloat_remove_reports_text()
        {
?>
            <script type="text/javascript">
                jQuery(document).ready(function($) {
                    if (window.location.search.includes('wc-reports')) {
                        $("strong:contains('WooCommerce 4.0')")
                            .parents('#message').remove();
                    }
                });
            </script>
<?php
        }

        /**
         * Dequeue admin styles
         */
        add_action(
            "admin_enqueue_scripts",
            "kgmwcbloat_disable_wc_admin_styles",
            99
        );
        function kgmwcbloat_disable_wc_admin_styles()
        {
            wp_dequeue_script("wc-admin-app");
            wp_dequeue_style("wc-admin-app");
            wp_dequeue_style("wc-onboarding");
        }
        remove_action("admin_init", [
            "Automattic\WooCommerce\Admin\Features\Marketing",
            "init",
        ]);
        remove_action("admin_init", [
            "Automattic\WooCommerce\Admin\Features\MarketplaceSuggestions",
            "init",
        ]);
    }

    if (sa_get_option("disable_wc_blocks_frontend")) {
        add_filter("woocommerce_use_blocks", "__return_false");
    }
}

if (sa_get_option('disbale_woodmart_patch_cheker')) {
    add_filter("woodmart_load_patches_map_from_server", function ($enabled) {
        if (is_admin() && isset($_GET["page"]) && $_GET["page"] === "xts_patcher") {
            return true;
        }
        return false;
    });
}
