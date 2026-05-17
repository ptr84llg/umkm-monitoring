(function () {
    'use strict';

    const Landing = window.UMKMLanding;

    Landing.initLocationGate = function () {
        if (!window.UMKM.locationGate || typeof window.UMKM.locationGate.init !== 'function') {
            Landing.log('warn', 'location gate core is not available', {});
            return;
        }

        window.UMKM.locationGate.init({
            rootSelector: '[data-location-gate-root]',
            fallbackRootSelector: '.umkm-landing',
            enableAutoCheck: true,
            autoCheckDelay: 450,
            checkOptions: {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        });
    };
})();
