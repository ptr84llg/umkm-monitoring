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

    UMKM.modal = {
        bindFocusGuard: bindFocusGuard,
        releaseFocus: releaseFocus,
        restoreFocus: restoreFocus,
        rememberTrigger: rememberTrigger,
        hide: hide,
        show: show
    };

    UMKM.register?.('modal', UMKM.modal);
})();
