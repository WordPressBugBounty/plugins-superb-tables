<?php

if (!defined('WPINC')) {
    die;
}

class spbtbl_Plugin
{
    private $version;
    private $page_slug;
    private $page_hook;
    private $base_url;
    private $base_dir;
    private $db;
    private $user_caps;

    public function __construct($_version, $_base_url = false, $base_dir)
    {
        require_once 'spbtbl-plugin-db.php';
        $this->version = $_version;
        $this->page_slug = 'spbtbl_plugin';
        $this->user_caps = 'manage_categories';
        $this->db = spbtbl_DB_Table::get_instance();
        $this->base_dir = $base_dir;
        add_action('admin_menu', array($this, 'spbtbl_add_menu_items'));
        add_action('admin_enqueue_scripts', array($this, 'spbtbl_backend_enqueue'));
        add_action('wp_enqueue_scripts', array($this, 'spbtbl_frontend_enqueue'));
        add_action('current_screen', array($this, 'spbtbl_eventHandler'));
        add_shortcode('spbtbl_sc', array($this, 'spbtbl_handleSC'));
        add_action('admin_init', array($this, 'spbtbl_spbThemesNotification'), 9);

        if (!$_base_url) {
            $this->base_url = plugins_url('', dirname(__FILE__));
        } else {
            $this->base_url = $_base_url;
        }

        if (is_admin() && !class_exists('SuperbThemes\AddonsRecommender\NoticeController')) {
            require_once $this->base_dir . '/recommender/recommender.php';
            \SuperbThemes\AddonsRecommender\NoticeController::init();
        }
    }
    public function spbtbl_spbThemesNotification()
    {
        $notifications = include($this->base_dir . '/admin_notification/Autoload.php');
        $options = array("delay" => "+3 days");
        $notifications->Add("spbtbl_admin_notification", "Unlock All Features with Superb Tables Premium", "
		
            Take advantage of the up to <span style='font-weight:bold;'>40% discount</span> and unlock all features for Superb Tables Premium. 
            The discount is only available for a limited time.
    
            <div>
            <a style='margin-bottom:15px;' class='button button-large button-secondary' target='_blank' href='https://superbthemes.com/plugins/superb-tables/'>Read more</a> <a style='margin-bottom:15px;' class='button button-large button-primary' target='_blank' href='https://superbthemes.com/plugins/superb-tables/'>Buy now</a>
            </div>
    
            ", "info", $options);
        $notifications->Boot();
    }



    public function spbtbl_add_menu_items()
    {
        $user_caps = apply_filters('spbtbl_user_capabilities', $this->user_caps);
        $has_menu = menu_page_url('spbhlpr', false);
        if ($has_menu) {
            $this->page_hook = add_submenu_page('spbhlpr', 'Superb Tables', 'Superb Tables', $user_caps, $this->page_slug, array($this, "spbtbl_print_page"), 1);
        } else {
            $this->page_hook = add_menu_page('Superb Tables', 'Superb Tables', $user_caps, $this->page_slug, array($this, 'spbtbl_print_page'), $this->base_url . "/img/icon.png");
        }
    }

    public function spbtbl_backend_enqueue($hook)
    {
        if ($this->page_hook != $hook) {
            return;
        }
        wp_enqueue_style('superb-stylesheet', $this->base_url . '/css/table-plugin.css', false, $this->version, 'all');
        wp_enqueue_style('spbtbl-stylesheet', $this->base_url . '/css/data-table.css', false, $this->version, 'all');
        wp_enqueue_script('spbtbl-script', $this->base_url . '/js/table-plugin.js', array('jquery'), $this->version);
        wp_enqueue_script('tablednd', $this->base_url . '/js/jquery.tablednd.js');
        wp_enqueue_script('jquery-ui-dialog');
    }

    public function spbtbl_frontend_enqueue()
    {
        wp_enqueue_style('spbtbl-stylesheet', $this->base_url . '/css/data-table.css', false, $this->version, 'all');
    }


    public function spbtbl_print_page()
    {
?>
        <div class="review-banner">
            <p><span>&#128075;</span> Hi there! We sincerely hope you're enjoying our Superb Table plugin. Please consider <a href="https://wordpress.org/support/plugin/superb-tables/reviews/" target="_blank">reviewing it here</a>. It means the world to us!</p>
        </div>

        <div class="spbtbl_wrapper">
            <h1 class="spbtbl_backend_headline">Superb Tables</h1>

            <?php
            $func = isset($_GET['func']) ? sanitize_text_field(wp_unslash($_GET['func'])) : false;
            $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : false;
            $editnum = isset($_GET['editnum']) ? intval(sanitize_text_field(wp_unslash($_GET['editnum']))) : false;
            $savedTable = isset($_GET['savedTable']) ? sanitize_text_field(wp_unslash($_GET['savedTable'])) : false;

            if ($func && $func == 'add_table') {
                // 'ADD TABLE' UI
                $this->spbtbl_setup_UI(null);
            } elseif ($func && $func == 'edit_table' && $editnum !== false && wp_verify_nonce($nonce, 'edit_table')) {
                // 'EDIT TABLE' UI
                $table = $this->db->get($editnum);
                if ($table) {
                    $this->spbtbl_setup_UI($table, $editnum, $savedTable);
                }
            } else {
                $deletedTable = isset($_GET['deletedTable']) ? sanitize_text_field(wp_unslash($_GET['deletedTable'])) : false;

                echo "<div class='spbtbl_tip'><span>Tip:</span> Copy & Paste shortcodes in your post/page to show the table.</div>";
                echo '<span class="spbtbl_speaking_bubble">Unlock all features instantly</span><a href="https://superbthemes.com/plugins/superb-tables/" class="view-premium-version view-premium-version-all-right" target="_blank">View Premium Version</a>';
                // 'LIST TABLES' UI
                printf('<a class="spbtbl_btn spbtbl_btn_new_table" href="%s">%s</a>', esc_url(admin_url('admin.php?page=' . $this->page_slug . "&func=add_table")), 'Add New Table');
                if ($deletedTable) {
                    echo '<p class="spbtbl_removed">The table "' . esc_attr($deletedTable) . '" has successfully been deleted.</p>';
                }
                echo "<table class='spbtbl_backend-table viewalltables'> 
				<tr>
				<th>Table Name</th>
				<th class='shortcode-explanation'>Table Shortcode</th>
				<th>Edit Table</th>
				<th class='spbtbl_btn_copytable'>Copy Table</th>
				<th>Delete Table</th>
				</tr>";
                $all = $this->db->get_all();
                if ($all) {
                    $tableCount = count($all);
                    for ($i = 0; $i < $tableCount; $i++) {
            ?> <script type="text/javascript">
                            var colorScheme = "none";
                            var tableStyle = 0;
                            var fontsize_td = 0;
                            var fontsize_th = 0;
                            var floatmode = 0;
                            var fullwidth = 0;
                            var disableschema = 0;
                        </script> <?php

                                    printf('<tr>
							<td>%s</td>
							<td>
							<input type="text" class="spbtbl_shortcode" value="[spbtbl_sc id=%s]" readonly>
							</td>
							<td>
							<a class="spbtbl_btn" href="%s">%s</a>
							</td>
							<td>
							<a class="spbtbl_btn delete_btn" href="%s">%s</a>
							</td>
							</tr>', esc_html($all[$i]['name']), esc_attr($all[$i]['id']), esc_url(wp_nonce_url(admin_url('admin.php?page=' . $this->page_slug . "&func=edit_table" . '&editnum=' . $all[$i]['id']), 'edit_table')), 'Edit Table', esc_url(wp_nonce_url(admin_url('admin.php?page=' . $this->page_slug . "&func=delete_table&tableName=" . $all[$i]['name'] . '&deleteNum=' . $all[$i]['id']), 'delete_table')), 'Delete Table');
                                }
                            }
                        }
                        echo "</table>"; ?>

            <div class="spbtbl_discount">
                <div>Use our limited time offer & get a <strong>discount</strong> on Superb Tables Premium </div>
                <a href="https://superbthemes.com/plugins/superb-tables/" target="_blank">Get Superb Tables Premium</a>
            </div>
        </div>
    <?php
    }

    private function spbtbl_setup_UI($table, $editnum = false, $savedTable = false)
    {
        $tableName = $table != null ? esc_attr($table['name']) : '';
        $tableColor = $table != null ? esc_attr($table['color']) : 'standard';
        $tableStyle = $table != null ? intval($table['style']) : 0;
        $fontsize_td = $table != null ? intval($table['fontsize_td']) : 14;
        $fontsize_th = $table != null ? intval($table['fontsize_th']) : 15;
        $colsarray = $table != null ? $table['cols'] : ['', '', '', ''];
        $rowsarray = $table != null ? $table['rows'] : ['', '', '', '', '', '', '', ''];
        $floatmode = $table != null ? intval($table['floatmode']) : 0;
        $fullwidth = $table != null ? intval($table['fullwidth']) : 0;
        $disableschema = $table != null ? intval($table['disableschema']) : 0;

        $colCount = count($colsarray);
        $rowCount = count($rowsarray) / $colCount;
        $currentCell = 0;
        ///
        $defaultCellText = 'Insert text here';

        printf('<a class="spbtbl_btn btn_topright" href="%s">%s</a>', esc_url(wp_nonce_url(admin_url('admin.php?page=' . $this->page_slug), 'return')), 'View All Tables');
        echo '<a href="https://superbthemes.com/plugins/superb-tables/" class="view-premium-version view-premium-version-createtablepage" target="_blank">View Premium Version</a>'; ?>
        <script type="text/javascript">
            var defaultCellText = "<?php echo esc_attr($defaultCellText); ?>";
            var colorScheme = "<?php echo esc_attr($tableColor); ?>";
            var tableStyle = "<?php echo esc_attr($tableStyle); ?>";
            var fontsize_td = "<?php echo esc_attr($fontsize_td); ?>";
            var fontsize_th = "<?php echo esc_attr($fontsize_th); ?>";
            var floatmode = "<?php echo esc_attr($floatmode); ?>";
            var fullwidth = "<?php echo esc_attr($fullwidth); ?>";
            var disableschema = "<?php echo esc_attr($disableschema); ?>";
        </script>
        <form method="post" name="saveTable">
            <input class="spbtbl_btn top-save-button" type="submit" value="Save Table" />
            <input id="spbtbl_rowNum" type="hidden" value="<?php echo esc_attr($rowCount + 1); ?>" />
            <input id="spbtbl_colNum" type="hidden" value="<?php echo esc_attr($colCount + 1); ?>" />
            <?php if ($editnum !== false) { ?>
                <input name="tableId" type="hidden" value="<?php echo esc_attr($editnum) ?>" />
            <?php } ?>

            <!-- TABLE NAME INPUT -->
            <!-- SHOW SHORTCODE IF EDIT -->
            <div class="spbtbl_tableshortcode">

                <?php if ($editnum !== false) { ?><div class="spbtbl_shortcodewrapper"><span class="spbtbl_shortcodetext	">Shortcode</span><input type="text" class="spbtbl_shortcode shortcodeedittable" value="[spbtbl_sc id=<?php echo esc_attr($editnum); ?>]" readonly> </div>
                    <div class='spbtbl_tip spbtbl_tip_shortcode'><span>Tip:</span> Copy & Paste shortcodes in your post/page to show the table.</div>
                <?php } ?>
            </div>

            <!-- SHOW STYLE SELECT -->
            <!-- SHOW COLOR SELECT -->


            <div class="spbtbl_backend_tablewrapper">
                <table class="table-options">
                    <tr>
                        <td>
                            <div class="table-options-column-innner">
                                <span class="table-options-info">Table Name</span><br>
                                <input id="spbtbl_tableName" placeholder="Insert Table Name" type="text" name="tableName" value="<?php echo esc_attr($tableName); ?>" required="required">
                            </div>
                        </td>
                        <td>
                            <input class="spbtbl_btn" id="spbtbl_addRow" type="button" value="Add Row" />
                        </td>
                        <td>
                            <input class="spbtbl_btn" id="spbtbl_addCol" type="button" value="Add Column" />
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class="table-options-info">Color Scheme</span>
                            <br>
                            <select id="colorSelect" name="color">
                                <option value="standard">Standard</option>
                                <option value="dark">Dark</option>
                                <option value="standard" disabled>White (PRO)</option>
                                <option value="standard" disabled>Purple (PRO)</option>
                                <option disabled value="standard">Red (PRO)</option>
                                <option disabled value="standard">Blue (PRO)</option>
                            </select></option>
                        </td>

                        <td>
                            <span class="table-options-info">Table Design</span><br>
                            <select id="styleSelect" name="style">
                                <option value="0">Custom Design</option>
                                <option value="1">Theme Default</option>
                            </select>
                        </td>
                        <td class="spbtbl-second-row">
                            <span class="table-options-info">Float Mode</span><br>
                            <select id="floatmodeSelect" name="floatmode">
                                <option value="0">Block</option>
                                <option value="1">Left</option>
                                <option value="2">Inline Block</option>
                            </select>
                        </td>
                        <td class="spbtbl-second-row spbtbl-upgn-wrapper">
                            <a href="https://superbthemes.com/plugins/superb-tables/" target="_blank">
                                <span class="spbtbl-upgn">Upgrade To Premium To Unlock This Feature</span>
                                <span class="table-options-info">TD Font size (PRO)</span><br>
                                <input type="number" disabled value="14" min="14" max="14">
                                <input type="number" id="fontTDinput" name="fontsize_td" value="14" min="1" max="100" class="spbtbl_fntsize">
                            </a>
                        </td>
                        <td class="spbtbl-second-row spbtbl-upgn-wrapper">
                            <a href="https://superbthemes.com/plugins/superb-tables/" target="_blank">
                                <span class="spbtbl-upgn">Upgrade To Premium To Unlock This Feature</span>
                                <span class="table-options-info">TH Font size (PRO)</span><br>
                                <div class="table-options-info-disabled"></div>
                                <input type="number" value="15" min="14" max="15" disabled>
                                <input id="fontTHinput" name="fontsize_th" type="number" value="15" min="1" max="100" class="spbtbl_fntsize">
                            </a>
                        </td>

                        <td class="spbtbl-second-row">
                            <span class="table-options-info">Full width</span><br>
                            <select id="fullwidthSelect" name="fullwidth">
                                <option value="0">No</option>
                                <option value="1">Yes</option>
                            </select>
                        </td>
                        <td class="spbtbl-second-row spbtbl_dsblschema">
                            <span class="table-options-info">Disable Schema</span><br>
                            <select id="disableschemaSelect" name="disableschema">
                                <option value="0">No</option>
                                <option value="1">Yes</option>
                            </select>
                        </td>
                    </tr>
                </table>



                <table id="spbtbl" class="spbtbl-style backend spbtbl-color-<?php echo esc_attr($tableColor); ?>">
                    <thead>
                        <tr>
                            <th class="hidden_row"></th>
                            <?php for ($i = 0; $i < $colCount; $i++) {
                                if ($i == -1) {
                            ?><th align="left"><textarea rows="1" data-min-rows="1" placeholder="<?php echo esc_attr($defaultCellText); ?>" type="text" name="colValues[0][0]" class="text_input"><?php echo esc_attr($colsarray[$i]) ?></textarea></th><?php
                                                                                                                                                                                                                                                    } else {
                                                                                                                                                                                                                                                        ?><th align="left"><textarea rows="1" data-min-rows="1" placeholder="<?php echo esc_attr($defaultCellText); ?>" type="text" name="colValues[0][<?php echo esc_attr($i); ?>]" class="text_input"><?php echo esc_attr($colsarray[$i]) ?></textarea><input class='spbtbl_removeCol' type='button' data-value=<?php echo esc_attr(($i + 1)); ?>></th> <?php
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        }
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    } ?>

                        </tr>
                    </thead>
                    <tbody class="table-hover">
                        <?php for ($j = 0; $j < $rowCount; $j++) {
                        ?><tr><?php
                                if ($j == -1) {
                                ?><td class="hidden_row"></td><?php
                                                            } else {
                                                                ?><td class="hidden_row"><input class="spbtbl_dragRow" style="cursor: move;" type="button" /><input class="spbtbl_removeRow" type="button" />
                                    </td><?php
                                                            }
                                                            for ($k = 0; $k < $colCount; $k++) { ?>
                                    <td><textarea class="text_input" placeholder="<?php echo esc_attr($defaultCellText); ?>" rows="1" data-min-rows="1" name="rowValues[<?php echo esc_attr($j); ?>][<?php echo esc_attr($k); ?>]"><?php echo esc_attr($rowsarray[$currentCell++]) ?></textarea></td><?php
                                                                                                                                                                                                                                                                                                    } ?>
                            </tr><?php
                                } ?>
                    </tbody>
                </table>
            </div>
            <div class="plugin-savebutton-wrapper">
                <!-- SHOW SAVE SUCCESS-->
                <?php
                if ($savedTable) {
                    echo '<p class="spbtbl_success">Your table "' . esc_html($savedTable) . '" has been saved and is ready for use with the shortcode provided above.</p>';
                } ?>
                <!-- SAVE SUCCESS END -->

                <br>
                <input type="hidden" id="_wpnonce" name="_wpnonce" value="<?php echo esc_attr(wp_create_nonce('spbtbl_submit')); ?>" />
                <input class="spbtbl_btn spbtbl_saveNew_footer" id="spbtbl_saveNew" type="submit" value="Save Table" />
        </form>

        </div>

        <?php
    }

    public function spbtbl_eventHandler($current_screen)
    {
        $user_caps = apply_filters('spbtbl_user_capabilities', $this->user_caps);
        if (!current_user_can($user_caps)) {
            return;
        }

        $page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : false;
        $func = isset($_GET['func']) ? sanitize_text_field(wp_unslash($_GET['func'])) : false;
        $editnum = isset($_GET['editnum']) ? intval(sanitize_text_field(wp_unslash($_GET['editnum']))) : false;
        $copyNum = isset($_GET['copyNum']) ? intval(sanitize_text_field(wp_unslash($_GET['copyNum']))) : false;
        $deleteNum = isset($_GET['deleteNum']) ? intval(sanitize_text_field(wp_unslash($_GET['deleteNum']))) : false;
        $nonce = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : false;
        if (!$nonce) {
            $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : false;
        }

        $tableId = isset($_POST['tableId']) ? sanitize_text_field(wp_unslash($_POST['tableId'])) : false;
        $tableName = isset($_POST['tableName']) ? sanitize_text_field(wp_unslash($_POST['tableName'])) : false;
        $colValues = isset($_POST['colValues']) ? map_deep(wp_unslash($_POST['colValues']), 'wp_kses_post') : false;
        $rowValues = isset($_POST['rowValues']) ? map_deep(wp_unslash($_POST['rowValues']), 'wp_kses_post') : false;
        $tableColor = isset($_POST['color']) ? sanitize_text_field(wp_unslash($_POST['color'])) : false;
        $tableStyle = isset($_POST['style']) ? sanitize_text_field(wp_unslash($_POST['style'])) : false;
        $fontsize_td = isset($_POST['fontsize_td']) ? sanitize_text_field(wp_unslash($_POST['fontsize_td'])) : false;
        $fontsize_th = isset($_POST['fontsize_th']) ? sanitize_text_field(wp_unslash($_POST['fontsize_th'])) : false;
        $floatmode = isset($_POST['floatmode']) ? sanitize_text_field(wp_unslash($_POST['floatmode'])) : false;
        $fullwidth = isset($_POST['fullwidth']) ? sanitize_text_field(wp_unslash($_POST['fullwidth'])) : false;
        $disableschema = isset($_POST['disableschema']) ? sanitize_text_field(wp_unslash($_POST['disableschema'])) : false;

        if ($func && $func == 'add_table') {
            if ($colValues && $rowValues && wp_verify_nonce($nonce, 'spbtbl_submit')) {
                $result = $this->db->add($this->validateSanitize($tableName, 'string'), $this->validateSanitize($rowValues, 'array'), $this->validateSanitize($colValues, 'array'), $this->validateSanitize($tableColor, 'color'), $this->validateSanitize($tableStyle, 'int'), $this->validateSanitize($fontsize_td, 'int'), $this->validateSanitize($fontsize_th, 'int'), $this->validateSanitize($floatmode, 'int'), $this->validateSanitize($fullwidth, 'bit'), $this->validateSanitize($disableschema, 'bit'));
                if ($result) {
                    $sendback = add_query_arg(array('page' => $page, 'savedTable' => urlencode($tableName), 'success' => true), '');
                    wp_redirect($sendback);
                }
            }
        }

        if ($func && $func == 'edit_table' && $editnum !== false && wp_verify_nonce($nonce, 'spbtbl_submit')) {
            if ($colValues && $rowValues) {
                $result = $this->db->update($this->validateSanitize($tableId, 'id'), $this->validateSanitize($tableName, 'string'), $this->validateSanitize($rowValues, 'array'), $this->validateSanitize($colValues, 'array'), $this->validateSanitize($tableColor, 'color'), $this->validateSanitize($tableStyle, 'int'), $this->validateSanitize($fontsize_td, 'int'), $this->validateSanitize($fontsize_th, 'int'), $this->validateSanitize($floatmode, 'int'), $this->validateSanitize($fullwidth, 'bit'), $this->validateSanitize($disableschema, 'bit'));
                if ($result) {
                    $sendback = add_query_arg(array('page' => $page, 'func' => 'edit_table', 'editnum' => $editnum, 'savedTable' => urlencode($tableName), 'success' => true, '_wpnonce' => wp_create_nonce('edit_table')), '');
                    wp_redirect($sendback);
                }
            }
        }

        if ($func && $func == 'delete_table' && $deleteNum !== false && $tableName !== false && wp_verify_nonce($nonce, 'delete_table')) {
            $result = $this->db->delete(intval($deleteNum));
            if ($result) {
                $sendback = add_query_arg(array('page' => $page, 'deletedTable' => urlencode($tableName), 'success' => true), '');
                wp_redirect($sendback);
            }
        }
    }


    public function validateSanitize($input, $type)
    {
        if ($type == 'array') {
            return $this->sanitizeArray($input);
        }
        if ($type == 'bit') {
            if (strlen($input) > 1 || intval($input) < 0 || strlen($input) > 1) {
                return 0;
            } else {
                return intval($input);
            }
        }
        if ($type == 'color') {
            if (strlen($input) > 15) {
                return 'standard';
            } else {
                return sanitize_text_field($input);
            }
        }
        if ($type == 'int') {
            if (intval($input) > 100) {
                return 100;
            }
            if (intval($input) < 0) {
                return 0;
            }
            return intval($input);
        }
        if ($type == 'id') {
            return absint($input);
        }
        if ($type == 'string') {
            if (strlen($input) > 200) {
                return sanitize_text_field(substr($input, 0, 200));
            }
            return sanitize_text_field($input);
        }
    }

    public function sanitizeArray(&$array)
    {
        foreach ($array as &$value) {
            if (!is_array($value)) {
                $value = wp_kses_post($value);
            } else {
                $this->sanitizeArray($value);
            }
        }
        return $array;
    }


    public function spbtbl_handleSC($atts)
    {

        // Attributes
        $atts = shortcode_atts(
            array(
                'id' => '',
                'nested' => ''
            ),
            $atts,
            'spbtbl_sc'
        );

        // Return only if has ID attribute
        if (!$atts['id']) {
            return;
        }

        $table = $this->db->get($atts['id']);
        if ($table) {
            $tableName = esc_attr($table['name']);
            $tableColor = esc_attr($table['color']);
            $style = intval($table['style']);
            $colsarray = $table['cols'];
            $rowsarray = $table['rows'];
            $fontsize_td = intval($table['fontsize_td']);
            $fontsize_th = intval($table['fontsize_th']);
            $floatmode = 'blockmode';
            $fullwidth = intval($table['fullwidth']);
            $disableschema = intval($table['disableschema']);
            $nested = !is_null($atts['nested']) && !empty($atts['nested']) ? json_decode(urldecode($atts['nested'])) : array();

            $colCount = count($colsarray);
            $rowCount = count($rowsarray) / $colCount;
            $currentCell = 0;

            switch ($table['floatmode']) {
                case 0:
                    $floatmode = 'block';
                    break;
                case 1:
                    $floatmode = 'float';
                    break;
                case 2:
                    $floatmode = 'inline';
                    break;
                default:
                    $floatmode = 'block';
            }


            $tableClassString = $style == 0 ? "spbtbl-style spbtbl-color-" . $tableColor : "spbtbl-theme-style";
            $wrapperClassString = "spbtbl-" . $floatmode . "mode";
            if ($fullwidth == 1) {
                $wrapperClassString .= " spbtbl-fullwidth";
            }

            //handle SC loop -> prevent infinite load if same tables are nested within themselves
            foreach ($rowsarray as &$cell_content) {
                if (has_shortcode($cell_content, 'spbtbl_sc')) {
                    preg_match_all('/' . get_shortcode_regex() . '/', $cell_content, $matches, PREG_SET_ORDER);
                    foreach ($matches as &$match) {
                        $nested_id = $this->spbtbl_strposa($match[0], $nested);
                        if (strpos($match[0], $atts['id']) !== false || $nested_id !== false) {
                            $error_id = $nested_id !== false ? $nested_id : $atts['id'];
                            $cell_content = str_replace($match[0], "<b><i style='color:red!important;'>(Superb Tables: Table ID " . esc_html($error_id) . "- Cannot Display Table Within Itself)</i></b>", $cell_content);
                        } else {
                            $nested[] = $atts['id'];
                            $nested_sc = str_replace("]", " nested=" . urlencode(json_encode($nested)) . "]", $match[0]);
                            $cell_content = str_replace($match[0], $nested_sc, $cell_content);
                        }
                    }
                    unset($match);
                }
            }
            unset($cell_content);
            //

            ob_start();
            ///
        ?>
            <div <?php if ($style == 0) { ?> class="spbtbl-wrapper <?php echo esc_attr($wrapperClassString); ?>" <?php } ?>>
                <table class="<?php echo esc_attr($tableClassString); ?>" title="<?php echo esc_attr($tableName); ?>" <?php if ($disableschema == 0) { ?> itemscope itemtype="http://schema.org/Table" <?php } ?>>
                    <!-- Superb Tables Plugin -->
                    <tr>
                        <?php for ($i = 0; $i < $colCount; $i++) {
                        ?><th style="font-size: <?php echo esc_attr($fontsize_th); ?>px !important;" <?php if ($disableschema == 0) { ?> itemprop="name" <?php } ?> align="left"><?php echo wp_kses($colsarray[$i], "post"); ?></th><?php
                                                                                                                                                                                                                                } ?>

                    </tr>
                    <?php for ($j = 0; $j < $rowCount; $j++) {
                    ?><tr><?php
                            for ($k = 0; $k < $colCount; $k++) { ?>
                                <td style="font-size:<?php echo esc_attr($fontsize_td); ?>px !important;" <?php if ($disableschema == 0) { ?>itemprop="description" <?php } ?>><?php
                                                                                                                                                                                echo do_shortcode(wp_kses($rowsarray[$currentCell++], "post"));
                                                                                                                                                                                ?></td><?php
                                                                                                                                                                                    } ?>
                        </tr><?php
                            } ?>
                </table>
            </div>
<?php
            return ob_get_clean();
        }
    }

    private function spbtbl_strposa($haystack, $needles)
    {
        foreach ($needles as &$needle) {
            $res = strpos($haystack, $needle);
            if ($res !== false) {
                return $needle;
            }
        }
        unset($needle);
        return false;
    }


    public function spbtbl_initialize()
    {
        $this->db->create_table();
    }

    public function spbtbl_rollback()
    {
        $table = spbtbl_DB_Table::get_instance();
        $table->drop_table();
    }
}
?>