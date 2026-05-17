(function () {
    'use strict';

    window.UMKM = window.UMKM || {};

    const UMKM = window.UMKM;

    function qs(selector, root) {
        return (root || document).querySelector(selector);
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

    function setButtonLoading(button, loading, options) {
        if (!button) {
            return null;
        }

        const settings = Object.assign({
            text: null,
            loadingText: 'Memproses...',
            disabled: true
        }, options || {});

        if (loading) {
            if (!button.dataset.umkmOriginalHtml) {
                button.dataset.umkmOriginalHtml = button.innerHTML;
            }

            const loader = '<span class="umkm-button-loader" aria-hidden="true"></span>';
            const text = settings.loadingText || button.textContent || 'Memproses...';

            button.innerHTML = loader + '<span>' + text + '</span>';
            button.classList.add('umkm-button-loading');

            if (settings.disabled) {
                button.disabled = true;
                button.setAttribute('aria-disabled', 'true');
            }

            return button;
        }

        if (button.dataset.umkmOriginalHtml) {
            button.innerHTML = button.dataset.umkmOriginalHtml;
            delete button.dataset.umkmOriginalHtml;
        } else if (settings.text) {
            button.textContent = settings.text;
        }

        button.classList.remove('umkm-button-loading');
        button.disabled = false;
        button.removeAttribute('aria-disabled');

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
            message: 'Mohon tunggu, sistem sedang memproses permintaan.'
        }, options || {});

        const loader = getBlockingLoader();
        const title = qs('[data-umkm-blocking-title]', loader);
        const message = qs('[data-umkm-blocking-message]', loader);

        if (title) {
            title.textContent = settings.title;
        }

        if (message) {
            message.textContent = settings.message;
        }

        loader.hidden = false;
        document.body.classList.add('umkm-blocking-active');

        return loader;
    }

    function hideBlocking() {
        const loader = qs('[data-umkm-blocking-loader]');

        if (!loader) {
            return;
        }

        loader.hidden = true;
        document.body.classList.remove('umkm-blocking-active');
    }

    function setComponentLoading(target, loading) {
        const element = typeof target === 'string' ? qs(target) : target;

        if (!element) {
            return null;
        }

        element.classList.toggle('is-loading', Boolean(loading));
        element.setAttribute('aria-busy', loading ? 'true' : 'false');

        return element;
    }

    function skeleton(target, loading) {
        const element = typeof target === 'string' ? qs(target) : target;

        if (!element) {
            return null;
        }

        element.classList.toggle('umkm-skeleton', Boolean(loading));

        if (loading) {
            element.setAttribute('aria-busy', 'true');
        } else {
            element.removeAttribute('aria-busy');
        }

        return element;
    }

    const api = {
        button: setButtonLoading,
        block: showBlocking,
        unblock: hideBlocking,
        component: setComponentLoading,
        skeleton: skeleton
    };

    if (typeof UMKM.register === 'function') {
        UMKM.register('loader', api);
    } else {
        UMKM.loader = api;
    }
})();
