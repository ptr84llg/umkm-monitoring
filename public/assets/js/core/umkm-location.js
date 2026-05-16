(function () {
    'use strict';

    window.UMKM = window.UMKM || {};

    const UMKM = window.UMKM;

    const state = {
        supported: 'geolocation' in navigator,
        permissionSupported: Boolean(navigator.permissions && navigator.permissions.query),
        permission: 'unknown',
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
            code: error.code || 0,
            type: codes[error.code] || 'unknown',
            message: error.message || 'Lokasi tidak dapat diperiksa.'
        };
    }

    async function permissionStatus() {
        if (!state.supported) {
            state.permission = 'unsupported';

            return {
                supported: false,
                permissionSupported: false,
                state: 'unsupported',
                message: 'Browser tidak mendukung geolocation.'
            };
        }

        if (!navigator.permissions || typeof navigator.permissions.query !== 'function') {
            state.permission = 'unknown';

            return {
                supported: true,
                permissionSupported: false,
                state: 'unknown',
                message: 'Permissions API tidak tersedia pada browser ini.'
            };
        }

        try {
            const permission = await navigator.permissions.query({
                name: 'geolocation'
            });

            state.permission = permission.state || 'unknown';

            return {
                supported: true,
                permissionSupported: true,
                state: state.permission,
                message: `Status izin lokasi: ${state.permission}`
            };
        } catch (error) {
            state.permission = 'unknown';

            return {
                supported: true,
                permissionSupported: false,
                state: 'unknown',
                message: error.message || 'Status izin lokasi tidak dapat dibaca.'
            };
        }
    }

    async function watchPermission(callback) {
        if (!navigator.permissions || typeof navigator.permissions.query !== 'function') {
            return null;
        }

        try {
            const permission = await navigator.permissions.query({
                name: 'geolocation'
            });

            state.permission = permission.state || 'unknown';

            permission.onchange = function () {
                state.permission = permission.state || 'unknown';

                const payload = {
                    supported: state.supported,
                    permissionSupported: true,
                    state: state.permission,
                    changedAt: new Date().toISOString()
                };

                emit('permission-change', payload);

                if (typeof callback === 'function') {
                    callback(payload);
                }
            };

            return permission;
        } catch (error) {
            UMKM.log?.('warn', 'location permission watch failed', error);
            return null;
        }
    }

    async function check(options) {
        options = Object.assign({
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        }, options || {});

        state.checkedAt = new Date().toISOString();

        if (!state.supported) {
            state.status = 'unsupported';
            state.permission = 'unsupported';
            state.lastError = {
                type: 'unsupported',
                message: 'Browser tidak mendukung pemeriksaan lokasi.'
            };

            emit('blocked', state);
            UMKM.log?.('warn', 'location unsupported', state);

            return {
                ok: false,
                permission: await permissionStatus(),
                state: Object.assign({}, state)
            };
        }

        const permission = await permissionStatus();

        if (permission.state === 'denied') {
            state.status = 'blocked';
            state.lastPosition = null;
            state.lastError = {
                type: 'permission_denied_browser',
                message: 'Izin lokasi diblokir oleh browser.'
            };
            state.checkedAt = new Date().toISOString();

            emit('blocked', state);
            UMKM.log?.('warn', 'location permission denied by browser', {
                permission: permission,
                state: state
            });

            return {
                ok: false,
                permission: permission,
                state: Object.assign({}, state)
            };
        }

        state.status = 'checking';
        emit('checking', state);
        UMKM.log?.('info', 'location checking', permission);

        return new Promise((resolve) => {
            navigator.geolocation.getCurrentPosition(function (position) {
                state.status = 'granted';
                state.permission = 'granted';
                state.lastPosition = normalizePosition(position);
                state.lastError = null;
                state.checkedAt = new Date().toISOString();

                emit('granted', state);
                UMKM.log?.('info', 'location granted', {
                    accuracy: state.lastPosition.accuracy
                });

                resolve({
                    ok: true,
                    permission: {
                        supported: true,
                        permissionSupported: permission.permissionSupported,
                        state: 'granted',
                        message: 'Izin lokasi aktif.'
                    },
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
                    permission: permission,
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
        permissionStatus: permissionStatus,
        watchPermission: watchPermission,
        isSupported: function () {
            return state.supported;
        }
    });
})();
