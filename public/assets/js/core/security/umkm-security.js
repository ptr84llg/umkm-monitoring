(function () {
    'use strict';

    window.UMKM = window.UMKM || {};

    const UMKM = window.UMKM;

    const state = {
        initializedAt: new Date().toISOString(),
        client: UMKM.config?.client || 'web',
        profile: UMKM.config?.profile || 'default',
        hasCsrf: false,
        hasAjax: false,
        hasLocation: false,
        hasSession: false,
        fetchMetadataSupported: true,
        sameOrigin: window.location.origin,
        debugVisible: Boolean(UMKM.config?.debug),
        refreshedAt: null
    };

    let statusTimer = null;
    let lastPrintedSignature = '';

    function refresh() {
        state.hasCsrf = Boolean(document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'));
        state.hasAjax = Boolean(UMKM.ajax);
        state.hasLocation = Boolean(UMKM.location);
        state.hasSession = Boolean(UMKM.session);
        state.fetchMetadataSupported = true;
        state.refreshedAt = new Date().toISOString();

        return Object.assign({}, state);
    }

    function signature(current) {
        return [
            current.hasCsrf,
            current.hasAjax,
            current.hasLocation,
            current.hasSession,
            current.client,
            current.profile
        ].join('|');
    }

    function printStatus(force) {
        const current = refresh();
        const currentSignature = signature(current);

        if (!force && currentSignature === lastPrintedSignature) {
            return current;
        }

        lastPrintedSignature = currentSignature;

        if (UMKM.config?.debug) {
            console.groupCollapsed(
                '%c[UMKM Security]%c status',
                'background:#0f7665;color:#fff;border-radius:6px;padding:2px 6px;font-weight:700;',
                'font-weight:700;color:#10213d;'
            );
            console.table(current);
            console.groupEnd();
        }

        return current;
    }

    function scheduleStatusPrint(force) {
        if (statusTimer) {
            window.clearTimeout(statusTimer);
        }

        statusTimer = window.setTimeout(function () {
            printStatus(Boolean(force));
            statusTimer = null;
        }, 160);
    }

    function status() {
        return printStatus(true);
    }

    function markProtectedLinks(selector) {
        document.querySelectorAll(selector || '[data-security-gated]').forEach(function (element) {
            element.setAttribute('data-umkm-security-bound', 'true');
        });
    }

    function isCoreReady() {
        const current = refresh();

        return Boolean(
            current.hasCsrf &&
            current.hasAjax &&
            current.hasLocation &&
            current.hasSession
        );
    }

    UMKM.ready?.(function () {
        document.documentElement.setAttribute('data-umkm-security', 'booting');

        scheduleStatusPrint(true);

        window.setTimeout(function () {
            const current = refresh();

            document.documentElement.setAttribute(
                'data-umkm-security',
                isCoreReady() ? 'ready' : 'partial'
            );

            document.dispatchEvent(new CustomEvent('umkm:security:ready', {
                detail: current
            }));

            scheduleStatusPrint(true);
        }, 420);
    });

    document.addEventListener('umkm:module:ready', function () {
        const current = refresh();

        document.documentElement.setAttribute(
            'data-umkm-security',
            isCoreReady() ? 'ready' : 'partial'
        );

        document.dispatchEvent(new CustomEvent('umkm:security:updated', {
            detail: current
        }));

        scheduleStatusPrint(false);
    });

    UMKM.register?.('security', {
        state: refresh,
        status: status,
        isCoreReady: isCoreReady,
        markProtectedLinks: markProtectedLinks
    });
})();
