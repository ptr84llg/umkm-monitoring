(function () {
    'use strict';

    window.UMKM = window.UMKM || {};

    const UMKM = window.UMKM;

    const state = {
        startedAt: new Date().toISOString(),
        lastActivityAt: new Date().toISOString(),
        idleSeconds: 0,
        visible: document.visibilityState === 'visible',
        online: navigator.onLine
    };

    let timer = null;

    function touch(source) {
        state.lastActivityAt = new Date().toISOString();
        state.idleSeconds = 0;

        document.dispatchEvent(new CustomEvent('umkm:session:activity', {
            detail: {
                source: source || 'unknown',
                state: Object.assign({}, state)
            }
        }));
    }

    function updateIdle() {
        const last = new Date(state.lastActivityAt).getTime();
        state.idleSeconds = Math.max(0, Math.round((Date.now() - last) / 1000));

        document.dispatchEvent(new CustomEvent('umkm:session:tick', {
            detail: Object.assign({}, state)
        }));
    }

    function start() {
        if (timer) {
            return;
        }

        ['click', 'keydown', 'mousemove', 'scroll', 'touchstart'].forEach(function (eventName) {
            window.addEventListener(eventName, function () {
                touch(eventName);
            }, {
                passive: true
            });
        });

        document.addEventListener('visibilitychange', function () {
            state.visible = document.visibilityState === 'visible';

            document.dispatchEvent(new CustomEvent('umkm:session:visibility', {
                detail: Object.assign({}, state)
            }));
        });

        window.addEventListener('online', function () {
            state.online = true;
            document.dispatchEvent(new CustomEvent('umkm:session:network', {
                detail: Object.assign({}, state)
            }));
        });

        window.addEventListener('offline', function () {
            state.online = false;
            document.dispatchEvent(new CustomEvent('umkm:session:network', {
                detail: Object.assign({}, state)
            }));
        });

        timer = window.setInterval(updateIdle, 1000);

        UMKM.log?.('info', 'session monitor started', state);
    }

    function getState() {
        updateIdle();
        return Object.assign({}, state);
    }

    UMKM.ready?.(start);

    UMKM.register?.('session', {
        start: start,
        touch: touch,
        state: getState
    });
})();
