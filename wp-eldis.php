<?php
/*
Plugin Name: WP Eldis
Plugin URI: http://headshift.com/
Description: Allows searches to be performed on the Eldis API
Version: 1.0
Author: Ross Tweedie
Author URI: http://headshift.com/

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
//include(dirname(__FILE__) . DIRECTORY_SEPARATOR . "models/event-posts.php");
//include(dirname(__FILE__) . DIRECTORY_SEPARATOR . "models/events.php");
//include(dirname(__FILE__) . DIRECTORY_SEPARATOR . "models/groups.php");
//include(dirname(__FILE__) . DIRECTORY_SEPARATOR . "models/group-taxonomy.php");
include(dirname(__FILE__) . DIRECTORY_SEPARATOR . "models/options.php");
//include(dirname(__FILE__) . DIRECTORY_SEPARATOR . "models/api.php");
//include(dirname(__FILE__) . DIRECTORY_SEPARATOR . "controller.php");
//include(dirname(__FILE__) . DIRECTORY_SEPARATOR . "controllers/widget.php");
//include(dirname(__FILE__) . DIRECTORY_SEPARATOR . "controllers/events_controller.php");

$eldis = new WP_Eldis();

register_activation_hook( __FILE__, array($eldis, 'activate') );
register_deactivation_hook( __FILE__, array($eldis, 'deactivate') );

//add_action( 'widgets_init', create_function( '', 'return register_widget("WP_Meetup_Calendar_Widget");' ) );
add_action('admin_menu', array($eldis, 'admin_menu'));
add_filter( 'the_content', array($eldis, 'the_content_filter') );

//add_shortcode( 'wp-meetup-calendar', array($meetup, 'handle_shortcode') );

wp_register_style('wp-eldis', plugins_url('eldis.css', __FILE__));
wp_enqueue_style( 'wp-eldis' );
// wp_register_style('farbtastic', plugins_url('/js/farbtastic/farbtastic.css', __FILE__));
// wp_enqueue_style( 'farbtastic' );

// add_action('update_events_hook', array($meetup, 'cron_update'));

add_action('admin_init', array($eldis, 'admin_init'));

add_action('admin_notices', array($eldis, 'admin_notices'), 12);

class WP_Eldis {
    
    public $dir;
    public $admin_page_url;
    public $events_page_url;
    public $groups_page_url;
    public $dev_support_page_url;
    public $feedback = array('error' => array(), 'message' => array());
    public $plugin_url;

    function __construct() {
	
        $this->dir = WP_PLUGIN_DIR . "/wp-eldis/";
        $this->plugin_url = plugins_url('/', __FILE__);
        $this->admin_page_url = admin_url("admin.php?page=wp_eldis");
        // $this->events_page_url = admin_url("admin.php?page=wp_meetup_events");
        // $this->groups_page_url = admin_url("admin.php?page=wp_meetup_groups");
        // $this->dev_support_page_url = admin_url("admin.php?page=wp_meetup_dev_support");
    }
    
    function activate() {
    	//$events_model = new WP_Meetup_Events();
    	//$events_model->create_table();
	
    	//if ( !wp_next_scheduled('update_events_hook') ) {
    	//    wp_schedule_event( time(), 'hourly', 'update_events_hook' );
    	//}
    }
    
    function deactivate() {
    	//$events_model = new WP_Meetup_Events();
    	//$events_model->drop_table();
    	//$groups_model = new WP_Meetup_Groups();
    	//$groups_model->drop_table();
    	$options_model = new WP_Eldis_Options();
    	$options_model->delete_all();
	
    	wp_clear_scheduled_hook('update_events_hook');
    }
    
    function admin_init() {
        // wp_register_script('farbtastic', plugins_url('/js/farbtastic/farbtastic.js', __FILE__), array('jquery'));
        wp_register_script('options-page', plugins_url('/js/options-page.js', __FILE__), array('jquery'));
    }
    
    function cron_update() {
        $events_controller = new WP_Meetup_Events_Controller();
        $status = $events_controller->cron_update_events();
	
        //file_put_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'data.txt', date('r') . " " . ($status ? 'success' : 'failure') . "\n", FILE_APPEND);
    }
    
    function the_content_filter($content) {
        return $content;
        //$events_controller = new WP_Meetup_Events_Controller();
        //return $events_controller->the_content_filter($content);
    }
    
    //function group_url_name_to_meetup_url($group_url_name) {
    //    return "http://www.meetup.com/" . $group_url_name;
    //}
    
    //function meetup_url_to_group_url_name($meetup_url) {
	//$parsed_name = str_replace("http://www.meetup.com/", "", $meetup_url);
    //    return  strstr($parsed_name, "/") ? substr($parsed_name, 0, strpos($parsed_name, "/")) : $parsed_name;
    //}
    
    function admin_menu() {
        $this->import_model('options');
        
        //$events_controller = new WP_Meetup_Events_Controller();
        //$events_controller->handle_post_data();
        $pages = array();
        //$pages[] = add_menu_page('WP Eldis', 'WP Eldis', 'manage_options', 'wp_eldis', array($events_controller, 'admin_options'), FALSE, 30);
        //$pages[] = add_menu_page('WP Eldis', 'WP Eldis', 'manage_options', 'wp_eldis', array($options, 'admin_options'), FALSE, 30);
        // if ($this->options->get('api_key')) {
        //    $pages[] = add_submenu_page('wp_meetup', 'WP Meetup Groups', 'Groups', 'manage_options', 'wp_meetup_groups', array($events_controller, 'show_groups'));
        //    $pages[] = add_submenu_page('wp_meetup', 'WP Meetup Events', 'Events', 'manage_options', 'wp_meetup_events', array($events_controller, 'show_upcoming'));
        // }
        $page = add_options_page('WP Eldis Options', 'WP Eldis', 'manage_options', 'wp_eldis', array($options, 'admin_options'));
        //$this->pr($pages);
        foreach ($pages as $page)
            add_action('admin_print_styles-' . $page, array($this, 'admin_styles'));
        }
    
        function admin_styles() {
            wp_enqueue_script('options-page');
        }

    //    function handle_shortcode() {
    //        $events_controller = new WP_Meetup_Events_Controller();
	//
    //        $data = array();
    //        $data['events'] = $events_controller->events->get_all();
    //        $data['groups'] = $events_controller->groups->get_all();
    
    //        return $this->render("event-calendar.php", $data);
    //    }
    
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
    
    function data_table($headings = array(), $rows = array(), $table_attributes = array()) {
	$data = array(
	    'headings' => $headings,
	    'rows' => $rows,
	    'table_attributes' => $table_attributes
	);
	return $this->render('data_table.php', $data);
    }
    
    function open_form() {
	return "<form action=\"" . admin_url("admin.php?page=" . $_GET['page']) . "\" method=\"post\">";
    }
    
    function close_form() {
	return "</form>";
    }
    
    function pr($args) {
	
	$args = func_get_args();
	foreach ($args as $value) {
		echo "<pre>";
		print_r($value);
		echo "</pre>";
	}
	
    }
    
    function import_model($model) {
        $class_name = "WP_Eldis_" . ucfirst($model);
        $this->$model = new $class_name;
    }
    
    function admin_notices() {
        $this->import_model('options');
        if (!$this->options->get('api_key')) {
            if (!(array_key_exists('page', $_GET) && $_GET['page'] == 'wp_eldis')) {
                echo "<div class=\"updated\"><p>Configure <a href=\"" . $this->admin_page_url . "\">WP Eldis</a> to start using the API.</p></div>";
            }
        }
        //$this->pr($this->options->get('show_plug'), $_POST['show_plug']);
    }
    
    function display_feedback() {
        
        foreach ($this->feedback as $message_type => $messages): 

            foreach ($messages as $message):
                echo "<div class=\"" . ($message_type == 'error' ? 'error' : 'updated')  . "\"><p>{$message}</p></div>";
            endforeach;

        endforeach;
    }

}