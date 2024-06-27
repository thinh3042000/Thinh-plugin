<?php
/*
Plugin Name: Xoa Download PDF
Plugin URL:
Description: Convert single posts to downloadable PDFs for easy access, enhancing content delivery.
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
function xoa_download_pdf_start(){
    define( 'XOA_DOWNLOAD_PDF_FILE', __FILE__ );
    require_once(__DIR__.'/includes/xoa_download_pdf_plugin.php');
    \WP_xoa_download_pdf\XoaDownloadPdfPlugin::instance();
} 

add_action('plugins_loaded', 'xoa_download_pdf_start');
