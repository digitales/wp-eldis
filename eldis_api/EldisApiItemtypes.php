<?php
require_once(dirname(__FILE__) . '/EldisApi.php');

/**
 * EldisApiItemtypes
 *
 * This will search across item types.
 * 
 * @package EldisAPI
 * @version 1.0
 * @license GNU Public License Version 2.0
 */

class EldisApiItemtypes extends EldisApi {
    
    function __construct($apiKey) {
        $this->method = 'itemtypes';
        parent::__construct($apiKey, $this->method);
    }
    
}