<?php
require_once(dirname(__FILE__) . '/EldisApi.php');

/**
 * EldisApiAssets
 *
 * This will search across all assets.
 * If you only need only documents then use the documents API - EldisApiDocuments
 * 
 * @package EldisAPI
 * @version 1.0
 * @license GNU Public License Version 2.0
 */

class EldisApiAssets extends EldisApi {
    
    function __construct($apiKey) {
        $this->method = 'assets';
        parent::__construct($apiKey, $this->method);
    }
    
}