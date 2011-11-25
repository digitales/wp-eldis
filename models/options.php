<?php
class WP_Eldis_Options extends WP_Eldis_Model {
    
    private $option_key = 'wp_eldis_options';
    private $default_value = array(
        'api_token' => NULL,
    );
    private $default_category = 'documents';
    
    function __construct() {

        parent::__construct();
	
        //if (!$this->get('category_id')) {
        //    $this->set_category($this->default_category);
        //}
	
        print_r(get_option($this->option_key, $this->default_value));
	
    }
    
    function get($option_key) {
        $options = get_option($this->option_key, $this->default_value);
        if (array_key_exists($option_key, $options)) {
            return $options[$option_key];
        } else {
            return $this->default_value[$option_key];
        }
    }
    
    function set($key, $value) {
        $options = get_option($this->option_key, $this->default_value);
        $options[$key] = $value;
        update_option($this->option_key, $options);
    }
    
    function delete_all() {
        delete_option($this->option_key);
    }
    
}