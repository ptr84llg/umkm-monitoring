(function () {
    'use strict';

    window.UMKM = window.UMKM || {};

    const UMKM = window.UMKM;
    const registry = new WeakMap();

    function emit(name, detail) {
        if (UMKM.events && typeof UMKM.events.emit === 'function') {
            UMKM.events.emit(name, detail || {});
            return;
        }

        document.dispatchEvent(new CustomEvent('umkm:' + name, {
            detail: detail || {}
        }));
    }

    function log(level, message, data) {
        if (typeof UMKM.log === 'function') {
            UMKM.log(level, message, data);
        }
    }

    function resolveElement(target) {
        if (!target) {
            return null;
        }

        if (typeof target === 'string') {
            return document.querySelector(target);
        }

        if (target instanceof Element) {
            return target;
        }

        return null;
    }

    function isAvailable() {
        return Boolean(window.echarts && typeof window.echarts.init === 'function');
    }

    function init(target, option, options) {
        const element = resolveElement(target);
        const settings = Object.assign({
            theme: null,
            initOptions: {},
            replace: true,
            loading: false
        }, options || {});

        if (!element) {
            log('warn', 'ECharts target not found.', { target: target });
            return null;
        }

        if (!isAvailable()) {
            element.dataset.umkmEcharts = 'missing';
            log('warn', 'ECharts vendor is not loaded.');
            return null;
        }

        if (registry.has(element)) {
            const existing = registry.get(element);

            if (option) {
                existing.setOption(option, Boolean(settings.replace));
            }

            return existing;
        }

        const chart = window.echarts.init(element, settings.theme, settings.initOptions || {});

        if (settings.loading && typeof chart.showLoading === 'function') {
            chart.showLoading();
        }

        if (option) {
            chart.setOption(option, Boolean(settings.replace));
        }

        registry.set(element, chart);
        element.dataset.umkmEcharts = 'ready';

        emit('echarts:ready', {
            element: element
        });

        return chart;
    }

    function setOption(target, option, options) {
        const element = resolveElement(target);
        const chart = element && registry.has(element) ? registry.get(element) : init(element, null, options);

        if (!chart || !option) {
            return null;
        }

        chart.setOption(option, Boolean(options && options.replace));

        emit('echarts:update', {
            element: element
        });

        return chart;
    }

    function get(target) {
        const element = resolveElement(target);

        if (!element || !registry.has(element)) {
            return null;
        }

        return registry.get(element);
    }

    function resize(target) {
        const element = resolveElement(target);
        const chart = get(element);

        if (chart && typeof chart.resize === 'function') {
            chart.resize();

            emit('echarts:resize', {
                element: element
            });
        }

        return chart;
    }

    function dispose(target) {
        const element = resolveElement(target);
        const chart = get(element);

        if (!element || !chart) {
            return false;
        }

        if (typeof chart.dispose === 'function') {
            chart.dispose();
        }

        registry.delete(element);
        element.dataset.umkmEcharts = 'disposed';

        emit('echarts:dispose', {
            element: element
        });

        return true;
    }

    function state() {
        return {
            available: isAvailable(),
            version: window.echarts && window.echarts.version ? window.echarts.version : null
        };
    }

    const api = {
        available: isAvailable,
        version: function () {
            return state().version;
        },
        init: init,
        setOption: setOption,
        get: get,
        resize: resize,
        dispose: dispose,
        state: state
    };

    UMKM.state = UMKM.state || {};
    UMKM.state.echarts = state();

    if (typeof UMKM.register === 'function') {
        UMKM.register('echarts', api);
    } else {
        UMKM.echarts = api;
    }

    log('info', 'echarts bridge initialized', state());
})();
