<?php
require_once(dirname(__FILE__) . '/EldisApi.php');

/**
 * EldisApiOrganisations
 *
 * This will search across organisations.
 * 
 * @package EldisAPI
 * @version 1.0
 * @license GNU Public License Version 2.0
 */

class EldisApiOrganisations extends EldisApi {
    
    function __construct($apiKey) {
        $this->method = 'organisations';
        parent::__construct($apiKey, $this->method);
    }
    
}