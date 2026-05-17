(function () {
    'use strict';

    window.UMKM = window.UMKM || {};

    const UMKM = window.UMKM;

    const DEFAULT_SELECTORS = {
        root: '[data-location-gate-root]',
        notice: '[data-location-gate-notice]',
        title: '[data-location-gate-title]',
        message: '[data-location-gate-message]',
        retry: '[data-location-retry]',
        close: '[data-location-gate-close]',
        guideToggle: '[data-location-guide-toggle]',
        guide: '[data-location-guide]',
        permissionBox: '[data-location-permission-state]',
        permissionLabel: '[data-location-permission-label]',
        statusChip: '[data-location-status-chip]',
        statusOpen: '[data-location-status-open]',
        statusLabel: '[data-location-status-label]',
        statusHint: '[data-location-status-hint]',
        info: '[data-location-info]',
        infoStatus: '[data-location-info-status]',
        infoCoordinate: '[data-location-info-coordinate]',
        infoAccuracy: '[data-location-info-accuracy]',
        infoCheckedAt: '[data-location-info-checked-at]',
        infoIp: '[data-location-info-ip]',
        infoDevice: '[data-location-info-device]',
        loginMount: '[data-login-mount]'
    };

    const state = {
        status: 'booting',
        permission: 'unknown',
        checkedAt: null,
        lastResult: null,
        dismissed: false,
        initialized: false
    };

    let settings = {
        rootSelector: '[data-location-gate-root]',
        fallbackRootSelector: '.umkm-landing',
        loginUrl: '/login',
        enableAutoCheck: true,
        autoCheckDelay: 450,
        checkOptions: {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        }
    };

    let checking = false;
    let watchedPermission = false;

    function qs(selector, root) {
        return (root || document).querySelector(selector);
    }

    function qsa(selector, root) {
        return Array.from((root || document).querySelectorAll(selector));
    }

    function setTextAll(selector, value, root) {
        qsa(selector, root).forEach(function (element) {
            element.textContent = value == null ? '' : String(value);
        });
    }

    function log(level, message, data) {
        if (UMKM.log && typeof UMKM.log === 'function') {
            UMKM.log(level, message, data);
        }
    }

    function emit(name, detail) {
        document.dispatchEvent(new CustomEvent('umkm:location-gate:' + name, {
            detail: detail || {}
        }));
    }

    function rootElement() {
        return qs(settings.rootSelector)
            || qs(settings.fallbackRootSelector)
            || document.body;
    }

    function getLoginUrl() {
        const root = rootElement();

        return root && root.dataset.loginUrl ? root.dataset.loginUrl : settings.loginUrl;
    }

    function getClientIp() {
        const root = rootElement();

        return root && root.dataset.locationClientIp ? root.dataset.locationClientIp : 'Belum tersedia';
    }

    function getClientDevice() {
        const root = rootElement();
        const serverAgent = root && root.dataset.locationClientUserAgent ? root.dataset.locationClientUserAgent : '';
        const browserAgent = navigator.userAgent || '';

        return browserAgent || serverAgent || 'Tidak terbaca';
    }

    function loginIconSvg() {
        return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17v-3H3v-4h7V7l5 5-5 5Zm2-14h7a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-7v-2h7V5h-7V3Z"/></svg>';
    }

    function createLoginLink(mount) {
        const link = document.createElement('a');
        const label = mount.dataset.loginLabel || 'Masuk Sistem';
        const variant = mount.dataset.loginVariant || 'default';
        const classes = mount.dataset.loginClass || 'btn btn-primary';

        link.className = classes;
        link.href = getLoginUrl();
        link.dataset.locationCreatedLogin = 'true';

        if (mount.dataset.loginKey) {
            link.dataset.locationLoginKey = mount.dataset.loginKey;
        }

        if (mount.dataset.loginMenuLink === 'true') {
            link.dataset.menuLink = 'true';
        }

        if (variant === 'mobile') {
            link.innerHTML = '<span class="mobile-canvas-link-icon">' + loginIconSvg() + '</span><span>' + label + '</span>';
            return link;
        }

        if (variant === 'footer') {
            link.innerHTML = '<span class="footer-link-icon">' + loginIconSvg() + '</span><span>' + label + '</span>';
            return link;
        }

        link.innerHTML = loginIconSvg() + '<span>' + label + '</span>';

        return link;
    }

    function renderLoginLinks(allowed) {
        qsa(DEFAULT_SELECTORS.loginMount).forEach(function (mount) {
            const existing = mount.querySelector('[data-location-created-login]');

            if (!allowed) {
                if (existing) {
                    existing.remove();
                }

                return;
            }

            if (existing) {
                return;
            }

            const link = createLoginLink(mount);

            link.addEventListener('click', function (event) {
                if (state.status !== 'granted') {
                    event.preventDefault();
                    open(true);
                }
            });

            mount.appendChild(link);
        });
    }

    function locationCopy(status) {
        const map = {
            checking: {
                label: 'Proses mengecek',
                hint: 'Lokasi',
                title: 'Memeriksa status lokasi',
                message: 'Mohon tunggu, sistem sedang memastikan lokasi aktif sebelum membuka akses masuk.'
            },
            granted: {
                label: 'Lokasi aktif',
                hint: 'Klik detail',
                title: 'Lokasi aktif',
                message: 'Lokasi perangkat berhasil diverifikasi. Tombol masuk sistem sudah ditampilkan.'
            },
            prompt: {
                label: 'Perlu izin lokasi',
                hint: 'Klik panduan',
                title: 'Izin lokasi diperlukan',
                message: 'Pilih Izinkan pada permintaan lokasi browser agar akses masuk ke sistem dapat ditampilkan.'
            },
            denied: {
                label: 'Lokasi ditolak',
                hint: 'Klik panduan',
                title: 'Izin lokasi diblokir oleh browser',
                message: 'Website tidak dapat menampilkan ulang permintaan lokasi karena izin sudah diblokir. Ubah izin lokasi dari pengaturan situs pada browser, lalu refresh halaman.'
            },
            blocked: {
                label: 'Lokasi ditolak',
                hint: 'Klik panduan',
                title: 'Lokasi belum aktif',
                message: 'Aktifkan izin lokasi pada browser untuk membuka akses masuk ke sistem.'
            },
            unsupported: {
                label: 'Lokasi tidak didukung',
                hint: 'Klik info',
                title: 'Validasi lokasi belum dapat digunakan',
                message: 'Browser atau perangkat belum mendukung pemeriksaan izin lokasi yang dibutuhkan untuk membuka akses masuk sistem.'
            },
            error: {
                label: 'Gagal cek lokasi',
                hint: 'Klik info',
                title: 'Validasi lokasi terganggu',
                message: 'Pemeriksaan lokasi belum berhasil. Muat ulang halaman atau klik cek ulang lokasi.'
            }
        };

        return map[status] || map.blocked;
    }

    function setPermissionLabel(permissionState) {
        state.permission = permissionState || 'unknown';

        const permissionBox = qs(DEFAULT_SELECTORS.permissionBox);

        if (permissionBox) {
            permissionBox.hidden = false;
        }

        setTextAll(DEFAULT_SELECTORS.permissionLabel, state.permission);
    }

    function formatCoordinate(value) {
        const number = Number(value);

        if (!Number.isFinite(number)) {
            return null;
        }

        return number.toFixed(6);
    }

    function formatCheckedAt(value) {
        if (!value) {
            return 'Belum tersedia';
        }

        try {
            return new Date(value).toLocaleString('id-ID');
        } catch (error) {
            return String(value);
        }
    }

    function setInfoVisible(visible) {
        const info = qs(DEFAULT_SELECTORS.info);

        if (info) {
            info.hidden = !visible;
        }
    }

    function setGuideVisible(visible) {
        const guide = qs(DEFAULT_SELECTORS.guide);
        const guideToggle = qs(DEFAULT_SELECTORS.guideToggle);

        if (!guide) {
            return;
        }

        guide.hidden = !visible;

        if (guideToggle) {
            const text = guideToggle.querySelector('span');

            if (text) {
                text.textContent = visible ? 'Sembunyikan panduan' : 'Cara mengaktifkan izin';
            }
        }
    }

    function setLocationInfo(result, status) {
        const locationState = result && result.state ? result.state : {};
        const position = locationState.lastPosition || {};
        const latitude = formatCoordinate(position.latitude);
        const longitude = formatCoordinate(position.longitude);
        const accuracy = Number(position.accuracy);
        const coordinateText = latitude && longitude ? latitude + ', ' + longitude : 'Belum tersedia';

        setTextAll(DEFAULT_SELECTORS.infoStatus, status || state.status || 'unknown');
        setTextAll(DEFAULT_SELECTORS.infoCoordinate, coordinateText);
        setTextAll(DEFAULT_SELECTORS.infoAccuracy, Number.isFinite(accuracy) ? Math.round(accuracy) + ' meter' : 'Belum tersedia');
        setTextAll(DEFAULT_SELECTORS.infoCheckedAt, formatCheckedAt(locationState.checkedAt || state.checkedAt));
        setTextAll(DEFAULT_SELECTORS.infoIp, getClientIp());
        setTextAll(DEFAULT_SELECTORS.infoDevice, getClientDevice());
    }

    function setLocationChip(status) {
        const copy = locationCopy(status);

        document.documentElement.setAttribute('data-location-gate', status);

        qsa(DEFAULT_SELECTORS.statusChip).forEach(function (chip) {
            chip.classList.remove('is-checking', 'is-granted', 'is-prompt', 'is-denied', 'is-blocked', 'is-unsupported', 'is-error');
            chip.classList.add('is-' + status);
            chip.setAttribute('aria-label', 'Status lokasi: ' + copy.label);
        });

        setTextAll(DEFAULT_SELECTORS.statusLabel, copy.label);
        setTextAll(DEFAULT_SELECTORS.statusHint, copy.hint);
    }

    function updateState(status, result, permissionState) {
        state.status = status;
        state.permission = permissionState || state.permission || 'unknown';
        state.checkedAt = new Date().toISOString();
        state.lastResult = result || null;

        setLocationChip(status);
        setLocationInfo(result, status);
        renderLoginLinks(status === 'granted');

        emit('updated', Object.assign({}, state));

        log(status === 'granted' ? 'info' : 'warn', 'location gate updated', Object.assign({}, state));
    }

    function setNotice(status, titleText, messageText, permissionState, result) {
        const notice = qs(DEFAULT_SELECTORS.notice);
        const noticeCard = notice ? notice.querySelector('.location-gate-card') : null;
        const title = qs(DEFAULT_SELECTORS.title);
        const message = qs(DEFAULT_SELECTORS.message);

        document.documentElement.setAttribute('data-location-gate', status);

        if (noticeCard) {
            noticeCard.classList.remove(
                'is-checking',
                'is-granted',
                'is-blocked',
                'is-unsupported',
                'is-denied',
                'is-prompt',
                'is-error'
            );
            noticeCard.classList.add('is-' + status);
        }

        if (title) {
            title.textContent = titleText;
        }

        if (message) {
            message.textContent = messageText;
        }

        if (permissionState) {
            setPermissionLabel(permissionState);
        }

        setLocationInfo(result, status);
        setInfoVisible(status === 'granted');
        setGuideVisible(status !== 'granted' && status !== 'checking');
    }

    function releaseModalFocus(notice) {
        if (UMKM.modal && typeof UMKM.modal.releaseFocus === 'function') {
            UMKM.modal.releaseFocus(notice, DEFAULT_SELECTORS.statusOpen);
            return;
        }

        const activeElement = document.activeElement;

        if (!notice || !activeElement || !notice.contains(activeElement)) {
            return;
        }

        if (typeof activeElement.blur === 'function') {
            activeElement.blur();
        }

        if (!document.body.hasAttribute('tabindex')) {
            document.body.setAttribute('tabindex', '-1');
        }

        document.body.focus({
            preventScroll: true
        });
    }

    function open(forceGuide) {
        const notice = qs(DEFAULT_SELECTORS.notice);
        const status = state.status || 'checking';
        const copy = locationCopy(status);

        setNotice(status, copy.title, copy.message, state.permission, state.lastResult);

        if (forceGuide && status !== 'granted') {
            setGuideVisible(true);
        }

        if (!notice) {
            return;
        }

        if (window.bootstrap && window.bootstrap.Modal) {
            const modal = window.bootstrap.Modal.getOrCreateInstance(notice, {
                backdrop: true,
                keyboard: true,
                focus: true
            });

            modal.show();
            return;
        }

        notice.hidden = false;
        notice.classList.add('show');
        notice.style.display = 'block';
        notice.setAttribute('aria-modal', 'true');
        notice.removeAttribute('aria-hidden');
        document.body.classList.add('is-location-gate-open');
    }

    function close() {
        const notice = qs(DEFAULT_SELECTORS.notice);

        state.dismissed = true;

        if (notice) {
            releaseModalFocus(notice);

            if (window.bootstrap && window.bootstrap.Modal) {
                const modal = window.bootstrap.Modal.getInstance(notice);

                if (modal) {
                    modal.hide();
                } else {
                    notice.classList.remove('show');
                    notice.style.display = 'none';
                    notice.setAttribute('aria-hidden', 'true');
                    notice.removeAttribute('aria-modal');
                }
            } else {
                notice.hidden = true;
                notice.classList.remove('show');
                notice.style.display = '';
                notice.setAttribute('aria-hidden', 'true');
                notice.removeAttribute('aria-modal');
            }
        }

        document.body.classList.remove('is-location-gate-open');

        emit('dismissed', Object.assign({}, state));
        log('info', 'location gate dismissed by user', Object.assign({}, state));
    }

    async function permissionStatus() {
        if (!UMKM.location || typeof UMKM.location.permissionStatus !== 'function') {
            return {
                supported: false,
                permissionSupported: false,
                state: 'unsupported',
                message: 'Modul lokasi belum tersedia.'
            };
        }

        return await UMKM.location.permissionStatus();
    }

    function showDenied(permission) {
        const copy = locationCopy('denied');

        setNotice(
            'denied',
            copy.title,
            copy.message,
            permission && permission.state ? permission.state : 'denied',
            null
        );

        updateState('denied', null, 'denied');
    }

    function showUnsupported(permission) {
        const copy = locationCopy('unsupported');
        const permissionState = permission && permission.state ? permission.state : 'unsupported';

        setNotice(
            'unsupported',
            copy.title,
            copy.message,
            permissionState,
            null
        );

        updateState('unsupported', null, permissionState);
    }

    function blockByResult(result) {
        const permission = result && result.permission ? result.permission : null;
        const locationState = result && result.state ? result.state : {};
        const lastError = locationState.lastError || {};
        const permissionState = permission && permission.state ? permission.state : locationState.permission || 'unknown';
        const type = lastError.type || locationState.status || 'blocked';

        if (permissionState === 'denied' || type === 'permission_denied_browser') {
            showDenied(permission || { state: 'denied' });
            return;
        }

        if (permissionState === 'unsupported' || type === 'unsupported') {
            showUnsupported(permission || { state: 'unsupported' });
            return;
        }

        let copy = locationCopy('blocked');

        if (type === 'timeout') {
            copy = {
                title: 'Pemeriksaan lokasi terlalu lama',
                message: 'Pastikan layanan lokasi aktif, lalu klik cek ulang lokasi.'
            };
        }

        setNotice(
            'blocked',
            copy.title,
            copy.message,
            permissionState,
            result
        );

        updateState('blocked', result, permissionState);
    }

    async function check(options) {
        options = Object.assign({
            keepModalOpen: false
        }, options || {});

        if (checking) {
            return false;
        }

        if (!UMKM.location || typeof UMKM.location.check !== 'function') {
            showUnsupported({ state: 'unsupported' });
            return false;
        }

        checking = true;

        updateState('checking', null, state.permission || 'unknown');
        setNotice(
            'checking',
            locationCopy('checking').title,
            locationCopy('checking').message,
            state.permission || 'unknown',
            null
        );

        try {
            const permission = await permissionStatus();

            setPermissionLabel(permission.state || 'unknown');

            if (permission.state === 'denied') {
                showDenied(permission);
                return false;
            }

            if (permission.state === 'unsupported') {
                showUnsupported(permission);
                return false;
            }

            if (permission.state === 'prompt') {
                setNotice(
                    'prompt',
                    locationCopy('prompt').title,
                    locationCopy('prompt').message,
                    'prompt',
                    null
                );
                updateState('prompt', null, 'prompt');
            }

            const result = await UMKM.location.check(settings.checkOptions);

            if (result && result.ok) {
                setNotice(
                    'granted',
                    locationCopy('granted').title,
                    locationCopy('granted').message,
                    'granted',
                    result
                );

                updateState('granted', result, 'granted');
                return true;
            }

            blockByResult(result);
            return false;
        } catch (error) {
            setNotice(
                'error',
                locationCopy('error').title,
                locationCopy('error').message,
                'unknown',
                { error: error.message || 'location check failed' }
            );

            updateState('error', { error: error.message || 'location check failed' }, 'unknown');
            return false;
        } finally {
            checking = false;
        }
    }

    function bindModalEvents() {
        const notice = qs(DEFAULT_SELECTORS.notice);

        if (notice) {
            if (UMKM.modal && typeof UMKM.modal.bindFocusGuard === 'function') {
                UMKM.modal.bindFocusGuard(notice, {
                    fallbackTriggerSelector: DEFAULT_SELECTORS.statusOpen,
                    returnFocus: true,
                    setInertWhenHidden: true
                });
            }

            notice.addEventListener('shown.bs.modal', function () {
                document.body.classList.add('is-location-gate-open');
            });

            notice.addEventListener('hidden.bs.modal', function () {
                document.body.classList.remove('is-location-gate-open');
            });
        }

        qsa(DEFAULT_SELECTORS.statusOpen).forEach(function (button) {
            if (button.dataset.locationGateBound === 'true') {
                return;
            }

            button.dataset.locationGateBound = 'true';

            button.addEventListener('click', function () {
                open(state.status !== 'granted');
            });
        });

        qsa(DEFAULT_SELECTORS.retry).forEach(function (button) {
            if (button.dataset.locationRetryBound === 'true') {
                return;
            }

            button.dataset.locationRetryBound = 'true';

            button.addEventListener('click', function () {
                check({
                    keepModalOpen: true
                });
            });
        });

        qsa(DEFAULT_SELECTORS.close).forEach(function (button) {
            if (button.dataset.locationCloseBound === 'true') {
                return;
            }

            button.dataset.locationCloseBound = 'true';
            button.addEventListener('click', close);
        });

        qsa(DEFAULT_SELECTORS.guideToggle).forEach(function (button) {
            if (button.dataset.locationGuideBound === 'true') {
                return;
            }

            button.dataset.locationGuideBound = 'true';

            button.addEventListener('click', function () {
                const guide = qs(DEFAULT_SELECTORS.guide);

                if (!guide) {
                    return;
                }

                setGuideVisible(Boolean(guide.hidden));
            });
        });
    }

    function watchPermission() {
        if (watchedPermission || !UMKM.location || typeof UMKM.location.watchPermission !== 'function') {
            return;
        }

        watchedPermission = true;

        UMKM.location.watchPermission(function (permission) {
            setPermissionLabel(permission.state || 'unknown');

            if (permission.state === 'granted') {
                check({
                    keepModalOpen: false
                });
                return;
            }

            if (permission.state === 'denied') {
                showDenied(permission);
                return;
            }

            if (permission.state === 'prompt') {
                setNotice(
                    'prompt',
                    locationCopy('prompt').title,
                    'Klik status lokasi, lalu pilih Izinkan pada permintaan lokasi browser.',
                    'prompt',
                    null
                );
                updateState('prompt', null, 'prompt');
            }
        });
    }

    function init(options) {
        settings = Object.assign({}, settings, options || {});
        settings.checkOptions = Object.assign({
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        }, settings.checkOptions || {}, (options && options.checkOptions) || {});

        bindModalEvents();
        renderLoginLinks(false);
        setLocationChip('checking');

        watchPermission();

        state.initialized = true;

        if (settings.enableAutoCheck) {
            window.setTimeout(function () {
                check({
                    keepModalOpen: false
                });
            }, settings.autoCheckDelay);
        }

        if (UMKM.register) {
            UMKM.register('locationGate', {
                init: init,
                check: check,
                state: function () {
                    return Object.assign({}, state);
                },
                close: close,
                open: open,
                permission: permissionStatus,
                renderLoginLinks: renderLoginLinks
            });
        }

        emit('initialized', Object.assign({}, state));
    }

    UMKM.locationGate = {
        init: init,
        check: check,
        state: function () {
            return Object.assign({}, state);
        },
        close: close,
        open: open,
        permission: permissionStatus,
        renderLoginLinks: renderLoginLinks
    };

    UMKM.register?.('locationGate', UMKM.locationGate);
})();
