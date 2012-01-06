<?php
require_once(dirname(__FILE__) . '/EldisApi.php');

/**
 * EldisAPpiSectors
 *
 * This will search across sectors.
 * 
 * @package EldisAPI
 * @version 1.0
 * @license GNU Public License Version 2.0
 */

class EldisApiSectors extends EldisApi {
    
    function __construct($apiKey) {
        $this->method = 'sectors';
        parent::__construct($apiKey, $this->method);
    }
    
}