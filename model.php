<?php
/**
 * WP_Eldis_Model
 *
 * @package EldisAPI
 * @version 1.0
 * @copyright 2011 Headshift
 * @author Ross Tweedie <ross.tweedie@headshift.com> 
 * @license GNU Public License Version 2.0
 */
class WP_Eldis_Model extends WP_Eldis {
    
    public $table_prefix;
    public $table_name;
    
    function __construct() {
        parent::__construct();
        global $wpdb;
        $this->table_prefix = $wpdb->prefix . "wpeldis_";
    }
    
}