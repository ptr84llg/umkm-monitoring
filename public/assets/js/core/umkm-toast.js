(function () {
    'use strict';

    window.UMKM = window.UMKM || {};

    const UMKM = window.UMKM;
    const toastRegistry = new Set();

    function qs(selector, root) {
        return (root || document).querySelector(selector);
    }

    function ensureContainer() {
        let container = qs('[data-umkm-toast-container]');

        if (container) {
            return container;
        }

        container = document.createElement('div');
        container.className = 'umkm-toast-container';
        container.setAttribute('data-umkm-toast-container', 'true');
        container.setAttribute('aria-live', 'polite');
        container.setAttribute('aria-atomic', 'true');
        document.body.appendChild(container);

        return container;
    }

    function normalizeOptions(messageOrOptions, options) {
        if (typeof messageOrOptions === 'object' && messageOrOptions !== null) {
            return Object.assign({}, messageOrOptions);
        }

        return Object.assign({
            message: String(messageOrOptions || '')
        }, options || {});
    }

    function normalizeType(type) {
        if (type === 'danger') {
            return 'error';
        }

        if (type === 'success' || type === 'error' || type === 'warning' || type === 'info') {
            return type;
        }

        return 'info';
    }

    function titleFor(type) {
        if (type === 'success') {
            return 'Berhasil';
        }

        if (type === 'error') {
            return 'Belum Berhasil';
        }

        if (type === 'warning') {
            return 'Perhatian';
        }

        return 'Informasi';
    }

    function iconSvgFor(type) {
        if (type === 'success') {
            return '<svg viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="M9.4 16.6 4.9 12.1l1.7-1.7 2.8 2.8 8-8 1.7 1.7-9.7 9.7Z"/></svg>';
        }

        if (type === 'error') {
            return '<svg viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="M12 2.3 1.8 20h20.4L12 2.3Zm0 5.7c.7 0 1.2.5 1.2 1.2v4.2h-2.4V9.2c0-.7.5-1.2 1.2-1.2Zm0 9.8a1.35 1.35 0 1 1 0-2.7 1.35 1.35 0 0 1 0 2.7Z"/></svg>';
        }

        if (type === 'warning') {
            return '<svg viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="M12 2.4 1.8 20h20.4L12 2.4Zm-1.1 6.2h2.2v5.8h-2.2V8.6Zm1.1 9.3a1.3 1.3 0 1 1 0-2.6 1.3 1.3 0 0 1 0 2.6Z"/></svg>';
        }

        return '<svg viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="M12 2a10 10 0 1 0 .01 20.01A10 10 0 0 0 12 2Zm1.1 15.5h-2.2v-7.1h2.2v7.1ZM12 8.7a1.3 1.3 0 1 1 0-2.6 1.3 1.3 0 0 1 0 2.6Z"/></svg>';
    }

    function safeText(value, fallback) {
        const text = String(value || '').trim();
        return text || fallback || '';
    }

    function removeToast(toastElement) {
        if (!toastElement) {
            return;
        }

        toastRegistry.delete(toastElement);

        if (toastElement.parentNode) {
            toastElement.parentNode.removeChild(toastElement);
        }
    }

    function hide(toastElement) {
        if (!toastElement) {
            return;
        }

        if (window.bootstrap && window.bootstrap.Toast) {
            const instance = window.bootstrap.Toast.getInstance(toastElement);

            if (instance) {
                instance.hide();
                return;
            }
        }

        toastElement.classList.remove('show');
        removeToast(toastElement);
    }

    function show(messageOrOptions, options) {
        const settings = Object.assign({
            type: 'info',
            title: '',
            message: '',
            delay: 3400,
            autohide: true
        }, normalizeOptions(messageOrOptions, options));

        const type = normalizeType(settings.type);
        const container = ensureContainer();
        const toastElement = document.createElement('div');
        const title = safeText(settings.title, titleFor(type));
        const message = safeText(settings.message, 'Proses selesai.');

        toastElement.className = 'toast umkm-toast umkm-toast-' + type;
        toastElement.setAttribute('role', type === 'error' ? 'alert' : 'status');
        toastElement.setAttribute('aria-live', type === 'error' ? 'assertive' : 'polite');
        toastElement.setAttribute('aria-atomic', 'true');
        toastElement.setAttribute('data-umkm-toast-item', 'true');

        const body = document.createElement('div');
        body.className = 'umkm-toast-body';

        const accent = document.createElement('span');
        accent.className = 'umkm-toast-accent';
        accent.setAttribute('aria-hidden', 'true');

        const icon = document.createElement('span');
        icon.className = 'umkm-toast-icon';
        icon.setAttribute('aria-hidden', 'true');
        icon.innerHTML = iconSvgFor(type);

        const copy = document.createElement('div');
        copy.className = 'umkm-toast-copy';

        const titleElement = document.createElement('strong');
        titleElement.className = 'umkm-toast-title';
        titleElement.textContent = title;

        const messageElement = document.createElement('span');
        messageElement.className = 'umkm-toast-message';
        messageElement.textContent = message;

        const closeButton = document.createElement('button');
        closeButton.type = 'button';
        closeButton.className = 'btn-close umkm-toast-close';
        closeButton.setAttribute('aria-label', 'Tutup pemberitahuan');

        copy.appendChild(titleElement);
        copy.appendChild(messageElement);
        body.appendChild(accent);
        body.appendChild(icon);
        body.appendChild(copy);
        body.appendChild(closeButton);
        toastElement.appendChild(body);
        container.appendChild(toastElement);
        toastRegistry.add(toastElement);

        closeButton.addEventListener('click', function () {
            hide(toastElement);
        });

        toastElement.addEventListener('hidden.bs.toast', function () {
            removeToast(toastElement);
        });

        if (window.bootstrap && window.bootstrap.Toast) {
            const instance = window.bootstrap.Toast.getOrCreateInstance(toastElement, {
                autohide: Boolean(settings.autohide),
                delay: Number(settings.delay) || 3400
            });

            instance.show();
        } else {
            toastElement.classList.add('show');

            if (settings.autohide) {
                window.setTimeout(function () {
                    hide(toastElement);
                }, Number(settings.delay) || 3400);
            }
        }

        return toastElement;
    }

    function hideAll() {
        Array.from(toastRegistry).forEach(function (toastElement) {
            hide(toastElement);
        });
    }

    function success(messageOrOptions, options) {
        const settings = normalizeOptions(messageOrOptions, options);
        settings.type = 'success';
        return show(settings);
    }

    function error(messageOrOptions, options) {
        const settings = normalizeOptions(messageOrOptions, options);
        settings.type = 'error';
        return show(settings);
    }

    function warning(messageOrOptions, options) {
        const settings = normalizeOptions(messageOrOptions, options);
        settings.type = 'warning';
        return show(settings);
    }

    function info(messageOrOptions, options) {
        const settings = normalizeOptions(messageOrOptions, options);
        settings.type = 'info';
        return show(settings);
    }

    UMKM.toast = {
        show: show,
        hide: hide,
        hideAll: hideAll,
        success: success,
        error: error,
        warning: warning,
        info: info
    };

    UMKM.register?.('toast', UMKM.toast);
})();