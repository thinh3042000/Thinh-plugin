<?php
function xoa_display_generated_mp3_list()
{
    $upload_dir = wp_upload_dir();
    $audio_dir = $upload_dir['basedir'] . '/audio';
    $audio_url_base = $upload_dir['baseurl'] . '/audio';

    if (is_dir($audio_dir)) {
        $audio_files = scandir($audio_dir);
        $audio_files = array_diff($audio_files, array('.', '..'));

        usort($audio_files, function ($a, $b) use ($audio_dir) {
            $file_a = $audio_dir . '/' . $a;
            $file_b = $audio_dir . '/' . $b;
            return filemtime($file_a) - filemtime($file_b);
        });

        if (!empty($audio_files)) {
            echo '<h2>Generated MP3 Files</h2>';
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead>';
            echo '<tr>';
            echo '<th style="font-size:16px; width:40px">#</th>';
            echo '<th style="font-size:16px;">File Name</th>';
            echo '<th style="font-size:16px;">Actions</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';

            $count = 1;

            foreach ($audio_files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'mp3') {
                    echo '<tr>';
                    echo '<td style="font-size: 15px;">' . esc_html($count) . '.</td>';
                    echo '<td class="td-title" style="font-size: 15px;">' . esc_html($file) . '</td>';
                    echo '<td>';
                    echo '<audio controls style="height: 30px; width:70%;">';
                    echo '<source src="' . esc_url($audio_url_base . '/' . $file) . '" type="audio/mpeg">';
                    echo 'Trình duyệt của bạn không hỗ trợ phần tử âm thanh.';
                    echo '</audio>';
                    echo '</td>';
                    echo '</tr>';

                    $count++;
                }
            }

            echo '</tbody>';
            echo '</table>';
        } else {
            echo '<p>No MP3 files have been generated yet.</p>';
        }
    } else {
        echo '<p>The audio directory does not exist.</p>';
    }
}
