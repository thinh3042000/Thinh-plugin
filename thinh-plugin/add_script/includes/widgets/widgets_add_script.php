<?php
if (!defined('ABSPATH')) {
    exit;
}
class widgets_add_script extends \Elementor\Widget_Base
{

    public function get_name()
    {
        return 'custom-script-elementor-widget';
    }

    public function get_title()
    {
        return ('Custom Script Elementor Widget');
    }

    public function get_icon()
    {
        return 'eicon-code-highlight';
    }

    public function get_categories()
    {
        return ['widgets_add_script'];
    }

    public function get_keywords()
    {
        return ['script'];
    }

    protected function _register_controls()
    {
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Content', 'custom-script-elementor-widget'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'script_code',
            [
                'label' => __('Script Code', 'custom-script-elementor-widget'),
                'type' => \Elementor\Controls_Manager::CODE,
                'default' => '',
                'language' => 'javascript',
                'description' => __('Enter your script here.', 'custom-script-elementor-widget'),
            ]
        );

        $this->end_controls_section();
    }

    public function add_custom_script_to_head()
    {
        $settings = $this->get_settings_for_display();

        if (!empty($settings['script_code'])) {
?>
            <script>
                jQuery(document).ready(function($) {
                    <?php echo $settings['script_code']; ?>
                });
            </script>
<?php
        }
    }
    
    protected function render()
    {
        $settings = $this->get_settings_for_display();
        $page_id = is_single() ? 'single-post' : get_the_ID();
        $script_code = $settings['script_code'];
    
        $plugin_dir = plugin_dir_path(__FILE__);
        $js_dir = $plugin_dir . 'js/page-scripts/';
    
        $file_path = $js_dir . 'page-' . $page_id . '.js';
    
        if (!file_exists($file_path)) {
            if (!file_exists($js_dir)) {
                mkdir($js_dir, 0755, true);
            }
            file_put_contents($file_path, $script_code);
        } else {
            file_put_contents($file_path, $script_code);
        }
        // $this->add_custom_script_to_head();
    }
}
