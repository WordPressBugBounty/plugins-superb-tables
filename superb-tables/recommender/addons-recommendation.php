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

<div class="notice notice-info is-dismissible <?php echo esc_attr($notice['unique_id']); ?>">
    <span class="st-sa-notification-wrapper">
        <span class="st-sa-notification-wrapper-info"><?php echo esc_html__("Action Required", 'superb-tables'); ?></span>
        <span class="st-sa-notification-wrapper-headline"><?php echo esc_html__("Enable All Features", 'superb-tables'); ?></span>
        <span class="st-sa-notification-wrapper-paragraph"><?php echo esc_html__("Install the SuperbThemes companion plugin now to enable every great feature we offer for our themes and the WordPress editor.", 'superb-tables'); ?></span>
        <?php if (!isset($_GET['spbrec_enable_recommended'])): ?>
            <span class="st-sa-notification-buttons-wrapper">
                <a id="st-sa-notification-button-recommender-install-btn" href="<?php echo esc_url($nonce_url); ?>" class="st-sa-notification-buttons-green"><?php echo esc_html__("Install & Activate", 'superb-tables'); ?></a>
                <p id="st-sa-notification-button-recommender-install-notice"><?php echo esc_html__("Please wait a moment while we're getting the plugin ready for you.", 'superb-tables'); ?></p>
            </span>
        <?php else: ?>
            <?php wp_verify_nonce('spbrec_enable_recommended', 'spbrec_action'); ?>
            <?php add_thickbox(); ?>
            <p><?php echo esc_html("Unfortunately WordPress was not able to automatically install the plugin for you.", 'superb-tables'); ?></p>
            <p><?php echo esc_html("You can view and install the plugin manually by clicking the button below:", 'superb-tables'); ?></p>

            <a class="button button-large button-primary thickbox open-plugin-details-modal" href="<?php echo esc_url($spba_plugin_url); ?>">
                <?php echo esc_html__("View Plugin", "superb-tables"); ?>
            </a>
        <?php endif; ?>
    </span>
    <style>
        .st-sa-notification-wrapper {
            padding: 30px 400px 30px 30px;
            display: inline-block;
            width: 100%;
            -webkit-box-sizing: border-box;
            box-sizing: border-box;
            position: relative;
            background-size: contain;
        }

        .st-sa-notification-wrapper:after {
            display: block;
            width: 380px;
            content: " ";
            background-image: url(<?php echo esc_url(untrailingslashit(plugins_url('', __FILE__)) . "/addons-notice.png"); ?>);
            background-position: bottom center;
            position: absolute;
            bottom: -1px;
            right: -38px;
            height: 235px;
            background-size: contain;
            background-repeat: no-repeat;
        }

        .st-sa-notification-wrapper .st-sa-notification-wrapper-info {
            background: #fff8e1;
            color: #ff8f00;
            font-weight: bold;
            padding: 6px 10px;
            border-radius: 30px;
            display: inline-block;
        }

        .st-sa-notification-wrapper .st-sa-notification-wrapper-headline {
            width: 100%;
            display: inline-block;
            font-weight: 500;
            color: #263238;
            font-size: 30px;
            line-height: 130%;
            margin: 15px 0 20px;
        }

        .st-sa-notification-wrapper .st-sa-notification-wrapper-paragraph {
            display: inline-block;
            width: 100%;
            color: #546e7a;
            font-size: 16px;
            line-height: 144%;
            max-width: 500px;
            margin-bottom: 10px;
        }

        .st-sa-notification-buttons-wrapper {
            display: -webkit-box;
            display: -ms-flexbox;
            display: flex;
            -ms-flex-wrap: wrap;
            flex-wrap: wrap;
        }

        a.st-sa-notification-buttons-white,
        a.st-sa-notification-buttons-white:hover,
        a.st-sa-notification-buttons-white:active {
            border: 1px solid #cfd8dc;
            padding: 10px 15px;
            -webkit-box-shadow: 0px 1px 2px 0px rgba(0, 0, 0, 0.26);
            box-shadow: 0px 1px 2px 0px rgba(0, 0, 0, 0.26);
            color: #263238;
            font-weight: 500;
            margin-right: 15px;
            text-decoration: none;
            border-radius: 6px;
            margin: 10px 10px 0 0;
            font-size: 16px;
        }

        a.st-sa-notification-buttons-green,
        a.st-sa-notification-buttons-green:hover,
        a.st-sa-notification-buttons-green:active {
            border: 1px solid #cfd8dc;
            padding: 10px 15px;
            -webkit-box-shadow: 0px 1px 2px 0px rgba(0, 0, 0, 0.26);
            box-shadow: 0px 1px 2px 0px rgba(0, 0, 0, 0.26);
            color: #fff;
            font-weight: 500;
            margin-right: 15px;
            text-decoration: none;
            border-radius: 6px;
            background: -webkit-gradient(linear, left top, left bottom, from(#1fc76e), to(#00b67a)), -webkit-gradient(linear, left bottom, left top, from(rgba(255, 255, 255, 0.2)), to(rgba(255, 255, 255, 0.2)));
            background: -o-linear-gradient(top, #1fc76e 0%, #00b67a 100%), -o-linear-gradient(bottom, rgba(255, 255, 255, 0.2), rgba(255, 255, 255, 0.2));
            background: linear-gradient(180deg, #1fc76e 0%, #00b67a 100%), linear-gradient(0deg, rgba(255, 255, 255, 0.2), rgba(255, 255, 255, 0.2));
            border: 1px solid #ffffff33;
            margin: 10px 10px 0 0;
        }

        #st-sa-notification-button-recommender-install-notice {
            display: none;
            align-items: center;
            margin: 10px 10px 0 0;
            cursor: wait;
        }

        @media screen and (max-width: 1200px) {
            .st-sa-notification-wrapper {
                padding: 30px 380px 30px 30px;
            }
        }

        @media screen and (max-width: 1060px) {
            .st-sa-notification-wrapper {
                padding: 20px 0px 20px 20px;
                background-image: none !important;
            }

            .st-sa-notification-wrapper:after {
                display: none;
            }
        }
    </style>
</div>