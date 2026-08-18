(function () {
    'use strict';

    const Landing = window.UMKMLanding;
    if (!Landing) return;

    const state = {
        map: null,
        districtLayer: null,
        villageLayer: null,
        googleReady: null,
        loading: false,
        eventsBound: false,
        mapReady: false,
        geometryReady: false,
        interactionReady: false,
        hoveredCode: null,
        activeDistrictCode: '',
        activeVillageCode: '',
        lastDistrictPayload: null,
        lastVillagePayload: null,
        suppressMapRenderUntil: 0,
        lastFeatureClickAt: 0,
        mapInfoControl: null,
        mapActivePanel: null,
        mapHoverPanel: null
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

    function setLoadingState(message) {
        state.loading = true;
        state.geometryReady = false;
        state.interactionReady = false;
        state.hoveredCode = null;
        setReadyFlag(false);
        hideHoverPanel();
        setStatus(message || 'Memuat peta wilayah aktif...', 'loading');

        if (!state.lastDistrictPayload && !state.lastVillagePayload) {
            setText('[data-public-region-map-feature-count]', '0');
            setText('[data-public-region-map-total-umkm]', '0');
            setText('[data-public-region-map-density-max]', '0');
        }
    }

    function setReadyState() {
        state.loading = false;
        state.geometryReady = true;
        state.interactionReady = true;
        setReadyFlag(true);
        refreshLayerStyle();
    }

    function setFailedState(error, fallbackMessage) {
        state.loading = false;
        state.geometryReady = Boolean(state.lastDistrictPayload || state.lastVillagePayload);
        state.interactionReady = state.mapReady === true && state.geometryReady === true;
        setReadyFlag(state.interactionReady);
        refreshLayerStyle();
        setStatus(error && error.message ? error.message : (fallbackMessage || 'Peta wilayah belum dapat dimuat.'), 'warning');
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
            return Promise.reject(new Error('Peta belum dapat digunakan saat ini.'));
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

        return '#ef4444';
    }

    function blendHexColor(base, overlay, ratio) {
        const normalize = function (hex) {
            const value = String(hex || '').replace('#', '').trim();

            if (value.length === 3) {
                return value.split('').map(function (char) {
                    return char + char;
                }).join('');
            }

            return value.padEnd(6, '0').slice(0, 6);
        };

        const baseHex = normalize(base);
        const overlayHex = normalize(overlay);
        const safeRatio = Math.max(0, Math.min(1, Number(ratio) || 0));
        const read = function (hex, start) {
            return parseInt(hex.slice(start, start + 2), 16);
        };
        const mix = function (a, b) {
            return Math.round(a + ((b - a) * safeRatio));
        };
        const channel = function (value) {
            return value.toString(16).padStart(2, '0');
        };

        return '#'
            + channel(mix(read(baseHex, 0), read(overlayHex, 0)))
            + channel(mix(read(baseHex, 2), read(overlayHex, 2)))
            + channel(mix(read(baseHex, 4), read(overlayHex, 4)));
    }

    function districtFillColor(level, hasEmptyVillageWarning, cityContext) {
        const baseColor = densityColor(level);

        if (cityContext && hasEmptyVillageWarning) {
            return blendHexColor(baseColor, '#ef4444', 0.20);
        }

        return baseColor;
    }

    function densityOpacity(level, active, hovered, context) {
        if (hovered) return 0.78;
        if (active) return 0.72;
        if (context) return 0;

        const value = String(level || 'empty');

        if (value === 'high') return 0.62;
        if (value === 'medium') return 0.50;
        if (value === 'low') return 0.38;

        return 0.42;
    }

    function isInteractionReady() {
        return state.mapReady === true
            && state.geometryReady === true
            && state.interactionReady === true
            && state.loading !== true;
    }

    function shouldSuppressMapRender() {
        return state.suppressMapRenderUntil && Date.now() < state.suppressMapRenderUntil;
    }

    function districtStyle(feature) {
        const code = String(feature.getProperty('region_code') || '');
        const active = code !== '' && code === state.activeDistrictCode;
        const hovered = isInteractionReady() && code !== '' && code === state.hoveredCode;
        const cityContext = state.activeDistrictCode === '' && state.activeVillageCode === '';
        const level = String(feature.getProperty('density_level') || 'empty');
        const hasEmptyVillageWarning = Number(feature.getProperty('empty_village_count') || 0) > 0;

        return {
            fillColor: active ? '#f97316' : districtFillColor(level, hasEmptyVillageWarning, cityContext),
            fillOpacity: active ? 0 : densityOpacity(level, false, hovered, false),
            strokeColor: active || cityContext ? '#ea580c' : (hovered ? '#0f172a' : '#1e293b'),
            strokeOpacity: active || hovered || cityContext ? 0.98 : 0.54,
            strokeWeight: active ? 3.1 : (hovered ? 2.35 : (cityContext ? 1.6 : 1.1)),
            clickable: isInteractionReady(),
            zIndex: active ? 30 : 10
        };
    }

    function villageStyle(feature) {
        const code = String(feature.getProperty('region_code') || '');
        const active = code !== '' && code === state.activeVillageCode;
        const hovered = isInteractionReady() && code !== '' && code === state.hoveredCode;
        const level = String(feature.getProperty('density_level') || 'empty');

        return {
            fillColor: active ? '#f97316' : densityColor(level),
            fillOpacity: densityOpacity(level, active, hovered, false),
            strokeColor: active ? '#ea580c' : (hovered ? '#0f172a' : '#1e293b'),
            strokeOpacity: active || hovered ? 0.98 : 0.68,
            strokeWeight: active ? 3.1 : (hovered ? 2.35 : 1.25),
            clickable: isInteractionReady(),
            zIndex: active ? 80 : 50
        };
    }

    function refreshLayerStyle() {
        if (state.districtLayer) {
            state.districtLayer.revertStyle();
            state.districtLayer.setStyle(districtStyle);
        }

        if (state.villageLayer) {
            state.villageLayer.revertStyle();
            state.villageLayer.setStyle(villageStyle);
        }
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
            ensureMapInfoControls();
            state.districtLayer = new maps.Data({ map: state.map });
            state.villageLayer = new maps.Data({ map: state.map });
            state.mapReady = true;

            state.districtLayer.setStyle(districtStyle);
            state.villageLayer.setStyle(villageStyle);
            bindDataLayerEvents(state.districtLayer, 'district');
            bindDataLayerEvents(state.villageLayer, 'village');

            state.map.addListener('click', function () {
                if (!isInteractionReady()) return;

                if (Date.now() - state.lastFeatureClickAt < 250) {
                    return;
                }

                resetToCityMode();
            });

            return state.map;
        });
    }

    function bindDataLayerEvents(layer, level) {
        if (!layer) return;

        layer.addListener('mouseover', function (event) {
            if (!isInteractionReady()) return;

            state.hoveredCode = String(event.feature.getProperty('region_code') || '');
            refreshLayerStyle();

            if (level === 'district') {
                state.districtLayer.overrideStyle(event.feature, districtStyle(event.feature));
            } else {
                state.villageLayer.overrideStyle(event.feature, villageStyle(event.feature));
            }

            showHoverPanel(event.feature);
        });

        layer.addListener('mouseout', function () {
            state.hoveredCode = null;
            refreshLayerStyle();
            hideHoverPanel();
        });

        layer.addListener('click', function (event) {
            if (!isInteractionReady()) return;

            state.lastFeatureClickAt = Date.now();

            if (level === 'district') {
                activateDistrictFeature(event.feature);
            } else {
                activateVillageFeature(event.feature);
            }
        });
    }

    function ensureMapInfoControls() {
        if (!state.map || !(window.google && window.google.maps)) {
            return null;
        }

        if (state.mapInfoControl) {
            return state.mapInfoControl;
        }

        const stack = document.createElement('div');
        stack.className = 'umkm-map-info-stack';
        stack.setAttribute('aria-live', 'polite');

        const activePanel = document.createElement('div');
        activePanel.className = 'umkm-map-active-panel';
        activePanel.dataset.visible = 'true';
        activePanel.innerHTML = ''
            + '<div class="umkm-map-info-kicker">Wilayah aktif</div>'
            + '<div class="umkm-map-info-context" data-map-control-active-context hidden></div>'
            + '<div class="umkm-map-info-title" data-map-control-active-title>Kota Lubuklinggau</div>'
            + '<div class="umkm-map-info-meta">'
            + '<span data-map-control-active-layer>Tampilan Kota</span>'
            + '<span data-map-control-active-total>0 UMKM</span>'
            + '</div>';

        const hoverPanel = document.createElement('div');
        hoverPanel.className = 'umkm-map-hover-panel-control';
        hoverPanel.dataset.visible = 'false';
        hoverPanel.hidden = true;
        hoverPanel.innerHTML = ''
            + '<div class="umkm-map-info-kicker" data-map-control-hover-level>Wilayah</div>'
            + '<div class="umkm-map-info-title" data-map-control-hover-title>Wilayah</div>'
            + '<div class="umkm-map-info-meta">'
            + '<span data-map-control-hover-total>0 UMKM</span>'
            + '<span>Klik untuk memilih</span>'
            + '</div>';

        stack.appendChild(activePanel);
        stack.appendChild(hoverPanel);

        state.mapInfoControl = stack;
        state.mapActivePanel = activePanel;
        state.mapHoverPanel = hoverPanel;
        state.map.controls[window.google.maps.ControlPosition.TOP_LEFT].push(stack);

        return stack;
    }

    function setPanelText(root, selector, text) {
        if (!root) return;

        const element = root.querySelector(selector);
        if (element) {
            element.textContent = text == null ? '' : String(text);
        }
    }

    function umkmText(value) {
        const text = String(value == null || value === '' ? '0' : value);
        return text.indexOf('UMKM') >= 0 ? text : text + ' UMKM';
    }

    function layerLabelFromSelection(selection) {
        const safe = normalizeSelectionForMap(selection);
        if (safe.scope === 'village') return 'Kelurahan';
        if (safe.scope === 'district') return 'Kecamatan';
        return 'Kota';
    }

    function cleanDisplayName(name, prefix) {
        let value = String(name || '').trim();

        if (!value) return '';

        value = value.replace(new RegExp('^' + prefix + '\\s+', 'i'), '').trim();

        return value;
    }

    function isCodeLikeName(name, code) {
        const safeName = String(name || '').trim();
        const safeCode = String(code || '').trim();

        if (!safeName) return true;
        if (safeCode && safeName === safeCode) return true;

        return /^(\d{2}\.)+\d+$/u.test(safeName);
    }

    function districtNameFromLayer(code) {
        if (!state.districtLayer || !code) return '';

        let found = '';

        state.districtLayer.forEach(function (feature) {
            if (found) return;

            const featureCode = String(feature.getProperty('district_code') || feature.getProperty('region_code') || '');

            if (featureCode === String(code)) {
                found = String(feature.getProperty('district_name') || feature.getProperty('region_name') || '');
            }
        });

        return found;
    }

    function districtDisplayName(selection) {
        const safe = normalizeSelectionForMap(selection);
        const code = safe.district && safe.district.code ? String(safe.district.code) : '';
        let name = safe.district && safe.district.name ? String(safe.district.name) : '';

        if (isCodeLikeName(name, code)) {
            name = districtNameFromLayer(code) || name;
        }

        return cleanDisplayName(name || code || 'Wilayah', 'Kecamatan');
    }

    function villageDisplayName(selection) {
        const safe = normalizeSelectionForMap(selection);
        const code = safe.village && safe.village.code ? String(safe.village.code) : '';
        let name = safe.village && safe.village.name ? String(safe.village.name) : '';

        return cleanDisplayName(name || code || 'Wilayah', 'Kelurahan');
    }

    function activeInfoHierarchy(selection) {
        const safe = normalizeSelectionForMap(selection || { scope: 'city', label: 'Kota Lubuklinggau' });
        const cityName = 'Kota Lubuklinggau';

        if (safe.scope === 'village') {
            const districtName = districtDisplayName(safe);
            const villageName = villageDisplayName(safe);

            return {
                context: cityName + (districtName ? ' • Kecamatan ' + districtName : ''),
                title: 'Kelurahan ' + villageName
            };
        }

        if (safe.scope === 'district') {
            const districtName = districtDisplayName(safe);

            return {
                context: cityName,
                title: 'Kecamatan ' + districtName
            };
        }

        return {
            context: '',
            title: cityName
        };
    }

    function updateActiveInfoPanel(selection, totalText) {
        ensureMapInfoControls();

        const panel = state.mapActivePanel;
        if (!panel) return;

        const safe = normalizeSelectionForMap(selection || { scope: 'city', label: 'Kota Lubuklinggau' });
        const hierarchy = activeInfoHierarchy(safe);
        const contextElement = panel.querySelector('[data-map-control-active-context]');

        if (contextElement) {
            contextElement.textContent = hierarchy.context || '';
            contextElement.hidden = !hierarchy.context;
        }

        setPanelText(panel, '[data-map-control-active-title]', hierarchy.title);
        setPanelText(panel, '[data-map-control-active-layer]', 'Tampilan ' + layerLabelFromSelection(safe));
        setPanelText(panel, '[data-map-control-active-total]', umkmText(totalText));

        panel.hidden = false;
        panel.dataset.visible = 'true';
    }

    function showHoverPanel(feature) {
        if (!feature) return;

        ensureMapInfoControls();

        const name = String(feature.getProperty('region_name') || 'Wilayah');
        const level = feature.getProperty('region_level') === 'village' ? 'Kelurahan' : 'Kecamatan';
        const total = String(feature.getProperty('umkm_total_text') || '0');
        const density = densityLabel(feature.getProperty('density_level'));
        const panel = state.mapHoverPanel || qs('[data-public-region-map-hover-panel]');

        if (!panel) return;

        if (panel === state.mapHoverPanel) {
            setPanelText(panel, '[data-map-control-hover-level]', level + ' • ' + density);
            setPanelText(panel, '[data-map-control-hover-title]', level + ' ' + name);
            setPanelText(panel, '[data-map-control-hover-total]', umkmText(total));
        } else {
            setText('[data-public-region-map-hover-level]', level + ' • ' + density);
            setText('[data-public-region-map-hover-title]', level + ' ' + name);
            setText('[data-public-region-map-hover-total]', total);
        }

        panel.hidden = false;
        panel.dataset.visible = 'true';
    }

    function hideHoverPanel() {
        const oldPanel = qs('[data-public-region-map-hover-panel]');

        if (oldPanel) {
            oldPanel.dataset.visible = 'false';
            oldPanel.hidden = true;
        }

        if (state.mapHoverPanel) {
            state.mapHoverPanel.dataset.visible = 'false';
            state.mapHoverPanel.hidden = true;
        }
    }

    function densityLabel(level) {
        const value = String(level || 'empty');

        if (value === 'high') return 'jumlah banyak';
        if (value === 'medium') return 'jumlah sedang';
        if (value === 'low') return 'jumlah sedikit';

        return 'tanpa UMKM';
    }

    function clearLayer(layer) {
        if (!layer) return;

        layer.forEach(function (feature) {
            layer.remove(feature);
        });

        state.hoveredCode = null;
        hideHoverPanel();
    }

    function extendBoundsFromGeometry(bounds, geometry) {
        geometry.forEachLatLng(function (latLng) {
            bounds.extend(latLng);
        });
    }

    function layerBounds(layer) {
        if (!layer || !(window.google && window.google.maps)) return null;

        const bounds = new window.google.maps.LatLngBounds();
        let count = 0;

        layer.forEach(function (feature) {
            extendBoundsFromGeometry(bounds, feature.getGeometry());
            count += 1;
        });

        return count > 0 && !bounds.isEmpty() ? bounds : null;
    }

    function fitLayer(layer) {
        if (!state.map) return;

        const bounds = layerBounds(layer);
        if (bounds) {
            state.map.fitBounds(bounds, 28);
        }
    }

    function fitFeature(feature) {
        if (!state.map || !feature || !(window.google && window.google.maps)) return;

        const bounds = new window.google.maps.LatLngBounds();
        extendBoundsFromGeometry(bounds, feature.getGeometry());

        if (!bounds.isEmpty()) {
            state.map.fitBounds(bounds, 36);
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

    function selectionActiveCode(selection) {
        if (selection && selection.village && selection.village.code) return String(selection.village.code);
        if (selection && selection.district && selection.district.code) return String(selection.district.code);
        return '';
    }

    function districtGeometrySelection(selection) {
        const safe = normalizeSelectionForMap(selection);

        if (!safe.district || !safe.district.code) {
            return normalizeSelectionForMap({ scope: 'city' });
        }

        return normalizeSelectionForMap({
            scope: 'district',
            district: safe.district,
            district_code: safe.district.code,
            district_name: safe.district.name,
            districtAll: false,
            villageAll: true,
            village: null,
            label: 'Kecamatan ' + (safe.district.name || safe.district.code)
        });
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
            return Promise.reject(new Error('Informasi peta belum dapat dimuat. Silakan coba lagi.'));
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

            throw new Error('Bentuk wilayah pada peta belum dapat dimuat.');
        });
    }

    function updatePanel(payload) {
        const summary = payload.summary || {};
        const selection = normalizeSelectionForMap(payload.selection || {});
        const visibleLevel = layerLabelFromSelection(selection);
        const scopeLabel = selection.scope === 'village'
            ? 'Kelurahan aktif'
            : (selection.scope === 'district' ? 'Kecamatan aktif' : 'Kota aktif');

        setText('[data-public-region-map-title]', selection.label || 'Wilayah aktif');
        setText('[data-public-region-map-scope]', scopeLabel);
        const matchedText = summary.geometry_matched_total_text || summary.total_umkm_text || '0';
        const unmatched = Number(summary.geometry_unmatched_total_count || 0);
        const unmatchedWrap = document.querySelector('[data-public-region-map-unmatched-wrap]');

        setText('[data-public-region-map-feature-count]', String(summary.feature_count || 0));
        setText('[data-public-region-map-visible-level]', visibleLevel);
        setText('[data-public-region-map-total-umkm]', matchedText);
        setText('[data-public-region-map-operational-total]', summary.operational_total_text || matchedText);
        setText('[data-public-region-map-unmatched]', summary.geometry_unmatched_total_text || '0');
        setText('[data-public-region-map-density-max]', summary.max_umkm_text || '0');

        if (unmatchedWrap) {
            unmatchedWrap.classList.toggle('d-none', unmatched < 1);
        }

        updateActiveInfoPanel(selection, matchedText);
    }

    function updatePanelFromSelection(selection, feature) {
        const safe = normalizeSelectionForMap(selection);
        const scopeLabel = safe.scope === 'village'
            ? 'Kelurahan aktif'
            : (safe.scope === 'district' ? 'Kecamatan aktif' : 'Kota aktif');
        const total = feature ? String(feature.getProperty('umkm_total_text') || '0') : '0';

        const unmatchedWrap = document.querySelector('[data-public-region-map-unmatched-wrap]');

        setText('[data-public-region-map-title]', safe.label || 'Wilayah aktif');
        setText('[data-public-region-map-scope]', scopeLabel);
        setText('[data-public-region-map-total-umkm]', total);
        setText('[data-public-region-map-operational-total]', total);
        setText('[data-public-region-map-visible-level]', layerLabelFromSelection(safe));
        if (unmatchedWrap) unmatchedWrap.classList.add('d-none');
        updateActiveInfoPanel(safe, total);

        const payload = state.lastVillagePayload || state.lastDistrictPayload;
        if (payload && payload.summary) {
            const summary = payload.summary || {};
            setText('[data-public-region-map-feature-count]', String(summary.feature_count || 0));
            setText('[data-public-region-map-density-max]', summary.max_umkm_text || '0');
        }
    }

    function findFeatureByCode(layer, code) {
        if (!layer || !code) return null;

        let found = null;
        layer.forEach(function (feature) {
            if (found) return;

            if (String(feature.getProperty('region_code') || '') === String(code)) {
                found = feature;
            }
        });

        return found;
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
            const districtCode = String(feature.getProperty('district_code') || feature.getProperty('parent_code') || state.activeDistrictCode || '');
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

    function applySelectionFromMap(selection) {
        state.suppressMapRenderUntil = Date.now() + 5000;

        if (typeof Landing.applyRegionSelection === 'function') {
            return Promise.resolve(Landing.applyRegionSelection(selection));
        }

        document.dispatchEvent(new CustomEvent('umkm:landing-region:changed', {
            detail: {
                selection: selection,
                source: 'map'
            }
        }));

        return Promise.resolve();
    }

    function resetToCityMode() {
        if (state.loading) return;

        const selection = normalizeSelectionForMap({ scope: 'city', label: 'Kota Lubuklinggau' });

        hideHoverPanel();
        setLoadingState('Mengembalikan peta ke mode Kota Lubuklinggau...');

        state.activeDistrictCode = '';
        state.activeVillageCode = '';
        state.suppressMapRenderUntil = Date.now() + 5000;

        Promise.resolve()
            .then(function () {
                return applySelectionFromMap(selection).catch(function () { return null; });
            })
            .then(function () {
                clearLayer(state.villageLayer);
                state.lastVillagePayload = null;

                if (state.lastDistrictPayload) {
                    setReadyState();
                    updatePanel(state.lastDistrictPayload);
                    fitLayer(state.districtLayer);
                    refreshLayerStyle();
                    setStatus('Peta siap. Arahkan kursor ke wilayah untuk melihat ringkasan, atau klik kecamatan untuk membuka kelurahan.', 'success');
                    return null;
                }

                return loadDistrictLayer().then(function (districtPayload) {
                    setReadyState();
                    updatePanel(districtPayload);
                    fitLayer(state.districtLayer);
                    refreshLayerStyle();
                    setStatus('Peta siap. Arahkan kursor ke wilayah untuk melihat ringkasan, atau klik kecamatan untuk membuka kelurahan.', 'success');
                    return null;
                });
            })
            .catch(function (error) {
                setFailedState(error, 'Peta belum dapat dikembalikan ke mode kota.');
            })
            .finally(function () {
                state.suppressMapRenderUntil = 0;
            });
    }

    function loadDistrictLayer() {
        return requestGeometry({ scope: 'city' }).then(function (payload) {
            clearLayer(state.districtLayer);
            state.lastDistrictPayload = payload;
            state.districtLayer.addGeoJson(payload.geometry);
            return payload;
        });
    }

    function loadVillageLayer(selection) {
        const districtSelection = districtGeometrySelection(selection);

        if (!districtSelection.district || !districtSelection.district.code) {
            clearLayer(state.villageLayer);
            state.lastVillagePayload = null;
            return Promise.resolve(null);
        }

        return requestGeometry(districtSelection).then(function (payload) {
            clearLayer(state.villageLayer);
            state.lastVillagePayload = payload;
            state.villageLayer.addGeoJson(payload.geometry);
            return payload;
        });
    }

    function activateDistrictFeature(feature) {
        const selection = selectionFromFeature(feature);
        if (!selection || !selection.district || !selection.district.code) return;

        hideHoverPanel();
        setLoadingState('Memuat kelurahan dalam ' + selection.label + '...');

        state.activeDistrictCode = selection.district.code;
        state.activeVillageCode = '';
        refreshLayerStyle();

        Promise.all([
            applySelectionFromMap(selection).catch(function () { return null; }),
            loadVillageLayer(selection)
        ]).then(function (results) {
            const villagePayload = results[1];

            setReadyState();

            if (villagePayload) {
                updatePanel(villagePayload);
                updatePanelFromSelection(selection, feature);
                fitLayer(state.villageLayer);
            } else if (state.lastDistrictPayload) {
                updatePanel(state.lastDistrictPayload);
                fitLayer(state.districtLayer);
            }

            refreshLayerStyle();
            setStatus('Wilayah aktif: Kecamatan ' + districtDisplayName(selection) + '. Peta menampilkan kelurahan dalam kecamatan ini.', 'success');
        }).catch(function (error) {
            setFailedState(error, 'Kelurahan dalam kecamatan belum dapat dimuat.');
        }).finally(function () {
            state.suppressMapRenderUntil = 0;
        });
    }

    function activateVillageFeature(feature) {
        const selection = selectionFromFeature(feature);
        if (!selection || !selection.village || !selection.village.code) return;

        hideHoverPanel();

        if (selection.district && selection.district.code) {
            state.activeDistrictCode = selection.district.code;
        }

        state.activeVillageCode = selection.village.code;
        refreshLayerStyle();
        updatePanelFromSelection(selection, feature);
        fitFeature(feature);
        setStatus('Wilayah aktif: Kelurahan ' + villageDisplayName(selection) + '. Kelurahan lain tetap ditampilkan sebagai pembanding.', 'success');

        applySelectionFromMap(selection)
            .catch(function (error) {
                setStatus(error && error.message ? error.message : 'Wilayah belum dapat diaktifkan dari peta.', 'warning');
            })
            .finally(function () {
                state.suppressMapRenderUntil = 0;
                setReadyState();
            });
    }

    function renderFromSelection(selection) {
        if (state.loading) return;

        const normalized = normalizeSelectionForMap(selection || currentSelection());
        setLoadingState('Memuat peta wilayah aktif...');

        initMap()
            .then(function () {
                return loadDistrictLayer();
            })
            .then(function (districtPayload) {
                state.activeDistrictCode = normalized.district && normalized.district.code ? normalized.district.code : '';
                state.activeVillageCode = normalized.village && normalized.village.code ? normalized.village.code : '';

                if (normalized.scope === 'district' || normalized.scope === 'village') {
                    return loadVillageLayer(normalized).then(function (villagePayload) {
                        setReadyState();

                        if (villagePayload) {
                            updatePanel(villagePayload);
                            fitLayer(state.villageLayer);
                        } else {
                            updatePanel(districtPayload);
                            fitLayer(state.districtLayer);
                        }

                        if (normalized.scope === 'village') {
                            const feature = findFeatureByCode(state.villageLayer, normalized.village.code);
                            updatePanelFromSelection(normalized, feature);
                            setStatus('Wilayah aktif: ' + normalized.label + '. Kelurahan lain tetap ditampilkan sebagai pembanding.', 'success');
                        } else {
                            setStatus('Wilayah aktif: ' + normalized.label + '. Peta menampilkan kelurahan dalam kecamatan ini.', 'success');
                        }

                        refreshLayerStyle();
                    });
                }

                clearLayer(state.villageLayer);
                state.lastVillagePayload = null;
                setReadyState();
                updatePanel(districtPayload);
                fitLayer(state.districtLayer);
                setStatus('Peta siap. Arahkan kursor ke wilayah untuk melihat ringkasan, atau klik kecamatan untuk membuka kelurahan.', 'success');
            })
            .catch(function (error) {
                setFailedState(error, 'Peta wilayah belum dapat dimuat.');
            });
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
            text.indexOf('Informasi peta belum dapat dimuat') >= 0 ||
            text.indexOf('belum dapat dimuat') >= 0
        );
    }

    function scheduleInitialRender() {
        window.setTimeout(function () {
            renderFromSelection(currentSelection());
        }, 350);

        window.setTimeout(function () {
            if (shouldRetryInitialMap()) {
                renderFromSelection(currentSelection());
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
                if (shouldSuppressMapRender()) return;
                renderFromSelection(event.detail ? event.detail.selection : currentSelection());
            });

            document.addEventListener('umkm:landing-aggregate:ready', function () {
                if (shouldSuppressMapRender()) return;
                renderFromSelection(currentSelection());
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