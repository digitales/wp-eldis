<?php
require_once(dirname(__FILE__) . '/EldisApi.php');

/**
 * EldisApiCountries
 *
 * This will search across countries.
 * 
 * @package EldisAPI
 * @version 1.0
 * @license GNU Public License Version 2.0
 */

class EldisApiCountriesextends EldisApi {
    
    function __construct($apiKey) {
        $this->method = 'countries';
        parent::__construct($apiKey, $this->method);
    }
    
}