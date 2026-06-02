(function () {
    'use strict';

    window.UMKM = window.UMKM || {};

    const UMKM = window.UMKM;
    const SELECTOR = '[data-umkm-component]';
    const loadedElements = new WeakSet();
    const handlers = {};

    const state = {
        ready: false,
        total: 0,
        loaded: 0,
        failed: 0,
        skipped: 0,
        lastScanAt: null
    };

    function ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
            return;
        }

        callback();
    }

    function qsa(selector, root) {
        return Array.from((root || document).querySelectorAll(selector));
    }

    function emit(name, detail) {
        document.dispatchEvent(new CustomEvent('umkm:component-loader:' + name, {
            detail: detail || {}
        }));
    }

    function log(level, message, data) {
        if (typeof UMKM.log === 'function') {
            UMKM.log(level, message, data);
        }
    }

    function getComponentName(element) {
        return String(element?.dataset?.umkmComponent || '').trim() || 'anonymous-component';
    }

    function getEndpoint(element) {
        return String(
            element?.dataset?.umkmComponentUrl ||
            element?.dataset?.umkmComponentSource ||
            element?.dataset?.umkmComponentEndpoint ||
            ''
        ).trim();
    }

    function getLoadMode(element) {
        return String(element?.dataset?.umkmComponentLoadOn || '').trim() || 'readiness-hidden';
    }

    function isAuto(element) {
        return element?.dataset?.umkmComponentAuto !== 'false';
    }

    function shouldReplace(element) {
        return element?.dataset?.umkmComponentReplace !== 'false';
    }

    function shouldOverlay(element) {
        return element?.dataset?.umkmComponentOverlay !== 'false';
    }

    function sameOriginUrl(url) {
        try {
            const parsed = new URL(url, window.location.origin);
            return parsed.origin === window.location.origin ? parsed.toString() : '';
        } catch (error) {
            return '';
        }
    }

    function sanitizeHtml(html) {
        const template = document.createElement('template');
        template.innerHTML = String(html || '');

        template.content.querySelectorAll('script,noscript,iframe,object,embed').forEach(function (node) {
            node.remove();
        });

        return template.content;
    }

    function setStatus(element, status, message) {
        element.dataset.umkmComponentStatus = status;

        if (message) {
            element.dataset.umkmComponentMessage = message;
        }

        element.classList.remove(
            'is-component-pending',
            'is-component-loading',
            'is-component-loaded',
            'is-component-failed',
            'is-component-skipped'
        );

        element.classList.add('is-component-' + status);
    }

    function increment(status) {
        if (status === 'loaded') {
            state.loaded += 1;
            return;
        }

        if (status === 'failed') {
            state.failed += 1;
            return;
        }

        if (status === 'skipped') {
            state.skipped += 1;
        }
    }

    function markSkipped(element, message) {
        setStatus(element, 'skipped', message || 'Komponen tidak memerlukan pemuatan AJAX.');
        loadedElements.add(element);
        increment('skipped');

        emit('skipped', {
            component: getComponentName(element),
            status: 'skipped'
        });
    }

    function applyPayload(element, payload) {
        const componentName = getComponentName(element);
        const handler = handlers[componentName];

        if (typeof handler === 'function') {
            handler(element, payload);
            return;
        }

        if (typeof payload === 'string') {
            const fragment = sanitizeHtml(payload);

            if (shouldReplace(element)) {
                element.replaceChildren(fragment);
            } else {
                element.appendChild(fragment);
            }

            return;
        }

        if (payload && typeof payload === 'object' && typeof payload.html === 'string') {
            const fragment = sanitizeHtml(payload.html);

            if (shouldReplace(element)) {
                element.replaceChildren(fragment);
            } else {
                element.appendChild(fragment);
            }
        }
    }

    async function load(element, options) {
        if (!element || loadedElements.has(element)) {
            return null;
        }

        const settings = Object.assign({
            force: false
        }, options || {});

        const componentName = getComponentName(element);
        const rawEndpoint = getEndpoint(element);

        if (!settings.force && !isAuto(element)) {
            markSkipped(element, 'Komponen disiapkan untuk pemuatan manual.');
            return null;
        }

        if (!rawEndpoint) {
            markSkipped(element, 'Komponen belum memiliki sumber AJAX.');
            return null;
        }

        const endpoint = sameOriginUrl(rawEndpoint);

        if (!endpoint) {
            setStatus(element, 'failed', 'Sumber komponen tidak valid.');
            loadedElements.add(element);
            increment('failed');

            emit('failed', {
                component: componentName,
                status: 'failed'
            });

            return null;
        }

        if (!UMKM.ajax || typeof UMKM.ajax.get !== 'function') {
            setStatus(element, 'failed', 'Core AJAX belum tersedia.');
            loadedElements.add(element);
            increment('failed');

            emit('failed', {
                component: componentName,
                status: 'failed'
            });

            return null;
        }

        setStatus(element, 'loading', 'Memuat komponen.');

        if (UMKM.loader && typeof UMKM.loader.component === 'function') {
            UMKM.loader.component(element, true, {
                overlay: shouldOverlay(element),
                message: element.dataset.umkmComponentLoadingText || 'Memuat komponen...'
            });
        }

        emit('loading', {
            component: componentName,
            status: 'loading'
        });

        const result = await UMKM.ajax.get(endpoint, {
            headers: {
                'X-UMKM-Component': componentName
            }
        });

        if (UMKM.loader && typeof UMKM.loader.component === 'function') {
            UMKM.loader.component(element, false);
        }

        if (!result || !result.ok) {
            setStatus(element, 'failed', 'Komponen gagal dimuat.');
            loadedElements.add(element);
            increment('failed');

            emit('failed', {
                component: componentName,
                status: 'failed',
                httpStatus: result?.status || 0
            });

            return result;
        }

        applyPayload(element, result.payload);

        setStatus(element, 'loaded', 'Komponen berhasil dimuat.');
        loadedElements.add(element);
        increment('loaded');

        emit('loaded', {
            component: componentName,
            status: 'loaded',
            duration: result.duration || 0
        });

        return result;
    }

    function scan(root, options) {
        const settings = Object.assign({
            loadMode: null,
            force: false
        }, options || {});

        const elements = qsa(SELECTOR, root || document);

        state.total = elements.length;
        state.lastScanAt = new Date().toISOString();

        elements.forEach(function (element) {
            if (loadedElements.has(element)) {
                return;
            }

            setStatus(element, 'pending', 'Menunggu pemuatan komponen.');

            const mode = getLoadMode(element);

            if (settings.loadMode && mode !== settings.loadMode) {
                return;
            }

            load(element, {
                force: settings.force
            });
        });

        emit('scan', {
            total: state.total,
            loaded: state.loaded,
            failed: state.failed,
            skipped: state.skipped,
            loadMode: settings.loadMode || 'any'
        });

        return elements;
    }

    function reload(target) {
        const element = typeof target === 'string' ? document.querySelector(target) : target;

        if (!element) {
            return null;
        }

        loadedElements.delete(element);

        return load(element, {
            force: true
        });
    }

    function registerHandler(name, callback) {
        if (!name || typeof callback !== 'function') {
            return false;
        }

        handlers[name] = callback;

        return true;
    }

    function getState() {
        return Object.assign({}, state);
    }

    document.addEventListener('umkm:readiness:hidden', function () {
        scan(document, {
            loadMode: 'readiness-hidden'
        });
    });

    ready(function () {
        state.ready = true;

        scan(document, {
            loadMode: 'dom-ready'
        });

        emit('ready', getState());
    });

    const api = {
        scan: scan,
        load: load,
        reload: reload,
        handler: registerHandler,
        state: getState
    };

    if (typeof UMKM.register === 'function') {
        UMKM.register('componentLoader', api);
    } else {
        UMKM.componentLoader = api;
    }

    log('info', 'component loader initialized', getState());
})();
