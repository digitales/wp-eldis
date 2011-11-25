<?php
class WP_Eldis_Model extends WP_Eldis {
    
    public $table_prefix;
    public $table_name;
    
    function __construct() {
        parent::__construct();
        global $wpdb;
        $this->table_prefix = $wpdb->prefix . "wpeldis_";
    }
    
}