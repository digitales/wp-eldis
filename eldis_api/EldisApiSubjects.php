<?php
require_once(dirname(__FILE__) . '/EldisApi.php');

/**
 * EldisApiSubjects 
 *
 * This will search across Subjects.
 * 
 * @package EldisAPI
 * @version 1.0
 * @license GNU Public License Version 2.0
 */

class EldisApiSubjects extends EldisApi {
    
    function __construct($apiKey) {
        $this->method = 'subjects';
        parent::__construct($apiKey, $this->method);
    }
    
}