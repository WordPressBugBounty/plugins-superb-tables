<?php

if (! defined('WPINC')) {
    die;
}

class spbtbl_DB_Table
{
    private $db;

    public function __construct()
    {
        global $wpdb;
        $this->db = $wpdb;
        $this->table_name = $this->db->prefix ."spbtbl";
        $this->db_version = "1.3";
    }

    public static function get_instance()
    {
        static $instance = null;
        if ($instance == null) {
            $instance = new spbtbl_DB_Table();
        }
        return $instance;
    }

    public function create_table()
    {
        $sql = "CREATE TABLE $this->table_name (
		`id` MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
		`name` TINYTEXT NOT NULL,
		`rows` LONGTEXT NOT NULL,
		`cols` LONGTEXT NOT NULL,
		`color` TINYTEXT NOT NULL,
		`style` INT NOT NULL DEFAULT 0,
		`fontsize_td` INT NOT NULL DEFAULT 14,
		`fontsize_th` INT NOT NULL DEFAULT 15,
		`floatmode` INT NOT NULL DEFAULT 0,
		`fullwidth` INT NOT NULL DEFAULT 0,
		`disableschema` INT NOT NULL DEFAULT 0,
		UNIQUE KEY id (id)
	);
";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        add_option('spbtbl_db_version', $this->db_version);
    }

    public function add($name, $rows, $cols, $color, $style, $fontsize_td, $fontsize_th, $floatmode, $fullwidth, $disableschema)
    {
        $name 	= wp_strip_all_tags(wp_unslash($name));
        $rows 		= $this->serialize(wp_unslash($rows));
        $cols 		= $this->serialize(wp_unslash($cols));
        $color 		= strval(wp_unslash($color));
        $fullwidth 	= strval(wp_unslash($fullwidth));
        $disableschema = strval(wp_unslash($disableschema));


        $result = $this->db->insert($this->table_name, array('name' => $name, 'rows' => $rows, 'cols' => $cols, 'color' => $color, 'style' => $style, 'fontsize_td' => $fontsize_td, 'fontsize_th' => $fontsize_th, 'floatmode' => $floatmode, 'fullwidth' => $fullwidth, 'disableschema' => $disableschema));
        if ($result) {
            return $this->db->insert_id;
        } else {
            return false;
        }
    }

    public function update($id, $name, $rows, $cols, $color, $style, $fontsize_td, $fontsize_th, $floatmode, $fullwidth, $disableschema)
    {
        $name 	= wp_strip_all_tags(wp_unslash($name));
        $rows 		= $this->serialize(wp_unslash($rows));
        $cols 		= $this->serialize(wp_unslash($cols));
        $color 		= strval(wp_unslash($color));
        $fullwidth 	= strval(wp_unslash($fullwidth));
        $disableschema = strval(wp_unslash($disableschema));

        return $this->db->update($this->table_name, array('name' => $name, 'rows' => $rows, 'cols' => $cols, 'color' => $color, 'style' => $style, 'fontsize_td' => $fontsize_td, 'fontsize_th' => $fontsize_th, 'floatmode' => $floatmode, 'fullwidth' => $fullwidth, 'disableschema' => $disableschema ), array( 'id' => $id ));
    }

    public function drop_table()
    {
        $query = "DROP TABLE $this->table_name";
        return $this->db->query($query);
    }

    public function delete($id)
    {
        $query = $this->db->prepare("DELETE FROM $this->table_name WHERE id IN (%d)", $id);
        return $this->db->query($query);
    }

    public function get($id)
    {
        $query = $this->db->prepare("SELECT * FROM $this->table_name WHERE id IN (%d)", $id);
        $row = $this->db->get_row($query, ARRAY_A);
        if ($row) {
            $row['cols'] = $this->unserialize($row['cols']);
            $row['rows'] = $this->unserialize($row['rows']);
            return $row;
        }
        return false;
    }

    public function get_all()
    {
        $query = "SELECT id, name, style FROM $this->table_name";
        $results = $this->db->get_results($query, ARRAY_A);
        if ($results) {
            return $results;
        }
        return false;
    }

    private function serialize($item)
    {
        return base64_encode(serialize($item));
    }

    private function unserialize($item)
    {
        $u_item = unserialize(base64_decode($item));
        $rit = new RecursiveIteratorIterator(new RecursiveArrayIterator($u_item));
        $toreturn = iterator_to_array($rit, false);
        return $toreturn;
    }
}
