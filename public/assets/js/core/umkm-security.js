(function () {
    'use strict';

    window.UMKM = window.UMKM || {};

    const UMKM = window.UMKM;

    const state = {
        initializedAt: new Date().toISOString(),
        client: UMKM.config?.client || 'web',
        profile: UMKM.config?.profile || 'default',
        hasCsrf: Boolean(document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')),
        hasAjax: Boolean(UMKM.ajax),
        hasLocation: Boolean(UMKM.location),
        hasSession: Boolean(UMKM.session),
        fetchMetadataSupported: false,
        sameOrigin: window.location.origin,
        debugVisible: Boolean(UMKM.config?.debug)
    };

    function refresh() {
        state.hasCsrf = Boolean(document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'));
        state.hasAjax = Boolean(UMKM.ajax);
        state.hasLocation = Boolean(UMKM.location);
        state.hasSession = Boolean(UMKM.session);
        state.fetchMetadataSupported = true;
        state.refreshedAt = new Date().toISOString();

        return Object.assign({}, state);
    }

    function status() {
        const current = refresh();

        if (UMKM.config?.debug) {
            console.groupCollapsed('%c[UMKM Security]%c status', 'background:#0f7665;color:#fff;border-radius:6px;padding:2px 6px;font-weight:700;', 'font-weight:700;color:#10213d;');
            console.table(current);
            console.groupEnd();
        }

        return current;
    }

    function markProtectedLinks(selector) {
        document.querySelectorAll(selector || '[data-security-gated]').forEach(function (element) {
            element.setAttribute('data-umkm-security-bound', 'true');
        });
    }

    UMKM.ready?.(function () {
        window.setTimeout(function () {
            status();
            document.documentElement.setAttribute('data-umkm-security', 'ready');

            document.dispatchEvent(new CustomEvent('umkm:security:ready', {
                detail: refresh()
            }));
        }, 0);
    });

    document.addEventListener('umkm:module:ready', refresh);

    UMKM.register?.('security', {
        state: refresh,
        status: status,
        markProtectedLinks: markProtectedLinks
    });
})();
