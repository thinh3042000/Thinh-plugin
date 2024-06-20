<?php

namespace WP_add_script_elementor_widget;

if (!defined('ABSPATH')) {
    exit;
}

final class ScriptPlugin
{
    // check info version 
    const VERSION = "1.0.0";
    const MINIMUM_ELEMENTOR_VERSION = "3.7.0";
    const MINIMUM_PHP_VERSION = "7.4";

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
        if ($this->is_compatible()) {
            add_action('elementor/elements/categories_registered', [$this, 'register_categories']);
            add_action('elementor/init', [$this, 'init']);
        }
    }

    public function is_compatible()
    {
        //check if Elementor installed and activeted
        if (!did_action('elementor/loaded')) {
            add_action('admin_notices', [$this, 'admin_notice_missing_main_plugin']);
            return false;
        }
        //check for required Elementor version
        if (!version_compare(ELEMENTOR_VERSION, self::MINIMUM_ELEMENTOR_VERSION, '>=')) {
            add_action('admin_notices', [$this, 'admin_notice_minimum_elementor_version']);
            return false;
        }
        //check for required PHP version
        if (!version_compare(PHP_VERSION, self::MINIMUM_PHP_VERSION, '>=')) {
            add_action('admin_notices', [$this, 'admin_notice_minimum_php_version']);
            return false;
        }
        return true;
    }

    public function admin_notice_missing_main_plugin()
    {
        if (isset($_GET['activate'])) unset($_GET['activate']);
        $message = sprintf(
            esc_html__('"%1$s" requires "%2$s" to be installed abd activated.', 'elementor'),
            '<strong>' . esc_html__('WP ADD SCRIPT', 'elementor') . '</strong>',
            '<strong>' . esc_html__('Elementor', 'elementor') . '</strong>',
        );
        printf('<div class="notice_plugin"><p>%1$s</p></div>', $message);
    }

    public function admin_notice_minimum_elementor_version()
    {
        if (isset($_GET['activate'])) unset($_GET['activate']);
        $message = sprintf(
            esc_html__('"%1$s" requires "%2$s" version %3$s or greater.', 'elementor'),
            '<strong>' . esc_html__('WP ADD SCRIPT', 'elementor') . '</strong>',
            '<strong>' . esc_html__('Elementor', 'elementor') . '</strong>',
            self::MINIMUM_ELEMENTOR_VERSION
        );
        printf('<div class="notice_plugin"><p>%1$s</p></div>', $message);
    }

    public function admin_notice_minimum_php_version()
    {
        if (isset($_GET['activate'])) unset($_GET['activate']);
        $message = sprintf(
            esc_html__('"%1$s" requires "%2$s" version %3$s or greater.', 'elementor'),
            '<strong>' . esc_html__('WP ADD SCRIPT', 'elementor') . '</strong>',
            '<strong>' . esc_html__('PHP', 'elementor') . '</strong>',
            self::MINIMUM_PHP_VERSION
        );
        printf('<div class="notice_plugin"><p>%1$s</p></div>', $message);
    }


    public function init()
    {
        add_action('elementor/widgets/register', [$this, 'register_widgets']);
        add_action('elementor/frontend/after_register_scripts', [$this, 'frontend_scripts']);
    }

    public function register_categories($elements_manager)
    {
        $elements_manager->add_category(
            'widgets_add_script',
            [
                'title' => esc_html__('Script_WP', 'add_script_wp'),
                'icon' => 'fa fa-plug',
            ]
        );
    }

    public function register_widgets($widgets_manager)
    {
        require_once(__DIR__ . '/widgets/widgets_add_script.php');
        $widgets_manager->register(new \widgets_add_script());
    }

    public function frontend_scripts()
    {
        if (is_single()) {
            $page_id = 'single-post';
            wp_register_script('page-' . $page_id . '.js', plugins_url('includes/widgets/js/page-scripts/page-' . $page_id . '.js', ADD_SCRIPTS_FILE), array(), null, false);
            add_action('wp_head', [$this, 'enqueue_frontend_scripts'], 5);
        } else {
            $page_id = get_the_ID();
            if (!$page_id && isset($GLOBALS['post'])) {
                $page_id = $GLOBALS['post']->ID;
            }
            if ($page_id) {
                wp_register_script('page-' . $page_id . '.js', plugins_url('includes/widgets/js/page-scripts/page-' . $page_id . '.js', ADD_SCRIPTS_FILE), array(), null, false);
                add_action('wp_head', [$this, 'enqueue_frontend_scripts'], 5);
            }
        }
    }
    public function enqueue_frontend_scripts()
    {
     
        if (is_single()) {
            $page_id = 'single-post';
            $page_id2 = 'single-post';
            wp_enqueue_script('page-' . $page_id . '.js');
        } else {
            $page_id = get_the_ID();
            if (!$page_id && isset($GLOBALS['post'])) {
                $page_id = $GLOBALS['post']->ID;
            }
            if ($page_id) {
                wp_enqueue_script('page-' . $page_id . '.js');
            }
        }
    }
}
