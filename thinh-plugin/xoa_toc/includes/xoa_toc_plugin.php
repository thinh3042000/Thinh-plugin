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

        wp_register_script('single-post.js', plugins_url('assets/js/single-post.js', XOA_TOC_FILE), array(), null, false);
        wp_register_script('loading.js', plugins_url('assets/js/loading.js', XOA_TOC_FILE), array(), null, true);
        add_action('wp_head', [$this, 'enqueue_frontend_scripts'], 5);
    }


    public function enqueue_frontend_scripts()
    {

        wp_enqueue_script('single-post.js');
        wp_enqueue_script('loading.js');
    }
    function custom_toc_shortcode()
    {
        global $post;
        $content = $post->post_content;

        preg_match_all('/<h2.*?>(.*?)<\/h2>/', $content, $matches);

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
      
div#fixed-toc {
  padding: 10px 30px;
  overflow-y: scroll;
  max-height: 350px;
  margin-top: -40px;
  margin-bottom: -15px;
  border: 1px solid #e5e1e1;
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
        .toc-custom {
            font-size: 20px;
            font-weight: 500;
        }
        #toc-list {
            padding-left: 20px;
        }
        #toc-list li {
            margin-bottom: 10px;
        }
        .link-item-custom {
            color: black;
            font-weight: 400;
            text-decoration: none;
        }
        .link-item-custom.active {
            color: #00af5a !important;
        }
            #toc-content ol {
    list-style-type: decimal;
}

#toc-content ol li::marker {
    font-weight: 600;
    font-size: 16px;
}
    a.link-item-custom:hover {
    color: #00AF5A;
}
    .toc-toggle {
  font-size: 1.2em;
  display: flex;
  align-items: center;
}
  svg {
  height: 1em;
  width: 1em;
  fill: var(--toggle-button-color);
}
  #toc-content ::-webkit-scrollbar {
  width: 15px;
}

#toc-content ::-webkit-scrollbar-track {
  background: white; 
}
 
#toc-content ::-webkit-scrollbar-thumb {
  background: #f2f2f2 
  ; 
}

#toc-content ::-webkit-scrollbar-thumb:hover {
  background: #f0f0f0
  ; 
}

        </style>
        <div id="toc-loading">
            <div id="loading-spinner"></div>
        </div>
        <div id="toc-content" style="display: none;">
            <div id="fixed-toc" class="toc-container">
                <div class="toc-header">
                    <span class="toc-custom">Table of Contents</span>
                    <span id="toc-toggle" class="toc-toggle">
                      <svg viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg"><path d="M240.971 130.524l194.343 194.343c9.373 9.373 9.373 24.569 0 33.941l-22.667 22.667c-9.357 9.357-24.522 9.375-33.901.04L224 227.495 69.255 381.516c-9.379 9.335-24.544 9.317-33.901-.04l-22.667-22.667c-9.373-9.373-9.373-24.569 0-33.941L207.03 130.525c9.372-9.373 24.568-9.373 33.941-.001z"></path></svg>
                    </span>
                </div>
                <ol id="toc-list">';

        foreach ($matches[0] as $index => $match) {
            $id = 'toc-' . ($index + 1);
            $heading = strip_tags($match);

            $content = str_replace($match, preg_replace('/(<h2)(.*?)(>)/', '$1 id="' . $id . '"$2$3', $match), $content);

            $toc .= '<li><a class="link-item-custom" href="#' . $id . '">' . $heading . '</a></li>';
        }

        $toc .= '</ol>
            </div>
        </div>
       ';

        $post->post_content = $content;

        return $toc;
    }
    function handle_shortcode()
    {
        add_shortcode('custom_toc', [$this, 'custom_toc_shortcode']);
    }
}
