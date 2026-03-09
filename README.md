# Events Manager

WordPress плагин для управления событиями.

## Установка

1. Скопировать папку `events-manager` в `wp-content/plugins/`
2. Активировать плагин в админке

## Использование

Добавить шорткод на страницу или в запись:

```
[events_list]
```

## Функционал

- Кастомный тип записей «Событие» с полями: название, дата, место
- Шорткод выводит только предстоящие события (дата >= сегодня)
- Сортировка по дате (сначала ближайшие)
- Кнопка «Показать больше» — загрузка по 3 события через AJAX
- Карта: iframe, Яндекс API или Google Maps API (настройки в Настройки → Events Manager)
- Даты с учётом часового пояса WordPress

Отключить карты: добавить в functions.php темы `add_filter('events_manager_show_map', '__return_false');`

API-ключи: Яндекс — developer.tech.yandex.ru; Google — нужны Maps JavaScript API и Geocoding API в Cloud Console.

Ключ Яндекса через wp-config.php: добавь перед строкой `/* That's all, stop editing! */`:
`define('EVENTS_MANAGER_YANDEX_API_KEY', 'твой_ключ');`
