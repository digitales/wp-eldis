<?php
class WP_Eldis_Options_Controller extends WP_Eldis_Controller {
    
    protected $uses = array('api', 'options');
    
    function __construct()
    {
        parent::__construct();
    }
    
    function admin_options()
    {

        if (!current_user_can('manage_options'))  {
            wp_die( __('You do not have sufficient permissions to access this page.') );
        }
	
        $data = array();
        $data['has_api_key'] = $this->options->get('api_key') != FALSE;
        
        echo $this->render("options-page.php", $data);
        
    }
    
    function test_options()
    {
        $postdata = array();

        if (isset ( $_POST ) and (isset($_POST['task']) and $_POST['task'] == 'test' ) ){
            $postdata = $_POST;
        }
        
        $data = array();
        $data = $postdata;
        $data['has_api_key'] = $this->options->get('api_key') != FALSE;
        
        if ( isset($postdata) and isset($postdata['object']) ){
            $object = $postdata['object'];
            
            $url = 'openapi/eldis/search/'.$object.'/full';

            $this->api = new EldisAPI( $this->options->get('api_key'), $url);
            
            $this->api->setQuery(array(
        		'q' => $postdata['term']
        	    ));
    
    	    set_time_limit(0);
    	    $this->api->setPageSize(20);
    	    $this->api->setFormat('json');
            $this->api->setExcludeFormat();
            
    	    $response = $this->api->getResponse();
            
            $statusCode = $this->api->getResponseStatusCode();
            if ( isset($statusCode) and $statusCode == 500 ){
                
                // Let's check if the none full search works.
                $url = str_replace( '/full', '', $url);
                
                $this->api->setMethod( $url );
                
                $response = $this->api->getResponse();
                $statusCode = $this->api->getResponseStatusCode();
                
                if ($statusCode == 500 ){
                    $data['errorMessage'] = 'A server error occurred, please try again later';
                    
                    echo $this->render("options-page.php", $data);
                    exit;
                    
                }
                
            }
            
            // Check if the results are being refused.
            if ( isset($response->detail) ){
                $data['errorMessage'] = $response->detail;
                    
                echo $this->render("server-error.php", $data);
                exit;
            }
            
            $data['total_results'] = isset( $response->metadata->total_results )? $response->metadata->total_results : 0 ;
            $data['results'] = isset($response->results)? $response->results : null;
            
            echo $this->render('options-test.php', $data);            
            exit;
            
        }else {

            echo $this->render('options-test.php', $data);
            exit;
        }
        
    }

    function handle_post_data()
    {
        
        if (array_key_exists('api_key', $_POST) && $_POST['api_key'] != $this->options->get('api_key')) {
        	$this->options->set('api_key', $_POST['api_key']);
            $this->feedback['message'][] = "Successfully updated your API key!";
        }
	
    }
    
}