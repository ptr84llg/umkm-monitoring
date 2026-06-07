(function () {
    'use strict';

    const Landing = window.UMKMLanding;
    if (!Landing) return;

    const state = {
        map: null,
        dataLayer: null,
        infoWindow: null,
        googleReady: null,
        loading: false
    };

    function qs(selector, root) {
        return (root || document).querySelector(selector);
    }

    function setStatus(message, tone) {
        const container = qs('[data-public-google-region-map]');
        const status = qs('[data-public-region-map-state]', container);
        if (!status) return;
        status.textContent = message || '';
        status.dataset.tone = tone || 'info';
    }

    function setText(selector, text) {
        const element = qs(selector);
        if (element) element.textContent = text == null ? '' : String(text);
    }

    function endpoint() {
        const container = qs('[data-public-google-region-map]');
        return container ? (container.dataset.regionMapGeometryUrl || '/api/public/landing-region-map/geometry') : '/api/public/landing-region-map/geometry';
    }

    function apiKey() {
        const container = qs('[data-public-google-region-map]');
        return container ? (container.dataset.googleMapsKey || '') : '';
    }

    function mapId() {
        const container = qs('[data-public-google-region-map]');
        return container ? (container.dataset.googleMapsMapId || '') : '';
    }

    function loadGoogleMaps() {
        if (window.google && window.google.maps) {
            return Promise.resolve(window.google.maps);
        }

        if (state.googleReady) {
            return state.googleReady;
        }

        const key = apiKey();
        if (!key) {
            return Promise.reject(new Error('Kunci Google Maps belum tersedia.'));
        }

        state.googleReady = new Promise(function (resolve, reject) {
            const callback = '__umkmLandingGoogleRegionMapReady';
            const existing = document.querySelector('script[data-umkm-google-region-map="true"]');

            window[callback] = function () {
                resolve(window.google.maps);
            };

            if (existing) {
                return;
            }

            const script = document.createElement('script');
            const params = new URLSearchParams({
                key: key,
                v: 'weekly',
                callback: callback
            });

            if (mapId()) {
                params.set('map_ids', mapId());
            }

            script.src = 'https://maps.googleapis.com/maps/api/js?' + params.toString();
            script.async = true;
            script.defer = true;
            script.dataset.umkmGoogleRegionMap = 'true';
            script.onerror = function () {
                reject(new Error('Google Maps belum dapat dimuat.'));
            };

            document.head.appendChild(script);
        });

        return state.googleReady;
    }

    function initMap() {
        const canvas = qs('[data-public-google-region-map-canvas]');
        if (!canvas) {
            return Promise.reject(new Error('Wadah peta tidak ditemukan.'));
        }

        return loadGoogleMaps().then(function (maps) {
            if (state.map) return state.map;

            const options = {
                center: { lat: -3.296, lng: 102.878 },
                zoom: 11,
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: true,
                clickableIcons: false
            };

            if (mapId()) {
                options.mapId = mapId();
            }

            state.map = new maps.Map(canvas, options);
            state.dataLayer = new maps.Data({ map: state.map });
            state.infoWindow = new maps.InfoWindow();

            state.dataLayer.setStyle(function (feature) {
                const active = Boolean(feature.getProperty('active'));
                const level = String(feature.getProperty('region_level') || '');
                const order = Number(feature.getProperty('display_order') || 1);
                const alpha = active ? 0.42 : Math.min(0.30, 0.12 + ((order % 8) * 0.018));

                return {
                    fillColor: active ? '#f97316' : (level === 'village' ? '#2563eb' : '#16a34a'),
                    fillOpacity: alpha,
                    strokeColor: active ? '#ea580c' : '#0f172a',
                    strokeOpacity: active ? 0.95 : 0.58,
                    strokeWeight: active ? 2.5 : 1.25,
                    clickable: true
                };
            });

            state.dataLayer.addListener('click', function (event) {
                const name = event.feature.getProperty('region_name') || 'Wilayah';
                const level = event.feature.getProperty('region_level') === 'village' ? 'Kelurahan' : 'Kecamatan';
                state.infoWindow.setContent('<strong>' + level + '</strong><br>' + name);
                state.infoWindow.setPosition(event.latLng);
                state.infoWindow.open(state.map);
            });

            return state.map;
        });
    }

    function clearLayer() {
        if (!state.dataLayer) return;
        state.dataLayer.forEach(function (feature) {
            state.dataLayer.remove(feature);
        });
    }

    function extendBoundsFromGeometry(maps, bounds, geometry) {
        geometry.forEachLatLng(function (latLng) {
            bounds.extend(latLng);
        });
    }

    function fitToLayer() {
        if (!state.map || !state.dataLayer || !(window.google && window.google.maps)) return;

        const bounds = new window.google.maps.LatLngBounds();
        let count = 0;

        state.dataLayer.forEach(function (feature) {
            extendBoundsFromGeometry(window.google.maps, bounds, feature.getGeometry());
            count += 1;
        });

        if (count > 0 && !bounds.isEmpty()) {
            state.map.fitBounds(bounds, 28);
        }
    }

    function queryFromSelection(selection) {
        const safe = selection || {};
        const query = new URLSearchParams();
        query.set('scope', safe.scope || 'city');

        if (safe.district && safe.district.code) {
            query.set('district_code', safe.district.code);
            query.set('district_name', safe.district.name || '');
        }

        if (safe.village && safe.village.code) {
            query.set('village_code', safe.village.code);
            query.set('village_name', safe.village.name || '');
        }

        return query;
    }

    function requestGeometry(selection) {
        if (!(window.UMKM && window.UMKM.ajax && typeof window.UMKM.ajax.get === 'function')) {
            return Promise.reject(new Error('AJAX internal belum siap.'));
        }

        const url = endpoint() + '?' + queryFromSelection(selection).toString();

        return Promise.resolve(window.UMKM.ajax.get(url, {
            headers: {
                'X-UMKM-Request': 'internal',
                'X-UMKM-Internal-Request': '1',
                'X-UMKM-Map': 'region-geometry',
                'X-UMKM-Preview': 'landing-public-safe'
            }
        })).then(function (result) {
            const payload = Landing.unwrap ? Landing.unwrap(result) : result;
            if (!payload || payload.ok !== true || !payload.data) {
                throw new Error('Geometri wilayah belum dapat dimuat.');
            }

            return payload.data;
        });
    }

    function updatePanel(payload) {
        const summary = payload.summary || {};
        const selection = payload.selection || {};

        setText('[data-public-region-map-title]', selection.label || 'Wilayah aktif');
        setText('[data-public-region-map-scope]', selection.scope === 'village' ? 'Kelurahan aktif' : (selection.scope === 'district' ? 'Kecamatan aktif' : 'Kota aktif'));
        setText('[data-public-region-map-feature-count]', String(summary.feature_count || 0));
        setText('[data-public-region-map-visible-level]', summary.visible_level === 'village' ? 'Kelurahan' : 'Kecamatan');
    }

    function renderGeometry(selection) {
        if (state.loading) return;

        state.loading = true;
        setStatus('Memuat peta wilayah aktif...', 'loading');

        initMap()
            .then(function () { return requestGeometry(selection || Landing.regionState?.applied || Landing.DEFAULT_SELECTION); })
            .then(function (payload) {
                clearLayer();
                state.dataLayer.addGeoJson(payload.geometry);
                fitToLayer();
                updatePanel(payload);
                setStatus('Peta wilayah aktif siap.', 'success');
            })
            .catch(function (error) {
                setStatus(error && error.message ? error.message : 'Peta wilayah belum dapat dimuat.', 'warning');
            })
            .finally(function () {
                state.loading = false;
            });
    }

function selectionFromAggregatePayload() {
        const payload = window.PublicLandingAggregatePayload;
        const data = payload && payload.data ? payload.data : null;
        if (!data) return null;

        const selection = data.selection || {};
        const region = data.region || {};
        const safe = {
            scope: selection.scope || region.scope || 'city'
        };

        const districtCode = selection.district_code || region.district_code || '';
        const districtName = selection.district_name || region.district_name || '';
        const villageCode = selection.village_code || region.village_code || '';
        const villageName = selection.village_name || region.village_name || '';

        if (districtCode) {
            safe.scope = 'district';
            safe.district = {
                code: districtCode,
                name: districtName
            };
        }

        if (villageCode) {
            safe.scope = 'village';
            safe.village = {
                code: villageCode,
                name: villageName
            };
        }

        return safe;
    }

    function currentSelection() {
        if (Landing.regionState && Landing.regionState.applied) {
            return Landing.regionState.applied;
        }

        if (Landing.regionState && Landing.regionState.preview) {
            return Landing.regionState.preview;
        }

        return selectionFromAggregatePayload() || Landing.DEFAULT_SELECTION || { scope: 'city' };
    }

    function shouldRetryInitialMap() {
        const container = qs('[data-public-google-region-map]');
        const status = qs('[data-public-region-map-state]', container);
        const text = status ? String(status.textContent || '') : '';

        return !state.map && !state.loading && (
            text.indexOf('Menunggu') >= 0 ||
            text.indexOf('AJAX internal belum siap') >= 0 ||
            text.indexOf('belum dapat dimuat') >= 0
        );
    }

    function scheduleInitialRender() {
        window.setTimeout(function () {
            renderGeometry(currentSelection());
        }, 350);

        window.setTimeout(function () {
            if (shouldRetryInitialMap()) {
                renderGeometry(currentSelection());
            }
        }, 1400);
    }

function init() {
        const container = qs('[data-public-google-region-map]');
        if (!container) return;

        setStatus('Menunggu data wilayah aktif.', 'info');

        document.addEventListener('umkm:landing-region:changed', function (event) {
            renderGeometry(event.detail ? event.detail.selection : currentSelection());
        });

        document.addEventListener('umkm:landing-aggregate:ready', function () {
            renderGeometry(currentSelection());
        });

        scheduleInitialRender();
    }

Landing.ready(init);
})();
