<?php
/**
 * EldisAPI
 * This class contains the essential functionality to make building out the API functionality easier. It can also be implemented itself. In this case, it needs to be supplied the method for the API request.
 * @package EldisAPI
 * @version 1.0
 * @copyright 2011 Headshift
 * @author Ross Tweedie <ross.tweedie@headshift.com> 
 * @license GNU Public License Version 2.0
 */
class EldisAPI {
    
    protected $validFormats = array('json', 'json-alt', ); // Json-alt is used for versions of php less than 5.3 - it uses a different json parser.
    
    protected $apiKey,
        $apiUrl,
        $format,
        $method,
        $pageSize,
        $numPages,
        $sortDesc,
        $query,
        $curl; //This should be set to the Eldis API method implemented by the child class.

    /**
     * Construct
     *
     * @param string $apiKey.
     * @param
     * @return void.
     */
    function __construct($apiKey, $method) {
        // Get the config file
        require_once(dirname(__FILE__) . '/config.php');

        $this->apiKey = $apiKey;
        $this->method = $method;
        
        $this->setApiUrl( API_URL );
        $this->setFormat( API_FORMAT );
        
        if ($this->format == 'json') {
            //Choose the (hopefully) best json library available based on the PHP version
            if (version_compare(phpversion(), '5.3') >= 0) {
                //Do nothing
            } else {
                //Use the JSON functionality that the XML-RPC library provides
                $this->format = 'json-alt';
            }
        }
        $this->pageSize = API_PAGE_SIZE;
        $this->numPages = API_NUM_PAGES;
        $this->sortDesc = API_SORT_DESC;
    }

    
    function __autoload($className) {
        require_once(dirname(__FILE__) . $className . '.php');
    }

    
    /**
     * Set the API Url
     *
     * @param string $url
     * @return EldisAPI fluent inteface
     */
    function setApiUrl($url) {
        $this->apiUrl = $url;
        return $this;
    }
    

    /**
     * Set the format
     *
     * @param string $format
     * @return EldisAPI fluent inteface
     */
    function setFormat($format) {
        if (in_array($format, $this->validFormats)) $this->format = $format;
        return $this;
    }
    
    function setQuery($query) {
        $this->validateQuery($query);
        return $this;
    }

    
    /**
    * validateQuery 
    * In child classes, this should be overridden and strip out invalid parameters, etc. 
    * @param mixed $query 
    * @return EldisApi fluent interface
    */
    protected function validateQuery($query) {
        $this->query = $query;
        return $this;        
    }


    /**
     * Validate the format
     *
     * @param void
     * @return boolean true | false
     */
    protected function validateFormat() {
        return (!in_array($this->format, $this->validFormats))? FALSE : TRUE;
    }

    
    function getResponse($offset = 0, $responseData = NULL) {
        if (!$this->validateFormat()){
            return FALSE;
        }
        
        if (!isset($responseData)) {
            $responseData = $this->request($offset);
        }
        
        if ($pageResponse = $response = $this->parseResponse($responseData)) {
            $pagesRead = 1;
            $currOffset = $offset;
            //The Eldis API gives a link to the next set of results, this can be handy    
            $done = FALSE;
            while ($done != TRUE) {
                $nextRequestUrl = $this->getNextRequestUrl($pageResponse);
                //Are we out of stuff we need to fetch?
                if ( ($pagesRead == $this->numPages) || empty($nextRequestUrl) || ($this->pageSize * ($currOffset + 1)) > $this->getMeta($pageResponse, 'total_count') )  {
                  $done = TRUE;
                  continue;
                } else {
                    //Keep going
                    $pageResponseData = $this->request(0, $nextRequestUrl); //Wonder if I'll ever actually use the "offset" feature of MeetupAPIBase::request...?
                    //Overwrite the metadata and merge the results
                    if (!$pageResponse = $this->parseResponse($pageResponseData)) {
                      break;
                    }
                    $response->meta = $pageResponse->meta;
                    $response->results = array_merge($response->results, $pageResponse->results);
                    $pagesRead++;
                    $currOffset++;
                }
            }
            return $response;
        }
        
        return FALSE;
    }

