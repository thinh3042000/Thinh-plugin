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
        add_action('wp_enqueue_scripts', [$this, 'frontend_css_podcast']);

        add_action('init', [$this, 'register_actions']);
    }

    public function register_actions()
    {
        $this->handle_code();
        $this->xoa_register_podcast_cpt();
    }
    public function frontend_css_podcast()
    {
        wp_register_style('podcast-css', plugins_url('assets/css/podcast.css', XOA_PODCAST_FILE), array(), null, 'all');
        wp_enqueue_style('podcast-css');
    }
    public function frontend_scripts_podcast()
    {
        wp_register_script('podcast.js', plugins_url('assets/js/podcast.js', XOA_PODCAST_FILE), array(), null, false);
        wp_register_script('podcast-list.js', plugins_url('assets/js/podcast-list.js', XOA_PODCAST_FILE), array(), null, false);
        wp_register_script('podcast-single.js', plugins_url('assets/js/podcast-single.js', XOA_PODCAST_FILE), array(), null, false);
        wp_register_script('podcast-slider.js', plugins_url('assets/js/podcast-slider.js', XOA_PODCAST_FILE), array(), null, false);

        add_action('wp_head', [$this, 'enqueue_frontend_scripts_podcast'], 5);
    }
    public function enqueue_frontend_scripts_podcast()
    {
        wp_enqueue_script('podcast.js');
        wp_enqueue_script('podcast-list.js');
        wp_enqueue_script('podcast-single.js');
        wp_enqueue_script('podcast-slider.js');
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
                    $check_api_choose = get_option('check_api_choose', '');

                    $audio_content = ($check_api_choose != 'google') ?
                        xoa_text_to_speech(strip_tags($post_content)) :
                        xoa_text_to_speech_google_api(strip_tags($post_content));
                        
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
        // echo '<div class="wrap">';
        // echo '<h1>Generate MP3 for Existing Posts...</h1>';
        // echo '<form method="post" action="">';
        // echo '<input type="hidden" name="xoa_generate_mp3" value="1" />';
        // echo '<input type="submit" class="button button-primary" value="Generate MP3 Files" />';
        // echo '</form>';
        // echo '</div>';

        // if (isset($_POST['xoa_generate_mp3']) && $_POST['xoa_generate_mp3'] == '1') {
        //     xoa_generate_mp3_files();
        // }
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
        add_submenu_page('generate-mp3', 'Settings', 'Settings', 'manage_options', 'xoa-mp3-settings', [$this, 'xoa_display_settings_page']);
    }

    function xoa_display_settings_page()
    {
        if (isset($_POST['xoa_save_settings'])) {
            update_option('xoa_api_key', sanitize_text_field($_POST['xoa_api_key']));
            update_option('xoa_api_key_google', sanitize_text_field($_POST['xoa_api_key_google']));
            update_option('xoa_voice_id', sanitize_text_field($_POST['xoa_voice_id']));
            update_option('check_api_choose', sanitize_text_field($_POST['check_api_choose']));
            echo '<div class="updated"><p>Settings saved.</p></div>';
        }

        $api_key = get_option('xoa_api_key', '');
        $voice_id = get_option('xoa_voice_id', '');
        $api_key_google = get_option('xoa_api_key_google', '');
        $check_api_choose = get_option('check_api_choose', '');

        $file_path = plugin_dir_path(__FILE__) . 'voice_data.php';
        if (file_exists($file_path)) {
            include $file_path;
        } else {
            $data = [];
        }

        $file_path = plugin_dir_path(__FILE__) . 'voice_select.php';
        if (file_exists($file_path)) {
            include $file_path;
        } else {
            $data_name = [];
        }

        echo '<div class="wrap">';
        echo '<h1>Settings</h1>';
        echo '<form method="post" action="">';
        echo '<table class="form-table">';

        // API Key input for ElevenLabs
        echo '<tr>';
        echo '<th scope="row"><label for="xoa_api_key">API Key ElevenLabs</label></th>';
        echo '<td><input type="text" id="xoa_api_key" name="xoa_api_key" value="' . esc_attr($api_key) . '" class="regular-text"></td>';
        echo '</tr>';

        // API Key input for Google
        echo '<tr>';
        echo '<th scope="row"><label for="xoa_api_key_google">API Key Google</label></th>';
        echo '<td><input type="text" id="xoa_api_key_google" name="xoa_api_key_google" value="' . esc_attr($api_key_google) . '" class="regular-text"></td>';
        echo '</tr>';

        // Radio buttons for selecting the API
        echo '<tr>';
        echo '<th scope="row">Choose API</th>';
        echo '<td>';
        echo '<label><input type="radio" name="check_api_choose" value="eleven" ' . checked('eleven', $check_api_choose, false) . '> ElevenLabs</label><br>';
        echo '<label><input type="radio" name="check_api_choose" value="google" ' . checked('google', $check_api_choose, false) . '> Google</label>';
        echo '</td>';
        echo '</tr>';

        // Voice ID select
        echo '<tr>';
        echo '<th scope="row"><label for="xoa_voice_id">Voice ElevenLabs</label></th>';
        echo '<td>';
        echo '<select id="xoa_voice_id" name="xoa_voice_id" class="regular-text">';
        foreach ($data_name as $voice) {
            $selected = $voice['voice_id'] === $voice_id ? 'selected="selected"' : '';
            echo '<option value="' . esc_attr($voice['voice_id']) . '" ' . $selected . '>' . esc_html($voice['name']) . '</option>';
        }
        echo '</select>';
        echo '</td>';
        echo '</tr>';

        echo '</table>';
        echo '<p class="submit"><input type="submit" name="xoa_save_settings" id="xoa_save_settings" class="button button-primary" value="Save Changes"></p>';
        echo '</form>';

        echo '<h2>Voice Data</h2>';
        echo '<table class="widefat fixed" style="width:90%; margin: 0 auto;">';
        echo '<thead><tr>';
        echo '<th style="width:40px; font-size: 16px; font-weight: bold">#</th>';
        echo '<th style="font-size: 16px;font-weight: bold;">Name</th>';
        echo '<th style="font-size: 16px;font-weight: bold;">Voice ID</th>';
        echo '<th style="font-size: 16px;font-weight: bold;">Gender</th>';
        echo '<th style="font-size: 16px;font-weight: bold;">Age</th>';
        echo '<th style="font-size: 16px;font-weight: bold;">Accent</th>';
        echo '<th style="font-size: 16px;font-weight: bold;">Description</th>';
        echo '<th style="font-size: 16px;font-weight: bold;">Use Case</th>';
        echo '<th style="font-size: 16px;font-weight: bold;">Audio Test</th>';
        echo '</tr></thead>';
        echo '<tbody>';

        $count = 1;
        foreach ($data as $item) {
            $audio_url = plugins_url('../assets/audio/' . esc_attr($item['preview_url']), __FILE__);

            echo '<tr>';
            echo '<td class="sst-voice">' . esc_html($count) . '.</td>';
            echo '<td>' . esc_html($item['name']) . '</td>';
            echo '<td>' . esc_html($item['voice_id']) . '</td>';
            echo '<td>' . esc_html($item['gender']) . '</td>';
            echo '<td>' . esc_html($item['age']) . '</td>';
            echo '<td>' . esc_html($item['accent']) . '</td>';
            echo '<td>' . esc_html($item['description']) . '</td>';
            echo '<td>' . esc_html($item['use_case']) . '</td>';
            echo '<td><audio controls><source src="' . esc_url($audio_url) . '" type="audio/mp3"></audio></td>';
            echo '</tr>';
            $count++;
        }

        echo '</tbody>';
        echo '</table>';
        echo '</div>';
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
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments'),
            'taxonomies'         => array('podcast_category'),
        );

        register_post_type('podcast', $args);

        register_taxonomy('podcast_category', 'podcast', array(
            'labels' => array(
                'name' => __('Podcast Categories', 'hello-elementor'),
                'singular_name' => __('Podcast Category', 'hello-elementor'),
                'search_items' => __('Search Categories', 'hello-elementor'),
                'all_items' => __('All Categories', 'hello-elementor'),
                'parent_item' => __('Parent Category', 'hello-elementor'),
                'parent_item_colon' => __('Parent Category:', 'hello-elementor'),
                'edit_item' => __('Edit Category', 'hello-elementor'),
                'update_item' => __('Update Category', 'hello-elementor'),
                'add_new_item' => __('Add New Category', 'hello-elementor'),
                'new_item_name' => __('New Category Name', 'hello-elementor'),
                'menu_name' => __('Categories', 'hello-elementor'),
            ),
            'hierarchical' => true,
            'rewrite' => array('slug' => 'podcast-category'),
            'show_admin_column' => true,
            'show_ui' => true,
            'query_var' => true,
        ));
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
            'podcast_from_blog',
            __('Choose Podcast From Blog Posts', 'hello-elementor'),
            [$this, 'xoa_podcast_from_blogs_callback'],
            'podcast',
            'normal',
            'high'
        );
    }

    function create_default_podcast_meta_fields($post_id)
    {
        $existing_audio_file = get_post_meta($post_id, '_podcast_audio_file', true);
        if (empty($existing_audio_file)) {
            add_post_meta($post_id, '_podcast_audio_file', '', true);
        }

        $existing_audio_file = get_post_meta($post_id, '_podcast_from_blog', true);
        if (empty($existing_audio_file)) {
            add_post_meta($post_id, '_podcast_from_blog', '', true);
        }
    }


    function xoa_podcast_audio_file_callback($post)
    {
        wp_nonce_field('xoa_save_podcast_audio_file', 'xoa_podcast_audio_file_nonce');
        $value = get_post_meta($post->ID, '_podcast_audio_file', true);
        echo '<input type="file" id="podcast_audio_file" name="podcast_audio_file" />';
        if ($value) {
            echo '<div><audio controls>
            <source src="' . esc_url($value) . '" type="audio/mpeg">
            Your browser does not support the audio element.
          </audio>
          </div>';
            echo '<div><p>File hiện tại: <a href="' . esc_url($value) . '" target="_blank">' . esc_url($value) . '</a></p> </div>';
            echo '<input type="checkbox" id="delete_podcast_audio_file" name="delete_podcast_audio_file" value="1" /> Delete File';
        }
    }
    function xoa_podcast_from_blogs_callback($post)
    {

        wp_nonce_field('xoa_save_podcast_from_blogs', 'xoa_podcast_from_blogs_nonce');

        $selected_post_id = get_post_meta($post->ID, '_podcast_from_blog', true);
        $audio_file_url = esc_url($selected_post_id);

        echo '<input type="hidden" id="current_post_id" value="' . esc_attr($post->ID) . '">';
        echo '<input type="hidden" id="podcast_from_blog_id" name="podcast_from_blog_id" value="' . esc_attr($selected_post_id) . '" />';

        if ($selected_post_id) {
            echo '<div id="audio_player_container" style="padding: 10px; background: #f0f0f0; border: 1px solid #ccc; color: #333;">';
            echo '<p>Audio File:</p>';
            echo '<audio controls>';
            echo '<source src="' . $audio_file_url . '" type="audio/mpeg">';
            echo 'Your browser does not support the audio element.';
            echo '</audio>';
            echo '<button type="button" id="delete_audio_file" class="button button-secondary" style="margin-top: 10px;">' . esc_html__('Delete Audio File', 'hello-elementor') . '</button>';
            echo '<input type="hidden" id="xoa_delete_audio_file_nonce" value="' . wp_create_nonce('xoa_delete_audio_file_nonce') . '">';
            echo '</div>';
        } else {
            echo '<button type="button" id="show_blog_search" class="button button-primary">' . esc_html__('Choose from Blogs', 'hello-elementor') . '</button>';

            echo '<div id="blog_search_container" style="display: none;">';
            echo '<button type="button" id="back_to_initial" class="button button-secondary" style="margin-bottom: 10px;">' . esc_html__('Back', 'hello-elementor') . '</button>';
            echo '<input style="width: 100%; margin-bottom: 10px;" type="text" id="podcast_blog_search" placeholder="' . esc_attr__('Search from Blogs...', 'hello-elementor') . '" />';
            echo '<div id="podcast_blog_results"></div>';
            echo '</div>';
        }

        echo '<div id="status_message" style="display: none; padding: 10px; background: #f0f0f0; border: 1px solid #ccc; color: #333;"></div>';

        $data_name = [];

        $file_path = plugin_dir_path(__FILE__) . 'voice_select.php';
        if (file_exists($file_path)) {
            include $file_path;
        }

        $check_api_choose = get_option('check_api_choose', '');

        echo '<div id="select_voice_container" style="display: none;">';
        echo '<button type="button" id="back_to_search" class="button button-secondary">' . esc_html__('Back', 'hello-elementor') . '</button>';
        echo '<p id="voice_selection_message"></p>';

        if ($check_api_choose !== 'google') {
            $select_options = '<span>Voice: </span><select style="width:40%" id="voice_select" name="voice_select">';
            foreach ($data_name as $voice) {
                $select_options .= '<option value="' . esc_attr($voice['voice_id']) . '">' . esc_html($voice['name']) . '</option>';
            }
            $select_options .= '</select>';
            echo $select_options;
        } else {
            echo '<input type="hidden" id="voice_select" name="voice_select" value="" />';
        }


        echo '<button type="button" id="xoa_save_settings_sample" class="button button-primary">Audio Test</button>';
        echo '</div>';

        echo '
            <script>
    document.addEventListener("DOMContentLoaded", function() {
    var showBlogSearchButton = document.getElementById("show_blog_search");
    var blogSearchContainer = document.getElementById("blog_search_container");
    var selectVoiceContainer = document.getElementById("select_voice_container");
    var searchInput = document.getElementById("podcast_blog_search");
    var resultsContainer = document.getElementById("podcast_blog_results");
    var hiddenField = document.getElementById("podcast_from_blog_id");
    var backToSearchButton = document.getElementById("back_to_search");
    var backToInitialButton = document.getElementById("back_to_initial");
    var audioTestButton = document.getElementById("xoa_save_settings_sample");
    var currentPostId = document.getElementById("current_post_id").value;
    var statusMessage = document.getElementById("status_message");
    var deleteAudioButton = document.getElementById("delete_audio_file");

    if (showBlogSearchButton && blogSearchContainer) {
        showBlogSearchButton.addEventListener("click", function(event) {
            event.preventDefault();
            if (blogSearchContainer.style.display === "none" || blogSearchContainer.style.display === "") {
                blogSearchContainer.style.display = "block";
                showBlogSearchButton.style.display = "none";
            } else {
                blogSearchContainer.style.display = "none";
            }
        });
    }

    if (searchInput) {
        searchInput.addEventListener("keyup", function() {
            var searchQuery = searchInput.value;

            if (searchQuery.length > 2) {
                fetch(ajaxurl, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: new URLSearchParams({
                        action: "xoa_search_blog_posts",
                        search_query: searchQuery
                    })
                })
                .then(response => response.text())
                .then(data => {
                    resultsContainer.innerHTML = data;
                })
                .catch(error => console.error("Error:", error));
            } else {
                resultsContainer.innerHTML = "";
            }
        });
    }


    if (deleteAudioButton) {
        deleteAudioButton.addEventListener("click", function() {
            var nonce = document.getElementById("xoa_delete_audio_file_nonce").value;
                fetch(ajaxurl, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: new URLSearchParams({
                        action: "xoa_delete_audio_file",
                        post_id: currentPostId,
                        nonce: nonce
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert("Failed to delete audio file. Please try again.");
                    }
                })
                .catch(error => console.error("Error:", error));
        });
    }

    if (resultsContainer) {
        resultsContainer.addEventListener("click", function(event) {
            if (event.target && event.target.matches(".select-post")) {
                event.preventDefault();
                var postId = event.target.getAttribute("data-id");
                var postTitle = event.target.getAttribute("data-title");
                var postExcerpt = event.target.getAttribute("data-excerpt");

                if (hiddenField) {
                    hiddenField.value = postId;
                }
                if (blogSearchContainer) {
                    blogSearchContainer.style.display = "none";
                }
                if (selectVoiceContainer) {
                    selectVoiceContainer.style.display = "block";
                }

                document.getElementById("voice_selection_message").innerHTML = "<p>" + postTitle + "</p><p>" + postExcerpt + "</p><p>" + postId + "</p>";
            }
        });
    }

    if (backToSearchButton && selectVoiceContainer) {
        backToSearchButton.addEventListener("click", function(event) {
            event.preventDefault();
            if (selectVoiceContainer) {
                selectVoiceContainer.style.display = "none";
            }
            if (blogSearchContainer) {
                blogSearchContainer.style.display = "block";
            }
            document.getElementById("voice_selection_message").innerHTML = "";
        });
    }

    if (backToInitialButton && blogSearchContainer) {
        backToInitialButton.addEventListener("click", function(event) {
            event.preventDefault();
            if (blogSearchContainer) {
                blogSearchContainer.style.display = "none";
            }
            if (showBlogSearchButton) {
                showBlogSearchButton.style.display = "block";
            }
        });
    }

    if (audioTestButton) {
        audioTestButton.addEventListener("click", function(event) {
            var selectedVoice = document.getElementById("voice_select").value;
            var postExcerpt = document.querySelector("#voice_selection_message p:nth-child(2)").textContent;
            var Title = document.querySelector("#voice_selection_message p:nth-child(1)").textContent;
            var PostID = document.querySelector("#voice_selection_message p:nth-child(3)").textContent;

            fetch(ajaxurl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: new URLSearchParams({
                    action: "xoa_save_voice_and_excerpt",
                    voice_id: selectedVoice,
                    excerpt: postExcerpt,
                    title: Title,
                    nonce: "' . wp_create_nonce('xoa_save_voice_and_excerpt') . '"
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the interface to show the audio player and confirmation buttons
                    var audioContainer = document.createElement("div");
                    audioContainer.id = "audio_test_container";
                    audioContainer.innerHTML = `
                        <p>Audio Test:</p>
                        <audio controls>
                            <source src="${data.data.audio_url}" type="audio/mpeg">
                            Your browser does not support the audio element.
                        </audio>
                        <button type="button" id="confirm_audio_test" class="button button-primary">Confirm</button>
                        <button type="button" id="reject_audio_test" class="button button-secondary">Reject</button>
                    `;
                    
                    if (selectVoiceContainer) {
                        selectVoiceContainer.innerHTML = "";
                        selectVoiceContainer.appendChild(audioContainer);
                    }

                    document.getElementById("confirm_audio_test").addEventListener("click", function() {
         statusMessage.style.display = "block";
        statusMessage.textContent = "Processing... Please wait.";    fetch(ajaxurl, {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: new URLSearchParams({
            action: "xoa_save_voice_check_file_mp3_podcast",
            title: Title,
            post_id: PostID,
            currentPostId: currentPostId,
            nonce: "' . wp_create_nonce('xoa_save_voice_check_file_mp3_podcast') . '"
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload the page
            location.reload();
        } else {
            alert("Failed to process MP3 file. Please try again.");
        }
    })
    .catch(error => console.error("Error:", error));
});

                    document.getElementById("reject_audio_test").addEventListener("click", function() {
                         location.reload(); 
                    });

                } else {
                    alert("Failed to save. Please try again.");
                }
            })
            .catch(error => console.error("Error:", error));
        });
    }
});
</script>
        ';
    }

    function xoa_search_blog_posts()
    {
        global $wpdb;

        $search_query = sanitize_text_field($_POST['search_query']);

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT ID, post_title, post_content FROM $wpdb->posts WHERE post_type = 'post' AND post_status = 'publish' AND post_title LIKE %s",
            '%' . $wpdb->esc_like($search_query) . '%'
        ));

        if ($results) {
            foreach ($results as $post) {
                $excerpt = wp_trim_words($post->post_content, 40);
                echo '<div class="search-result">';
                echo '<button style="border: 1px solid #00AF5A; border-radius: 5px; padding: 3px 10px; font-weight: 600" class="select-post" data-id="' . esc_attr($post->ID) . '" data-title="' . esc_attr($post->post_title) . '" data-excerpt="' . esc_attr($excerpt) . '">' . esc_html($post->post_title) . '</button>';
                echo '<p>' . esc_html($excerpt) . '</p>';
                echo '</div>';
            }
        } else {
            echo '<p>' . esc_html__('No posts found', 'hello-elementor') . '</p>';
        }

        wp_die();
    }

    function xoa_save_voice_and_excerpt()
    {
        check_ajax_referer('xoa_save_voice_and_excerpt', 'nonce');

        $voice_id = sanitize_text_field($_POST['voice_id']);
        $excerpt = sanitize_text_field($_POST['excerpt']);
        $title = sanitize_text_field($_POST['title']);
        update_option('xoa_voice_id_sample', $voice_id);

        $check_api_choose = get_option('check_api_choose', '');

        $audio_content = ($check_api_choose != 'google') ?
            xoa_text_to_speech_sample(strip_tags($excerpt)) :
            xoa_text_to_speech_google_api(strip_tags($excerpt));

        if ($audio_content !== false) {
            $upload_dir = wp_upload_dir();
            $audio_dir = $upload_dir['basedir'] . '/excerpt/';

            if (!file_exists($audio_dir)) {
                wp_mkdir_p($audio_dir);
            }

            $post_title = sanitize_title($title);
            $audio_file = $audio_dir . $post_title . '.mp3';

            if (file_exists($audio_file)) {
                unlink($audio_file);
            }

            file_put_contents($audio_file, $audio_content);

            wp_send_json_success(array('audio_url' => $upload_dir['baseurl'] . '/excerpt/' . $post_title . '.mp3'));
        } else {
            wp_send_json_error();
        }
    }

    function xoa_save_voice_check_file_mp3_podcast()
    {
        check_ajax_referer('xoa_save_voice_check_file_mp3_podcast', 'nonce');

        $title = sanitize_title(sanitize_text_field($_POST['title']));
        $post_id = sanitize_text_field($_POST['post_id']);
        $currentPostId = sanitize_text_field($_POST['currentPostId']);


        $upload_dir = wp_upload_dir();
        $audio_dir = $upload_dir['basedir'] . '/podcast/';
        $audio_file = $audio_dir . $title . '.mp3';
        $audio_url = $upload_dir['baseurl'] . '/podcast/' . $title . '.mp3';

        $audio_dir_ex = $upload_dir['basedir'] . '/excerpt/';
        $files = glob($audio_dir_ex . '*.mp3');

        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }

        $content = get_post_field('post_content', $post_id);

        $check_api_choose = get_option('check_api_choose', '');

        $audio_content = ($check_api_choose != 'google') ?
            xoa_text_to_speech_sample(strip_tags($content)) :
            xoa_text_to_speech_google_api(strip_tags($content));

        if ($audio_content !== false) {
            if (!file_exists($audio_dir)) {
                wp_mkdir_p($audio_dir);
            }
            file_put_contents($audio_file, $audio_content);
            update_post_meta($currentPostId, '_podcast_from_blog', $audio_url);
            update_post_meta($currentPostId, '_podcast_audio_file', '');
            wp_send_json_success(array('audio_url' => $upload_dir['baseurl'] . '/podcast/' . $title . '.mp3'));
        } else {
            wp_send_json_error();
        }
    }

    function xoa_delete_audio_file_callback()
    {
        check_ajax_referer('xoa_delete_audio_file_nonce', 'nonce');

        if (isset($_POST['post_id'])) {
            $post_id = intval($_POST['post_id']);

            $file_url = get_post_meta($post_id, '_podcast_from_blog', true);

            $file_path = str_replace(home_url('/'), ABSPATH, $file_url);

            if (file_exists($file_path)) {
                unlink($file_path);
                delete_post_meta($post_id, '_podcast_from_blog');
            }
            wp_send_json_success();
        } else {
            wp_send_json_error('No post ID provided.');
        }
    }

    function xoa_add_enctype_attribute($post)
    {
        echo ' enctype="multipart/form-data"';
    }

    function xoa_save_podcast_meta_boxes($post_id)
    {
        if (!isset($_POST['xoa_podcast_audio_file_nonce']) || !wp_verify_nonce($_POST['xoa_podcast_audio_file_nonce'], 'xoa_save_podcast_audio_file')) {
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

        if (isset($_POST['delete_podcast_audio_file']) && $_POST['delete_podcast_audio_file'] == '1') {
            $old_file_url = get_post_meta($post_id, '_podcast_audio_file', true);
            if ($old_file_url) {
                $upload_dir = wp_upload_dir();
                $old_file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $old_file_url);
                if (file_exists($old_file_path)) {
                    unlink($old_file_path);
                }
            }
            delete_post_meta($post_id, '_podcast_audio_file');
        } elseif (isset($_FILES['podcast_audio_file']) && !empty($_FILES['podcast_audio_file']['name'])) {
            $old_file_url = get_post_meta($post_id, '_podcast_audio_file', true);
            if ($old_file_url) {
                $upload_dir = wp_upload_dir();
                $old_file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $old_file_url);
                if (file_exists($old_file_path)) {
                    unlink($old_file_path);
                }
            }
            $uploaded_audio = wp_handle_upload($_FILES['podcast_audio_file'], ['test_form' => false]);
            if (!isset($uploaded_audio['error'])) {
                update_post_meta($post_id, '_podcast_from_blog', '');
                update_post_meta($post_id, '_podcast_audio_file', $uploaded_audio['url']);
            }
        }
    }
    //content page podcast
    function latest_podcast_info_shortcode()
    {
        $args = array(
            'post_type' => 'podcast',
            'posts_per_page' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_podcast_audio_file',
                    'compare' => 'EXISTS'
                ),
                array(
                    'key' => '_podcast_from_blog',
                    'compare' => 'EXISTS'
                )
            )
        );
        $latest_podcast = new \WP_Query($args);
        ob_start();

        if ($latest_podcast->have_posts()) {
            while ($latest_podcast->have_posts()) {
                $latest_podcast->the_post();

                $podcast_audio_link = get_post_meta(get_the_ID(), '_podcast_audio_file', true);
                if (empty($podcast_audio_link)) {
                    $podcast_audio_link = get_post_meta(get_the_ID(), '_podcast_from_blog', true);
                }
?>
                <div class="podcast-info">
                    <div class="podcast-thumbnail">
                        <?php if (has_post_thumbnail()) { ?>
                            <div class="img-podcast">
                                <a href="<?php echo get_permalink(); ?> ">
                                    <?php the_post_thumbnail('full'); ?>
                                </a>
                                <style>
                                    @media (min-width: 1024px) {
                                        .img-podcast img {
                                            width: 704px;
                                            height: 778px;
                                        }
                                    }

                                    @media (max-width: 500px) {
                                        .podcast-thumbnail {
                                            margin: 0 auto;
                                            width: 100%;
                                        }
                                    }
                                </style>
                            </div>
                        <?php } else {
                            echo '<a href="' . get_permalink() . '"><img  src="' . plugins_url('../assets/img/no-image.png', __FILE__) . '" width="704" height="778" alt="" class="play-icon" /></a>';
                        } ?>
                    </div>
                    <div class="podcast-details">
                        <div class="podcast-author-date" style="display:flex; justify-content:space-between">
                            <span class="author_podcast" style="color: #666666; text-transform: capitalize;">By <?php echo get_the_author(); ?></span>
                            <span class="date_podcast" style="color: #666666;"><?php echo get_the_date(); ?></span>
                        </div>
                        <div class="podcast-title">
                            <a class="podcast-title" style="color: #272727" href="<?php echo get_permalink(); ?>">
                                <?php the_title(); ?>
                            </a>
                        </div>
                        <div class="podcast-excerpt" style="color: #666666; margin-top: 10px;">
                            <?php echo wp_trim_words(get_the_excerpt(), 30, '...'); ?>
                        </div>

                        <div class="podcast-share-icons" style="margin-top: 15px;">
                            <span style="margin-right:5px">Share on:</span>
                            <a href="https://www.facebook.com/" target="_blank"><img src="<?php echo plugins_url('../assets/img/1.svg', __FILE__); ?>" alt="" width="25px">
                            </a>
                            <a class="fb-icon-podcast" style="padding:0px 5px" href="https://x.com/" target="_blank"><img src="<?php echo plugins_url('../assets/img/2.svg', __FILE__); ?>" alt="" width="25px"></a>
                            <a href="https://www.linkedin.com/" target="_blank"><img src="<?php echo plugins_url('../assets/img/3.svg', __FILE__); ?>" alt="" width="25px"></a>
                        </div>

                        <?php if ($podcast_audio_link) { ?>
                            <style>
                                .podcast-details {
                                    display: flex;
                                    flex-direction: column;
                                    justify-content: center;
                                    width: 700px;
                                    padding: 30px;
                                }

                                .podcast-title {
                                    font-size: 60px;
                                    font-weight: bold;
                                    letter-spacing: -1px;
                                    line-height: 1.2;
                                }

                                .podcast-info {
                                    display: flex;
                                }

                                .podcast-share-icons {
                                    display: flex;
                                }

                                #progress {
                                    -webkit-appearance: none;
                                    width: 100%;
                                    height: 5px;
                                    border-radius: 4px;
                                    cursor: pointer;
                                    background: linear-gradient(to right, #00AF5A 0%, #00AF5A 50%, #CCCCCC 50%, #CCCCCC 100%);
                                }

                                #progress::-webkit-slider-thumb {
                                    -webkit-appearance: none;
                                    background: #00AF5A;
                                    width: 20px;
                                    height: 20px;
                                    border-radius: 50%;
                                    border: 4px solid white;
                                    box-shadow: 0 5px 5px rgba(0, 175, 90, 0.4);
                                }

                                .progress {
                                    -webkit-appearance: none;
                                    width: 100%;
                                    height: 5px;
                                    border-radius: 4px;
                                    cursor: pointer;
                                    background: linear-gradient(to right, #00AF5A 0%, #00AF5A 50%, #CCCCCC 50%, #CCCCCC 100%);
                                }

                                .progress::-webkit-slider-thumb {
                                    -webkit-appearance: none;
                                    background: #00AF5A;
                                    width: 20px;
                                    height: 20px;
                                    border-radius: 50%;
                                    border: 4px solid white;
                                    box-shadow: 0 5px 5px rgba(0, 175, 90, 0.4);
                                }

                                .controls-podcast {
                                    cursor: pointer;
                                    display: flex;
                                    justify-content: space-between;
                                    align-items: center;
                                }

                                span#stop-button {
                                    padding: 0px 15px;
                                }

                                .controls-main {
                                    display: flex;
                                    justify-content: center;
                                    align-items: center;
                                }

                                time-display {
                                    margin-top: 10px;
                                }

                                .button-container {
                                    cursor: pointer;
                                    transition: background-color 0.3s ease;
                                }

                                .button-container.active {
                                    background-color: #00AF5A;
                                    border-radius: 50%;
                                }

                                @media (max-width: 500px) {
                                    .podcast-info {
                                        display: block;
                                    }

                                    .podcast-title {
                                        font-size: 32px;
                                        margin: 2px 0px;
                                        text-align: center;
                                    }

                                    .podcast-excerpt {
                                        text-align: center;
                                    }

                                    .podcast-details {
                                        display: flex;
                                        flex-direction: column;
                                        justify-content: center;
                                        width: 100%;
                                        padding: 30px;
                                    }

                                }
                            </style>
                            <div class="podcast-audio" style="margin-top: 20px;">
                                <audio id="podcast-audio-player" style="width: 100%;">
                                    <source src="<?php echo esc_url($podcast_audio_link); ?>" type="audio/mpeg">
                                </audio>
                                <input type="range" value="0" id="progress">
                                <div class="time-display" style="display: flex; justify-content: space-between;">
                                    <span id="current-time">0:00</span>
                                    <span id="total-time">0:00</span>
                                </div>
                                <div class="controls-podcast" style="margin-top: 10px;">
                                    <span id="reset-button">
                                        <img src="<?php echo plugins_url('../assets/img/repeatBtn.svg', __FILE__); ?>" alt="Reset" width="30px">
                                    </span>
                                    <div class="controls-main">
                                        <span id="rewind-button">
                                            <img src="<?php echo plugins_url('../assets/img/prevBtn.svg', __FILE__); ?>" alt="Rewind" width="35px">
                                        </span>
                                        <span onclick="playPause()" id="stop-button">
                                            <img id="play-img" src="<?php echo plugins_url('../assets/img/pause-icon.svg', __FILE__); ?>" alt="Play" width="70px">
                                            <img id="pause-img" src="<?php echo plugins_url('../assets/img/play-icon-1.svg', __FILE__); ?>" alt="Pause" width="70px" style="display: none;">
                                        </span>
                                        <span id="forward-button">
                                            <img src="<?php echo plugins_url('../assets/img/nextBtn.svg', __FILE__); ?>" alt="Forward" width="35px">
                                        </span>
                                    </div>
                                    <span id="love-button">
                                        <img class="love-button" src="<?php echo plugins_url('../assets/img/love.svg', __FILE__); ?>" alt="Forward" width="30px">
                                    </span>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            <?php
            }
            wp_reset_postdata();
        } else {
            echo 'Không có bài viết nào.';
        }

        return ob_get_clean();
    }
    //render_podcast
    function render_podcast_shortcode()
    {
        $args = array(
            'post_type' => 'podcast',
            'posts_per_page' => 9,
            'offset' => 1,
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_podcast_audio_file',
                    'compare' => 'EXISTS'
                ),
                array(
                    'key' => '_podcast_from_blog',
                    'compare' => 'EXISTS'
                )
            )
        );
        $query =  new \WP_Query($args);

        if ($query->have_posts()) {
            $output = '<div class="podcast-grid">';
            $i = 1;
            $count = 0;

            while ($query->have_posts()) {
                $query->the_post();

                if ($count % 3 == 0) {
                    if ($count > 0) {
                        $output .= '</div>';
                    }
                    $output .= '<div class="podcast-row">';
                }
                $output .= '<div class="podcast-item" data-id="podcast-item-' . $i . '">';
                $output .= '<div class="podcast-content-list">';
                if (has_post_thumbnail()) {
                    $output .= '<div class="podcast-thumbnail-list"><a href="' . get_permalink() . '">' . get_the_post_thumbnail(get_the_ID(), 'medium') . '</a></div>';
                } else {
                    $output .= '<div class="podcast-thumbnail-list">';
                    $output .= '<a href="' . get_permalink() . '">';
                    $output .= '<img src="' . plugins_url('../assets/img/no-image.png', __FILE__) . '" alt="No Image" width="100">';
                    $output .= '</a>';
                    $output .= '</div>';
                }
                $output .= '<h2 class="podcast-title-list"><a class="link-title-podcast" href="' . get_permalink() . '">' . get_the_title() . '</a>';
                $output .= '<div class="author-container">';
                $output .= '<span class="author_podcast" style="color: #666666; text-transform: capitalize;">' . get_the_author() . '</span>';
                $output .= '<span class="stop-button" id="stop-button-' . $i . '">';
                $output .= '<img id="play-icon-' . $i . '" src="' . plugins_url('../assets/img/Frame.svg', __FILE__) . '" width="40" alt="" class="play-icon" />';
                $output .= '<img style="display:none;" id="play-icon-run-' . $i . '" src="' . plugins_url('../assets/img/play-icon-list.svg', __FILE__) . '" width="40" alt="" class="play-icon" />';
                $output .= '</span>';
                $output .= '</div>';
                $output .= '<div class="podcast-audio-controls-list">';
                $output .= '<input type="range" value="0" class="progress" id="progress-' . $i . '" data-id="progress-' . get_the_ID() . '">';
                $output .= '<div class="time-display-list" style="display: flex; justify-content: space-between;">';
                $output .= '<span id="current-time-' . $i . '" data-id="current-time-' . $i . '">0:00</span>';
                $output .= '<span id="total-time-' . $i . '" data-id="total-time-' . $i . '">0:00</span>';
                $output .= '</div>';
                $output .= '</div>';
                $output .= '</h2>';

                $audio_file = get_post_meta(get_the_ID(), '_podcast_audio_file', true);
                if (empty($audio_file)) {
                    $audio_file = get_post_meta(get_the_ID(), '_podcast_from_blog', true);
                }
                if ($audio_file) {
                    $output .= '<div>';
                    $output .= '<audio id="podcast-audio-list-controls-' . $i . '" data-id="podcast-audio-list-controls-' . get_the_ID() . '">';
                    $output .= '<source src="' . esc_url($audio_file) . '" type="audio/mpeg">';
                    $output .= '</audio>';
                    $output .= '</div>';
                }

                $output .= '</div>';
                $output .= '</div>';

                $count++;
                $i++;
            }

            $output .= '</div>';
            $output .= '</div>
<style>
	.author-container {
		display: flex;
		align-items: center;
		justify-content: space-between;
	}

	.podcast-thumbnail-list img {
		width: 100px;
		height: 100px;
		border-radius: 10px;
	}

	.podcast-content-list {
		border-radius: 10px;
		width: 400px;
		height: 139px;
		display: flex;
		background: #F2F2F2;
		align-items: center;
	}

	.podcast-grid {
		display: flex;
		flex-wrap: wrap;
		gap: 20px;
	}

	.podcast-row {
		display: flex;
		justify-content: space-between;
		width: 100%;
	}

	.podcast-item {
		flex: 1 1 calc(33.33% - 20px);
		box-sizing: border-box;
		margin-bottom: 20px;
	}

	h2.podcast-title-list {
		font-size: 16px;
		width: 245px;
	}

	.podcast-title-list {
		margin: 0px 20px;
	}

	.podcast-thumbnail-list {
		margin-left: 20px;
	}

	span.stop-button {
		display: flex;
		justify-content: center;
		align-items: center;
	}

	.link-title-podcast {
		color: #252525;
		margin-right: -70px;
	}

	.time-display-list span {
		color: #4D4D4D;
		margin-top: 5px;
		font-weight: 400;
	}
	@media (max-width: 1250px) {
		.podcast-item {
			flex: 1 1 calc(50% - 20px);
		}
    .podcast-content-list {
    border-radius: 10px;
    width: 325px;
    height: 139px;
    display: flex;
    background: #F2F2F2;
    align-items: center;
}
    }
    @media (max-width: 768px) {
		.podcast-item {
			flex: 1 1 100%;
		}

		.podcast-row {
			flex-direction: column;
			margin-bottom: -18px;
		}
        .podcast-content-list {
    border-radius: 10px;
    width: 300px;
    height: 139px;
    display: flex;
    background: #F2F2F2;
    align-items: center;
	}
	@media (max-width: 500px) {
		.podcast-content-list {
			border-radius: 10px;
			width: 90%;
			height: 139px;
			display: flex;
			background: #F2F2F2;
			align-items: center;
			justify-content: center;
			margin: 0 auto;
		}
	}
</style>
            ';
            wp_reset_postdata();

            return $output;
        } else {
            return '<p>No podcasts found.</p>';
        }
    }

    // end content page podcast

    //single podcast

    function latest_podcast_shortcode()
    {
        $current_post_id = get_the_ID();
        $args = array(
            'post_type' => 'podcast',
            'p'         => $current_post_id,
        );

        $query = new \WP_Query($args);

        if ($query->have_posts()) {
            $query->the_post();
            $thumbnail_url = get_the_post_thumbnail_url(get_the_ID(), 'full');

            $podcast_audio_link = get_post_meta(get_the_ID(), '_podcast_audio_file', true);
            if (empty($podcast_audio_link)) {
                $podcast_audio_link = get_post_meta(get_the_ID(), '_podcast_from_blog', true);
            }
            ob_start();
            ?>

            <style>
                .podcast-hero {
                    background: linear-gradient(to top, rgb(0 0 0) 25%, rgb(0 0 0 / 94%) 30%, rgb(0 0 0 / 77%) 38%, rgb(0 0 0 / 35%) 53%, rgb(0 0 0 / 30%) 70%,
                            rgb(0 0 0 / 10%) 85%),
                        url('<?php echo $thumbnail_url; ?>') no-repeat center center;
                    background-size: cover;
                    height: 778px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: #fff;
                    text-align: center;
                    padding: 20px;
                }

                .podcast-info-single {
                    padding: 20px;
                    border-radius: 10px;
                    width: 800px;
                    margin-top: 210px;
                }

                .title-single-podcast {
                    font-size: 60px;
                    color: white;
                    font-weight: bold;
                }

                .excerpt-single-podcast {
                    font-size: 16px;
                    color: white;
                }

                .single-podcast-content {
                    width: 800px;
                    height: 232px;
                    display: flex;
                    background: #00AF5A;
                    border-radius: 12px;
                    align-items: center;
                    margin-top: 30px;
                }

                .single-podcast-image img {
                    width: 200px;
                    height: 200px;
                    border-radius: 12px;
                    margin-top: 5px;
                }

                .single-podcast-image {
                    width: 40%;
                }

                .single-podcast-info {
                    width: 90%;
                }

                .podcast-author-date-single {
                    display: flex;
                    justify-content: space-between;
                    width: 95%;
                    margin: 12px 0px;
                }

                .podcast-title-child {
                    font-size: 20px;
                    font-weight: bold;
                    display: flex;
                    justify-content: start;
                }

                #progress-single {
                    -webkit-appearance: none;
                    width: 100%;
                    height: 5px;
                    border-radius: 4px;
                    cursor: pointer;
                    background: linear-gradient(to right, while 0%, while 50%, #CCCCCC 50%, #CCCCCC 100%);
                    margin-top: 10px;
                }

                #progress-single::-webkit-slider-thumb {
                    -webkit-appearance: none;
                    background: #abe2c7;
                    width: 20px;
                    height: 20px;
                    border-radius: 50%;
                    border: 4px solid white;
                    box-shadow: 0 5px 5px rgba(0, 175, 90, 0.4);

                }

                span#rewind-button-single {
                    margin-bottom: -2px;
                }

                .controls-main-single {
                    gap: 5px;
                    display: flex;
                    align-items: end;
                }

                .time-display-single {
                    width: 80%;
                }

                .podcast-audio-single {
                    padding: 0px 25px 0px 0px;
                }

                span#current-time-single {
                    padding: 0px 5px;
                    font-weight: 300;
                }

                span#total-time-single {
                    padding: 0px 5px;
                    font-weight: 300;
                }

                span#stop-button-single {
                    display: flex;

                    margin-bottom: -10px;
                }

                @media (max-width: 860px) {
                    .single-podcast-content {
                        width: 600px;
                        margin: 0 auto;
                    }
                }

                @media (max-width: 768px) {
                    .podcast-info-single {
                        margin-top: 200px;
                    }

                    .single-podcast-content {
                        width: 550px;
                        margin: 0 auto;
                    }

                    .single-podcast-image {
                        width: 60%;
                    }

                    .title-single-podcast {
                        font-size: 40px;

                    }

                    .podcast-title-child {
                        font-size: 20px;

                    }

                    .single-podcast-info {
                        width: 90%;
                        margin: 0 auto;
                    }

                    .podcast-audio-single {
                        padding: 0px 0px 25px 0px;
                    }
                }

                @media (max-width: 560px) {
                    .single-podcast-content {
                        height: auto;
                        width: 100%;
                        margin: 0 auto;
                        display: block;
                        margin-bottom: 64px;
                    }

                    .podcast-info-single {
                        margin-top: 910px;
                        padding: 0px;
                    }


                    .title-single-podcast {
                        font-size: 24px;
                    }

                    .single-podcast-image img {
                        width: 100%;
                        height: 330px;
                        border-radius: 12px;
                        margin-top: 12px;
                    }

                    .single-podcast-image {
                        margin: 0 auto;
                        width: 90%;
                        margin-top: 10px;
                    }

                    .podcast-hero {
                        margin-bottom: 400px;
                    }

                }
            </style>

            <div class="podcast-hero">
                <div class="podcast-info-single">
                    <h2 class="title-single-podcast"><?php echo get_the_title(); ?></h2>
                    <span class="excerpt-single-podcast"><?php echo wp_trim_words(get_the_excerpt(), 20, '...'); ?></span>
                    <div class="single-podcast-content">
                        <div class="single-podcast-image">
                            <?php the_post_thumbnail('full'); ?>
                        </div>
                        <div class="single-podcast-info">
                            <div class="podcast-title-child">
                                <?php echo get_the_title(); ?>
                            </div>
                            <div class="podcast-author-date-single">
                                <span class="author_podcast" style="color: white; text-transform: capitalize;">By <?php echo get_the_author(); ?></span>
                                <span class="date_podcast" style="color: white;"> <?php echo get_the_date(); ?></span>
                            </div>

                            <div class="podcast-share-icons" style="margin-top: 15px;">
                                <span style="margin-right:5px">Share on:</span>
                                <a href="https://www.facebook.com/" target="_blank"><img src="<?php echo plugins_url('../assets/img/media-2.svg', __FILE__); ?>" alt="" width="25px"></a>

                                <a style="padding:0px 5px" href="https://x.com/" target="_blank"><img src="<?php echo plugins_url('../assets/img/media-1.svg', __FILE__); ?>" alt="" width="25px"></a>

                                <a href="https://www.linkedin.com/" target="_blank"><img src="<?php echo plugins_url('../assets/img/media-3.svg', __FILE__); ?>" alt="" width="25px"></a>
                            </div>
                            <div class="podcast-audio-single" style="margin-top: 20px;">
                                <audio id="podcast-audio-player-single" style="width: 100%;">
                                    <source src="<?php echo esc_url($podcast_audio_link); ?>" type="audio/mpeg">
                                </audio>

                                <div class="controls-podcast-single" style="margin-top: 10px;">
                                    <div class="controls-main-single">
                                        <span id="rewind-button-single">
                                            <img src="<?php echo plugins_url('../assets/img/backward.svg', __FILE__); ?>" alt="Rewind" width="23px">
                                        </span>
                                        <div class="time-display-single" style="display: flex; justify-content: space-between;">
                                            <span id="current-time-single">0:00</span>
                                            <input type="range" value="0" class="progress-single" id="progress-single">
                                            <span id="total-time-single">0:00</span>
                                        </div>
                                        <span id="forward-button-single">
                                            <img src="<?php echo plugins_url('../assets/img/Vector.svg', __FILE__); ?>" alt="Forward" width="20px">
                                        </span>
                                        <span id="stop-button-single">
                                            <img id="play-img-single" src="<?php echo plugins_url('../assets/img/Frame2.svg', __FILE__); ?>" alt="Play" width="50px">
                                            <img id="pause-img-single" src="<?php echo plugins_url('../assets/img/play-icon-list.svg', __FILE__); ?>" alt="Pause" width="50px" style="display: none;">
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php
            wp_reset_postdata();
            return ob_get_clean();
        } else {
            return '<p>No podcast found.</p>';
        }
    }

    //end single podcast

    // slider-podcast
    function podcast_slider_shortcode($atts)
    {
        $atts = shortcode_atts(
            array(
                'taxonomy' => 'podcast_category',
            ),
            $atts,
            'podcast_slider'
        );

        $categories = get_terms(array(
            'taxonomy' => $atts['taxonomy'],
            'hide_empty' => true,
            'orderby' => 'id',
            'order' => 'DESC',
        ));

        $latest_category_slug = !empty($categories) ? $categories[0]->slug : '';

        ob_start();
        ?>
        <div class="podcast-filter-container">
            <div class="podcast-category-filters">
                <?php foreach ($categories as $category) : ?>
                    <span class="podcast-category-filter <?php echo $category->slug === $latest_category_slug ? 'active' : ''; ?>" data-term="<?php echo esc_attr($category->slug); ?>">
                        <?php echo esc_html($category->name); ?>
                    </span>
                <?php endforeach; ?>
            </div>
            <div class="slider-controls">
                <span class="slider-prev"><img src="<?php echo plugins_url('../assets/img/Left.svg', __FILE__); ?>" alt=""></span>
                <span class="slider-next"><img src="<?php echo plugins_url('../assets/img/Right.svg', __FILE__); ?>" alt=""></span>
            </div>
        </div>

        <div class="podcast-slider-container">
            <div class="podcast-slider-wrapper">
                <div class="podcast-slider">
                    <?php load_podcast_slider_content($latest_category_slug); ?>
                </div>
            </div>
        </div>
        <style>
            .podcast-filter-container {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
            }

            .podcast-category-filters {
                display: flex;
                align-items: center;
            }

            .podcast-category-filter {
                background-color: #F6F9FC;
                border: 1px solid #ccc;
                padding: 10px 20px;
                cursor: pointer;
                margin: 0 5px;
                border: 1px solid #252525;
                border-radius: 3px;
                font-weight: 500;
            }

            button.podcast-category-filter:hover {
                background: #252525;
                color: white;
            }

            .podcast-filter-container {
                max-width: 1264px;
                margin: 0 auto;
            }

            .podcast-category-filter.active {
                background-color: #252525;
                color: #fff;
                border: #252525;
                outline: none;

            }

            button.podcast-category-filter {
                color: black;
                font-weight: 500;
                text-transform: capitalize;
            }

            .slider-controls {
                display: flex;
                align-items: center;
            }

            .slider-prev,
            .slider-next {
                cursor: pointer;
                z-index: 10;
                margin-left: 20px;
            }

            .podcast-slider .podcast-slide:first-child {
                margin-left: -245px;
            }

            .podcast-slider-container {
                position: relative;
                width: 100%;
                max-width: 100%;
                margin: 0 auto;
                overflow: hidden;
            }

            .podcast-slider-wrapper {
                overflow: hidden;
            }

            .podcast-slider {
                display: flex;
                transition: transform 0.5s ease-in-out;
            }

            .podcast-slide {
                box-sizing: border-box;
                padding: 10px;
                flex: 0 0 25%;
            }

            .podcast-slide img {
                width: 100%;
                height: 490px;
                display: block;
            }

            @media (max-width: 768px) {
                .podcast-slide {
                    flex: 0 0 100%;
                }

                .podcast-slide:nth-child(2n+1) {
                    flex: 0 0 100%;
                }
            }

            .podcast-slide-overlay {
                display: flex;
                justify-content: space-between;
            }

            .podcast-slide {
                position: relative;
                width: 368px;
                height: 490px;
                overflow: hidden;
            }

            .podcast-slide img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                transition: filter 0.3s ease;
            }

            .podcast-slide:hover img {
                filter: brightness(1.2);
            }

            .podcast-slide-content {
                position: relative;
                width: 100%;
                height: 100%;
            }

            .podcast-slide-overlay {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: linear-gradient(to bottom, rgba(0, 0, 0, 0) 0%, rgba(0, 0, 0, 0.7) 100%);
                color: white;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                padding: 15px;
            }

            .info-slider {
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                height: 100%;
            }

            .podcast-slide-title,
            .podcast-slide-author {
                margin: 0;
            }

            .podcast-slide-title {
                font-size: 24px;
                font-weight: bold;
                margin-top: 320px;
            }

            .podcast-slide-author {
                font-size: 14px;
                text-transform: capitalize;
            }

            .podcast-play-icon {
                position: absolute;
                bottom: 15px;
                right: 15px;
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 1;
            }

            .podcast-play-icon img {
                width: 50px;
                height: 50px;
            }



            @media (max-width: 500px) {
                .podcast-slider-container {
                    position: relative;
                    width: 100%;
                    max-width: 375px;
                    margin: 0 auto;
                    overflow: hidden;
                }

                .podcast-slider .podcast-slide:first-child {
                    margin-left: -30px;
                }

                .podcast-slider .podcast-slide:first-child .podcast-slide-title {
                    font-size: 24px;
                    font-weight: bold;
                    margin-top: 260px;
                    margin-left: 12px;
                }

                .podcast-slider .podcast-slide:first-child .podcast-slide-author {
                    font-size: 14px;
                    text-transform: capitalize;
                    margin-left: 12px;
                }

                .slider-controls {
                    display: none;
                }

                .podcast-slide-title {
                    font-size: 24px;
                    font-weight: bold;
                    margin-top: 260px;
                }

                .podcast-category-filters {
                    display: flex;
                    overflow-x: auto;
                    white-space: nowrap;
                    margin-bottom: 20px;
                    scroll-snap-type: x mandatory;
                    -webkit-overflow-scrolling: touch;
                    margin-left: 20px;
                }

                .podcast-category-filter {
                    flex: 0 0 auto;
                    margin-right: 10px;
                    padding: 5px 10px;
                    border: 1px solid #252525;
                    color: #333;
                    font-size: 16px;
                    cursor: pointer;
                    transition: background-color 0.3s ease;
                    scroll-snap-align: start;
                }

                .podcast-category-filter.active {
                    color: white;
                }

                .podcast-category-filter {
                    padding: 8px 15px;
                    font-size: 14px;
                }

                .podcast-slide {
                    width: 100%;
                    height: auto;
                }

                .podcast-slide img {
                    height: auto;
                }
            }
        </style>

