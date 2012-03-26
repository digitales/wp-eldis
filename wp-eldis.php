<?php
/*
Plugin Name: WP Eldis
Plugin URI: http://headshift.com/
Description: Allows searches to be performed on the Eldis API
Version: 1.0
Author: Ross Tweedie, Pavlos Syngelakis
Author URI: http://headshift.com/

The code has been influenced by the WP Meetup plugin ( http://nuancedmedia.com/wordpress-meetup-plugin/ ) by Nuanced Media (http://nuancedmedia.com/)

Copyright 2011  Headshift

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

include(dirname(__FILE__) . DIRECTORY_SEPARATOR . "eldis_api/EldisApi.php");
include(dirname(__FILE__) . DIRECTORY_SEPARATOR . "model.php");
include(dirname(__FILE__) . DIRECTORY_SEPARATOR . "wp-eldis-reading-widget.php");
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . "wp-eldis-import-cron.php");
include(dirname(__FILE__) . DIRECTORY_SEPARATOR . "models/api.php");
include(dirname(__FILE__) . DIRECTORY_SEPARATOR . "models/options.php");
include(dirname(__FILE__) . DIRECTORY_SEPARATOR . "controller.php");
include(dirname(__FILE__) . DIRECTORY_SEPARATOR . "controllers/options_controller.php");


$eldis = new WP_Eldis();

register_activation_hook( __FILE__, array($eldis, 'activate') );
register_deactivation_hook( __FILE__, array($eldis, 'deactivate') );

//add_action( 'widgets_init', create_function( '', 'return register_widget("WP_Eldis_Documents");' ) );
add_action( 'widgets_init', create_function( '', 'return register_widget("WP_Eldis_Reading_Widget");' ) );
add_action('admin_menu', array($eldis, 'admin_menu'));
// add_filter( 'the_content', array($eldis, 'the_content_filter') );

add_action('admin_init', array($eldis, 'admin_init'));
add_action('admin_notices', array($eldis, 'admin_notices'), 12);

add_action('regioncats_add_form_fields', array($eldis, 'add_eldis_object_field'));
add_action('regioncats_edit_form_fields', array($eldis, 'add_eldis_object_field'), 10, 2);

add_action('themecats_add_form_fields', array($eldis, 'add_eldis_object_field'));
add_action('themecats_edit_form_fields', array($eldis, 'add_eldis_object_field'), 10, 2);

add_action( 'created_term', array($eldis, 'save_eldis_object_field'), 10, 3);
add_action( 'edited_term', array($eldis, 'save_eldis_object_field'), 10, 3);

add_action( 'admin_enqueue_scripts', array( $eldis, 'add_eldis_theme_script'), 10, 1 );

add_action( 'wp_ajax_theme_results', array( $eldis, 'theme_results_callback') );

class WP_Eldis {
    
    public $dir;
    public $admin_page_url;
    public $feedback = array('error' => array(), 'message' => array());
    public $plugin_url;

    function __construct() {	
        $this->dir = WP_PLUGIN_DIR . "/wp-eldis/";
        $this->plugin_url = plugins_url('/', __FILE__);
        $this->admin_page_url = admin_url("admin.php?page=wp_eldis");
				self::init();
    }
    
    function init(){
        wp_enqueue_style( 'wp-eldis' );
        wp_register_style('wp-eldis', plugins_url('eldis.css', __FILE__));
    }
    
	/**
	 * Adds the eldis object id field to the regions term backend
	 * 
	 * @param object $term
	 * @return void
	 */
	function add_eldis_object_field( $term) {
		$this->setAPI();
		
		$object = '';
		$eldis_object = $this->get_eldis_object( $term );
		$object_type;
		
		switch($term->taxonomy){
			case 'themecats':
					$this->display_theme_eldis_object_field($eldis_object);
				break;
			case 'regioncats':
					if($term->parent == 0){
						$object = 'regions';
						$object_type = 'object_id';
					} else {
						$object = 'countries';
						$object_type = 'iso_two_letter_code';
					}
					$regionResults = $this->get_region_results($object);
					$this->display_region_eldis_object_field( $regionResults, $object_type, $eldis_object );
				break;
			default:
				break;
		}
	}
	
	/**
	 * Returns a collection from all regions or countries
	 * 
	 * @param string $object
	 * @return array
	 * 
	 */
	function get_region_results($object){		
		$url = 'openapi/eldis/get_all/'.$object.'/full/';
		$this->api->setMethod($url);
		
		$response = $this->api->getResponse();
		return $response->results;
	}
	
	/**
	 * Displays the added eldis field for regions
	 * 
	 * @return void
	 */
	function display_region_eldis_object_field( $results, $object_type, $eldis_object ){
		?>
		<div class="eldis-form-field">
		<label for="regioncats_eldis_object">
			Eldis Object ID
		</label>
		<select name="regioncats_eldis_object">
		<option>none</option>
		<?php foreach($results AS $region): ?>
		<option value="<?php echo $region->$object_type; ?>" <?php echo $region->$object_type == $eldis_object ? ' selected="selected"' : ''; ?>  ><?php echo $region->title; ?></option>
		<?php endforeach; ?>
		</select>
		</div>		
		<?php
	}
	
	/**
	 * Displays the added eldis field for themes
	 * 
	 * @return void
	 */
	function display_theme_eldis_object_field( $eldis_object ){
		?>
		<div class="eldis-form-field">
		<label for="themecats_eldis_object">
			Eldis Object ID
		</label>
		<input type="text" value="<?php echo $eldis_object ? $eldis_object : 'Enter keyword here.' ?>" id="keywords" />
		<input type="submit" name="search-eldis-objects" value="search" class="button-primary" id="theme_results_button"/>
		<fieldset id="theme_results">
		</fieldset>
		</div>		
		<?php
	}
	
	/**
	 * Adds Javascript needed for the theme eldis object field
	 * 
	 * @return void
	 */
	function add_eldis_theme_script($hook){
		if($hook == 'edit-tags.php'){
			wp_enqueue_script( 'wp-eldis-theme-results',plugins_url() . '/' . basename(dirname(__FILE__)) .'/js/wp-eldis-theme-results.js', array('jquery'), false, true );
			wp_localize_script( 'wp-eldis-theme-results', 'wpajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
		}		
	}
	
	/**
	 * Callback function for the theme eldis object id search
	 * 
	 * @return void
	 */
	function theme_results_callback(){
		$results = null;
		$keywords = !empty($_POST['keywords']) && isset($_POST['keywords']) ? explode(' ',$_POST['keywords']) : null;
		if($keywords && !in_array('',$keywords)){
			$this->setAPI();
			$url = 'openapi/eldis/search/themes/';
			$this->api->setMethod($url);
			$this->api->setQuery(array(
	    		'q' => implode(' ',$keywords),
	    	));
			$response = $this->api->getResponse();
			$results = $response->results;
		}
		
		$this->print_theme_results($results);
		die;
	}
	
	/**
	 * Displays the found results for the theme eldis object search
	 * 
	 * @return void
	 */
	function print_theme_results($results){
		if(isset($results) && !empty($results)){
			echo '<legend>Results</legend>';
			foreach($results as $result){
				echo '<label><input type="radio" name="themecats_eldis_object" value="'.$result->object_id.'" />&nbsp;'.$result->title.'</label><br />';
			}
		} else {
			echo 'Sorry no matching results were found.';
		}
	}
	
	/**
	 * Retrieves the eldis object id of the given term
	 * 
	 * @param object $term
	 * @return string
	 */
	function get_eldis_object( $term ){
		return get_metadata($term->taxonomy, $term->term_id, $term->taxonomy.'_eldis_object', true);
	}
	
	/**
	 * Saves the eldis object id set in the term edit page
	 * 
	 * @param string $term_id
	 * @param string $tt_id
	 * @param string $taxonomy
	 * @return void
	 */
	function save_eldis_object_field($term_id, $tt_id = NULL, $taxonomy = NULL){
		if ( isset( $_POST[$taxonomy.'_eldis_object']) && $taxonomy){
			update_metadata($taxonomy, $term_id, $taxonomy.'_eldis_object', $_POST[$taxonomy.'_eldis_object']);
		}
	}
	
	/**
	 * Instantiates the api object when and imports the options model if not set
	 * Sets an empty api query to prevent failures
	 * Sets desirable configuration for the api
	 * 
	 * @return void
	 */
	function setAPI(){
		if(!isset($this->api)){
			$this->import_model('options');
			$this->api = new EldisAPI( $this->options->get('api_key') );
		}
		
		//if no querystring is needed, set an empty one to prevent faulty results
		$this->api->setQuery(array(
    		'' => '',
    	));

		$this->api->setFormat('json');
		$this->api->setExcludeFormat();
	}
	
    /**
     * Activate the plugin
     *
     * @param void
     * @return void
     */
    function activate()
    {
    	// @todo We need to create a cache folder and assign the relevant permissions.
    	
    	// Schedule caching event daily
    	wp_schedule_event(time(), 'daily', array('WP_Eldis_Import', 'eldis_cache_objects'));
    }
    
    /**
     * Deactivate the plugin
     *
     * @param void
     * @return void
     */
    function deactivate()
    {
    	$options_model = new WP_Eldis_Options();
    	$options_model->delete_all();        
    }
    
    /**
     * Admin_init
     *
     * @param void
     * @return void
     */  
    function admin_init()
    {
    }
    
    /**
     * Admin menu
     *
     * Add the Eldis menu option to the administration system
     *
     * @param void
     * @return void
     */
    function admin_menu()
    {
        $this->import_model('options');
        
        $options_controller = new WP_Eldis_Options_Controller();
        $options_controller->handle_post_data();
        
        $pages = array();
        $pages[] = add_menu_page('WP Eldis', 'WP Eldis', 'manage_options', 'wp_eldis', array($options_controller, 'admin_options'), FALSE);
   
        if ($this->options->get('api_key')) {
            $pages[] = add_submenu_page('wp_eldis', 'WP Options', 'Test', 'manage_options', 'wp_eldis_test', array($this, 'test_page_submit'));
        }

        //foreach ($pages as $page){
        //    add_action('admin_print_styles-' . $page, array($this, 'admin_styles'));
        //}
    }
    
    function test_page_submit() {
      
      // Handle testing of Eldis options
      if (isset($_POST['test-eldis-options'])) {
        $options_controller = new WP_Eldis_Options_Controller();
        $options_controller->handle_post_data();
        $options_controller->test_options();
      }
      
      // Handle testing of Eldis import script
      if (isset($_POST['test-eldis-import'])) {
        require_once __DIR__ . '/wp-eldis-import-cron.php';
        // TRUE for testing purposes
        $import_dry_run = WP_Eldis_Import::start_import(TRUE);
        
        echo $this->render('import-dryrun.php', array(
          'import_dry_run' => $import_dry_run
        ));
      } else {
        $result = array();
        
        if (isset($_POST['do-eldis-import'])) {
          $imported_count = WP_Eldis_Import::start_import();
          
          $result['imported_count'] = $imported_count;
        }
        
        if (isset($_POST['clear-eldis-import'])) {
          // Get all posts from eldiscommunity user (id: 46)
          // and delete those posts
          $posts_query = array(
            'author' => 46,
            'post_type' => 'resource',
            'posts_per_page' => -1,
          );
          $posts_result = new WP_Query();
          $posts_result = $posts_result->query($posts_query);
          $deleted_count = count($posts_result);
          foreach ($posts_result as $resource_post) {
            wp_delete_post($resource_post->ID, true);
          }
          
          $result['deleted_posts'] = true;
          $result['deleted_count'] = $deleted_count;
        }
        
        echo $this->render('import-dryrun.php', $result);
      }
      
    }
   
    /**
     * Admin styles
     *
     * @param void
     * @return void
     */
    function admin_styles()
    {
        wp_enqueue_script('options-page');
    }

    /**
     * Render the template
     * Take the vars and assign them to variables accessible from the rendered template / view
     *
     * @param string $filename
     * @param array $vars
     * @return void
     */
    function render($filename, $vars = array()) {
        if (is_file($this->dir . 'views/' . $filename)) {
            ob_start();
            extract($vars);
            include $this->dir . 'views/' . $filename;
            return ob_get_clean();
        }
        return false;
    }
    
    
    function element($tag_name, $content = '', $attributes = NULL) {
    	if ($attributes) {
    	    $html_string = "<$tag_name";
    	    foreach ($attributes as $key => $value) {
        		if (in_array($key, array('selected', 'checked'))) {
        		    if ($value)
            			$html_string .= " {$key}=\"{$key}\"";
            	} else if ($value != '') {
                    $html_string .= " {$key}=\"{$value}\"";
                }
            }
        } else {
            $html_string = "<$tag_name"; //$html_string = "<$tag_name>";
        }
	
        if (!in_array($tag_name, array('input', 'hr', 'br'))) {
            $html_string .= ">";
            $html_string .= $content;
            $html_string .= "</$tag_name>";
        } else {
            $html_string .= " />";
        }
	
        return $html_string;
    }

    
    function data_table($headings = array(), $rows = array(), $table_attributes = array())
    {
        $data = array(
            'headings' => $headings,
            'rows' => $rows,
            'table_attributes' => $table_attributes
        );
        return $this->render('data_table.php', $data);
    }
    
    
    function open_form()
    {
        return "<form action=\"" . admin_url("admin.php?page=" . $_GET['page']) . "\" method=\"post\">";
    }

    
    function close_form()
    {
        return "</form>";
    }

    
    function pr($args)
    {
        $args = func_get_args();
        foreach ($args as $value) {
        	echo "<pre>";
        	print_r($value);
        	echo "</pre>";
        }
    }

    
    function import_model($model)
    {
        $class_name = "WP_Eldis_" . ucfirst($model);
        $this->$model = new $class_name;
    }

    
    function admin_notices()
    {
        $this->import_model('options');
        if (!$this->options->get('api_key')) {
            if (!(array_key_exists('page', $_GET) && $_GET['page'] == 'wp_eldis')) {
                echo "<div class=\"updated\"><p>Configure <a href=\"" . $this->admin_page_url . "\">WP Eldis</a> to start using the API.</p></div>";
            }
        }
    }
    
    
    function display_feedback()
    {        
        foreach ($this->feedback as $message_type => $messages): 
            foreach ($messages as $message):
                echo "<div class=\"" . ($message_type == 'error' ? 'error' : 'updated')  . "\"><p>{$message}</p></div>";
            endforeach;

        endforeach;
    }

}