    function getRawResponse( $offset = 0 ) {
        return (!$this->validateFormat())? FALSE : $this->request($offset) ;
    }

  
    protected function getMeta($response, $field) {
        switch($this->format) {
            case 'json':
            case 'json-alt':
                return $response->meta->$field;
                break;
            default:
                return FALSE;
                break;
        }
    }

    protected function getPagingRequest($response, $direction = 'next') {
        return $this->getMeta($response, $direction);
    }

    protected function getNextRequestUrl($response) {
        return $this->getPagingRequest($response);
    }
  
    protected function getPrevRequestUrl($response) {
        return $this->getPagingRequest($response, 'prev');
    }

    function parseResponse($responseData) {
        switch($this->format):
            case 'json':
                // We should use the included decoding when we are using php 5.3
    
                $jsonResponse = json_decode($responseData);
                return $jsonResponse;
                break;
            case 'json-alt':
                require_once(dirname(__FILE__) . '/libraries/xmlrpc/lib/xmlrpc.inc');
                require_once(dirname(__FILE__) . '/libraries/xmlrpc/extras/jsonrpc/jsonrpc.inc');
                require_once(dirname(__FILE__) . '/libraries/xmlrpc/extras/jsonrpc/json_extension_api.inc');
                $jp = 'json_';
                if (in_array('json', get_loaded_extensions())) {
                    $jp = 'json_alt_';
                }
                $jsonDecode = $jp . 'decode';
                $jsonLastError = $jp . 'last_error'; //TODO: Actually handle this
                $jsonResponse = $jsonDecode($responseData, FALSE, 1);
      
                return $jsonResponse;
                break;
            default:
                // We can only process JSON at the moment, this isn't too bad since eldis returns JSON data
                return FALSE;
                break;
        endswitch;
    }


    /**
    * initRequest 
    * Wrapper function to initiate the HTTP request.
    * @return void
    */
    protected function initRequest( $offset = 0, $request = NULL ) {
        if (!isset($request)) {
            //URL-encode the query
            $request_settings = array('key' => $this->apiKey,
              'page' => $this->pageSize,
              'offset' => $offset,
              'desc' => $this->sortDesc,
            );
            if ($this->sortDesc == NULL){
                unset( $request_settings['desc'] );
            }
            // Don't put desc in the query string at all if it isn't being used; its very presence sorts results descending!
            $request = $this->getRequestUrl() . '?' . http_build_query(array_merge($this->query, $request_settings));
        }
        $this->curl = curl_init($request);
        curl_setopt($this->curl, CURLOPT_HEADER, FALSE);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, TRUE);
    }

    /**
    * execRequest 
    * 
    * @return stdClass
    */
    protected function execRequest() {
        // json_decode cannot handle very large numbers. So the UTC time is shortened. 
        $responseData = preg_replace("/(\d{10})000,/",'$1,',curl_exec($this->curl));
        return $responseData;
    }

    /**
    * closeRequest 
    * Wrapper function to close the HTTP request. 
    * @return void
    */
    protected function closeRequest() {
        curl_close($this->curl);
    }

    protected function request($offset = 0, $request = NULL) {
        $this->initRequest($offset, $request);
        $responseData = $this->execRequest();
        $this->closeRequest();
        return $responseData;
    }

    protected function getRequestUrl() {
        return $this->apiUrl . $this->method . '.' . $this->formatRequestName($this->format);
    }

    /**
     * formatRequestName 
     * When various implementations of a format exist, this helper function ensures the right name is still passed to the API. 
     * @param mixed $formatName 
     */
    protected function formatRequestName($formatName) {
        switch($formatName):
            case 'json-alt':
                return 'json';
            default:
                return $formatName;
        endswitch;
    }
  
    function setPageSize($pageSize) {
        if ((int) $pageSize > -1) $this->pageSize = (int) $pageSize;
    }
  
    function getPageSize() {
        return $this->pageSize;
    }
  
    function setNumPages($numPages) {
        if (is_numeric($numPages) && (int) $numPages >= 0){
            $this->numPages = $numPages;
        }
    }
  
    function getNumPages() {
        return $this->numPages;
    }
  
    function setSortDesc($sortDesc) {
        if ($sortDesc == TRUE){
            $this->sortDesc = 'true';
        } else {
            $this->sortDesc = NULL;
        }
    }
  
    function getSortDesc() {
        return $this->sortDesc;
    }
}