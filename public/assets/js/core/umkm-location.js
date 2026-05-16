(function () {
    'use strict';

    window.UMKM = window.UMKM || {};

    const UMKM = window.UMKM;

    const state = {
        supported: 'geolocation' in navigator,
        status: 'idle',
        lastPosition: null,
        lastError: null,
        checkedAt: null
    };

    function emit(name, detail) {
        document.dispatchEvent(new CustomEvent(`umkm:location:${name}`, {
            detail: detail || {}
        }));
    }

    function normalizePosition(position) {
        return {
            latitude: position.coords.latitude,
            longitude: position.coords.longitude,
            accuracy: position.coords.accuracy,
            altitude: position.coords.altitude,
            altitudeAccuracy: position.coords.altitudeAccuracy,
            heading: position.coords.heading,
            speed: position.coords.speed,
            timestamp: position.timestamp
        };
    }

    function normalizeError(error) {
        const codes = {
            1: 'permission_denied',
            2: 'position_unavailable',
            3: 'timeout'
        };

        return {
            code: error.code,
            type: codes[error.code] || 'unknown',
            message: error.message || 'Lokasi tidak dapat diperiksa.'
        };
    }

    function check(options) {
        options = Object.assign({
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        }, options || {});

        state.checkedAt = new Date().toISOString();

        if (!state.supported) {
            state.status = 'unsupported';
            state.lastError = {
                type: 'unsupported',
                message: 'Browser tidak mendukung pemeriksaan lokasi.'
            };

            emit('blocked', state);
            UMKM.log?.('warn', 'location unsupported', state);

            return Promise.resolve({
                ok: false,
                state: Object.assign({}, state)
            });
        }

        state.status = 'checking';
        emit('checking', state);
        UMKM.log?.('info', 'location checking');

        return new Promise((resolve) => {
            navigator.geolocation.getCurrentPosition(function (position) {
                state.status = 'granted';
                state.lastPosition = normalizePosition(position);
                state.lastError = null;
                state.checkedAt = new Date().toISOString();

                emit('granted', state);
                UMKM.log?.('info', 'location granted', {
                    accuracy: state.lastPosition.accuracy
                });

                resolve({
                    ok: true,
                    state: Object.assign({}, state)
                });
            }, function (error) {
                state.status = 'blocked';
                state.lastPosition = null;
                state.lastError = normalizeError(error);
                state.checkedAt = new Date().toISOString();

                emit('blocked', state);
                UMKM.log?.('warn', 'location blocked', state.lastError);

                resolve({
                    ok: false,
                    state: Object.assign({}, state)
                });
            }, options);
        });
    }

    function currentState() {
        return Object.assign({}, state);
    }

    UMKM.register?.('location', {
        check: check,
        state: currentState,
        isSupported: function () {
            return state.supported;
        }
    });
})();
