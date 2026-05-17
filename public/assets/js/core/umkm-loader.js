(function () {
    'use strict';

    window.UMKM = window.UMKM || {};

    const UMKM = window.UMKM;

    const buttonState = new WeakMap();

    function qs(selector, root) {
        return (root || document).querySelector(selector);
    }

    function qsa(selector, root) {
        return Array.from((root || document).querySelectorAll(selector));
    }

    function createElement(tag, className, text) {
        const element = document.createElement(tag);

        if (className) {
            element.className = className;
        }

        if (text !== undefined && text !== null) {
            element.textContent = String(text);
        }

        return element;
    }

    function normalizeTarget(target) {
        if (!target) {
            return null;
        }

        if (typeof target === 'string') {
            return qs(target);
        }

        if (target instanceof Element || target === document || target === window) {
            return target;
        }

        return null;
    }

    function emit(name, detail) {
        document.dispatchEvent(new CustomEvent('umkm:loader:' + name, {
            detail: detail || {}
        }));
    }

    function log(level, message, data) {
        if (typeof UMKM.log === 'function') {
            UMKM.log(level, message, data);
        }
    }

    function safeText(value, fallback) {
        const text = value === undefined || value === null ? fallback : value;

        return String(text || '');
    }

    function setButtonLoading(buttonTarget, loading, options) {
        const button = normalizeTarget(buttonTarget);

        if (!button) {
            return null;
        }

        const settings = Object.assign({
            text: null,
            loadingText: 'Memproses...',
            disabled: true,
            busy: true
        }, options || {});

        if (loading) {
            if (!buttonState.has(button)) {
                buttonState.set(button, {
                    html: button.innerHTML,
                    disabled: button.disabled,
                    ariaDisabled: button.getAttribute('aria-disabled'),
                    ariaBusy: button.getAttribute('aria-busy')
                });
            }

            const loader = '<span class="umkm-button-loader" aria-hidden="true"></span>';
            const text = safeText(settings.loadingText, button.textContent || 'Memproses...');

            button.innerHTML = loader + '<span>' + text + '</span>';
            button.classList.add('umkm-button-loading');

            if (settings.disabled) {
                button.disabled = true;
                button.setAttribute('aria-disabled', 'true');
            }

            if (settings.busy) {
                button.setAttribute('aria-busy', 'true');
            }

            emit('button:start', {
                button: button,
                text: text
            });

            return button;
        }

        const previous = buttonState.get(button);

        if (previous) {
            button.innerHTML = previous.html;
            button.disabled = Boolean(previous.disabled);

            if (previous.ariaDisabled === null) {
                button.removeAttribute('aria-disabled');
            } else {
                button.setAttribute('aria-disabled', previous.ariaDisabled);
            }

            if (previous.ariaBusy === null) {
                button.removeAttribute('aria-busy');
            } else {
                button.setAttribute('aria-busy', previous.ariaBusy);
            }

            buttonState.delete(button);
        } else if (settings.text) {
            button.textContent = settings.text;
            button.disabled = false;
            button.removeAttribute('aria-disabled');
            button.removeAttribute('aria-busy');
        } else {
            button.disabled = false;
            button.removeAttribute('aria-disabled');
            button.removeAttribute('aria-busy');
        }

        button.classList.remove('umkm-button-loading');

        emit('button:stop', {
            button: button
        });

        return button;
    }

    function getBlockingLoader() {
        let loader = qs('[data-umkm-blocking-loader]');

        if (loader) {
            return loader;
        }

        loader = createElement('div', 'umkm-blocking-loader');
        loader.hidden = true;
        loader.dataset.umkmBlockingLoader = 'true';
        loader.setAttribute('role', 'status');
        loader.setAttribute('aria-live', 'polite');
        loader.setAttribute('aria-modal', 'true');

        const card = createElement('div', 'umkm-blocking-loader-card');
        const spinner = createElement('span', 'umkm-blocking-spinner');
        const title = createElement('h2', 'umkm-blocking-title', 'Memproses');
        const message = createElement('p', 'umkm-blocking-message', 'Mohon tunggu, sistem sedang memproses permintaan.');

        title.dataset.umkmBlockingTitle = 'true';
        message.dataset.umkmBlockingMessage = 'true';

        card.appendChild(spinner);
        card.appendChild(title);
        card.appendChild(message);
        loader.appendChild(card);

        document.body.appendChild(loader);

        return loader;
    }

    function showBlocking(options) {
        const settings = Object.assign({
            title: 'Memproses',
            message: 'Mohon tunggu, sistem sedang memproses permintaan.',
            lockScroll: true,
            sensitive: false
        }, options || {});

        const loader = getBlockingLoader();
        const title = qs('[data-umkm-blocking-title]', loader);
        const message = qs('[data-umkm-blocking-message]', loader);

        if (title) {
            title.textContent = safeText(settings.title, 'Memproses');
        }

        if (message) {
            message.textContent = safeText(settings.message, 'Mohon tunggu, sistem sedang memproses permintaan.');
        }

        loader.hidden = false;
        loader.dataset.umkmBlockingSensitive = settings.sensitive ? 'true' : 'false';

        document.body.classList.add('umkm-blocking-active');

        if (settings.lockScroll) {
            document.documentElement.classList.add('umkm-blocking-lock');
        }

        emit('blocking:show', {
            title: settings.title,
            message: settings.message,
            sensitive: Boolean(settings.sensitive)
        });

        return loader;
    }

    function hideBlocking() {
        const loader = qs('[data-umkm-blocking-loader]');

        if (!loader) {
            return;
        }

        loader.hidden = true;
        loader.dataset.umkmBlockingSensitive = 'false';

        document.body.classList.remove('umkm-blocking-active');
        document.documentElement.classList.remove('umkm-blocking-lock');

        emit('blocking:hide', {});
    }

    function setComponentLoading(target, loading, options) {
        const element = normalizeTarget(target);

        if (!element) {
            return null;
        }

        const settings = Object.assign({
            message: 'Memuat data...',
            overlay: false,
            busy: true
        }, options || {});

        element.classList.toggle('is-loading', Boolean(loading));
        element.classList.toggle('umkm-component-loading', Boolean(loading));

        if (settings.busy) {
            element.setAttribute('aria-busy', loading ? 'true' : 'false');
        }

        let overlay = qs('[data-umkm-component-loader]', element);

        if (loading && settings.overlay) {
            if (!overlay) {
                overlay = createElement('div', 'umkm-component-loader');
                overlay.dataset.umkmComponentLoader = 'true';

                const box = createElement('div', 'umkm-component-loader-box');
                const spinner = createElement('span', 'umkm-inline-spinner');
                const text = createElement('span', 'umkm-component-loader-text', settings.message);

                box.appendChild(spinner);
                box.appendChild(text);
                overlay.appendChild(box);
                element.appendChild(overlay);
            } else {
                const text = qs('.umkm-component-loader-text', overlay);

                if (text) {
                    text.textContent = settings.message;
                }
            }
        }

        if (!loading && overlay) {
            overlay.remove();
        }

        emit(loading ? 'component:start' : 'component:stop', {
            element: element,
            overlay: Boolean(settings.overlay)
        });

        return element;
    }

    function skeleton(target, loading, options) {
        const element = normalizeTarget(target);

        if (!element) {
            return null;
        }

        const settings = Object.assign({
            busy: true,
            lines: 0
        }, options || {});

        element.classList.toggle('umkm-skeleton', Boolean(loading));

        if (settings.busy) {
            if (loading) {
                element.setAttribute('aria-busy', 'true');
            } else {
                element.removeAttribute('aria-busy');
            }
        }

        if (loading && settings.lines > 0) {
            element.innerHTML = '';

            for (let index = 0; index < settings.lines; index += 1) {
                const line = createElement('span', 'umkm-skeleton-line');
                line.style.width = index % 3 === 0 ? '86%' : (index % 3 === 1 ? '72%' : '94%');
                element.appendChild(line);
            }
        }

        emit(loading ? 'skeleton:start' : 'skeleton:stop', {
            element: element
        });

        return element;
    }

    function skeletonMany(selector, loading, options) {
        const elements = qsa(selector);

        elements.forEach(function (element) {
            skeleton(element, loading, options);
        });

        return elements;
    }

    function makeInlineLoader(message) {
        const wrapper = createElement('div', 'umkm-inline-loader');
        const spinner = createElement('span', 'umkm-inline-spinner');
        const text = createElement('span', 'umkm-inline-loader-text', message || 'Memuat...');

        wrapper.appendChild(spinner);
        wrapper.appendChild(text);

        return wrapper;
    }

    function showInContainer(target, type, options) {
        const element = normalizeTarget(target);

        if (!element) {
            return null;
        }

        const settings = Object.assign({
            message: 'Memuat data...',
            replace: false
        }, options || {});

        const loaderClass = 'umkm-' + type + '-loader';
        let loader = qs('[data-umkm-' + type + '-loader]', element);

        if (!loader) {
            loader = createElement('div', loaderClass);
            loader.dataset['umkm' + type.charAt(0).toUpperCase() + type.slice(1) + 'Loader'] = 'true';
            loader.appendChild(makeInlineLoader(settings.message));
        } else {
            const text = qs('.umkm-inline-loader-text', loader);

            if (text) {
                text.textContent = settings.message;
            }
        }

        if (settings.replace) {
            element.innerHTML = '';
        }

        if (!loader.parentElement) {
            element.appendChild(loader);
        }

        element.setAttribute('aria-busy', 'true');
        element.classList.add('is-loading', 'umkm-' + type + '-loading');

        emit(type + ':show', {
            element: element,
            message: settings.message
        });

        return loader;
    }

    function hideInContainer(target, type) {
        const element = normalizeTarget(target);

        if (!element) {
            return null;
        }

        const loader = qs('[data-umkm-' + type + '-loader]', element);

        if (loader) {
            loader.remove();
        }

        element.removeAttribute('aria-busy');
        element.classList.remove('is-loading', 'umkm-' + type + '-loading');

        emit(type + ':hide', {
            element: element
        });

        return element;
    }

    function table(target, loading, options) {
        return loading
            ? showInContainer(target, 'table', Object.assign({ message: 'Memuat tabel...' }, options || {}))
            : hideInContainer(target, 'table');
    }

    function chart(target, loading, options) {
        return loading
            ? showInContainer(target, 'chart', Object.assign({ message: 'Memuat grafik...' }, options || {}))
            : hideInContainer(target, 'chart');
    }

    function map(target, loading, options) {
        return loading
            ? showInContainer(target, 'map', Object.assign({ message: 'Memuat peta...' }, options || {}))
            : hideInContainer(target, 'map');
    }

    function upload(target, progress, options) {
        const element = normalizeTarget(target);

        if (!element) {
            return null;
        }

        const settings = Object.assign({
            message: 'Mengunggah berkas...'
        }, options || {});

        let loader = qs('[data-umkm-upload-loader]', element);

        if (!loader) {
            loader = createElement('div', 'umkm-upload-loader');
            loader.dataset.umkmUploadLoader = 'true';

            const label = createElement('div', 'umkm-upload-loader-label', settings.message);
            const track = createElement('div', 'umkm-upload-loader-track');
            const bar = createElement('div', 'umkm-upload-loader-bar');

            bar.dataset.umkmUploadProgress = 'true';
            track.appendChild(bar);
            loader.appendChild(label);
            loader.appendChild(track);
            element.appendChild(loader);
        }

        const safeProgress = Math.max(0, Math.min(100, Number(progress || 0)));
        const bar = qs('[data-umkm-upload-progress]', loader);

        if (bar) {
            bar.style.width = safeProgress + '%';
        }

        element.setAttribute('aria-busy', safeProgress < 100 ? 'true' : 'false');

        emit('upload:progress', {
            element: element,
            progress: safeProgress
        });

        return loader;
    }

    function exportLoader(target, loading, options) {
        return loading
            ? showInContainer(target, 'export', Object.assign({ message: 'Menyiapkan export...' }, options || {}))
            : hideInContainer(target, 'export');
    }

    function state() {
        return {
            blockingActive: Boolean(qs('[data-umkm-blocking-loader]') && !qs('[data-umkm-blocking-loader]').hidden),
            blockingLock: document.documentElement.classList.contains('umkm-blocking-lock'),
            readinessLock: document.documentElement.classList.contains('umkm-readiness-lock')
        };
    }

    const api = {
        button: setButtonLoading,
        block: showBlocking,
        unblock: hideBlocking,
        component: setComponentLoading,
        skeleton: skeleton,
        skeletonMany: skeletonMany,
        table: table,
        chart: chart,
        map: map,
        upload: upload,
        export: exportLoader,
        inline: makeInlineLoader,
        state: state
    };

    UMKM.state = UMKM.state || {};
    UMKM.state.loader = state();

    if (typeof UMKM.register === 'function') {
        UMKM.register('loader', api);
    } else {
        UMKM.loader = api;
    }

    log('info', 'loader core initialized', state());
})();
