<?php
/**
 * WP_Eldis_Controller
 *
 * @package EldisAPI
 * @version 1.0
 * @copyright 2011 Headshift
 * @author Ross Tweedie <ross.tweedie@headshift.com> 
 * @license GNU Public License Version 2.0
 */
class WP_Eldis_Controller extends WP_Eldis {
    
    function __construct()
    {
        parent::__construct();
        foreach ($this->uses as $model_name) {
            $this->import_model($model_name);
        }
    }
    
}