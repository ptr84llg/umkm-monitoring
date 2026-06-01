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

    function iconFor(type) {
        if (type === 'success') {
            return '✓';
        }

        if (type === 'error' || type === 'danger') {
            return '!';
        }

        if (type === 'warning') {
            return 'i';
        }

        return 'i';
    }

    function titleFor(type) {
        if (type === 'success') {
            return 'Berhasil';
        }

        if (type === 'error' || type === 'danger') {
            return 'Belum Berhasil';
        }

        if (type === 'warning') {
            return 'Perhatian';
        }

        return 'Informasi';
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

    function show(messageOrOptions, options) {
        const settings = Object.assign({
            type: 'info',
            title: '',
            message: '',
            delay: 3200,
            autohide: true
        }, normalizeOptions(messageOrOptions, options));

        const type = settings.type === 'danger' ? 'error' : settings.type;
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

        const icon = document.createElement('span');
        icon.className = 'umkm-toast-icon';
        icon.setAttribute('aria-hidden', 'true');
        icon.textContent = iconFor(type);

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
                delay: Number(settings.delay) || 3200
            });

            instance.show();
        } else {
            toastElement.classList.add('show');

            if (settings.autohide) {
                window.setTimeout(function () {
                    hide(toastElement);
                }, Number(settings.delay) || 3200);
            }
        }

        return toastElement;
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