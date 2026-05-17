(function () {
    'use strict';

    const Landing = window.UMKMLanding;

    Landing.refreshLocationGateMounts = function () {
        if (!window.UMKM.locationGate || typeof window.UMKM.locationGate.renderLoginLinks !== 'function') {
            return;
        }

        const gateState = typeof window.UMKM.locationGate.state === 'function'
            ? window.UMKM.locationGate.state()
            : {};

        window.UMKM.locationGate.renderLoginLinks(
            gateState.status === 'granted' && gateState.serverVerified === true
        );
    };

    Landing.initLoadedLandingComponent = function (detail) {
        if (!detail || !detail.component) {
            return;
        }

        Landing.initRevealAnimation?.();
        Landing.refreshLocationGateMounts();

        if (detail.component === 'landing-hero-preview-board') {
            Landing.initCounters?.();
            Landing.initTiltCard?.();
            Landing.initRegionModal?.();

            window.setTimeout(function () {
                Landing.loadPreviewData?.(Landing.regionState.applied || Object.assign({}, Landing.DEFAULT_SELECTION));
            }, 60);

            return;
        }

        if (detail.component === 'landing-dashboard-preview') {
            Landing.initChart?.();
            Landing.initChartResponsiveEvents?.();

            window.setTimeout(function () {
                Landing.loadPreviewData?.(Landing.regionState.applied || Object.assign({}, Landing.DEFAULT_SELECTION));
            }, 60);

            return;
        }

        if (detail.component === 'landing-summary-section' || detail.component === 'landing-cta-section') {
            Landing.initRevealAnimation?.();
            Landing.refreshLocationGateMounts();
        }
    };

    document.addEventListener('umkm:component-loader:loaded', function (event) {
        Landing.initLoadedLandingComponent(event.detail || {});
    });
})();
