<?php
get_header();

while (have_posts()) {
    the_post();
    $date = get_post_meta(get_the_ID(), 'event_date', true);
    $place = get_post_meta(get_the_ID(), 'event_place', true);
    $formatted_date = events_manager_format_date($date);
    $place_encoded = $place ? rawurlencode($place) : '';
    $show_map = apply_filters('events_manager_show_map', true);
    $api_key = EVENTS_MANAGER_YANDEX_API_KEY;
    $use_api = !empty($api_key);
    ?>
    <article class="em-single-event">
        <?php if (has_post_thumbnail()): ?>
        <div class="em-single-event-thumb">
            <?php the_post_thumbnail('large'); ?>
        </div>
        <?php endif; ?>
        <header class="em-single-event-header">
            <h1 class="em-single-event-title"><?php the_title(); ?></h1>
            <?php if ($formatted_date): ?>
            <div class="em-single-event-date"><?php echo esc_html($formatted_date); ?></div>
            <?php endif; ?>
            <?php if ($place): ?>
            <div class="em-single-event-place"><?php echo esc_html($place); ?></div>
            <?php endif; ?>
        </header>
        <div class="em-single-event-content">
            <?php the_content(); ?>
        </div>
        <?php if ($place_encoded && $show_map): ?>
        <div class="em-single-event-map-wrap">
            <?php if ($use_api): ?>
            <div class="em-event-map em-map-api" data-address="<?php echo esc_attr($place); ?>" data-provider="yandex"></div>
            <?php else: ?>
            <div class="em-event-map">
                <iframe
                    src="https://yandex.ru/maps/?text=<?php echo esc_attr($place_encoded); ?>"
                    width="100%"
                    height="350"
                    frameborder="0"
                    allowfullscreen
                    loading="lazy"
                ></iframe>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </article>
    <?php
}

get_footer();
