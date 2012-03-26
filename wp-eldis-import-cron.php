<?php


class WP_Eldis_Import extends WP_Eldis {
	protected $numberdocs;
	protected $totalterms;
	protected $resources;
	protected $eldis_author_id = 46;
	
	
	/**
	 * Adds the eldis object id field to the regions term backend
	 * 
	 * @return void
	 */
	function __construct(){
		$this->numberdocs = 3;
		parent::__construct();		
	}
	
	/**
	 * Initiates the import by creating a new Import object.
	 * This function is static so we don't have to create a new object when scheduling the event.
	 *
	 * @author Maarten Jacobs
	 */
	static function start_import($dry_run = FALSE) {
	  $handler = new static();
	  $results = $handler->initiate_import($dry_run);
	  
	  if ($dry_run) {
	    return $results;
	  }
	}
	
	/**
	 * Sets all the needed variables and starts the import
	 * 
	 * @return void
	 */
	function initiate_import( $dry_run = FALSE ){
		$object_ids = $this->get_region_object_ids();
		$object_ids['theme'] = $this->get_theme_object_ids();
		$this->totalterms = count( $object_ids );
		$this->resources = $this->get_resource_object_ids();
		
		$this->setAPI();
		$url = 'openapi/eldis/search/documents/full';
		$this->api->setMethod( $url );
		$this->api->setPageSize( $this->numberdocs );
		
		return $this->import( $object_ids, $dry_run );
	}
	
	/**
	 * Calls the API to get the latest resources and imports them if they're not in the CDKN db
	 * 
	 * @param array $object_ids
	 * @return array
	 */
	private function import($object_ids, $dry_run = FALSE){
	  $results = array();
	  
		foreach( $object_ids as $object_type => $term_resources_holder ){
		  foreach ($term_resources_holder as $term_resources) {
		    $term = $term_resources['term'];
  		  $term_resource_ids = $term_resources['resource_ids'];
  		  
  		  foreach ($term_resource_ids as $object_id) {
  				$this->api->setQuery( array( $object_type => $object_id, ) );
  				$response = $this->api->getResponse( 0, null, 1);

  				foreach( $response->results as $resource ){
  					if( !in_array( $resource->object_id, $this->resources ) ){
  						$this->resources[] = $resource->object_id;
  					  $results[] = $this->add_new_resource( $term, $resource, $dry_run );
  					}
  				}
  			}
		  }
		}
		
		return $results;
	}
	
	/**
	 * Imports resource object into CDKN
	 * 
	 * @param Object $resource
	 * @return void
	 */
	function add_new_resource( $term, $resource, $dry_run = FALSE ){
	  
	  // 1. Build resource post
	  $resource_author;
	  if (is_array($resource->author) && isset($resource->author[0]) && $resource->author[0]) {
	    $resource_author = $resource->author[0];
	  } else {
	    $resource_author = 'Eldis';
	  }
	  
	  $resource_post = array(
	    'post' => array(
	      'post_author' => $this->eldis_author_id,
        'post_content' => $resource->description,
        'post_date' => $resource->date_created,
        'post_status' => 'publish',
        'post_title' => $resource->title,
        'post_type' => 'resource',
        'post_excerpt' => $resource->title,
	    ),
	    'meta' => array(
	      'eldis_real_author' => $resource_author,
	      'eldis_uri' => $resource->website_url,
	    )
	  );
	  
	  // Add term, which is the link to this resource
	  if (isset($term) && isset($term->taxonomy)) {
	    $resource_post['post']['tax_input'] = array(
        $term->taxonomy => array($term->term_id,)
	    );
	  }
	  
	  // On dry run, create the post associative arrays as you would before actual creation (duh)
	  if ($dry_run) {
	    return $resource_post;
	  }
	  
	  // Insert the post into the db
	  $resource_id = wp_insert_post($resource_post['post'], TRUE);
	  if (!is_int($resource_id)) {
	    // WP_Error has occurred :(
	    var_dump($resource_id);
	  } else {
	    // Success!
	    // Add post meta
	    foreach ($resource_post['meta'] as $meta_key => $meta_value) {
        add_post_meta($resource_id, $meta_key, $meta_value);
	    }
	  }
	}

	/**
	 * Get all the theme eldis object id's currently contained whitin CDKN
	 * 
	 * @return array
	 */
	private function get_theme_object_ids(){
		$themes = get_terms('themecats');
		$theme_object_ids = array();
		foreach( $themes as $theme ){
			$object_id = $this->get_eldis_object( $theme );
			if ($object_id) {
			  if (!isset($region_object_ids[$region->term_id])) {
  			  $theme_object_ids[$theme->term_id] = array(
  			    'term' => $theme,
  			    'resource_ids' => array()
  			  );
  			}
			  
			  if (!isset($theme_object_ids[$theme->term_id]['resource_ids'][$object_id])) {
			    $theme_object_ids[$theme->term_id]['resource_ids'][$object_id] = $object_id;
			  }
			}
		}		
		return $theme_object_ids;
	}	
	
	/**
	 * Get all the region eldis object ids currently contained within CDKN
	 * 
	 * @return array
	 */
	private function get_region_object_ids(){
		$regions = get_terms('regioncats');
		$region_object_ids = array();
		$country_object_ids = array();
		foreach( $regions as $region ){
			$object_id = $this->get_eldis_object( $region );
			
			if($object_id){
			  if (!isset($region_object_ids[$region->term_id])) {
  			  $region_object_ids[$region->term_id] = array(
  			    'term' => $region,
  			    'resource_ids' => array()
  			  );
  			  $country_object_ids[$region->term_id] = array(
  			    'term' => $region,
  			    'resource_ids' => array()
  			  );
  			}
			  
			  $parent_is_zero = $region->parent == 0;
			  $saved_in_regions = isset($region_object_ids[$region->term_id]['resource_ids'][$object_id]);
			  $saved_in_countries = isset($country_object_ids[$region->term_id]['resource_ids'][$object_id]);
			  
			  // Save the object id in the appropriate place if not already present
				if($parent_is_zero && !$saved_in_regions){
					$region_object_ids[$region->term_id]['resource_ids'][$object_id] = $object_id;
				} else if ($region->parent != 0 && !$saved_in_countries) {
					$country_object_ids[$region->term_id]['resource_ids'][$object_id] = $object_id;
				}
			}
		}
		return array( 'country' => $country_object_ids, 'region' => $region_object_ids);
	}
	
	/**
	 * Get the last added resource eldis object id's
	 * 
	 * @return array
	 */
	private function get_resource_object_ids(){
		$resources = get_posts(array( 'numberposts' => $this->numberdocs*$this->totalterms, 'post_type' => 'resource' ));
		$resource_object_ids = array();
		foreach( $resources as $resource ){
			$object_id = get_post_meta( $resource->ID, 'eldis_object_id', true );
			if( $object_id ){
				$resource_object_ids[] = $object_id;
			}
		}
		return array_unique( $resource_object_ids );
	}
}