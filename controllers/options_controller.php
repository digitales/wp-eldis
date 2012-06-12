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

        $data['link_status'] = $this->handle_submit();
        
        echo $this->render("options-page.php", $data);
        
    }

    /**
     * Retrieves a set of posts, and assigns the term to the posts.
     *
     * @param int $term_id
     *      The category id
     * @param array $query
     *      Optional query setup
     *      Overwrites the settings of the function
     * @param string $term_taxonomy
     *      The taxonomy of the term given
     * @return int The number of posts affected
     * @author Maarten Jacobs
     **/
    protected function link_posts_to_term( $term_id, array $post_query = array(), $term_taxonomy = 'category' ) {
        if (!is_int($term_id) || !$term_id) {
            throw new Exception('No term id was given.');
        }

        // Build the query
        $query = array( 
            'posts_per_page' => -1,
        );
        $query = array_merge( $post_query, $query );

        // Get the posts
        $query_result = new WP_Query( $query );
        $query_result = $query_result->get_posts();

        // Link the term to the posts
        // TEST
        foreach ( $query_result as $post ) {
            $link_result = wp_set_post_terms( $post->ID, $term_id, $term_taxonomy, true );
            if ( is_wp_error( $link_result ) ) {
                throw new Exception('Invalid taxonomy given.');
            }
        }

        return count( $query_result );
    }

    function handle_submit() {
        $data = $_POST;

        if ( isset( $data['link_eldis_news'] ) ) {
            $news_cat_id = (int)$this->get_category_id( 'news', 'category' );
            $result = $this->link_posts_to_term( $news_cat_id, array(
                    'category__in' => array( (int)$this->get_category_id( 'indirect', 'category' ) ),
                    'category__not_in' => array( $news_cat_id ),
                    'author' => 46,
                    'post_type' => array( 'resource' )
                ) 
            );
            return "Affected $result resources.";
        }

        return FALSE;
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