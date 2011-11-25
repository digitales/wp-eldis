<?php
require_once(dirname(__FILE__) . '/EldisApi.php');

/**
 * EldisApiThemes
 *
 * This will search across documents.
 * 
 * @package EldisAPI
 * @version 1.0
 * @license GNU Public License Version 2.0
 */

class EldisApiThemes extends EldisApi {
    
    function __construct($apiKey) {
        $this->method = 'themes';
        parent::__construct($apiKey, $this->method);
    }
    
}