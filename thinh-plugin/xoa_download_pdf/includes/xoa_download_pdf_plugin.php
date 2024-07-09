<?php

namespace WP_xoa_download_pdf;

if (!defined('ABSPATH')) {
    exit;
}
require_once plugin_dir_path(__FILE__) . '../vendor/tecnickcom/tcpdf/tcpdf.php';
use \TCPDF;

class MYPDF extends TCPDF
{
    public function Header()
    {
        $plugin_url = plugin_dir_url(__FILE__);
        $image_url = $plugin_url . '../assets/img/logo.png';
        $image_file =  $image_url;

        $file_extension = pathinfo($image_file, PATHINFO_EXTENSION);

        if (in_array($file_extension, ['svg', 'webp'])) {
            $converted_image = $this->convertImageToPng($image_file);
            if ($converted_image) {
                $image_file = $converted_image;
            }
        }

        $this->Image($image_file, 10, 10, 40, '', strtoupper($file_extension), '', 'T', false, 300, '', false, false, 0, false, false, false);
        $this->SetY($this->GetY() - 15);
    }

    private function convertImageToPng($image_file)
    {
        if (extension_loaded('imagick')) {
            try {
                $imagick = new Imagick($image_file);
                $imagick->setImageFormat('png');
                $converted_image = sys_get_temp_dir() . '/' . uniqid() . '.png';
                $imagick->writeImage($converted_image);
                $imagick->clear();
                $imagick->destroy();
                return $converted_image;
            } catch (Exception $e) {
                return false;
            }
        }
        return false;
    }
}

final class XoaDownloadPdfPlugin extends MYPDF
{

    private static $_instance = null;

    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct()
    {
        add_action('elementor/frontend/after_register_scripts', [$this, 'frontend_scripts']);
        add_action('init', [$this, 'handle_fitler']);
        add_action('template_redirect', [$this, 'create_pdf']);
    }

