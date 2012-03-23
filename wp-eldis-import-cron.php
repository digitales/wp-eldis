<?php


class WP_Eldis_Import extends WP_Eldis {
	protected $numberdocs;
	protected $totalterms;
	protected $resources;
	
	
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
	 * Sets all the needed variables and starts the import
	 * 
	 * @return void
	 */
	function initiate_import(){
		$object_ids = $this->get_region_object_ids();
		$object_ids['theme'] = $this->get_theme_object_ids();
		$this->totalterms = count( $object_ids );
		$this->resources = $this->get_resource_object_ids();
		
		$this->setAPI();
		$url = 'openapi/eldis/search/documents/full';
		$this->api->setMethod( $url );
		$this->api->setPageSize( $this->numberdocs );
		
		$this->import( $object_ids );
		
	}
	
	/**
	 * Calls the API to get the latest resources and imports them if they're not in the CDKN db
	 * 
	 * @param array $object_ids
	 * @return void
	 */
	private function import($object_ids){
		foreach( $object_ids as $object_type => $object_type_ids ){
			foreach( $object_type_ids as $object_id ){
				$this->api->setQuery( array(
	        		$object_type => $object_id,
	        	));
				$response = $this->api->getResponse( 0, null, 1);
				foreach( $response->results as $resource ){
					if( !in_array( $resource->object_id, $this->resources ) ){
						$this->resources[] = $resource->object_id;
						$this->add_new_resource( $resource );						
					}
				}
			}
		}
	}
	
	/**
	 * Imports resource object into CDKN
	 * 
	 * @param Object $resource
	 * @return void
	 */
	function add_new_resource( $resource ){
		// $post = array(
		// 		'post_author' =>,
		// 		'post_category' =>,
		// 		'post_content' =>,
		// 		'post-date' =>,
		// 		'post_date_gmt' =>,
		// 		'post_status' => 'publish',
		// 		'post_title' => $resource->title,
		// 		'post_type' => 'resource',
		// 		
		// 	);
		//here we want to wp_insert the resource
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
			if($object_id){
			$theme_object_ids[] = $object_id;
			}
		}		
		return array_unique( $theme_object_ids );
	}	
	
	/**
	 * Get all the region eldis object id's currently contained whitin CDKN
	 * 
	 * @return array
	 */
	private function get_region_object_ids(){
		$regions = get_terms('regioncats');
		$region_object_ids = array();
		$country_object_ids = array();
		foreach( $regions as $region){
			$object_id = $this->get_eldis_object( $region );
			if($object_id){
				if($region->parent == 0){
					$region_object_ids[] = $object_id;
				} else {
					$country_object_ids[] = $object_id;
				}
			}
		}
		return array( 'country' => array_unique( $country_object_ids ), 'region' => array_unique( $region_object_ids ) );
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