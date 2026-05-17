(function () {
    'use strict';

    const Landing = window.UMKMLanding;

    function boot() {
        Landing.initHeaderAndNavigation?.();
        Landing.initRevealAnimation?.();
        Landing.initParallax?.();
        Landing.initRegionModal?.();
        Landing.initLocationGate?.();

        window.setTimeout(function () {
            if (Landing.qs('[data-public-metric="total"]')) {
                Landing.applyRegionSelection?.(Object.assign({}, Landing.DEFAULT_SELECTION));
            }
        }, 180);
    }

    Landing.ready(boot);
})();
