<?php

namespace SuperbThemes\AddonsRecommender;

defined('ABSPATH') || exit();

class NoticeController
{
    const PREFIX = 'spbrecommender_notice_';
    const PREFIX_DELAY = 'spbrecommender_notice_delay_';

    const ALLOWED_HTML = [
        'div'     => [
            'class' => [],
            'style' => []
        ],
        'p'      => [
            'id' => [],
            'class' => []
        ],
        'h2'      => [
            'class' => []
        ],
        'ul'      => [
            'class' => []
        ],
        'li'      => [
            'class' => []
        ],
        'span' => [
            'class' => []
        ],
        'a'      => [
            'class' => [],
            'href' => [],
            'rel'  => [],
            'target' => [],
            'id' => []
        ],
        'em'     => [
            'class' => []
        ],
        'strong' => [
            'class' => []
        ],
        'img' => [
            'class' => [],
            'alt' => [],
            'src' => [],
            'width' => [],
            'height' => []
        ],
        'br'     => [],
        'style' => []
    ];

    private static $notices = [];

    public static function init()
    {
        $notices = array(
            array(
                'unique_id' => 'addons_recommendation',
                'content' => "addons-recommendation.php",
                'delay' => '+1 days'
            ),
            array(
                'unique_id' => 'addons_recommendation-def',
                'content' => "addons-recommendation-def.php",
                'requires_dismiss' => 'addons_recommendation',
                'delay' => '+3 days'
            )
        );
        self::$notices = $notices;

        add_action('admin_notices', array(__CLASS__, 'AdminNotices'));
        add_action('wp_ajax_spbrec_dismiss_notice', array(__CLASS__, 'MaybeDismissNotice'));
        add_action('admin_init', array(__CLASS__, 'MaybeHandleAction'));
    }

    public static function AdminNotices()
    {
        foreach (self::$notices as $notice) {
            $notice_path = trailingslashit(__DIR__) . '/' . $notice['content'];
            if (!file_exists($notice_path)) {
                continue;
            }

            if (isset($notice['requires_dismiss']) && !get_user_meta(get_current_user_id(), self::PREFIX . $notice['requires_dismiss'], true)) {
                continue;
            }

            // Check if the notice has been dismissed.
            if (get_user_meta(get_current_user_id(), self::PREFIX . $notice['unique_id'], true)) {
                continue;
            }

            // Check if the notice is delayed
            if (isset($notice['delay'])) {
                $delay_init = get_user_meta(get_current_user_id(), self::PREFIX_DELAY . $notice['unique_id'], true);
                if (!$delay_init) {
                    update_user_meta(get_current_user_id(), self::PREFIX_DELAY . $notice['unique_id'], time());
                    continue;
                }

                $delay = strtotime($notice['delay'], $delay_init);
                if ($delay > time()) {
                    continue;
                }
            }

            ob_start();
            include_once $notice_path;
            $content = ob_get_clean();
            echo wp_kses($content, self::ALLOWED_HTML);
        }

        self::PrintScripts();
    }

    public static function PrintScripts()
    {
?>
        <script>
            window.addEventListener("load", function() {
                setTimeout(function() {
                    var notice_ids = <?php echo wp_json_encode(array_column(self::$notices, 'unique_id')); ?>;
                    var nonce = "<?php echo esc_attr(wp_create_nonce('spbrec_dismiss_notice')); ?>";
                    var ajaxurl = "<?php echo esc_url(admin_url('admin-ajax.php')); ?>";

                    notice_ids.forEach(function(notice) {
                        var dismissBtn = document.querySelector(
                            "." + notice + " .notice-dismiss"
                        );

                        if (!dismissBtn) return;

                        // Add an event listener to the dismiss button.
                        dismissBtn.addEventListener("click", function(event) {
                            var httpRequest = new XMLHttpRequest(),
                                postData = "";

                            // Build the data to send in our request.
                            // Data has to be formatted as a string here.
                            postData += "id=" + notice;
                            postData += "&action=spbrec_dismiss_notice";
                            postData += "&nonce=" + nonce;

                            httpRequest.open("POST", ajaxurl);
                            httpRequest.setRequestHeader(
                                "Content-Type",
                                "application/x-www-form-urlencoded"
                            );
                            httpRequest.send(postData);
                        });
                    });
                }, 0);
                var i_a_btn = document.getElementById('st-sa-notification-button-recommender-install-btn');
                if (i_a_btn) {
                    i_a_btn.addEventListener('click', function() {
                        i_a_btn.innerText = 'Installing...';
                        // disable a tag
                        i_a_btn.style.opacity = '0.5';
                        i_a_btn.style.cursor = 'wait';
                        setTimeout(() => {
                            i_a_btn.removeAttribute('href');
                            var i_a_notice = document.getElementById('st-sa-notification-button-recommender-install-notice');
                            if (i_a_notice) {
                                i_a_notice.style.display = 'flex';
                            }
                        }, 0);
                    });
                }
            });
        </script>
<?php
    }

