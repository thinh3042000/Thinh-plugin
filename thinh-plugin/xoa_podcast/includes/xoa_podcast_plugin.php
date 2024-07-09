<?php

namespace WP_xoa_PODCAST;

if (!defined('ABSPATH')) {
    exit;
}

final class XoaPodcastPlugin
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
        add_action('elementor/frontend/after_register_scripts', [$this, 'frontend_scripts_podcast']);
        add_action('init', [$this, 'register_actions']);
    }

    public function register_actions()
    {
        $this->handle_code();
        $this->xoa_register_podcast_cpt();
    }

    public function frontend_scripts_podcast()
    {
        if (is_single()) {
            wp_register_style('podcast.css', plugins_url('assets/css/podcast.css', XOA_PODCAST_FILE), array(), null, 'all');
            add_action('wp_head', [$this, 'enqueue_frontend_scripts_podcast'], 5);
        }
    }
    public function enqueue_frontend_scripts_podcast()
    {
        if (is_single()) {
            wp_enqueue_style('podcast.css');
        }
    }

    function xoa_add_play_button($content)
    {
        if (is_single() && in_the_loop() && is_main_query() && !post_password_required()) {
            global $post;

            $upload_dir = wp_upload_dir();
            $post_title = sanitize_title($post->post_title);
            $audio_file = $upload_dir['basedir'] . '/audio/' . $post_title . '.mp3';
            $audio_url = $upload_dir['baseurl'] . '/audio/' . $post_title . '.mp3';
            $audio_url_fail = $upload_dir['baseurl'] . '/audio/auto-draft.mp3';

            if (file_exists($audio_file)) {
                $audio_player = '<audio controls><source src="' . esc_url($audio_url) . '" type="audio/mpeg"></audio>';
            } else {
                $audio_player = '<audio controls><source src="' . esc_url($audio_url_fail) . '" type="audio/mpeg"></audio>';
            }
            $content = $audio_player . $content;
        }
        return $content;
    }

    function xoa_generate_mp3_on_publish_or_update($new_status, $old_status, $post)
    {
        if ($post->post_type === 'post') {
            if (($new_status === 'publish' && $old_status !== 'publish') || ($new_status === 'publish' && $old_status === 'publish')) {
                if (!post_password_required($post->ID)) {
                    if (strpos($post->post_content, '<table') !== false) {
                        $post_content = preg_replace('/<table(.*?)<\/table>/is', '<p>For more details, please view the additional information in the table.</p>', $post->post_content);
                    } else {
                        $post_content = $post->post_content;
                    }
                    $upload_dir = wp_upload_dir();
                    $audio_dir = $upload_dir['basedir'] . '/audio/';

                    if (!file_exists($audio_dir)) {
                        wp_mkdir_p($audio_dir);
                    }

                    $post_title = sanitize_title($post->post_title);
                    $audio_file = $audio_dir . $post_title . '.mp3';

                    if (file_exists($audio_file)) {
                        unlink($audio_file);
                    }

                    $audio_content = xoa_text_to_speech(strip_tags($post_content));
                    if ($audio_content !== false) {
                        file_put_contents($audio_file, $audio_content);
                    }
                }
            }
        }
    }

    //dashboard admin
    function xoa_generate_mp3_page()
    {
        echo '<div class="wrap">';
        echo '<h1>Generate MP3 for Existing Posts...</h1>';
        echo '<form method="post" action="">';
        echo '<input type="hidden" name="xoa_generate_mp3" value="1" />';
        echo '<input type="submit" class="button button-primary" value="Generate MP3 Files" />';
        echo '</form>';
        echo '</div>';

        if (isset($_POST['xoa_generate_mp3']) && $_POST['xoa_generate_mp3'] == '1') {
            xoa_generate_mp3_files();
        }
    }

    function xoa_display_generated_mp3_page()
    {
        echo '<div class="wrap">';
        echo '<h1>Generated MP3 Files</h1>';
        xoa_display_generated_mp3_list();
        echo '</div>';
    }

    function xoa_add_admin_menu()
    {
        add_menu_page('Generate MP3 Podcast', 'Generate MP3 Podcast', 'manage_options', 'generate-mp3', [$this, 'xoa_generate_mp3_page']);
        add_submenu_page('generate-mp3', 'Generated MP3 List', 'Generated MP3 List', 'manage_options', 'generated-mp3-list', [$this, 'xoa_display_generated_mp3_page']);
    }

    function xoa_register_podcast_cpt()
    {
        $labels = array(
            'name'               => _x('Podcasts', 'post type general name', 'hello-elementor'),
            'singular_name'      => _x('Podcast', 'post type singular name', 'hello-elementor'),
            'menu_name'          => _x('Podcasts', 'admin menu', 'hello-elementor'),
            'name_admin_bar'     => _x('Podcast', 'add new on admin bar', 'hello-elementor'),
            'add_new'            => _x('Add New', 'podcast', 'hello-elementor'),
            'add_new_item'       => __('Add New Podcast', 'hello-elementor'),
            'new_item'           => __('New Podcast', 'hello-elementor'),
            'edit_item'          => __('Edit Podcast', 'hello-elementor'),
            'view_item'          => __('View Podcast', 'hello-elementor'),
            'all_items'          => __('All Podcasts', 'hello-elementor'),
            'search_items'       => __('Search Podcasts', 'hello-elementor'),
            'parent_item_colon'  => __('Parent Podcasts:', 'hello-elementor'),
            'not_found'          => __('No podcasts found.', 'hello-elementor'),
            'not_found_in_trash' => __('No podcasts found in Trash.', 'hello-elementor')
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array('slug' => 'podcast'),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments'),
            'taxonomies'         => array('category', 'post_tag'),
        );

        register_post_type('podcast', $args);
    }


    function xoa_add_podcast_meta_boxes()
    {
        add_meta_box(
            'podcast_audio_file',
            __('Podcast Audio File', 'hello-elementor'),
            [$this, 'xoa_podcast_audio_file_callback'],
            'podcast',
            'normal',
            'high'
        );

        add_meta_box(
            'podcast_image_file',
            __('Podcast Image File', 'hello-elementor'),
            [$this, 'xoa_podcast_image_file_callback'],
            'podcast',
            'normal',
            'high'
        );
    }

    function create_default_podcast_meta_fields($post_id)
    {
        // Tạo trường post meta '_podcast_audio_file' nếu chưa tồn tại
        $existing_audio_file = get_post_meta($post_id, '_podcast_audio_file', true);
        if (empty($existing_audio_file)) {
            add_post_meta($post_id, '_podcast_audio_file', '', true);
        }

        // Tạo trường post meta '_podcast_image_file' nếu chưa tồn tại
        $existing_image_file = get_post_meta($post_id, '_podcast_image_file', true);
        if (empty($existing_image_file)) {
            add_post_meta($post_id, '_podcast_image_file', '', true);
        }
    }
    function xoa_podcast_audio_file_callback($post)
    {
        wp_nonce_field('xoa_save_podcast_audio_file', 'xoa_podcast_audio_file_nonce');
        $value = get_post_meta($post->ID, '_podcast_audio_file', true);

        echo '<input type="file" id="podcast_audio_file" name="podcast_audio_file" />';
        if ($value) {
            echo '<p>Current file: <a href="' . esc_url($value) . '" target="_blank">' . esc_url($value) . '</a></p>';
        }
    }

    function xoa_podcast_image_file_callback($post)
    {
        wp_nonce_field('xoa_save_podcast_image_file', 'xoa_podcast_image_file_nonce');
        $value = get_post_meta($post->ID, '_podcast_image_file', true);
        echo '<input type="text" id="podcast_image_file" name="podcast_image_file" />';
        if ($value) {
            echo '<p>Current file: <a href="' . esc_url($value) . '" target="_blank">' . esc_url($value) . '</a></p>';
        }
    }

    function xoa_save_podcast_meta_boxes($post_id)
    {
        if (!isset($_POST['xoa_podcast_audio_file_nonce']) || !wp_verify_nonce($_POST['xoa_podcast_audio_file_nonce'], 'xoa_save_podcast_audio_file')) {
            return;
        }

        if (!isset($_POST['xoa_podcast_image_file_nonce']) || !wp_verify_nonce($_POST['xoa_podcast_image_file_nonce'], 'xoa_save_podcast_image_file')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (isset($_POST['post_type']) && $_POST['post_type'] === 'podcast') {
            if (!current_user_can('edit_post', $post_id)) {
                return;
            }
        }

        // Lưu trữ file audio
        if (isset($_FILES['podcast_audio_file']) && !empty($_FILES['podcast_audio_file']['name'])) {
            $upload = wp_handle_upload($_FILES['podcast_audio_file'], array('test_form' => false));
            if ($upload && !isset($upload['error'])) {
                update_post_meta($post_id, '_podcast_audio_file', $upload['url']);
            } else {
                error_log('Audio file upload error: ' . $upload['error']);
            }
        }

        // Lưu trữ file hình ảnh
        if (isset($_FILES['podcast_image_file']) && !empty($_FILES['podcast_image_file']['name'])) {
            $upload = wp_handle_upload($_FILES['podcast_image_file'], array('test_form' => false));
            if ($upload && !isset($upload['error'])) {
                update_post_meta($post_id, '_podcast_image_file', $upload['url']);
            } else {
                error_log('Image file upload error: ' . $upload['error']);
            }
        }
    }

    // handle code
    function handle_code()
    {
        add_action('transition_post_status', [$this, 'xoa_generate_mp3_on_publish_or_update'], 10, 3);
        add_action('admin_menu', [$this, 'xoa_add_admin_menu']);
        add_filter('the_content', [$this, 'xoa_add_play_button']);
        add_action('add_meta_boxes', [$this, 'xoa_add_podcast_meta_boxes']);
        add_action('publish_post', [$this, 'xoa_save_podcast_meta_boxes']);
        add_action('save_post', [$this, 'xoa_save_podcast_meta_boxes']);
        add_action('save_post', [$this, 'create_default_podcast_meta_fields']);
    }
}

require_once plugin_dir_path(__FILE__) . 'xoa_text_to_speech.php';
require_once plugin_dir_path(__FILE__) . 'xoa_generate_mp3_files.php';
require_once plugin_dir_path(__FILE__) . 'xoa_display_generated_mp3_list.php';
