<?php
if (!defined('ABSPATH')) {
    return;
}
if (is_plugin_active('superb-blocks/plugin.php')) {
    return;
}

$spba_plugin_url = admin_url('plugin-install.php?tab=plugin-information&plugin=superb-blocks&TB_iframe=true&width=772&height=550');
$nonce_url = add_query_arg(
    array(
        'spbrec_enable_recommended' => wp_create_nonce('spbrec_action'),
    ),
    admin_url("plugins.php")
);

?>
<div class="notice notice-warning settings-error is-dismissible <?php echo esc_attr($notice['unique_id']); ?>">
    <?php add_thickbox(); ?>
    <p>
        <strong>
            <?php echo esc_html__("The following plugin is recommended to enhance your WordPress exprience:", "superb-tables"); ?>
            <a class="thickbox open-plugin-details-modal" href="<?php echo esc_url($spba_plugin_url); ?>">
                <?php echo esc_html__("SuperbThemes", "superb-tables"); ?>
            </a>
        </strong>
    </p>
    <div style="margin-bottom:10px;">
        <?php if (!isset($_GET['spbrec_enable_recommended'])): ?>
            <a id="st-sa-notification-button-recommender-install-btn" class="button button-large button-primary" href="<?php echo esc_url($nonce_url); ?>">
                <?php echo esc_html__("Install Now", "superb-tables"); ?>
            </a>
            <a class="button button-large thickbox open-plugin-details-modal" href="<?php echo esc_url($spba_plugin_url); ?>">
                <?php echo esc_html__("Read More", "superb-tables"); ?>
            </a>
            <p id="st-sa-notification-button-recommender-install-notice"><?php echo esc_html__("Please wait a moment while we're getting the plugin ready for you.", 'superb-tables'); ?></p>
        <?php else: ?>
            <?php wp_verify_nonce('spbrec_enable_recommended', 'spbrec_action'); ?>
            <?php add_thickbox(); ?>
            <p><?php echo esc_html("Unfortunately WordPress was not able to automatically install the plugin for you.", 'superb-tables'); ?></p>
            <p><?php echo esc_html("You can view and install the plugin manually by clicking the button below:", 'superb-tables'); ?></p>

            <a class="button button-large button-primary thickbox open-plugin-details-modal" href="<?php echo esc_url($spba_plugin_url); ?>">
                <?php echo esc_html__("View Plugin", "superb-tables"); ?>
            </a>
        <?php endif; ?>
    </div>

    <button type="button" class="notice-dismiss">
        <span class="screen-reader-text">
            <?php echo esc_html__("Dismiss this notice.", "superb-tables"); ?>
        </span>
    </button>
    <style>
        #st-sa-notification-button-recommender-install-notice {
            display: none;
            align-items: center;
            margin: 10px 10px 0 0;
            cursor: wait;
        }
    </style>
</div>