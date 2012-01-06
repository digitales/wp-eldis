<?php
require_once(dirname(__FILE__) . '/EldisApi.php');

/**
 * EldisApiRegions
 *
 * This will search across regions.
 * 
 * @package EldisAPI
 * @version 1.0
 * @license GNU Public License Version 2.0
 */

class EldisApiRegions extends EldisApi {
    
    function __construct($apiKey) {
        $this->method = 'regions';
        parent::__construct($apiKey, $this->method);
    }
    
}