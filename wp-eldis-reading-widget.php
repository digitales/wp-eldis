<?php
/**
 * Reading Widget
 *
 * Please note that this widget is not stand-alone, it requires the CDKN_Xili_Post class.
 */
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . "wp-eldis-import-cron.php");
class WP_Eldis_Reading_Widget extends WP_Widget {
	
	protected $apiKey,
		$api,
		$url,
		$max_display;
		
    function __construct()
    {	
	$import = new WP_Eldis_Import();
	$import->initiate_import();
		$this->apiKey = $this->get_api_key();
		$this->url = 'openapi/eldis/search/documents/full';
		$this->api = new EldisAPI($this->apiKey,$this->url);
		
        parent::WP_Widget( false, $name = 'WP Eldis Reading');
    }
    

	//Gets the api_key from the options table if it's set, else it returns false
	function get_api_key(){
		$option = 'wp_eldis_options';
		$option_key = 'api_key';
	    $default_value = array(
	        'api_token' => NULL,
	    );
	
		$options = get_option( $option, $default_value );
		if ( array_key_exists( $option_key, $options ) ) {
            return $options[$option_key];
        } else {
			return false;
		}
	}
	
	//Composes the query url to pass to openapi
    function set_url_query(){
		
		$taxonomy = get_query_var('taxonomy');
		$term = get_query_var('term');
		$termobj = get_term_by( 'slug', $term, $taxonomy );
		
		switch($taxonomy){
			case 'regioncats':
			//Lets see if the term is a parent, if so it means that we should search on region instead of country
				$eldis_object_id = $this->get_region_eldis_object( $termobj );
				$object = '';
				
				if($this->is_parent_term( $termobj )){
					$object = 'region';
				} else {
					$object = 'country';
				}
				
				$this->api->setQuery(array(
	        		$object => $eldis_object_id,
	        	));
				break;
			case 'themecasts':
				
				break;
			default:
				break;
		}
	}
	
	//Returns the object id for the given region
	function get_region_eldis_object( $term ){
		return get_metadata($term->taxonomy, $term->term_id, 'regioncats_eldis_object', true);
	}
	
	
	//Returns boolean value, check wether the term is a parent term or not
	function is_parent_term( $term ){
		return $term->parent == 0;
	}
	
    function widget( $args, $instance )
    {
        global $post;
		$oldpost = $post;
        $responseResults;

        extract ( $args );
        // Sort out the title, if it hasn't been set, use a default.
        $widget_title = apply_filters( 'widget_title', $instance['title'] );        
        $title = $widget_title != ''? $widget_title : 'Recommended reading' ;
        
        $widget_readmore = apply_filters( 'widget_title', $instance['readmore_title'] );
        $readmore_title = $widget_readmore !=''? $readmore_title : 'More news articles'; 
        
        $this->max_display = ( isset( $instance['max_display'] ) && !empty( $instance['max_display'] ) )? (int)$instance['max_display'] : 5 ;
        
        $before_widget = preg_replace('/id="[^"]*"/','id="news_recommended"', $before_widget);
        
        echo $before_widget;
        echo $before_title . __( $title, 'cdkn-xili') . $after_title;
		
		$query = array(QUETAG=>'','cat'=>$category,'posts_per_page'=> $this->max_display, 'author' => '46', 'post_type'=>array('post','resource') );

        if ( isset( $instance['organisationcats'] ) or isset( $instance['regioncats'] ) or isset( $instance['themecats'] ) ) {
			wp_reset_query();
			$query[get_query_var('taxonomy')] = get_query_var('term');
			unset( $instance['organisationcats']);
			unset( $instance['regioncats']);
			unset($instance['themecats']);

		} else if ( is_region() or is_themecats() or is_organisation() ){
			$this->set_url_query();
			
			$this->api->setPageSize($this->max_display);
			$this->api->setExcludeFormat();
			$response = $this->api->getResponse(0,null,1);			
			$responseResults = $response->results;
			
			$posts = $this->get_results_as_posts($responseResults);
        } else {
			$query = array_merge( $query, $instance );
			$posts = get_posts($query);
		}
		
		if (count($posts)) {
						
							foreach($posts as $post) {
												setup_postdata($post);
								
												CDKN_Xili_2012_Post::output(array('thumbnail'	=> false,
																			 'utility'		=> false,
																			 'translate'	=> true,
																			 'comments'		=> false,
																			 'class'		=> 'hentry news-item'),
								                                        false, false);
											}
						} else {
							echo '<p>'.__('Sorry, there are no external posts for this page.', 'cdkn-xili').'</p>';
						}
		
		$post = $oldpost;
        
        echo '<a class="inset" href="/?s=*&fq=author:%22Eldis%22&core=&loclang=' . the_curlang() . '">'.__('More from Eldis', 'cdkn-xili').'</a>'."\n";

        echo $after_widget;
    }
    
	//returns an array with post objects from the eldis api results
	//TO DO get actual nr of results from api
    function get_results_as_posts($responseResults){
		$posts = array();
		foreach($responseResults as $row){
			$post = new stdClass;
			$post->post_type = 'resource';
			$post->post_author = '46';
			$post->post_title = $row->title;
			$post->syndicated_permalink = $row->website_url;
			$posts[] = $post;
		}				
		return $posts;
	}
    
    function update($new_instance, $old_instance)
	{
		$instance = $old_instance;
		$instance['title']              = strip_tags($new_instance['title']);
        $instance['max_display']        = strip_tags($new_instance['max_display']);
        $instance['readmore_title']     = strip_tags($new_instance['readmore_title']);
		return $instance;
	}    
    
    function form( $instance )
    {
        $title = esc_attr( $instance['title'] );
                
        echo '<p><label for="' . $this->get_field_id('title') .'">Title: <input class="widefat" id="' . $this->get_field_id('title') . '" name="' . $this->get_field_name('title') .'" type="text" value="' . $title .'" /></label></p>';
        echo '<p><label for="'.$this->get_field_id('max_display').'">'.__('Maximum number of articles to show:', 'cdkn-xili').' <input class="widefat" id="'.$this->get_field_id('max_display').'" name="'.$this->get_field_name('max_display').'" type="text" value="'.esc_attr($instance['max_display']).'" /></label></p>'; 
        echo '<p><label for="' . $this->get_field_id('readmore_title') .'">Readmore link title: <input class="widefat" id="' . $this->get_field_id('readmore_title') . '" name="' . $this->get_field_name('readmore_title') .'" type="text" value="' . esc_attr($instance['readmore_title']) .'" /></label></p>';
        
    }
}