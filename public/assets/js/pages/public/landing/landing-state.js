(function () {
    'use strict';

    window.UMKM = window.UMKM || {};
    window.UMKMLanding = window.UMKMLanding || {};

    const Landing = window.UMKMLanding;
    const UMKM = window.UMKM;

    Landing.UMKM = UMKM;

    Landing.SELECTORS = {
        header: '[data-landing-header]',
        reveal: '.reveal',
        parallax: '[data-parallax]',
        counter: '.count-up',
        tiltCard: '[data-tilt-card]',
        menuCanvas: '[data-menu-canvas]',
        menuClose: '[data-menu-close], [data-menu-link]',
        toTop: '[data-to-top]',
        chartTab: '[data-chart-mode]',
        chartCanvas: '#landingMainChart',
        mainChartTitle: '#mainChartTitle',
        mainChartSubtitle: '#mainChartSubtitle',
        chartSummaryOne: '#chartSummaryOne',
        chartSummaryTwo: '#chartSummaryTwo',
        chartSummaryThree: '#chartSummaryThree',
        publicChartRegion: '[data-public-chart-region]',
        publicRegionSource: '[data-public-region-source]',
        publicWatchedLabel: '[data-public-watched-label]',
        publicDominantLabel: '[data-public-dominant-label]',
        publicFieldList: '[data-public-field-list]',
        publicAreaList: '[data-public-area-list]',
        publicEmptyState: '[data-public-empty-state]',
        publicEmptyTitle: '[data-public-empty-title]',
        publicEmptyMessage: '[data-public-empty-message]',
        regionModalShell: '[data-region-modal]',
        regionModalPanel: '[data-region-modal] [data-region-panel], [data-region-modal] .landing-region-modal',
        regionModalOpen: '[data-region-open], [data-region-modal-open]',
        regionModalClose: '[data-region-close], [data-region-modal-close]',
        regionModalApply: '[data-region-apply], [data-region-modal-apply]',
        regionModalAlert: '[data-region-alert], [data-region-modal-alert]',
        regionModalCurrent: '[data-region-current], [data-region-modal-current]',
        provinceSelect: '[data-region-province], [data-landing-region-province]',
        citySelect: '[data-region-city], [data-landing-region-city]',
        districtSelect: '[data-region-district], [data-landing-region-district]',
        villageSelect: '[data-region-village], [data-landing-region-village]'
    };

    Landing.API = {
        regionContext: '/api/public/landing-regions/context',
        regionChildren: '/api/public/landing-regions/children',
        previewData: '/api/public/landing-preview/data'
    };

    Landing.DEFAULT_CONTEXT = {
        province: { code: '16', name: 'Sumatera Selatan', level: 'province' },
        city: { code: '16.73', name: 'Kota Lubuklinggau', level: 'city' },
        options: {
            district_all: { code: '__ALL_DISTRICTS__', name: 'Semua Kecamatan', level: 'district', is_virtual: true },
            village_all: { code: '__ALL_VILLAGES__', name: 'Semua Kelurahan', level: 'village', is_virtual: true }
        }
    };

    Landing.DEFAULT_SELECTION = {
        province: Landing.DEFAULT_CONTEXT.province,
        city: Landing.DEFAULT_CONTEXT.city,
        district: null,
        village: null,
        districtAll: true,
        villageAll: true,
        hasPublicUmkmData: null,
        label: 'Kota Lubuklinggau',
        scope: 'city'
    };

    Landing.regionState = {
        loading: false,
        contextLoaded: false,
        context: Landing.DEFAULT_CONTEXT,
        districts: [],
        villages: [],
        applied: Object.assign({}, Landing.DEFAULT_SELECTION),
        preview: null,
        previewLoading: false
    };

    Landing.mainChart = null;
    Landing.activeMode = 'kinerja';
    Landing.chartResponsiveBound = false;

    Landing.ready = function (callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
            return;
        }

        callback();
    };

    Landing.qs = function (selector, root) {
        return (root || document).querySelector(selector);
    };

    Landing.qsa = function (selector, root) {
        return Array.from((root || document).querySelectorAll(selector));
    };

    Landing.setText = function (selector, value, root) {
        const element = Landing.qs(selector, root);

        if (element) {
            element.textContent = value == null ? '' : String(value);
        }
    };

    Landing.formatNumber = function (value) {
        return Number(value || 0).toLocaleString('id-ID');
    };

    Landing.cleanRegionLabel = function (value) {
        return String(value || '').replace(/Sumber\s+data\s*:\s*/gi, '').trim();
    };

    Landing.cleanName = function (value) {
        return String(value || '')
            .replace(/^Kecamatan\s+/i, '')
            .replace(/^Kelurahan\s+/i, '')
            .replace(/^Desa\s+/i, '')
            .trim();
    };

    Landing.log = function (level, message, data) {
        if (UMKM.log && typeof UMKM.log === 'function') {
            UMKM.log(level, message, data);
        }
    };

    Landing.emit = function (name, detail) {
        document.dispatchEvent(new CustomEvent(name, {
            detail: detail || {}
        }));
    };

    Landing.ajaxReady = function () {
        return Boolean(UMKM.ajax && typeof UMKM.ajax.get === 'function');
    };

    Landing.unwrap = function (result) {
        if (!result) {
            return null;
        }

        if (result.payload && typeof result.payload === 'object') {
            return result.payload;
        }

        return result;
    };

    Landing.resultOk = function (result) {
        return Boolean(result && result.ok);
    };
})();
