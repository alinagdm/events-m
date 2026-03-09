(function() {
    'use strict';

    var config = typeof eventsManager !== 'undefined' ? eventsManager : {};
    var mapScriptLoaded = { yandex: false, google: false };

    function initMaps() {
        var containers = document.querySelectorAll('.em-map-api:not([data-initialized])');
        if (!containers.length || !config.mapProvider || !config.mapApiKey) return;

        if (config.mapProvider === 'yandex') {
            loadYandexMaps(function() {
                containers.forEach(function(el) {
                    initYandexMap(el);
                });
            });
        } else if (config.mapProvider === 'google') {
            loadGoogleMaps(function() {
                containers.forEach(function(el) {
                    initGoogleMap(el);
                });
            });
        }
    }

    function loadYandexMaps(callback) {
        if (mapScriptLoaded.yandex) {
            if (typeof ymaps !== 'undefined') {
                ymaps.ready(callback);
            }
            return;
        }
        var script = document.createElement('script');
        script.src = 'https://api-maps.yandex.ru/2.1/?apikey=' + encodeURIComponent(config.mapApiKey) + '&lang=ru_RU';
        script.onload = function() {
            mapScriptLoaded.yandex = true;
            ymaps.ready(callback);
        };
        document.head.appendChild(script);
    }

    function initYandexMap(container) {
        var address = container.getAttribute('data-address');
        if (!address) return;
        container.setAttribute('data-initialized', '1');
        var map = new ymaps.Map(container, { center: [55.76, 37.64], zoom: 10, controls: ['zoomControl'] });
        ymaps.geocode(address).then(function(res) {
            var first = res.geoObjects.get(0);
            if (first) {
                map.geoObjects.add(first);
                var bounds = first.properties.get('boundedBy');
                if (bounds) {
                    map.setBounds(bounds, { checkZoomRange: true });
                } else {
                    var coords = first.geometry.getCoordinates();
                    map.setCenter(coords, 14);
                }
            }
        });
    }

    function loadGoogleMaps(callback) {
        if (mapScriptLoaded.google) {
            callback();
            return;
        }
        var cbName = 'emGoogleMapsCb_' + Date.now();
        window[cbName] = function() {
            mapScriptLoaded.google = true;
            delete window[cbName];
            callback();
        };
        var script = document.createElement('script');
        script.src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent(config.mapApiKey) + '&callback=' + cbName;
        document.head.appendChild(script);
    }

    function initGoogleMap(container) {
        var address = container.getAttribute('data-address');
        if (!address) return;
        container.setAttribute('data-initialized', '1');
        var map = new google.maps.Map(container, {
            center: { lat: 55.76, lng: 37.64 },
            zoom: 10
        });
        var geocoder = new google.maps.Geocoder();
        geocoder.geocode({ address: address }, function(results, status) {
            if (status === 'OK' && results[0]) {
                map.setCenter(results[0].geometry.location);
                map.fitBounds(results[0].geometry.viewport);
                new google.maps.Marker({
                    map: map,
                    position: results[0].geometry.location
                });
            }
        });
    }

    function init() {
        var list = document.querySelector('.em-events-list');
        if (!list) return;

        var container = list.querySelector('.em-events-container');
        var btn = list.querySelector('.em-load-more');

        initMaps();

        if (!container || !btn) return;

        btn.addEventListener('click', function() {
            var currentPage = parseInt(list.getAttribute('data-page') || '1', 10);
            var nextPage = currentPage + 1;
            var ajaxUrl = config.ajaxUrl || (typeof ajaxurl !== 'undefined' ? ajaxurl : '');
            var nonce = config.nonce || btn.getAttribute('data-nonce') || '';
            var action = config.action || 'events_load_more';

            if (!ajaxUrl || !nonce) return;

            btn.disabled = true;
            btn.textContent = 'Загрузка...';

            var formData = new FormData();
            formData.append('action', action);
            formData.append('nonce', nonce);
            formData.append('page', nextPage);

            fetch(ajaxUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success && data.data && data.data.html) {
                    container.insertAdjacentHTML('beforeend', data.data.html);
                    list.setAttribute('data-page', data.data.next_page || nextPage);
                    if (!data.data.has_more) {
                        btn.style.display = 'none';
                    }
                    initMaps();
                }
            })
            .catch(function() {
                btn.disabled = false;
                btn.textContent = 'Показать больше';
            })
            .finally(function() {
                btn.disabled = false;
                btn.textContent = 'Показать больше';
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
