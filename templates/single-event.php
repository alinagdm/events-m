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
        <div class="em-single-event-body">
            <header class="em-single-event-header">
                <h1 class="em-single-event-title"><?php the_title(); ?></h1>
                <div class="em-single-event-meta">
                    <?php if ($formatted_date): ?>
                    <span class="em-single-event-meta-item em-single-event-date">
                        <span class="em-single-event-meta-icon">📅</span>
                        <?php echo esc_html($formatted_date); ?>
                    </span>
                    <?php endif; ?>
                    <?php if ($place): ?>
                    <span class="em-single-event-meta-item em-single-event-place">
                        <span class="em-single-event-meta-icon">📍</span>
                        <?php echo esc_html($place); ?>
                    </span>
                    <?php endif; ?>
                </div>
                <?php if (has_excerpt()): ?>
                <div class="em-single-event-excerpt"><?php the_excerpt(); ?></div>
                <?php endif; ?>
            </header>
            <div class="em-single-event-content">
                <?php the_content(); ?>
            </div>
            <?php if ($place_encoded && $show_map): ?>
            <div class="em-single-event-map-wrap">
                <h3 class="em-single-event-map-title">Место проведения</h3>
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
        </div>
    </article>
    <?php
}

get_footer();
