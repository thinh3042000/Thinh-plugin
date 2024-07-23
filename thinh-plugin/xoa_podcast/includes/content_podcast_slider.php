<?php

function load_podcast_slider_content($term = '')
{
    $args = array(
        'post_type' => 'podcast',
        'posts_per_page' => 6,
        'orderby' => 'id',
        'order' => 'DESC',
    );

    if (!empty($term)) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'podcast_category',
                'field'    => 'slug',
                'terms'    => $term,
            ),
        );
    }

    $query = new WP_Query($args);
    if ($query->have_posts()) :
        while ($query->have_posts()) : $query->the_post();
            $thumbnail_url = get_the_post_thumbnail_url(get_the_ID(), 'full');
    ?>
            <div class="podcast-slide">
                <div class="podcast-slide-content">
                    <a href="<?php the_permalink(); ?>">
                        <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php the_title(); ?>">
                    <div class="podcast-slide-overlay">
                        <div class="info-slider">
                        <div class="podcast-slide-title"><?php the_title(); ?></div>
                        <div class="podcast-slide-author"><?php echo get_the_author(); ?></div>
                        </div>
                        <div class="podcast-play-icon">
                            <img class="icon-slider" src="<?php echo plugins_url('../assets/img/Frame.svg', __FILE__); ?>" alt="" width="40px">
                        </div>
                    </div>
                    </a>
                </div>
            </div>
<?php
        endwhile;
        wp_reset_postdata();
    endif;
}