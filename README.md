# Events Manager

WordPress плагин для управления событиями.

## Установка

1. Скопировать папку в `wp-content/plugins/`
2. Активировать плагин

## Использование

Шорткод `[events_list]` на странице или в записи.

## Функционал

- CPT «Событие»: название, дата, место
- Только предстоящие события, сортировка по дате
- Кнопка «Показать больше» (AJAX, по 3 события)
- Карта Яндекса (API-ключ в коде плагина — константа EVENTS_MANAGER_YANDEX_API_KEY в events-manager.php)

Отключить карты: `add_filter('events_manager_show_map', '__return_false');` в functions.php темы.
