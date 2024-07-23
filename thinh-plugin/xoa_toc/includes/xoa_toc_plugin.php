<?php

namespace WP_xoa_TOC;

if (!defined('ABSPATH')) {
    exit;
}

final class XoaTocPlugin
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
        add_action('init', [$this, 'handle_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'handle_css']);
    }

    public function handle_css()
    {
        wp_enqueue_style('single-post.css', plugins_url('assets/css/single-post.css', XOA_TOC_FILE), array(), '1.0.0', 'all');
        wp_enqueue_style('loading.css', plugins_url('assets/css/loading.css', XOA_TOC_FILE), array(), '1.0.0', 'all');
    }
    public function frontend_scripts()
    {
        if (is_single()) {
            wp_register_script('single-post.js', plugins_url('assets/js/single-post.js', XOA_TOC_FILE), array(), null, false);
            wp_register_script('loading.js', plugins_url('assets/js/loading.js', XOA_TOC_FILE), array(), null, true);
            add_action('wp_head', [$this, 'enqueue_frontend_scripts'], 5);
        }
    }


    public function enqueue_frontend_scripts()
    {
        if (is_single()) {
            wp_enqueue_script('single-post.js');
            wp_enqueue_script('loading.js');
        }
    }
    function custom_toc_shortcode()
    {
        global $post;
        $content = $post->post_content;

        preg_match_all('/<h2.*?>(.*?)<\/h2>|<h3.*?>(.*?)<\/h3>/', $content, $matches);

        if (empty($matches[0])) {
            return '';
        }
        $toc = '<style>
        #toc-loading {
    position: relative;
    width: 100%;
    height: 100%;
    background: #fff;
    display: flex;
    justify-content: center;
    align-items: center;
}

#loading-spinner {
    border: 4px solid #f3f3f3;
    border-radius: 50%;
    border-top: 4px solid #00AF5A; 
    width: 30px; 
    height: 30px; 
    -webkit-animation: spin 1s linear infinite; 
    animation: spin 1s linear infinite; 
}

@-webkit-keyframes spin {
    0% { -webkit-transform: rotate(0deg); }
    100% { -webkit-transform: rotate(360deg); }
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
    li.toc-item {
  list-style: none;
}

li.toc-subitem {
  list-style: none;
}

a.link-item-custom-child {
  color: black;
  font-weight: 400;
}

div#fixed-toc {
  padding: 10px 30px;
  border-radius: 10px;
  border: 1px solid #979595;
  overflow-y: scroll;
  max-height: 350px;
  margin-top: -40px;
  margin-bottom: -15px;
  border: 1px solid #e5e1e1;
}
div#fixed-toc-mobile {
  padding: 2px 15px 0px 15px;
  border-radius: 10px;
  border: 1px solid #ffffff;
}

ul#toc-list {
  padding: 0px;
}

a.link-item-custom {
  color: black;
  font-weight: 600;
}

.toc-subitem {
  margin-left: -23px;
}

svg {
  height: 1em;
  width: 1em;
  fill: var(--toggle-button-color);
}
span.toc-custom-mobile {
  font-size: 16px;
  font-weight: 400;
}
span.toc-custom {
  font-size: 20px;
  font-weight: 500;
}
.toc-container {
  border: 1px solid #ccc;
  padding: 10px;
  margin-bottom: 20px;
}
.toc-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  cursor: pointer;
  margin-bottom: 15px;
}
.toc-toggle {
  font-size: 1.2em;
  display: flex;
  align-items: center;
}
.toc-item a.active,
.toc-subitem a.active {
  color: #00af5a !important;
}
.toc-sublist {
  display: none;
}
.toc-item.active .toc-sublist {
  display: block;
}

        </style>
        <div id="toc-loading">';
        $toc .= '<div id="loading-spinner"></div>';
        $toc .= '</div>';
        $toc .= '<div id="toc-content" style="display: none;">';
        $toc .= '<div id="fixed-toc" class="toc-container">';
        $toc .= '<div class="toc-header"><span class="toc-custom">Table of Contents</span><span id="toc-toggle" class="toc-toggle">
                    <svg aria-hidden="true" class="e-font-icon-svg e-fas-chevron-up" viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg">
                        <path d="M240.971 130.524l194.343 194.343c9.373 9.373 9.373 24.569 0 33.941l-22.667 22.667c-9.357 9.357-24.522 9.375-33.901.04L224 227.495 69.255 381.516c-9.379 9.335-24.544 9.317-33.901-.04l-22.667-22.667c-9.373-9.373-9.373-24.569 0-33.941L207.03 130.525c9.372-9.373 24.568-9.373 33.941-.001z"></path>
                    </svg>
                </span></div>';
        $toc .= '<ul id="toc-list" class="toc-list" style="display: block;">';

        $i = 0;
        $last_h2_id = '';

        foreach ($matches[0] as $match) {
            if (strpos($match, '<h2') !== false) {
                $i++;
                $j = 0;
                $id = 'toc-' . $i;
                $heading = strip_tags($match);
                $numbered_heading = $i . '. ' . $heading;

                $content = str_replace($match, preg_replace('/(<h2)(.*?)(>)/', '$1 id="' . $id . '"$2$3', $match), $content);

                if ($last_h2_id !== '') {
                    $toc .= '</ul></li>';
                }

                $toc .= '<li class="toc-item"><a class="link-item-custom" href="#' . $id . '">' . $numbered_heading . '</a>';
                $toc .= '<ul class="toc-sublist">';
                $last_h2_id = $id;
            } elseif (strpos($match, '<h3') !== false) {
                $j++;
                $id = 'toc-' . $i . '-' . $j;
                $heading = strip_tags($match);
                $numbered_heading = $i . '.' . $j . ' ' . $heading;

                $content = str_replace($match, preg_replace('/(<h3)(.*?)(>)/', '$1 id="' . $id . '"$2$3', $match), $content);

                $toc .= '<li class="toc-subitem"><a class="link-item-custom-child" href="#' . $id . '">' . $numbered_heading . '</a></li>';
            }
        }

        if ($last_h2_id !== '') {
            $toc .= '</ul></li>';
        }

        $toc .= '</ul></div></div>';

        $post->post_content = $content;

        return $toc;
    }
    function handle_shortcode()
    {
        add_shortcode('custom_toc', [$this, 'custom_toc_shortcode']);
    }
}
