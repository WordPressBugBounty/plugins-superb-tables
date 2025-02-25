<?php

namespace SuperbThemes\AddonsRecommender;

if (!defined('ABSPATH')) {
    die;
}
if (!class_exists('WP_Upgrader_Skin')) {
    include_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');
}

class spbrecommender_upgrader_skin extends \WP_Upgrader_Skin
{
    private $error = array();

    public function feedback($feedback, ...$args)
    {
        // nothing
    }

    public function header()
    {
        // nothing
    }

    public function footer()
    {
        // nothing
    }

    public function error($error)
    {
        if (!empty($error)) {
            $this->error = $error;
        }
    }

    public function get_error()
    {
        return $this->error;
    }
}
