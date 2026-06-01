(function () {
    'use strict';

    window.UMKM = window.UMKM || {};

    const UMKM = window.UMKM;
    const registry = new WeakMap();

    function qs(selector, root) {
        return (root || document).querySelector(selector);
    }

    function isFocusable(element) {
        if (!element || typeof element.focus !== 'function') {
            return false;
        }

        if (!document.contains(element)) {
            return false;
        }

        if (element.disabled) {
            return false;
        }

        if (element.closest('[hidden], [inert], [aria-hidden="true"]')) {
            return false;
        }

        return true;
    }

    function ensureBodyFocusable() {
        if (!document.body.hasAttribute('tabindex')) {
            document.body.setAttribute('tabindex', '-1');
            document.body.dataset.umkmModalBodyTabindex = 'true';
        }

        return document.body;
    }

    function safeFocus(element) {
        if (!isFocusable(element)) {
            return false;
        }

        try {
            element.focus({
                preventScroll: true
            });
            return document.activeElement === element;
        } catch (error) {
            try {
                element.focus();
                return document.activeElement === element;
            } catch (innerError) {
                return false;
            }
        }
    }

    function resolveElement(target, root) {
        if (!target) {
            return null;
        }

        if (typeof target === 'string') {
            return qs(target, root || document);
        }

        if (target instanceof Element) {
            return target;
        }

        return null;
    }

    function rememberTrigger(modalElement, trigger) {
        if (!modalElement) {
            return;
        }

        const record = registry.get(modalElement) || {};
        const safeTrigger = resolveElement(trigger) || document.activeElement;

        if (safeTrigger && safeTrigger !== modalElement && !modalElement.contains(safeTrigger)) {
            record.trigger = safeTrigger;
            registry.set(modalElement, record);
        }
    }

    function fallbackElement(modalElement, fallback) {
        const record = modalElement ? (registry.get(modalElement) || {}) : {};
        const configuredFallback = resolveElement(fallback);

        if (configuredFallback && configuredFallback !== modalElement && !modalElement.contains(configuredFallback)) {
            return configuredFallback;
        }

        if (record.fallback && document.contains(record.fallback) && !modalElement.contains(record.fallback)) {
            return record.fallback;
        }

        if (record.trigger && document.contains(record.trigger) && !modalElement.contains(record.trigger)) {
            return record.trigger;
        }

        return ensureBodyFocusable();
    }

    function releaseFocus(modalElement, fallback) {
        const modal = resolveElement(modalElement);

        if (!modal) {
            return false;
        }

        const activeElement = document.activeElement;
        const fallbackTarget = fallbackElement(modal, fallback);

        if (activeElement && modal.contains(activeElement) && typeof activeElement.blur === 'function') {
            activeElement.blur();
        }

        if (document.activeElement && modal.contains(document.activeElement)) {
            ensureBodyFocusable().focus({
                preventScroll: true
            });
        }

        if (document.activeElement && modal.contains(document.activeElement)) {
            safeFocus(fallbackTarget);
        }

        return !(document.activeElement && modal.contains(document.activeElement));
    }

    function restoreFocus(modalElement, fallback) {
        const modal = resolveElement(modalElement);

        if (!modal) {
            return false;
        }

        const target = fallbackElement(modal, fallback);

        window.requestAnimationFrame(function () {
            safeFocus(target);
        });

        return true;
    }

    function setInert(modalElement, inert) {
        const modal = resolveElement(modalElement);

        if (!modal || !('inert' in modal)) {
            return;
        }

        modal.inert = Boolean(inert);
    }

    function bindFocusGuard(modalElement, options) {
        const modal = resolveElement(modalElement);

        if (!modal) {
            return null;
        }

        options = Object.assign({
            fallbackTriggerSelector: null,
            fallbackTrigger: null,
            returnFocus: true,
            setInertWhenHidden: true
        }, options || {});

        if (modal.dataset.umkmModalFocusGuardBound === 'true') {
            return registry.get(modal) || null;
        }

        const record = {
            options: options,
            fallback: resolveElement(options.fallbackTrigger) || resolveElement(options.fallbackTriggerSelector)
        };

        registry.set(modal, record);
        modal.dataset.umkmModalFocusGuardBound = 'true';

        if (record.fallback) {
            modal.dataset.umkmModalFallbackKnown = 'true';
        }

        if (options.setInertWhenHidden && !modal.classList.contains('show')) {
            setInert(modal, true);
        }

        modal.addEventListener('show.bs.modal', function () {
            setInert(modal, false);
            rememberTrigger(modal, document.activeElement);
        });

        modal.addEventListener('shown.bs.modal', function () {
            setInert(modal, false);
        });

        modal.addEventListener('hide.bs.modal', function () {
            releaseFocus(modal, options.fallbackTrigger || options.fallbackTriggerSelector);
        });

        modal.addEventListener('hidden.bs.modal', function () {
            releaseFocus(modal, options.fallbackTrigger || options.fallbackTriggerSelector);

            if (options.setInertWhenHidden) {
                setInert(modal, true);
            }

            if (options.returnFocus) {
                restoreFocus(modal, options.fallbackTrigger || options.fallbackTriggerSelector);
            }
        });

        return record;
    }

    function hide(modalElement, fallback) {
        const modal = resolveElement(modalElement);

        if (!modal) {
            return;
        }

        releaseFocus(modal, fallback);

        if (window.bootstrap && window.bootstrap.Modal) {
            const instance = window.bootstrap.Modal.getInstance(modal);

            if (instance) {
                instance.hide();
                return;
            }
        }

        modal.classList.remove('show');
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
        modal.removeAttribute('aria-modal');
        setInert(modal, true);
        restoreFocus(modal, fallback);
    }

    function show(modalElement, options) {
        const modal = resolveElement(modalElement);

        if (!modal) {
            return;
        }

        options = Object.assign({
            backdrop: true,
            keyboard: true,
            focus: true
        }, options || {});

        setInert(modal, false);

        if (window.bootstrap && window.bootstrap.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(modal, options).show();
            return;
        }

        modal.hidden = false;
        modal.classList.add('show');
        modal.style.display = 'block';
        modal.setAttribute('aria-modal', 'true');
        modal.removeAttribute('aria-hidden');
    }

    /* FEEDBACK-CORE-1B LOADING MODAL START */
    let loadingModalElement = null;

    function ensureLoadingModal() {
        if (loadingModalElement && document.contains(loadingModalElement)) {
            return loadingModalElement;
        }

        const wrapper = document.createElement('div');
        wrapper.innerHTML = [
            '<div class="modal fade umkm-loading-modal" id="umkmCoreLoadingModal" tabindex="-1" aria-labelledby="umkmCoreLoadingModalTitle" aria-hidden="true" data-umkm-loading-modal="true">',
            '  <div class="modal-dialog modal-dialog-centered">',
            '    <section class="modal-content umkm-loading-card">',
            '      <div class="modal-body umkm-loading-body">',
            '        <div class="umkm-loading-visual" aria-hidden="true">',
            '          <span class="umkm-loading-ring"></span>',
            '          <span class="umkm-loading-dot"></span>',
            '        </div>',
            '        <div class="umkm-loading-copy">',
            '          <span class="umkm-loading-kicker" data-umkm-loading-kicker>Mohon Tunggu</span>',
            '          <h5 class="umkm-loading-title" id="umkmCoreLoadingModalTitle" data-umkm-loading-title>Proses sedang berjalan</h5>',
            '          <p class="umkm-loading-message" data-umkm-loading-message>Sistem sedang memproses permintaan Anda.</p>',
            '          <div class="umkm-loading-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="30">',
            '            <span data-umkm-loading-progress-bar></span>',
            '          </div>',
            '          <small class="umkm-loading-caption" data-umkm-loading-caption>Jangan menutup halaman sampai proses selesai.</small>',
            '        </div>',
            '      </div>',
            '    </section>',
            '  </div>',
            '</div>'
        ].join('');

        loadingModalElement = wrapper.firstElementChild;
        document.body.appendChild(loadingModalElement);

        bindFocusGuard(loadingModalElement, {
            returnFocus: false,
            setInertWhenHidden: true
        });

        return loadingModalElement;
    }

    function setLoadingContent(options) {
        const modal = ensureLoadingModal();
        const settings = Object.assign({
            kicker: 'Mohon Tunggu',
            title: 'Proses sedang berjalan',
            message: 'Sistem sedang memproses permintaan Anda.',
            caption: 'Jangan menutup halaman sampai proses selesai.',
            progress: null
        }, options || {});

        const kicker = qs('[data-umkm-loading-kicker]', modal);
        const title = qs('[data-umkm-loading-title]', modal);
        const message = qs('[data-umkm-loading-message]', modal);
        const caption = qs('[data-umkm-loading-caption]', modal);
        const progress = qs('.umkm-loading-progress', modal);
        const progressBar = qs('[data-umkm-loading-progress-bar]', modal);

        if (kicker) {
            kicker.textContent = String(settings.kicker || 'Mohon Tunggu');
        }

        if (title) {
            title.textContent = String(settings.title || 'Proses sedang berjalan');
        }

        if (message) {
            message.textContent = String(settings.message || 'Sistem sedang memproses permintaan Anda.');
        }

        if (caption) {
            caption.textContent = String(settings.caption || 'Jangan menutup halaman sampai proses selesai.');
        }

        if (progress && progressBar && settings.progress !== null && settings.progress !== undefined) {
            const value = Math.max(0, Math.min(100, Number(settings.progress) || 0));
            progress.setAttribute('aria-valuenow', String(value));
            progressBar.style.width = value + '%';
            progress.classList.add('is-determinate');
        } else if (progress && progressBar) {
            progress.removeAttribute('aria-valuenow');
            progressBar.style.width = '';
            progress.classList.remove('is-determinate');
        }

        return modal;
    }

    function showLoading(options) {
        const modal = setLoadingContent(options);

        show(modal, {
            backdrop: 'static',
            keyboard: false,
            focus: true
        });

        return modal;
    }

    function hideLoading(fallback) {
        const modal = ensureLoadingModal();
        hide(modal, fallback);
    }
    /* FEEDBACK-CORE-1B LOADING MODAL END */

    UMKM.modal = {
        bindFocusGuard: bindFocusGuard,
        releaseFocus: releaseFocus,
        restoreFocus: restoreFocus,
        rememberTrigger: rememberTrigger,
        hide: hide,
        show: show,
        showLoading: showLoading,
        hideLoading: hideLoading,
        setLoadingContent: setLoadingContent
    };
    UMKM.register?.('modal', UMKM.modal);
})();
