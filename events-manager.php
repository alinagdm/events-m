<?php
/**
 * Plugin Name: Events Manager
 * Description: Custom post type for events with shortcode and AJAX load more
 * Version: 1.0
 * Author: Events Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

define('EVENTS_MANAGER_VERSION', '1.0');
define('EVENTS_MANAGER_PATH', plugin_dir_path(__FILE__));
define('EVENTS_MANAGER_URL', plugin_dir_url(__FILE__));

class Events_Manager {

    private static $instance = null;
    private $cpt_slug = 'event';
    private $per_page = 3;
    private $nonce_action = 'events_load_more';

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('init', [$this, 'register_post_type']);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post_' . $this->cpt_slug, [$this, 'save_meta'], 10, 2);
        add_shortcode('events_list', [$this, 'shortcode_events_list']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_events_load_more', [$this, 'ajax_load_more']);
        add_action('wp_ajax_nopriv_events_load_more', [$this, 'ajax_load_more']);
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function add_settings_page() {
        add_submenu_page(
            'edit.php?post_type=' . $this->cpt_slug,
            'Настройки карт',
            'Настройки карт',
            'manage_options',
            'events-manager',
            [$this, 'render_settings_page']
        );
    }

    private function get_map_config() {
        $key = defined('EVENTS_MANAGER_YANDEX_API_KEY') && EVENTS_MANAGER_YANDEX_API_KEY !== ''
            ? EVENTS_MANAGER_YANDEX_API_KEY
            : get_option('em_map_api_key', '');
        $provider = defined('EVENTS_MANAGER_YANDEX_API_KEY') && EVENTS_MANAGER_YANDEX_API_KEY !== ''
            ? 'yandex'
            : get_option('em_map_provider', 'iframe');
        return [$provider, $key];
    }

    public function register_settings() {
        register_setting('events_manager', 'em_map_provider', [
            'type' => 'string',
            'default' => 'iframe',
        ]);
        register_setting('events_manager', 'em_map_api_key', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ]);
    }

    public function render_settings_page() {
        list($provider, $api_key) = $this->get_map_config();
        $from_code = defined('EVENTS_MANAGER_YANDEX_API_KEY') && EVENTS_MANAGER_YANDEX_API_KEY !== '';
        ?>
        <div class="wrap">
            <h1>Events Manager — карты</h1>
            <?php if ($from_code): ?>
            <p><em>API-ключ задан в коде плагина (EVENTS_MANAGER_YANDEX_API_KEY).</em></p>
            <?php endif; ?>
            <form method="post" action="options.php">
                <?php settings_fields('events_manager'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Провайдер карт</th>
                        <td>
                            <select name="em_map_provider" id="em_map_provider" <?php echo $from_code ? 'disabled' : ''; ?>>
                                <option value="iframe" <?php selected($provider, 'iframe'); ?>>iframe (без API)</option>
                                <option value="yandex" <?php selected($provider, 'yandex'); ?>>Яндекс.Карты API</option>
                                <option value="google" <?php selected($provider, 'google'); ?>>Google Maps API</option>
                                <option value="none" <?php selected($provider, 'none'); ?>>Отключить</option>
                            </select>
                            <?php if ($from_code): ?><input type="hidden" name="em_map_provider" value="yandex"><?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">API-ключ</th>
                        <td>
                            <?php if ($from_code): ?>
                            <input type="text" value="<?php echo esc_attr($api_key ? substr($api_key, 0, 8) . '...' : ''); ?>" class="regular-text" disabled>
                            <p class="description">Ключ задан в events-manager.php</p>
                            <?php else: ?>
                            <input type="text" name="em_map_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text">
                            <p class="description">Нужен для Яндекс или Google. Либо укажи EVENTS_MANAGER_YANDEX_API_KEY в коде плагина.</p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                <?php if (!$from_code) submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function register_post_type() {
        $labels = [
            'name' => 'События',
            'singular_name' => 'Событие',
            'add_new' => 'Добавить событие',
            'add_new_item' => 'Добавить новое событие',
            'edit_item' => 'Редактировать событие',
            'new_item' => 'Новое событие',
            'view_item' => 'Просмотр события',
            'search_items' => 'Искать события',
            'not_found' => 'События не найдены',
            'not_found_in_trash' => 'В корзине событий нет',
        ];
        $args = [
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => ['slug' => 'events'],
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 20,
            'supports' => ['title', 'editor', 'thumbnail'],
        ];
        register_post_type($this->cpt_slug, $args);
    }

    public function add_meta_boxes() {
        add_meta_box(
            'events_manager_meta',
            'Данные события',
            [$this, 'render_meta_box'],
            $this->cpt_slug,
            'normal'
        );
    }

    public function render_meta_box($post) {
        wp_nonce_field('events_manager_save', 'events_manager_nonce');
        $date = get_post_meta($post->ID, 'event_date', true);
        $place = get_post_meta($post->ID, 'event_place', true);
        ?>
        <p>
            <label for="event_date">Дата события:</label><br>
            <input type="date" id="event_date" name="event_date" value="<?php echo esc_attr($date); ?>" style="width:100%;max-width:250px;">
        </p>
        <p>
            <label for="event_place">Место проведения:</label><br>
            <input type="text" id="event_place" name="event_place" value="<?php echo esc_attr($place); ?>" style="width:100%;max-width:400px;">
        </p>
        <?php
    }

    public function save_meta($post_id, $post) {
        if (!isset($_POST['events_manager_nonce']) || !wp_verify_nonce($_POST['events_manager_nonce'], 'events_manager_save')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        if (isset($_POST['event_date'])) {
            update_post_meta($post_id, 'event_date', sanitize_text_field($_POST['event_date']));
        }
        if (isset($_POST['event_place'])) {
            update_post_meta($post_id, 'event_place', sanitize_text_field($_POST['event_place']));
        }
    }

    private function format_date_for_display($date_str) {
        if (empty($date_str)) {
            return '';
        }
        $date = date_create_from_format('Y-m-d', $date_str, wp_timezone());
        if (!$date) {
            return $date_str;
        }
        return $date->format('d.m.Y');
    }

    private function get_events_query($paged = 1, $per_page = null) {
        if ($per_page === null) {
            $per_page = $this->per_page;
        }
        $today = current_time('Y-m-d');
        $args = [
            'post_type' => $this->cpt_slug,
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $paged,
            'meta_key' => 'event_date',
            'meta_query' => [
                [
                    'key' => 'event_date',
                    'value' => $today,
                    'compare' => '>=',
                    'type' => 'DATE',
                ],
            ],
            'orderby' => 'meta_value',
            'order' => 'ASC',
            'meta_type' => 'DATE',
        ];
        return new WP_Query($args);
    }

    private function render_events_html($query) {
        ob_start();
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                $date = get_post_meta($post_id, 'event_date', true);
                $place = get_post_meta($post_id, 'event_place', true);
                $formatted_date = $this->format_date_for_display($date);
                $place_encoded = $place ? rawurlencode($place) : '';
                $show_map = apply_filters('events_manager_show_map', true);
                list($map_provider, $map_api_key) = $this->get_map_config();
                $use_api = in_array($map_provider, ['yandex', 'google']) && !empty($map_api_key);
                ?>
                <article class="em-event" data-post-id="<?php echo esc_attr($post_id); ?>">
                    <h3 class="em-event-title"><?php the_title(); ?></h3>
                    <div class="em-event-date"><?php echo esc_html($formatted_date); ?></div>
                    <div class="em-event-place"><?php echo esc_html($place); ?></div>
                    <?php if ($place_encoded && $show_map && $map_provider !== 'none'): ?>
                    <?php if ($use_api): ?>
                    <div class="em-event-map em-map-api" data-address="<?php echo esc_attr($place); ?>" data-provider="<?php echo esc_attr($map_provider); ?>"></div>
                    <?php else: ?>
                    <div class="em-event-map">
                        <iframe
                            src="https://yandex.ru/maps/?text=<?php echo esc_attr($place_encoded); ?>"
                            width="100%"
                            height="200"
                            frameborder="0"
                            allowfullscreen
                            loading="lazy"
                        ></iframe>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </article>
                <?php
            }
            wp_reset_postdata();
        }
        return ob_get_clean();
    }

    public function shortcode_events_list() {
        wp_enqueue_style('events-manager');
        wp_enqueue_script('events-manager');
        $query = $this->get_events_query(1);
        $html = '<div class="em-events-list" data-page="1" data-total="' . esc_attr($query->max_num_pages) . '">';
        $html .= '<div class="em-events-container">';
        $html .= $this->render_events_html($query);
        $html .= '</div>';
        if ($query->max_num_pages > 1) {
            $html .= '<button type="button" class="em-load-more">Показать больше</button>';
        }
        $html .= '</div>';
        return $html;
    }

    public function enqueue_assets() {
        wp_register_style('events-manager', EVENTS_MANAGER_URL . 'assets/events-manager.css', [], EVENTS_MANAGER_VERSION);
        wp_register_script('events-manager', EVENTS_MANAGER_URL . 'assets/events-manager.js', [], EVENTS_MANAGER_VERSION, true);
        list($map_provider, $map_api_key) = $this->get_map_config();
        $use_map_api = in_array($map_provider, ['yandex', 'google']) && !empty($map_api_key);
        wp_localize_script('events-manager', 'eventsManager', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce($this->nonce_action),
            'action' => 'events_load_more',
            'mapProvider' => $use_map_api ? $map_provider : '',
            'mapApiKey' => $use_map_api ? $map_api_key : '',
        ]);
    }

    public function ajax_load_more() {
        if (!check_ajax_referer($this->nonce_action, 'nonce', false)) {
            wp_send_json_error(['message' => 'Invalid nonce']);
        }
        $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
        if ($page < 1) {
            wp_send_json_error(['message' => 'Invalid page']);
        }
        $query = $this->get_events_query($page);
        $html = $this->render_events_html($query);
        wp_send_json_success([
            'html' => $html,
            'has_more' => $page < $query->max_num_pages,
            'next_page' => $page + 1,
        ]);
    }
}

function events_manager_init() {
    return Events_Manager::get_instance();
}

add_action('plugins_loaded', 'events_manager_init');
