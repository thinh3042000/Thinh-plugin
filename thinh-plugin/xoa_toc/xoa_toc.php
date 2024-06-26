<?php
/*
Plugin Name: Xoa TOC
Plugin URL:
Description: Xoa TOC is a WordPress plugin that automatically generates a Table of Contents (TOC) for your single posts.
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

function xoa_toc_start(){
    define( 'XOA_TOC_FILE', __FILE__ );
    require_once(__DIR__.'/includes/xoa_toc_plugin.php');
    \WP_xoa_TOC\XoaTocPlugin::instance();
} 

add_action('plugins_loaded', 'xoa_toc_start');