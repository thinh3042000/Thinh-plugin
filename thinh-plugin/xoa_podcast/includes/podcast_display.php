<?php
function latest_podcast_info_shortcode()
{
    $args = array(
        'post_type' => 'podcast',
        'posts_per_page' => 1,
        'orderby' => 'date',
        'order' => 'DESC',
        'meta_query' => array(
            array(
                'key' => '_podcast_audio_file',
                'compare' => 'EXISTS'
            )
        )
    );
    $latest_podcast = new WP_Query($args);
    ob_start();

    if ($latest_podcast->have_posts()) {
        while ($latest_podcast->have_posts()) {
            $latest_podcast->the_post();

            $podcast_audio_link = get_post_meta(get_the_ID(), '_podcast_audio_file', true);
?>
            <div class="podcast-info" style="display: flex;">
                <div class="podcast-thumbnail" style="margin-right: 20px;">
                    <?php if (has_post_thumbnail()) { ?>
                        <div class="img-podcast">
                            <?php the_post_thumbnail('full'); ?>
                            <style>
                                @media (min-width: 1024px) {
                                    .img-podcast img {
                                        width: 704px;
                                        height: 778px;
                                    }
                                }
                            </style>
                        </div>
                    <?php } else {
                        echo 'Không có ảnh đại diện.';
                    } ?>
                </div>
                <div class="podcast-details" style="display: flex; flex-direction: column; justify-content: center; width: 700px; padding: 30px;">
                    <div class="podcast-author-date" style="display:flex; justify-content:space-between">
                        <span class="author_podcast" style="color: #666666;">By <?php the_author(); ?></span>
                        <span class="date_podcast" style="color: #666666;"> <?php the_date(); ?></span>
                    </div>
                    <div class="podcast-title" style=" font-size: 60px; font-weight: bold;  letter-spacing: -1px; line-height: 1.2; ">
                        <?php the_title(); ?>
                    </div>
                    <div class="podcast-excerpt" style="color: #666666; margin-top: 10px;">
                        <?php the_excerpt(); ?>
                    </div>

                    <div class="podcast-share-icons" style="margin-top: 20px;">
                        <span>Share on:</span>
                        <a href="https://www.tiktok.com/" target="_blank"><img src="https://cdn.pixabay.com/photo/2022/02/09/08/35/tiktok-7002882_1280.png" alt="TikTok" width="30px"></a>
                        <a href="https://www.facebook.com/" target="_blank"><img src="https://upload.wikimedia.org/wikipedia/commons/8/82/Facebook_icon.jpg" alt="Facebook" width="30px"></a>
                        <a href="https://www.instagram.com/" target="_blank"><img src="https://upload.wikimedia.org/wikipedia/commons/thumb/a/a5/Instagram_icon.png/2048px-Instagram_icon.png" alt="Instagram" width="30px"></a>
                    </div>

                    <?php if ($podcast_audio_link) { ?>
                        <style>
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
                                width: 30px;
                                height: 30px;
                                border-radius: 50%;
                                border: 8px solid white;
                                box-shadow: 0 5px 5px rgba(0, 175, 90, 0.4);
                            }
                        </style>
                        <div class="podcast-audio" style="margin-top: 20px;">
                            <audio id="podcast-audio-player" controls style="width: 100%;">
                                <source src="<?php echo esc_url($podcast_audio_link); ?>" type="audio/mpeg">
                                Your browser does not support the audio element.
                            </audio>
                            <input type="range" value="0" id="progress">
                            <div class="controls-podcast" style="margin-top: 10px;">
                                <button id="reset-button">Reset</button>
                                <button id="rewind-button">Return 10s</button>
                                <button onclick="playPause()" id="stop-button" class="fa-solid fa-play">Play/Pause</button>
                                <button id="forward-button">Forward 10s</button>
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
