<?php
/*
Plugin Name: Xoa Podcast
Plugin URL:
Description: The Xoa Podcast plugin converts WordPress posts to MP3 podcasts using text-to-speech, updating audio files on post publish or update.
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

function xoa_podcast_start(){
    define( 'XOA_PODCAST_FILE', __FILE__ );
    require_once(__DIR__.'/includes/xoa_podcast_plugin.php');
    \WP_xoa_PODCAST\XoaPodcastPlugin::instance();
} 

add_action('plugins_loaded', 'xoa_podcast_start');