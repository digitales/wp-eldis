<?php
/*
Plugin Name: WP Eldis
Plugin URI: http://headshift.com/
Description: Allows searches to be performed on the Eldis API
Version: 1.0
Author: Ross Tweedie
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
add_filter( 'the_content', array($eldis, 'the_content_filter') );

add_action('admin_init', array($eldis, 'admin_init'));
add_action('admin_notices', array($eldis, 'admin_notices'), 12);

add_action('regioncats_add_form_fields', array($eldis, 'add_eldis_object_field'));
add_action('regioncats_edit_form_fields', array($eldis, 'add_eldis_object_field'), 10, 2);

//TO DO: Make this work with regioncats hook
add_action( 'created_term',array($eldis, 'save_eldis_object_field'), 10, 3);
add_action( 'edited_term', array($eldis, 'save_eldis_object_field'), 10, 3);

class WP_Eldis {
    
    public $dir;
    public $admin_page_url;
    public $feedback = array('error' => array(), 'message' => array());
    public $plugin_url;

    function __construct() {
	
        $this->dir = WP_PLUGIN_DIR . "/wp-eldis/";
        $this->plugin_url = plugins_url('/', __FILE__);
        $this->admin_page_url = admin_url("admin.php?page=wp_eldis");
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
		$object = '';
		$eldis_object = $this->get_region_eldis_object( $term );
		$object_type;
		
		if($term->parent == 0){
			$object = 'regions';
			$object_type = 'object_id';
		} else {
			$object = 'countries';
			$object_type = 'iso_two_letter_code';
		}
		
		$regionResults = $this->get_region_results($object);
		$this->display_eldis_object_field( $regionResults, $object_type, $eldis_object );
		
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
		$api = new EldisAPI($this->options->get('api_key'),$url);
		
		//if no querystring is needed, set an empty one to prevent faulty results
		$api->setQuery(array(
    		'' => '',
    	));
		$api->setFormat('json');
		$api->setExcludeFormat();		
		
		$response = $api->getResponse();
		return $response->results;
	}
	
	/**
	 * Displays the added eldis field for regions
	 */
	function display_eldis_object_field( $results, $object_type, $eldis_object ){
		?>
		<div class="form-field">
		<label for="region_eldis_object">
			Eldis Object ID
		</label>
		<select name="region_eldis_object">
		<option>none</option>
		<?php foreach($results AS $region): ?>
		<option value="<?php echo $region->$object_type; ?>" <?php echo $region->$object_type == $eldis_object ? ' selected="selected"' : ''; ?>  ><?php echo $region->title; ?></option>
		<?php endforeach; ?>
		</select>
		</div>		
		<?php
	}
	
	//Returns the object id for the given region
	function get_region_eldis_object( $term ){
		return get_metadata($term->taxonomy, $term->term_id, 'regioncats_eldis_object', true);
	}
	
	//Saves the object id to wp_regioncatsmeta
	function save_eldis_object_field($term_id, $tt_id = NULL, $taxonomy = NULL){
		if ( isset( $_POST['region_eldis_object']) && $taxonomy == 'regioncats' ){
			update_metadata($taxonomy, $term_id, 'regioncats_eldis_object', $_POST['region_eldis_object']);
		}
	}
	
	
    /**
     * Activate the plugin
     *
     * @param void
     * @return void
     */
    function activate()
    {
    	// @todo We need to create a cache folder and assign the relavent permissions.
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
            $pages[] = add_submenu_page('wp_eldis', 'WP Options', 'Test', 'manage_options', 'wp_eldis_test', array($options_controller, 'test_options'));
        }

        //foreach ($pages as $page){
        //    add_action('admin_print_styles-' . $page, array($this, 'admin_styles'));
        //}
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