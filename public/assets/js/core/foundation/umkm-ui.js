(function () {
    'use strict';

    window.UMKM = window.UMKM || {};

    const UMKM = window.UMKM;

    UMKM.version = UMKM.version || '0.2.0';
    UMKM.modules = UMKM.modules || {};
    UMKM.state = UMKM.state || {};
    UMKM.events = UMKM.events || {};

    UMKM.config = Object.assign({
        appName: 'Monitoring UMKM',
        client: document.querySelector('meta[name="umkm-client"]')?.getAttribute('content') || 'web',
        profile: document.querySelector('meta[name="umkm-security-profile"]')?.getAttribute('content') || 'default',
        isLocal: ['localhost', '127.0.0.1', '::1'].includes(window.location.hostname),
        debug: window.UMKM_DEBUG === true || document.querySelector('meta[name="umkm-debug"]')?.getAttribute('content') === 'true'
    }, UMKM.config || {});

    UMKM.events.emit = function (name, detail) {
        document.dispatchEvent(new CustomEvent(`umkm:${name}`, {
            detail: detail || {}
        }));
    };

    UMKM.events.on = function (name, callback) {
        document.addEventListener(`umkm:${name}`, function (event) {
            callback(event.detail || {}, event);
        });
    };

    UMKM.register = function (name, api) {
        UMKM.modules[name] = {
            name: name,
            ready: true,
            loadedAt: new Date().toISOString()
        };

        if (api && typeof api === 'object') {
            UMKM[name] = api;
        }

        UMKM.events.emit('module:ready', {
            name: name
        });

        UMKM.log('info', `module ready: ${name}`);
    };

    UMKM.log = function (level, message, data) {
        if (!UMKM.config.debug) {
            return;
        }

        const method = typeof console[level || 'log'] === 'function' ? level || 'log' : 'log';
        const tag = '%c[UMKM]';
        const style = 'background:#0f7665;color:#fff;border-radius:6px;padding:2px 6px;font-weight:700;';

        if (data !== undefined) {
            console[method](tag, style, message, data);
        } else {
            console[method](tag, style, message);
        }
    };

    UMKM.ready = function (callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
            return;
        }

        callback();
    };

    UMKM.ready(function () {
        document.documentElement.setAttribute('data-umkm-core', 'ready');

        UMKM.state.core = {
            ready: true,
            loadedAt: new Date().toISOString(),
            client: UMKM.config.client,
            profile: UMKM.config.profile
        };

        UMKM.events.emit('core:ready', UMKM.state.core);
        UMKM.log('info', 'core initialized', UMKM.state.core);
    });

    UMKM.register('ui', {
        version: UMKM.version,
        config: UMKM.config,
        state: UMKM.state,
        modules: UMKM.modules,
        on: UMKM.events.on,
        emit: UMKM.events.emit,
        log: UMKM.log
    });
})();
