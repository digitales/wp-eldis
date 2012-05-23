<?php
$wp_eldis_import = new WP_Eldis_Import();

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
		add_action( 'wp_eldis_cron_job', array( $this, 'start_import') );
		parent::__construct();		
	}
	
	/**
	 * Initiates the import by creating a new Import object.
	 * This function is static so we don't have to create a new object when scheduling the event.
	 *
	 * @author Maarten Jacobs
	 */
	static function start_import($dry_run = FALSE) {
	  $handler = new WP_Eldis_Import();
	  $results = $handler->initiate_import($dry_run);
	  
	  return $results;
	}
	
	/**
	 * Sets all the needed variables and starts the import
	 * 
	 * @return array
	 */
	function initiate_import( $dry_run = FALSE ){

		// Get the linked theme ids from our terms
		$object_ids = $this->get_region_object_ids();
		$object_ids['theme'] = $this->get_theme_object_ids();

		// TODO: figure out if the error is here.
		// The totalterms will always equal 3 here, because it looks at the toplevel values
		// as opposed to the term ids per category (country, theme, region)
		$this->totalterms = 0; // count( $object_ids );
		foreach ($object_ids as $term_holder) {
			$this->totalterms += count($term_holder);
		}
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
  				$this->api->setQuery( array( 
  						$object_type => $object_id, 
  					) 
  				);
  				$response = $this->api->getResponse( 0, null, 1 );

  				foreach( $response->results as $resource ){
  					$is_existing_resource = in_array( $resource->object_id, $this->resources );

  					if( !$is_existing_resource ){
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
	 * Retrieves and caches the term id of the Resource term related to the imported Eldis resourc.
	 * If the term does not exist, it will create the term as well.
	 * 
	 * This is necessary for importing documents from Eldis, as they need
	 * to be tagged with their appropriate resource type.
	 *
	 * @param string $resource_type The imported resource's type. For instance: 'document'
	 * @return int
	 * @author Maarten Jacobs
	 **/
	protected function get_resource_term_id( $resource_type ) { 
		static $term_cache;

		// Initialise the cache if necessary
		$existing_cache = is_array( $term_cache );
		if (!$existing_cache) {
			$term_cache = array();
		}

		// Check if the term is set in the cache
		$resource_type_key = strtolower($resource_type);
		if ( !isset( $term_cache[$resource_type_key] ) ) {

			$existing_term = get_term_by( 'name', $resource_type, 'resourcecats', ARRAY_A );

			// Check if the term exists
			// If not, create the term
			if (!$existing_term) {
				$existing_term = wp_insert_term( $resource_type, 'resourcecats' );
			}

			// Add the term to the cache
			$term_cache[ $resource_type_key ] = $existing_term[ 'term_id' ];

		}

		return $term_cache[ $resource_type_key ]; 
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
	  
	  $cat_id = $this->get_category_id('indirect','category');
	  $eldis_term_id =  $this->get_category_id('Eldis','organisationcats');
	  
	  $resource_post = array(
	    'post' => array(
	      'post_author' => $this->eldis_author_id,
        'post_content' => $resource->description,
        'post_date' => $resource->date_created,
        'post_status' => 'publish',
        'post_title' => $resource->title,
        'post_type' => 'resource',
        'post_excerpt' => $resource->title,
        'post_category' => array($cat_id),
	    ),
	    'meta' => array(
	      'eldis_real_author' => $resource_author,
	      'syndication_permalink' => $resource->website_url,
	      'eldis_object_id' => $resource->object_id,
	    )
	  );
	  
	  // Add term, which is the link to this resource
	  if (isset($term) && isset($term->taxonomy)) {
	  	
	    $resource_post['post']['tax_input'] = array(
        $term->taxonomy => array(
        	$term->term_id
        ),
        'organisationcats' => array(
        	$eldis_term_id
        ),
        'resourcecats' => array(
        	$this->get_resource_term_id( $resource->object_type )
        )
	    );

	    // The imported document might not be imported via a 
	  	// region term. In that case, we'll pull out the regions of the document
	  	// and search for a term that matches it.
	  	if ($term->taxonomy != 'regioncats' && isset($resource->category_region_array)) {
	  		foreach ($resource->category_region_array->Region as $region) {
	  			$region_term = WP_Eldis::get_term_by_eldis_id( $region->object_id, 'regioncats' );

	  			if ($region_term) {
	  				if (!isset($resource_post['post']['tax_input']['regioncats'])) {
	  					$resource_post['post']['tax_input']['regioncats'] = array();
	  				}

	  				$resource_post['post']['tax_input']['regioncats'][] = (int)$region_term->term_id;
	  			}
	  		}
	  	}
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
	    // Couldn't be inserted
	    return false;
	  } else {
	    // Success!
	    // Add post meta
	    foreach ($resource_post['meta'] as $meta_key => $meta_value) {
        add_post_meta($resource_id, $meta_key, $meta_value);
	    }
	    // Add post terms
	    if(isset($resource_post['post']['tax_input'])){
        foreach( $resource_post['post']['tax_input'] as $taxonomy => $term_ids){
        	$term_ids = array_unique( array_map( 'intval', $term_ids ) );
          wp_set_object_terms( $resource_id, $term_ids, $taxonomy );
        }
      }
	  }
	  
	  // Inserted resource
	  return true;
	}

  //get category id by it's name
  function get_category_id($cat_name, $type){
    $term = get_term_by('name', $cat_name, $type);
    return $term->term_id;      
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
		$region_object_ids = array();
		$country_object_ids = array();

		// Get all region terms
		$regions = get_terms('regioncats');

		foreach( $regions as $region ){

			// Get the linked Eldis object id
			$object_id = $this->get_eldis_object( $region );
			if($object_id){

				// Initialise the region for saving resource ids
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
  			// Hold a reference to the resource holder for ease of use
  			$region_objects = &$region_object_ids[$region->term_id]['resource_ids'];
  			$country_objects = &$country_object_ids[$region->term_id]['resource_ids'];
			  
			  $parent_is_zero = $region->parent == 0;
			  $saved_in_regions = isset( $region_objects[$object_id] );
			  $saved_in_countries = isset( $country_objects[$object_id] );
			  
			  // Save the object id in the appropriate place if not already present
				if($parent_is_zero && !$saved_in_regions){
					$region_objects[$object_id] = $object_id;
				} else if (!$parent_is_zero && !$saved_in_countries) {
					$country_objects[$object_id] = $object_id;
				}

			}
		}

		return array( 
			'country' => $country_object_ids, 
			'region' => $region_object_ids
		);
	}
	
	/**
	 * Get the last added resource eldis object id's
	 * 
	 * @return array
	 */
	protected function get_resource_object_ids(){
		$resources = get_posts(
			array( 
				'numberposts' => $this->numberdocs * $this->totalterms, 
				'post_type' => 'resource' 
			)
		);
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