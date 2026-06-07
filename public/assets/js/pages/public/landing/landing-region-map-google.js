(function () {
    'use strict';

    const Landing = window.UMKMLanding;
    if (!Landing) return;

    const state = {
        map: null,
        dataLayer: null,
        googleReady: null,
        loading: false,
        eventsBound: false,
        mapReady: false,
        geometryReady: false,
        interactionReady: false,
        hoveredCode: null,
        activeCode: null,
        lastPayload: null,
        pendingDirectSelection: null,
        pendingDirectSelectionUntil: 0
    };

    function qs(selector, root) {
        return (root || document).querySelector(selector);
    }

    function container() {
        return qs('[data-public-google-region-map]');
    }

    function setReadyFlag(value) {
        const mapContainer = container();

        if (mapContainer) {
            mapContainer.dataset.interactionReady = value ? 'true' : 'false';
        }

        const legend = qs('[data-public-region-map-legend]');
        if (legend) {
            legend.dataset.ready = value ? 'true' : 'false';
        }
    }

    function setStatus(message, tone) {
        const status = qs('[data-public-region-map-state]');

        if (!status) return;

        status.textContent = message || '';
        status.dataset.tone = tone || 'info';
    }

    function setText(selector, text) {
        const element = qs(selector);
        if (element) element.textContent = text == null ? '' : String(text);
    }

    function setLoadingState() {
        state.geometryReady = false;
        state.interactionReady = false;
        state.hoveredCode = null;
        setReadyFlag(false);
        hideHoverPanel();
        setStatus('Memuat peta wilayah aktif...', 'loading');

        if (!state.lastPayload) {
            setText('[data-public-region-map-feature-count]', '0');
            setText('[data-public-region-map-total-umkm]', '0');
            setText('[data-public-region-map-density-max]', '0');
        }
    }

    function endpoint() {
        const mapContainer = container();
        return mapContainer ? (mapContainer.dataset.regionMapGeometryUrl || '/api/public/landing-region-map/geometry') : '/api/public/landing-region-map/geometry';
    }

    function apiKey() {
        const mapContainer = container();
        return mapContainer ? (mapContainer.dataset.googleMapsKey || '') : '';
    }

    function mapId() {
        const mapContainer = container();
        return mapContainer ? (mapContainer.dataset.googleMapsMapId || '') : '';
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

    function densityColor(level) {
        const value = String(level || 'empty');

        if (value === 'high') return '#1d4ed8';
        if (value === 'medium') return '#3b82f6';
        if (value === 'low') return '#93c5fd';

        return '#e2e8f0';
    }

    function densityOpacity(level, active, hovered) {
        if (hovered) return 0.74;
        if (active) return 0.70;

        const value = String(level || 'empty');

        if (value === 'high') return 0.62;
        if (value === 'medium') return 0.50;
        if (value === 'low') return 0.38;

        return 0.20;
    }

    function isInteractionReady() {
        return state.mapReady === true
            && state.geometryReady === true
            && state.interactionReady === true
            && state.loading !== true;
    }

    function featureStyle(feature) {
        const code = String(feature.getProperty('region_code') || '');
        const active = Boolean(feature.getProperty('active')) || code === state.activeCode;
        const hovered = isInteractionReady() && code !== '' && code === state.hoveredCode;
        const level = String(feature.getProperty('density_level') || 'empty');

        return {
            fillColor: active ? '#f97316' : densityColor(level),
            fillOpacity: densityOpacity(level, active, hovered),
            strokeColor: active ? '#ea580c' : (hovered ? '#0f172a' : '#1e293b'),
            strokeOpacity: active || hovered ? 0.98 : 0.62,
            strokeWeight: active ? 2.8 : (hovered ? 2.35 : 1.2),
            clickable: isInteractionReady()
        };
    }

    function refreshLayerStyle() {
        if (!state.dataLayer) return;
        state.dataLayer.revertStyle();
        state.dataLayer.setStyle(featureStyle);
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
            state.mapReady = true;
            state.dataLayer.setStyle(featureStyle);

            state.dataLayer.addListener('mouseover', function (event) {
                if (!isInteractionReady()) return;

                state.hoveredCode = String(event.feature.getProperty('region_code') || '');
                state.dataLayer.revertStyle();
                state.dataLayer.overrideStyle(event.feature, featureStyle(event.feature));
                showHoverPanel(event.feature);
            });

            state.dataLayer.addListener('mouseout', function () {
                if (!state.dataLayer) return;

                state.hoveredCode = null;
                refreshLayerStyle();
                hideHoverPanel();
            });

            state.dataLayer.addListener('click', function (event) {
                if (!isInteractionReady()) return;
                activateFeature(event.feature);
            });

            return state.map;
        });
    }

    function showHoverPanel(feature) {
        const panel = qs('[data-public-region-map-hover-panel]');
        if (!panel || !feature) return;

        const name = String(feature.getProperty('region_name') || 'Wilayah');
        const level = feature.getProperty('region_level') === 'village' ? 'Kelurahan' : 'Kecamatan';
        const total = String(feature.getProperty('umkm_total_text') || '0');
        const density = densityLabel(feature.getProperty('density_level'));

        setText('[data-public-region-map-hover-level]', level + ' • ' + density);
        setText('[data-public-region-map-hover-title]', level + ' ' + name);
        setText('[data-public-region-map-hover-total]', total);

        panel.hidden = false;
        panel.dataset.visible = 'true';
    }

    function hideHoverPanel() {
        const panel = qs('[data-public-region-map-hover-panel]');
        if (!panel) return;

        panel.dataset.visible = 'false';
        panel.hidden = true;
    }

    function densityLabel(level) {
        const value = String(level || 'empty');

        if (value === 'high') return 'kepadatan tinggi';
        if (value === 'medium') return 'kepadatan sedang';
        if (value === 'low') return 'kepadatan rendah';

        return 'belum ada data';
    }

    function clearLayer() {
        if (!state.dataLayer) return;

        state.dataLayer.forEach(function (feature) {
            state.dataLayer.remove(feature);
        });

        state.hoveredCode = null;
        state.activeCode = null;
        hideHoverPanel();
    }

    function extendBoundsFromGeometry(bounds, geometry) {
        geometry.forEachLatLng(function (latLng) {
            bounds.extend(latLng);
        });
    }

    function fitToLayer() {
        if (!state.map || !state.dataLayer || !(window.google && window.google.maps)) return;

        const bounds = new window.google.maps.LatLngBounds();
        let count = 0;

        state.dataLayer.forEach(function (feature) {
            extendBoundsFromGeometry(bounds, feature.getGeometry());
            count += 1;
        });

        if (count > 0 && !bounds.isEmpty()) {
            state.map.fitBounds(bounds, 28);
        }
    }

    function getNestedCode(source, objectKey, codeKeys) {
        const object = source && source[objectKey] && typeof source[objectKey] === 'object' ? source[objectKey] : null;
        if (!object) return '';

        for (let i = 0; i < codeKeys.length; i += 1) {
            const value = object[codeKeys[i]];
            if (value) return String(value);
        }

        return '';
    }

    function getNestedName(source, objectKey, nameKeys) {
        const object = source && source[objectKey] && typeof source[objectKey] === 'object' ? source[objectKey] : null;
        if (!object) return '';

        for (let i = 0; i < nameKeys.length; i += 1) {
            const value = object[nameKeys[i]];
            if (value) return String(value);
        }

        return '';
    }

    function firstString(source, keys) {
        if (!source) return '';

        for (let i = 0; i < keys.length; i += 1) {
            const value = source[keys[i]];
            if (value) return String(value);
        }

        return '';
    }

    function normalizeSelectionForMap(input) {
        const safe = input && typeof input === 'object' ? input : {};
        const region = safe.region && typeof safe.region === 'object' ? safe.region : {};
        const base = Object.assign({}, Landing.DEFAULT_SELECTION || {}, safe);

        let scope = String(safe.scope || region.scope || 'city');

        let districtCode = getNestedCode(safe, 'district', ['code', 'district_code', 'region_code']);
        let districtName = getNestedName(safe, 'district', ['name', 'district_name', 'region_name', 'label']);
        let villageCode = getNestedCode(safe, 'village', ['code', 'village_code', 'region_code']);
        let villageName = getNestedName(safe, 'village', ['name', 'village_name', 'region_name', 'label']);

        districtCode = districtCode || firstString(safe, ['district_code', 'districtCode', 'selected_district_code']);
        districtName = districtName || firstString(safe, ['district_name', 'districtName', 'selected_district_name']);
        villageCode = villageCode || firstString(safe, ['village_code', 'villageCode', 'selected_village_code']);
        villageName = villageName || firstString(safe, ['village_name', 'villageName', 'selected_village_name']);

        districtCode = districtCode || firstString(region, ['district_code', 'districtCode']);
        districtName = districtName || firstString(region, ['district_name', 'districtName']);
        villageCode = villageCode || firstString(region, ['village_code', 'villageCode']);
        villageName = villageName || firstString(region, ['village_name', 'villageName']);

        if (villageCode) {
            scope = 'village';
        } else if (districtCode) {
            scope = 'district';
        } else if (scope !== 'city' && scope !== 'district' && scope !== 'village') {
            scope = 'city';
        }

        const normalized = Object.assign({}, base, {
            scope: scope,
            district: null,
            village: null,
            districtAll: false,
            villageAll: false
        });

        if (districtCode) {
            normalized.district = {
                code: districtCode,
                name: districtName || districtCode,
                level: 'district',
                isVirtual: false,
                hasPublicUmkmData: safe.hasPublicUmkmData == null ? null : safe.hasPublicUmkmData
            };
        }

        if (villageCode) {
            normalized.village = {
                code: villageCode,
                name: villageName || villageCode,
                level: 'village',
                isVirtual: false,
                hasPublicUmkmData: safe.hasPublicUmkmData == null ? null : safe.hasPublicUmkmData
            };
        }

        if (scope === 'city') {
            normalized.label = firstString(safe, ['label']) || firstString(region, ['label', 'name']) || 'Kota Lubuklinggau';
            normalized.districtAll = true;
            normalized.villageAll = true;
        } else if (scope === 'district') {
            normalized.label = 'Kecamatan ' + (districtName || districtCode || 'Wilayah');
            normalized.districtAll = false;
            normalized.villageAll = true;
            normalized.village = null;
        } else {
            normalized.label = 'Kelurahan ' + (villageName || villageCode || 'Wilayah');
            normalized.districtAll = false;
            normalized.villageAll = false;
        }

        return normalized;
    }

    function selectionHasSpecificRegion(selection) {
        return Boolean(selection && (
            (selection.district && selection.district.code) ||
            (selection.village && selection.village.code)
        ));
    }

    function selectionForRender(input) {
        const normalized = normalizeSelectionForMap(input || currentSelection());
        const pending = state.pendingDirectSelection;

        if (pending && Date.now() < state.pendingDirectSelectionUntil) {
            if (!selectionHasSpecificRegion(normalized) && selectionHasSpecificRegion(pending)) {
                return pending;
            }
        }

        return normalized;
    }

    function selectionActiveCode(selection) {
        if (selection && selection.village && selection.village.code) return String(selection.village.code);
        if (selection && selection.district && selection.district.code) return String(selection.district.code);
        return '';
    }

    function queryFromSelection(selection) {
        const safe = normalizeSelectionForMap(selection);
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

            if (payload && payload.ok === true && payload.data) {
                return payload.data;
            }

            if (payload && payload.geometry && payload.summary) {
                return payload;
            }

            throw new Error('Geometri wilayah belum dapat dimuat.');
        });
    }

    function updatePanel(payload) {
        const summary = payload.summary || {};
        const selection = payload.selection || {};
        const visibleLevel = summary.visible_level === 'village' ? 'Kelurahan' : 'Kecamatan';
        const scopeLabel = selection.scope === 'village'
            ? 'Kelurahan aktif'
            : (selection.scope === 'district' ? 'Kecamatan aktif' : 'Kota aktif');

        setText('[data-public-region-map-title]', selection.label || 'Wilayah aktif');
        setText('[data-public-region-map-scope]', scopeLabel);
        setText('[data-public-region-map-feature-count]', String(summary.feature_count || 0));
        setText('[data-public-region-map-visible-level]', visibleLevel);
        setText('[data-public-region-map-total-umkm]', summary.total_umkm_text || '0');
        setText('[data-public-region-map-density-max]', summary.max_umkm_text || '0');
    }

    function updatePanelFromSelection(selection, feature) {
        const safe = normalizeSelectionForMap(selection);
        const scopeLabel = safe.scope === 'village'
            ? 'Kelurahan aktif'
            : (safe.scope === 'district' ? 'Kecamatan aktif' : 'Kota aktif');
        const total = feature ? String(feature.getProperty('umkm_total_text') || '0') : '0';

        setText('[data-public-region-map-title]', safe.label || 'Wilayah aktif');
        setText('[data-public-region-map-scope]', scopeLabel);
        setText('[data-public-region-map-total-umkm]', total);
    }

    function selectionFromFeature(feature) {
        if (!feature) return null;

        const level = String(feature.getProperty('region_level') || '');
        const code = String(feature.getProperty('region_code') || '');
        const name = String(feature.getProperty('region_name') || code || 'Wilayah');
        const count = Number(feature.getProperty('umkm_total') || 0);

        if (!code || (level !== 'district' && level !== 'village')) {
            return null;
        }

        const selection = Object.assign({}, Landing.DEFAULT_SELECTION || {}, {
            scope: level,
            label: level === 'village' ? 'Kelurahan ' + name : 'Kecamatan ' + name,
            hasPublicUmkmData: count > 0,
            source: 'map'
        });

        if (level === 'district') {
            selection.district = {
                code: code,
                name: name,
                level: 'district',
                isVirtual: false,
                hasPublicUmkmData: count > 0
            };
            selection.village = null;
            selection.districtAll = false;
            selection.villageAll = true;
        }

        if (level === 'village') {
            const districtCode = String(feature.getProperty('district_code') || feature.getProperty('parent_code') || '');
            const districtName = String(feature.getProperty('district_name') || districtCode || 'Kecamatan');

            selection.district = districtCode ? {
                code: districtCode,
                name: districtName,
                level: 'district',
                isVirtual: false,
                hasPublicUmkmData: null
            } : null;
            selection.village = {
                code: code,
                name: name,
                level: 'village',
                isVirtual: false,
                hasPublicUmkmData: count > 0
            };
            selection.districtAll = false;
            selection.villageAll = false;
        }

        return normalizeSelectionForMap(selection);
    }

    function activateFeature(feature) {
        const selection = selectionFromFeature(feature);
        if (!selection) return;

        state.pendingDirectSelection = selection;
        state.pendingDirectSelectionUntil = Date.now() + 3000;
        state.interactionReady = false;
        state.activeCode = selectionActiveCode(selection);
        setReadyFlag(false);
        hideHoverPanel();
        refreshLayerStyle();
        updatePanelFromSelection(selection, feature);
        setStatus('Mengaktifkan ' + selection.label + '...', 'loading');

        const finishWithDirectRender = function () {
            window.setTimeout(function () {
                renderGeometry(selection);
            }, 80);
        };

        if (typeof Landing.applyRegionSelection === 'function') {
            Promise.resolve(Landing.applyRegionSelection(selection))
                .then(finishWithDirectRender)
                .catch(function (error) {
                    setStatus(error && error.message ? error.message : 'Wilayah belum dapat diaktifkan dari peta.', 'warning');
                    state.interactionReady = state.geometryReady === true && state.mapReady === true;
                    setReadyFlag(state.interactionReady);
                });
            return;
        }

        document.dispatchEvent(new CustomEvent('umkm:landing-region:changed', {
            detail: {
                selection: selection,
                source: 'map'
            }
        }));
        finishWithDirectRender();
    }

    function renderGeometry(selection) {
        if (state.loading) return;

        const renderSelection = selectionForRender(selection);

        state.loading = true;
        setLoadingState();

        initMap()
            .then(function () { return requestGeometry(renderSelection); })
            .then(function (payload) {
                clearLayer();
                state.lastPayload = payload;
                state.dataLayer.addGeoJson(payload.geometry);
                state.activeCode = activeRegionCodeFromPayload(payload) || selectionActiveCode(renderSelection);
                fitToLayer();
                updatePanel(payload);
                state.geometryReady = true;
                state.interactionReady = true;
                state.pendingDirectSelection = null;
                state.pendingDirectSelectionUntil = 0;
                setReadyFlag(true);
                refreshLayerStyle();
                setStatus('Peta siap. Arahkan kursor ke wilayah untuk melihat ringkasan, atau klik wilayah untuk mengaktifkan filter.', 'success');
            })
            .catch(function (error) {
                state.geometryReady = false;
                state.interactionReady = false;
                setReadyFlag(false);

                if (state.lastPayload) {
                    updatePanel(state.lastPayload);
                }

                setStatus(error && error.message ? error.message : 'Peta wilayah belum dapat dimuat.', 'warning');
            })
            .finally(function () {
                state.loading = false;
            });
    }

    function activeRegionCodeFromPayload(payload) {
        const features = payload && payload.geometry && Array.isArray(payload.geometry.features)
            ? payload.geometry.features
            : [];
        const activeFeature = features.find(function (feature) {
            return feature && feature.properties && feature.properties.active === true;
        });

        return activeFeature && activeFeature.properties ? String(activeFeature.properties.region_code || '') : '';
    }

    function selectionFromAggregatePayload() {
        const payload = window.PublicLandingAggregatePayload;
        const data = payload && payload.data ? payload.data : null;
        if (!data) return null;

        const selection = data.selection || {};
        const region = data.region || {};

        return normalizeSelectionForMap(Object.assign({}, selection, {
            region: region
        }));
    }

    function currentSelection() {
        if (Landing.regionState && Landing.regionState.applied) {
            return normalizeSelectionForMap(Landing.regionState.applied);
        }

        if (Landing.regionState && Landing.regionState.preview) {
            return normalizeSelectionForMap(Landing.regionState.preview);
        }

        return selectionFromAggregatePayload() || normalizeSelectionForMap(Landing.DEFAULT_SELECTION || { scope: 'city' });
    }

    function shouldRetryInitialMap() {
        const status = qs('[data-public-region-map-state]');
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
        let observer = null;

        function bindRegionMapEvents() {
            if (state.eventsBound === true) {
                return;
            }

            state.eventsBound = true;

            document.addEventListener('umkm:landing-region:changed', function (event) {
                renderGeometry(event.detail ? event.detail.selection : currentSelection());
            });

            document.addEventListener('umkm:landing-aggregate:ready', function () {
                renderGeometry(currentSelection());
            });

            document.addEventListener('umkm:component-loader:loaded', function (event) {
                const component = event.detail ? event.detail.component : '';
                if (component === 'landing-hero-preview-board') {
                    bootRegionMap();
                }
            });
        }

        function bootRegionMap() {
            const mapContainer = container();
            if (!mapContainer) {
                return false;
            }

            if (mapContainer.dataset.publicRegionMapBooted === 'true') {
                return true;
            }

            mapContainer.dataset.publicRegionMapBooted = 'true';
            setReadyFlag(false);
            hideHoverPanel();
            setStatus('Menunggu data wilayah aktif.', 'info');
            bindRegionMapEvents();
            scheduleInitialRender();

            if (observer) {
                observer.disconnect();
                observer = null;
            }

            return true;
        }

        if (bootRegionMap()) {
            return;
        }

        bindRegionMapEvents();

        observer = new MutationObserver(function () {
            bootRegionMap();
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    Landing.ready(init);
})();