    public function frontend_scripts()
    {
        if (is_single()) {
            wp_register_script('pdf.js', plugins_url('assets/js/pdf.js', XOA_DOWNLOAD_PDF_FILE), array(), null, false);
            wp_register_style('pdf.css', plugins_url('assets/css/pdf.css', XOA_DOWNLOAD_PDF_FILE), array(), null, 'all');
            add_action('wp_head', [$this, 'enqueue_frontend_scripts'], 5);
        }
    }
    public function enqueue_frontend_scripts()
    {
        if (is_single()) {
            wp_enqueue_script('pdf.js');
            wp_enqueue_style('pdf.css');
        }
    }
    function create_pdf()
    {
        if (isset($_GET['download_pdf']) && is_single()) {
            $post_id = get_the_ID();
            $post = get_post($post_id);
            $content = apply_filters('the_content', $post->post_content);
            $title = get_the_title($post_id);
            $link = get_permalink($post_id);
            $content = preg_replace('/<div class="elementor-button-wrapper">.*?<\/div>/s', '', $content);
            $content = preg_replace('/<table>/', '<table style="border: 1px solid hsla(0,0%,50.2%,.5019607843); border-collapse: collapse; padding: 10px;">', $content);
            $content = preg_replace('/<td>/', '<td style="border: 1px solid hsla(0,0%,50.2%,.5019607843); padding: 10px;">', $content);
            $content = preg_replace('/<th>/', '<th style="border: 1px solid hsla(0,0%,50.2%,.5019607843); padding: 10px;">', $content);
            $content = preg_replace('/<a>/', '<a style="color: black !important;">', $content);

            $content = preg_replace("/\n\s*\n/s", "\n", $content);
            if (has_post_thumbnail($post_id)) {

                $author_name = get_the_author_meta('display_name', $post->post_author);
                $author_link = get_author_posts_url($post->post_author);

                $thumbnail_url = get_the_post_thumbnail_url($post_id);

                $thumbnail = '<div class="post-thumbnail-container" style="display: flex; align-items: center;">';
                $thumbnail .= '<div style="flex-grow: 1;">';
                $thumbnail .= '<h1 style="font-size:30px; color:#00AF5A; font-weight:800; margin-bottom:0; padding-bottom:5px; border-bottom:2px solid black; line-height: 1;">' . esc_html($title) . '</h1>';
                $thumbnail .= '<p style="font-size: 14px; margin-top: 5px;">';
                $thumbnail .= '<span style="font-weight: bold;">By:</span> <a href="' . esc_url($author_link) . '">' . esc_html($author_name) . '</a>';
                $thumbnail .= '</p>';
                $thumbnail .= '<div style="flex-shrink: 0; margin-right: 15px;">';
                $thumbnail .= '<img src="' . esc_url($thumbnail_url) . '" alt="' . esc_attr($title) . '">';
                $thumbnail .= '</div>';
                $thumbnail .= '</div>';
                $thumbnail .= '</div>';

                $content = $thumbnail . $content;
            }

            $content = preg_replace_callback('/src="([^"]*)"/i', function ($matches) {
                $url = $matches[1];
                if (substr($url, 0, 4) !== 'http') {
                    $url = site_url() . '/' . $url;
                }
                return 'src="' . $url . '"';
            }, $content);

            $toc = '<h2 style="font-size: 16px;">Table of Contents</h2><ul style="list-style-type: none;">';
            $tocItems = [];
            $h2_counter = 0;
            $h3_counter = 0;

            $content = preg_replace_callback('/<h([23]).*?>(.*?)<\/h[23]>/', function ($matches) use (&$tocItems, &$h2_counter, &$h3_counter) {
                $level = $matches[1];
                $text = strip_tags($matches[2]);
                $id = sanitize_title($text);

                if ($level == 2) {
                    $h2_counter++;
                    $h3_counter = 0;
                    $numbering = $h2_counter . '.';
                } else {
                    $h3_counter++;
                    $numbering = $h2_counter . '.' . $h3_counter;
                }

                $tocItems[] = ['level' => $level, 'text' => $text, 'id' => $id, 'numbering' => $numbering];
                return '<h' . $level . ' id="' . $id . '">' . $matches[2] . '</h' . $level . '>';
            }, $content);

            foreach ($tocItems as $item) {
                $indent = ($item['level'] == 3) ? ' style="font-size: 13px;"' : ' style="font-size: 15px; font-weight: bold;"';
                $numberingStyle = ' style=""';
                $toc .= '<li' . $indent . '><span' . $numberingStyle . '>' . $item['numbering'] . '</span> <a href="#' . $item['id'] . '" style="text-decoration: none; color: black;">' . $item['text'] . '</a></li>';
            }
            $toc .= '</ul>';

            $content = $toc . $content;


            $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor('Your Name');
            $pdf->SetTitle($title);
            $pdf->SetSubject('PDF Document');
            $pdf->SetKeywords('PDF, example, guide');

            $pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
            $pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

            $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
            $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
            $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

            $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

            $pdf->SetFont('helvetica', '', 10);

            $pdf->AddPage();
            $pdf->SetTextColor(0, 0, 0);
            $pdf->writeHTML($content, true, false, true, false, '');

            $pdf->lastPage();


            ob_clean();
            $pdf->Output('' . $title . '.pdf', 'I');
            exit;
        }
    }
    function add_download_pdf_button($content)
    {
        if (is_single() && is_main_query() && !isset($_GET['download_pdf'])) {
            $plugin_url = plugin_dir_url(__FILE__);
            $image_url = $plugin_url . '../assets/img/pdf.png';
            $button = '<div class="download-pdf-link">';
            $button .= '<a target="_blank" href="?download_pdf" class="download-pdf-button" style="display: inline-flex; align-items: center; text-decoration: none;">';
            $button .= '<img src="' . esc_url($image_url) . '" alt="Download PDF" width="70" height="70" style="margin-right: 5px;">';
            $button .= '<span style="font-size: 16px; color: #00AF5A;">Download PDF</span>';
            $button .= '</a>';
            $button .= '</div>';
            $content .= $button;
        }
        return $content;
    }
    function handle_fitler()
    {
        add_filter('the_content', [$this, 'add_download_pdf_button']);
    }
}
