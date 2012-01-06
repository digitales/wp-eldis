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
        $statusCode,
        $curl; //This should be set to the Eldis API method implemented by the child class.
    
    /**
     * @var include_format Should we include the format in the request URL.
     *      
     */
    protected $include_format = true;

    /**
     * Construct the class
     *
     * @param string $apiKey.
     * @param string $method
     * @return void.
     */
    function __construct($apiKey, $method)
    {        
        // Get the config file
        require_once(dirname(__FILE__) . '/config.php');

        $this->apiKey = $apiKey;
        $this->method = $method;
        $this->pageSize = API_PAGE_SIZE;
        $this->numPages = API_NUM_PAGES;
        $this->sortDesc = API_SORT_DESC;
        
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
    }
    
    /**
     * Set the method to use.
     *
     * @param string $method.
     * @return object EldisApi fluent interface
     */
    public function setMethod( $method )
    {
        $this->method = $method;
        return $this;
    }

    /**
     * Autoloader
     *
     * @param string $className
     * @return void
     */
    function __autoload($className)
    {
        require_once(dirname(__FILE__) . $className . '.php');
    }

    /**
     * Set the API Url
     *
     * @param string $url
     * @return EldisAPI fluent inteface
     */
    function setApiUrl($url)
    {
        $this->apiUrl = $url;
        return $this;
    }

    /**
     * Set the format
     *
     * @param string $format
     * @return EldisAPI fluent inteface
     */
    function setFormat($format)
    {
        if (in_array($format, $this->validFormats)){ 
            $this->format = $format;
        }
        return $this;
    }
    
    /**
     * Set the query
     *
     * @param string $query
     * @return object EldisApi fluent inteface
     */    
    function setQuery($query)
    {
        $this->validateQuery($query);
        return $this;
    }
    
    /**
    * validateQuery 
    * In child classes, this should be overridden and strip out invalid parameters, etc.
    * 
    * @param mixed $query 
    * @return EldisApi fluent interface
    */
    protected function validateQuery($query)
    {
        $this->query = $query;
        return $this;        
    }

    /**
     * Validate the format
     *
     * @param void
     * @return boolean true | false
     */
    protected function validateFormat()
    {
        return (!in_array($this->format, $this->validFormats))? FALSE : TRUE;
    }

    /**
     * Get the API response
     *
     * @param integer $offset where should we start in the response object
     * @param object $responseData
     * @return false | object $response
     */
    function getResponse($offset = 0, $responseData = NULL)
    {
        
        if (!$this->validateFormat()){ return FALSE; }
        
        if (!isset($responseData)) { $responseData = $this->request($offset); }
        
        if ($pageResponse = $response = $this->parseResponse($responseData)) {
            
            $pagesRead = 1;
            $currOffset = $offset;
            
            //The Eldis API gives a link to the next set of results, this can be handy    
            $done = FALSE;
            
            $count = 0; // This is used to avoid an infinate loop situation.
            while ($done != TRUE and $count < 999 ) {
                $nextRequestUrl = $this->getNextRequestUrl($pageResponse);
                
                //Are we out of stuff we need to fetch?
                if ( ($pagesRead == $this->numPages) || empty($nextRequestUrl) || ($this->pageSize * ($currOffset + 1)) > $this->getMeta($pageResponse, 'total_results') )  {
                
                    $done = TRUE;
                    
                    $count++;
                    
                    continue;
                } else {
                    
                    // Run through another set of results.
                    $pageResponseData = $this->request(0, $nextRequestUrl);
                    
                    // Overwrite the metadata and merge the results
                    if (!$pageResponse = $this->parseResponse($pageResponseData)) {
                      break;
                    }
                    
                    $response->metadata = $pageResponse->metadata;
                    $response->results = array_merge($response->results, $pageResponse->results);
                    
                    // Increment the counts
                    $pagesRead++;
                    $currOffset++;                   
                    $count++;
                }
            }
            return $response;
        }
        // If we got this far, we couldn't find any data, so return false instead.
        return false;
    }    
    
    /**
     * Get the raw response object
     *
     * @param integer $offset
     * @return false | object
     */
    function getRawResponse( $offset = 0 )
    {
        return (!$this->validateFormat())? FALSE : $this->request($offset) ;
    }

    /**
     * Get meta data from the response object
     *
     * @param object $response
     * @param string $field The aspect of the response object to retrieve.
     *
     * @return false | string
     */ 
    protected function getMeta($response, $field)
    {
        switch($this->format) {
            case 'json':
            case 'json-alt':
                //Check if the field exists, if so return it otherwise return false.
                if ( isset ( $response->metadata->$field ) ){
                    return $response->metadata->$field;
                } else {
                    return false;
                }

                break;
            default:
                return false;
                break;
        }
    }
    
    /**
     * Get the next or the previous page URLs from the request object
     *
     * i.e get the next page or the previous page.
     *
     * @param object $response
     * @param string $direction
     * @return string
     */
    protected function getPagingRequest($response, $direction = 'next_page')
    {
        return $this->getMeta($response, $direction);
    }

    /**
     * Get the next page URL from the response object
     *
     * @param object $response
     * @return string
     */
    protected function getNextRequestUrl($response)
    {
        return $this->getPagingRequest($response, 'next_page');
    }
    
    /**
     * Get the previous page URL from the response object
     *
     * @param object $response
     * @return string
     */
    protected function getPrevRequestUrl($response)
    {
        return $this->getPagingRequest($response, 'previous_page');
    }

    /**
     * Process the request object data
     *
     * @param object | string $responseData
     * @return object
     */
    function parseResponse($responseData)
    {
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
    protected function initRequest( $offset = 0, $request = NULL, $include_format = true )
    {
        if (!isset($request)) {
            //URL-encode the query
            $request_settings = array('_token_guid' => $this->apiKey,
              'num_results' => $this->pageSize,
              'start_offset' => $offset,
              'sortDesc' => $this->sortDesc,
            );
            if ($this->sortDesc == NULL){
                unset( $request_settings['sortDesc'] );
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
    protected function execRequest()
    {
        
        // json_decode cannot handle very large numbers. So the UTC time is shortened. 
        $responseData = preg_replace("/(\d{10})000,/",'$1,', curl_exec($this->curl));
        
        // Now get the server response status code
        $this->statusCode = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);

        return $responseData;
    }
    
    /**
     * Get the response code for the request
     *
     * We can then check if everything has been received properly. i.e. no server 500 errors
     *
     * @param void
     * @return integer
     */
    public function getResponseStatusCode()
    {
        return $this->statusCode;
    }

    /**
    * closeRequest 
    * Wrapper function to close the HTTP request. 
    *
    * @param void
    * @return void
    */
    protected function closeRequest()
    {
        curl_close($this->curl);
    }

    /**
     * request
     *
     * @param integer $offset
     * @param null | object $request
     * @return object
     */
    protected function request($offset = 0, $request = NULL)
    {
        $this->initRequest($offset, $request);
        $responseData = $this->execRequest();
        $this->closeRequest();
        return $responseData;
    }
    
    /**
     * Get the request URL.
     *
     * @param void
     * @return string
     */
    protected function getRequestUrl()
    {   
        if ( $this->include_format == true ){
            return $this->apiUrl . $this->method . '.' . $this->formatRequestName($this->format);
        } else {
            return $this->apiUrl . $this->method;
        }
    }

    /**
     * formatRequestName 
     *
     * When various implementations of a format exist, this helper function ensures the right name is still passed to the API. 
     *
     * @param mixed $formatName
     * @return string
     */
    protected function formatRequestName($formatName)
    {
        switch($formatName):
            case 'json-alt':
                return 'json';
            default:
                return $formatName;
        endswitch;
    }
  
    /**
     * Set the size of the page, i.e. the number of results to display
     *
     * @param integer $pageSize
     * @return void
     */
    function setPageSize($pageSize)
    {
        if ( (int)$pageSize > -1){
            $this->pageSize = (int) $pageSize;
        }
    }
    
    /**
     * Get the size of the page, i.e. the number of results to display
     *
     * @param void
     * @return integer
     */  
    function getPageSize()
    {
        return $this->pageSize;
    }
  
    /**
     * Set the number of pages to retrieve
     *
     * @param integer $numPages
     * @return void
     */
    function setNumPages($numPages)
    {
        if (is_numeric($numPages) && (int) $numPages >= 0){
            $this->numPages = $numPages;
        }
    }
  
    /**
     * Set the size of thew page, i.e. the number of results to display
     *
     * @param integer $pageSize
     * @return void
     */
    function getNumPages()
    {
        return $this->numPages;
    }
  
    /**
     * Set the size of thew page, i.e. the number of results to display
     *
     *  
     * @param integer $pageSize
     * @return void
     */
    function setSortDesc($sortDesc)
    {
        if ($sortDesc == TRUE){
            $this->sortDesc = 'true';
        } else {
            $this->sortDesc = NULL;
        }
    }
  
    function getSortDesc()
    {
        return $this->sortDesc;
    }

    /**
     * Set to include the format in the request URL.
     *
     * @param false
     * @return object EldisAPI fluent interface.
     */
    function setIncludeFormat()
    {
        $this->include_format = true;
        return $this;
    }
    
    /**
     * Exclude the format in the request URL.
     *
     * @param false
     * @return object EldisAPI fluent interface.
     */
    function setExcludeFormat()
    {
        $this->include_format = false;
        return $this;
    }
    
    /**
     * Print to the screen in a nice format.
     *
     * @param string | array | object $data
     * @return void
     */
    function pr( $data , $name = '')
    {
        echo $name.':<pre>'.print_r($data, 1).'</pre>';
    }
    
}