    public static function MaybeHandleAction()
    {
        try {
            if (!isset($_GET['spbrec_enable_recommended'])) {
                return;
            }
            $nonce = sanitize_text_field(wp_unslash($_GET['spbrec_enable_recommended']));
            if (!wp_verify_nonce($nonce, 'spbrec_action') || !current_user_can('install_plugins')) {
                return;
            }

            if (!class_exists('WP_Upgrader')) {
                require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
            }
            if (!class_exists('Plugin_Upgrader')) {
                require_once ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php';
            }
            if (!class_exists('spbrecommender_upgrader_skin')) {
                require_once untrailingslashit(__DIR__) . '/recommender-skin.php';
            }
            if (!function_exists('get_plugins')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
            if (!function_exists('plugins_api')) {
                require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
            }

            // Initialize WP_Upgrader
            $skin = new spbrecommender_upgrader_skin();

            $upgrader = new \Plugin_Upgrader($skin);

            // Install the plugin, or update it if it's already installed
            $installed_plugins = get_plugins();
            if (isset($installed_plugins['superb-blocks/plugin.php'])) {
                $upgrader->upgrade('superb-blocks/plugin.php');
                $result = true;
            } else {
                $plugins_api = \plugins_api(
                    'plugin_information',
                    array(
                        'slug' => 'superb-blocks',
                        'fields' => array(
                            'short_description' => false,
                            'sections' => false,
                            'requires' => false,
                            'rating' => false,
                            'ratings' => false,
                            'downloaded' => false,
                            'last_updated' => false,
                            'added' => false,
                            'tags' => false,
                            'compatibility' => false,
                            'homepage' => false,
                            'donate_link' => false,
                        ),
                    )
                );

                if (is_wp_error($plugins_api)) {
                    return false;
                }
                $result = $upgrader->install($plugins_api->download_link);
            }

            if (!$result || is_wp_error($result)) {
                return;
            }

            $activate = activate_plugin('superb-blocks/plugin.php');
            if (is_wp_error($activate)) {
                return;
            }

            // Redirect to plugin dashboard
            wp_safe_redirect(admin_url('admin.php?page=superbaddons'));
            exit;
        } catch (\Exception $e) {
            return;
        }
    }

    public static function MaybeDismissNotice()
    {
        // Sanity check: Early exit if we're not on a spbrec_dismiss_notice action.
        if (!isset($_POST['action']) || 'spbrec_dismiss_notice' !== $_POST['action']) {
            return;
        }

        // Sanity check: Early exit if the ID of the notice does not exist.
        if (!isset($_POST['id']) || !in_array($_POST['id'], array_column(self::$notices, 'unique_id'))) {
            return;
        }

        // Notice ID exists in array, so we can safely use it.
        $notice_id = sanitize_text_field(wp_unslash($_POST['id']));

        // Security check: Make sure nonce is OK. check_ajax_referer exits if it fails.
        check_ajax_referer('spbrec_dismiss_notice', 'nonce', true);

        // Dismiss the notice.
        self::DismissNotice($notice_id);
    }

    public static function DismissNotice($notice_id)
    {
        update_user_meta(get_current_user_id(), self::PREFIX . $notice_id, true);
    }

    public static function Cleanup()
    {
        foreach (self::$notices as $notice) {
            delete_metadata('user', 0, self::PREFIX . $notice['unique_id'], false, true);
            if (isset($notice['delay'])) {
                delete_metadata('user', 0, self::PREFIX_DELAY . $notice['unique_id'], false, true);
            }
        }
    }
}