<?php
        return ob_get_clean();
    }
    function load_podcast_slider()
    {
        $term = isset($_GET['term']) ? sanitize_text_field($_GET['term']) : '';
        load_podcast_slider_content($term);
        die();
    }
    //end slider podcast
    // handle code
    function handle_code()
    {
        add_action('transition_post_status', [$this, 'xoa_generate_mp3_on_publish_or_update'], 10, 3);
        add_action('admin_menu', [$this, 'xoa_add_admin_menu']);
        add_filter('the_content', [$this, 'xoa_add_play_button']);
        add_action('add_meta_boxes', [$this, 'xoa_add_podcast_meta_boxes']);
        add_action('publish_post', [$this, 'xoa_save_podcast_meta_boxes']);
        add_action('save_post', [$this, 'xoa_save_podcast_meta_boxes']);
        add_action('publish_post', [$this, 'create_default_podcast_meta_fields']);
        add_action('post_edit_form_tag', [$this, 'xoa_add_enctype_attribute']);
        add_shortcode('latest_podcast_info', [$this, 'latest_podcast_info_shortcode']);
        add_shortcode('render_podcast', [$this, 'render_podcast_shortcode']);
        add_shortcode('podcast_single', [$this, 'latest_podcast_shortcode']);
        add_shortcode('podcast_slider', [$this, 'podcast_slider_shortcode']);
        add_action('wp_ajax_load_podcast_slider', [$this, 'load_podcast_slider']);
        add_action('wp_ajax_nopriv_load_podcast_slider', [$this, 'load_podcast_slider']);
        add_action('wp_ajax_xoa_search_blog_posts', [$this, 'xoa_search_blog_posts']);
        add_action('wp_ajax_xoa_save_voice_and_excerpt', [$this, 'xoa_save_voice_and_excerpt']);
        add_action('wp_ajax_xoa_save_voice_check_file_mp3_podcast', [$this, 'xoa_save_voice_check_file_mp3_podcast']);
        add_action('wp_ajax_xoa_delete_audio_file', [$this, 'xoa_delete_audio_file_callback']);
    }
}

require_once plugin_dir_path(__FILE__) . 'xoa_text_to_speech.php';
require_once plugin_dir_path(__FILE__) . 'xoa_text_to_speech_sample.php';

// require_once plugin_dir_path(__FILE__) . 'xoa_generate_mp3_files.php';
require_once plugin_dir_path(__FILE__) . 'xoa_display_generated_mp3_list.php';
require_once plugin_dir_path(__FILE__) . 'content_podcast_slider.php';
require_once plugin_dir_path(__FILE__) . 'xoa_text_to_speech_google_api.php';
