<?php
function xoa_generate_mp3_files()
{
    $args = array(
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
    );
    $query = new WP_Query($args);

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $post_title = sanitize_title(get_the_title());
            $post_content = get_the_content();

            if (strpos($post_content, '<table') !== false) {
                $post_content = preg_replace('/<table(.*?)<\/table>/is', '<p>For more details, please view the additional information in the table.</p>', $post_content);
            }

            $upload_dir = wp_upload_dir();
            $audio_dir = $upload_dir['basedir'] . '/audio/';
            $audio_file = $audio_dir . $post_title . '.mp3';

            if (!file_exists($audio_dir)) {
                wp_mkdir_p($audio_dir);
            }

            if (!file_exists($audio_file)) {
                $audio_content = xoa_text_to_speech(strip_tags($post_content));

                if ($audio_content !== false) {
                    file_put_contents($audio_file, $audio_content);
                    echo '<p>Generated MP3 for post ID: ' . $post_id . '</p>';
                } else {
                    echo '<p>Failed to generate MP3 for post ID: ' . $post_id . '</p>';
                }
            } else {
                echo '<p>MP3 already exists for post ID: ' . $post_id . '</p>';
            }
        }
    } else {
        echo '<p>No posts found.</p>';
    }
    wp_reset_postdata();
}