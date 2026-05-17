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
        return typeof window.Tabulator === 'function';
    }

    function create(target, options) {
        const element = resolveElement(target);

        if (!element) {
            log('warn', 'Tabulator target not found.', { target: target });
            return null;
        }

        if (!isAvailable()) {
            element.dataset.umkmTabulator = 'missing';
            log('warn', 'Tabulator vendor is not loaded.');
            return null;
        }

        if (registry.has(element)) {
            return registry.get(element);
        }

        const settings = Object.assign({
            layout: 'fitColumns',
            reactiveData: false,
            placeholder: 'Data belum tersedia.',
            paginationSize: 10,
            movableColumns: false,
            resizableColumnFit: true
        }, options || {});

        const table = new window.Tabulator(element, settings);

        registry.set(element, table);
        element.dataset.umkmTabulator = 'initializing';

        if (typeof table.on === 'function') {
            table.on('tableBuilt', function () {
                element.dataset.umkmTabulator = 'ready';

                emit('tabulator:ready', {
                    element: element
                });
            });
        }

        emit('tabulator:init', {
            element: element
        });

        return table;
    }

    function get(target) {
        const element = resolveElement(target);

        if (!element || !registry.has(element)) {
            return null;
        }

        return registry.get(element);
    }

    function replaceData(target, data) {
        const table = get(target);

        if (!table || typeof table.replaceData !== 'function') {
            return null;
        }

        return table.replaceData(Array.isArray(data) ? data : []);
    }

    function redraw(target) {
        const table = get(target);

        if (table && typeof table.redraw === 'function') {
            table.redraw(true);
        }

        return table;
    }

    function destroy(target) {
        const element = resolveElement(target);
        const table = get(element);

        if (!element || !table) {
            return false;
        }

        if (typeof table.destroy === 'function') {
            table.destroy();
        }

        registry.delete(element);
        element.dataset.umkmTabulator = 'destroyed';

        emit('tabulator:destroy', {
            element: element
        });

        return true;
    }

    function state() {
        return {
            available: isAvailable(),
            version: window.Tabulator && window.Tabulator.prototype ? 'loaded' : null
        };
    }

    const api = {
        available: isAvailable,
        create: create,
        get: get,
        replaceData: replaceData,
        redraw: redraw,
        destroy: destroy,
        state: state
    };

    UMKM.state = UMKM.state || {};
    UMKM.state.tabulator = state();

    if (typeof UMKM.register === 'function') {
        UMKM.register('tabulator', api);
    } else {
        UMKM.tabulator = api;
    }

    log('info', 'tabulator bridge initialized', state());
})();
