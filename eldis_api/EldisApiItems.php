<?php
require_once(dirname(__FILE__) . '/EldisApi.php');

/**
 * EldisApiItems
 *
 * This will search across items.
 * 
 * @package EldisAPI
 * @version 1.0
 * @license GNU Public License Version 2.0
 */

class EldisApiItems extends EldisApi {
    
    function __construct($apiKey) {
        $this->method = 'items';
        parent::__construct($apiKey, $this->method);
    }
    
}