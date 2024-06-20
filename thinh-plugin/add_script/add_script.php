<?php
/*
Plugin Name: Add Script Elementor Widget
Plugin URL:
Description: A plugin that adds a widget to Elementor to insert script into specific page head.
Version: 1.0.0
Author: Thinh2k
Author URL:
Text Domain:
Requires at least:
Requires PHP: 7.4
*/
if (!defined('ABSPATH')) {
    exit;
}

function add_script_elementor_widget_start(){
    define( 'ADD_SCRIPTS_FILE', __FILE__ );
    require_once(__DIR__.'/includes/script_plugin.php');
    \WP_add_script_elementor_widget\ScriptPlugin::instance();
} 

add_action('plugins_loaded', 'add_script_elementor_widget_start');