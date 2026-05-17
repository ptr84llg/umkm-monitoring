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
        return Boolean(window.Vue && typeof window.Vue.createApp === 'function');
    }

    function createApp(component, props) {
        if (!isAvailable()) {
            log('warn', 'Vue vendor is not available.');
            return null;
        }

        return window.Vue.createApp(component || {}, props || {});
    }

    function mount(target, component, options) {
        const element = resolveElement(target);
        const settings = Object.assign({
            props: {},
            provide: {},
            globalProperties: {},
            plugins: []
        }, options || {});

        if (!element) {
            log('warn', 'Vue mount target not found.', { target: target });
            return null;
        }

        if (!isAvailable()) {
            element.dataset.umkmVue = 'missing';
            log('warn', 'Vue vendor is not loaded.');
            return null;
        }

        if (registry.has(element)) {
            return registry.get(element);
        }

        const app = createApp(component || {}, settings.props);

        Object.keys(settings.provide || {}).forEach(function (key) {
            app.provide(key, settings.provide[key]);
        });

        Object.keys(settings.globalProperties || {}).forEach(function (key) {
            app.config.globalProperties[key] = settings.globalProperties[key];
        });

        (settings.plugins || []).forEach(function (plugin) {
            if (plugin) {
                app.use(plugin);
            }
        });

        const instance = app.mount(element);

        const record = {
            app: app,
            instance: instance,
            element: element,
            mountedAt: new Date().toISOString()
        };

        registry.set(element, record);
        element.dataset.umkmVue = 'mounted';

        emit('vue:mounted', {
            element: element
        });

        return record;
    }

    function unmount(target) {
        const element = resolveElement(target);

        if (!element || !registry.has(element)) {
            return false;
        }

        const record = registry.get(element);

        if (record.app && typeof record.app.unmount === 'function') {
            record.app.unmount();
        }

        registry.delete(element);
        element.dataset.umkmVue = 'unmounted';

        emit('vue:unmounted', {
            element: element
        });

        return true;
    }

    function state() {
        return {
            available: isAvailable(),
            version: window.Vue && window.Vue.version ? window.Vue.version : null
        };
    }

    const api = {
        available: isAvailable,
        version: function () {
            return state().version;
        },
        createApp: createApp,
        mount: mount,
        unmount: unmount,
        state: state
    };

    UMKM.state = UMKM.state || {};
    UMKM.state.vue = state();

    if (typeof UMKM.register === 'function') {
        UMKM.register('vue', api);
    } else {
        UMKM.vue = api;
    }

    log('info', 'vue bridge initialized', state());
})();
