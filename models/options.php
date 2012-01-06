<?php
/**
 * WP_Eldis_Options
 * This model helps with interacting with the database to retrieve the Eldis options.
 *
 * @package EldisAPI
 * @version 1.0
 * @copyright 2011 Headshift
 * @author Ross Tweedie <ross.tweedie@headshift.com> 
 * @license GNU Public License Version 2.0
 */
class WP_Eldis_Options extends WP_Eldis_Model {
    
    private $option_key = 'wp_eldis_options';
    private $default_value = array(
        'api_token' => NULL,
    );
    private $default_category = 'documents';
    
    /**
     * Construct
     *
     * @param void
     * @return void
     */
    function __construct()
    {
        parent::__construct();
    }
    
    /**
     * Get an option from the configuration or options table.
     *
     * @param string $option_key
     * @return string || false
     */
    function get($option_key)
    { 
        $options = get_option($this->option_key, $this->default_value);
        
        if (array_key_exists($option_key, $options)) {
            return $options[$option_key];
        } else {
            if ( isset( $this->default_value[$option_key] ) and !empty( $this->default_value[$option_key] ) ){
                return $this->default_value[$option_key];
            } else {
                // We could not find option, so let's return false and move on
                return false;
            }
        }
    }
    
    /**
     * Set an option value
     *
     * @param string $key
     * @param string $value
     * @return boolean true || false
     */
    function set($key, $value)
    {
        $options = get_option($this->option_key, $this->default_value);
        $options[$key] = $value;
        update_option($this->option_key, $options);
    }
    
    /**
     * Delete all the options for this plugin.
     *
     * This method will generally be used when uninstalling / deactivating the plugin.
     *
     * @param void
     * @return true
     */
    function delete_all()
    {
        delete_option($this->option_key);
        return true;
    }
    
}