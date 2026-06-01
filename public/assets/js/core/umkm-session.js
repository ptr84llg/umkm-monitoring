(function () {
    'use strict';

    window.UMKM = window.UMKM || {};

    const UMKM = window.UMKM;

    const state = {
        startedAt: new Date().toISOString(),
        lastActivityAt: new Date().toISOString(),
        idleSeconds: 0,
        visible: document.visibilityState === 'visible',
        online: navigator.onLine,
        warningVisible: false,
        timedOut: false,
        keepAliveRunning: false
    };

    let timer = null;
    let warningModal = null;
    let warningNode = null;

    function toPositiveInt(value, fallback) {
        const parsed = Number.parseInt(value, 10);

        return Number.isFinite(parsed) && parsed > 0 ? parsed : fallback;
    }

    function guardElement() {
        return document.querySelector('[data-umkm-session-guard]');
    }

    function settings() {
        const guard = guardElement();
        const lifetimeMinutes = toPositiveInt(guard && guard.dataset.umkmSessionLifetimeMinutes, 60);
        const lifetimeSeconds = Math.max(60, lifetimeMinutes * 60);
        const requestedWarningSeconds = toPositiveInt(guard && guard.dataset.umkmSessionWarningSeconds, 300);
        const warningSeconds = Math.min(Math.max(30, requestedWarningSeconds), Math.max(30, lifetimeSeconds - 30));

        return {
            enabled: Boolean(guard),
            lifetimeMinutes: lifetimeMinutes,
            lifetimeSeconds: lifetimeSeconds,
            warningSeconds: warningSeconds,
            redirectUrl: guard && guard.dataset.umkmSessionRedirectUrl ? guard.dataset.umkmSessionRedirectUrl : '/',
            keepAliveUrl: guard && guard.dataset.umkmSessionKeepAliveUrl ? guard.dataset.umkmSessionKeepAliveUrl : '',
            logoutFormSelector: '.dashboard-logout-form'
        };
    }

    function formatDuration(seconds) {
        const safeSeconds = Math.max(0, Number.parseInt(seconds, 10) || 0);
        const minutes = Math.floor(safeSeconds / 60);
        const remainingSeconds = safeSeconds % 60;

        return String(minutes).padStart(2, '0') + ':' + String(remainingSeconds).padStart(2, '0');
    }

    function csrfTokenValue() {
        const meta = document.querySelector('meta[name="csrf-token"]');

        return meta ? (meta.getAttribute('content') || '') : '';
    }

    function dispatch(name) {
        document.dispatchEvent(new CustomEvent(name, {
            detail: Object.assign({}, state)
        }));
    }

    function touch(source, options) {
        const settingsData = settings();
        const config = Object.assign({
            force: false
        }, options || {});

        if (state.timedOut) {
            return;
        }

        if (state.warningVisible && !config.force) {
            return;
        }

        state.lastActivityAt = new Date().toISOString();
        state.idleSeconds = 0;

        dispatch('umkm:session:activity');

        if (source && source !== 'tick') {
            UMKM.log?.('debug', 'session activity touched', {
                source: source
            });
        }

        if (settingsData.enabled && state.warningVisible && config.force) {
            hideWarningModal();
        }
    }

    function updateIdle() {
        const last = new Date(state.lastActivityAt).getTime();

        state.idleSeconds = Math.max(0, Math.round((Date.now() - last) / 1000));

        dispatch('umkm:session:tick');

        enforceIdleGuard();
    }

    function remainingSeconds() {
        const settingsData = settings();

        return Math.max(0, settingsData.lifetimeSeconds - state.idleSeconds);
    }

    function warningThresholdReached() {
        const settingsData = settings();

        return state.idleSeconds >= (settingsData.lifetimeSeconds - settingsData.warningSeconds);
    }

    function createWarningModal() {
        if (warningNode) {
            return warningNode;
        }

        const node = document.createElement('div');

        node.className = 'modal fade';
        node.tabIndex = -1;
        node.setAttribute('aria-hidden', 'true');
        node.setAttribute('data-umkm-session-warning-modal', 'true');

        node.innerHTML = [
            '<div class="modal-dialog modal-dialog-centered">',
            '    <div class="modal-content border-0 rounded-4 shadow-lg">',
            '        <div class="modal-header border-0 pb-0">',
            '            <div>',
            '                <p class="text-uppercase small fw-bold text-warning-emphasis mb-1">Keamanan Sesi</p>',
            '                <h5 class="modal-title fw-bold">Sesi hampir berakhir</h5>',
            '            </div>',
            '        </div>',
            '        <div class="modal-body">',
            '            <p class="mb-3 text-body-secondary">Tidak ada aktivitas yang terdeteksi. Demi keamanan, sesi akan diakhiri otomatis jika tidak dilanjutkan.</p>',
            '            <div class="rounded-4 border bg-light p-3 d-flex align-items-center justify-content-between gap-3">',
            '                <span class="fw-semibold">Sisa waktu</span>',
            '                <span class="badge rounded-pill text-bg-warning fs-6" data-umkm-session-countdown>--:--</span>',
            '            </div>',
            '        </div>',
            '        <div class="modal-footer border-0 pt-0">',
            '            <button type="button" class="btn btn-outline-secondary rounded-pill" data-umkm-session-logout-now>Keluar sekarang</button>',
            '            <button type="button" class="btn btn-primary rounded-pill" data-umkm-session-continue>Tetap masuk</button>',
            '        </div>',
            '    </div>',
            '</div>'
        ].join('');

        document.body.appendChild(node);

        const continueButton = node.querySelector('[data-umkm-session-continue]');
        const logoutButton = node.querySelector('[data-umkm-session-logout-now]');

        if (continueButton) {
            continueButton.addEventListener('click', function () {
                keepSessionAlive();
            });
        }

        if (logoutButton) {
            logoutButton.addEventListener('click', function () {
                expireSession('manual_logout_from_warning');
            });
        }

        warningNode = node;

        return warningNode;
    }

    function showWarningModal() {
        const node = createWarningModal();

        state.warningVisible = true;
        updateCountdown();

        if (window.bootstrap && window.bootstrap.Modal) {
            warningModal = warningModal || new window.bootstrap.Modal(node, {
                backdrop: 'static',
                keyboard: false
            });

            warningModal.show();
        } else {
            node.classList.add('show');
            node.removeAttribute('aria-hidden');
        }

        dispatch('umkm:session:warning');
    }

    function hideWarningModal() {
        if (!warningNode) {
            state.warningVisible = false;
            return;
        }

        if (warningModal) {
            warningModal.hide();
        } else {
            warningNode.classList.remove('show');
            warningNode.setAttribute('aria-hidden', 'true');
        }

        state.warningVisible = false;
        dispatch('umkm:session:warning-hidden');
    }

    function updateCountdown() {
        if (!warningNode) {
            return;
        }

        const countdown = warningNode.querySelector('[data-umkm-session-countdown]');

        if (countdown) {
            countdown.textContent = formatDuration(remainingSeconds());
        }
    }

    async function keepSessionAlive() {
        const settingsData = settings();
        const csrfToken = csrfTokenValue();

        if (state.keepAliveRunning || state.timedOut) {
            return;
        }

        if (!settingsData.keepAliveUrl || !csrfToken) {
            expireSession('keep_alive_not_available');
            return;
        }

        state.keepAliveRunning = true;

        try {
            const response = await fetch(settingsData.keepAliveUrl, {
                method: 'POST',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-UMKM-Request': 'internal'
                },
                body: JSON.stringify({
                    intent: 'keep_alive'
                })
            });

            if (!response || !response.ok) {
                expireSession('keep_alive_failed');
                return;
            }

            const payload = await response.json().catch(function () {
                return null;
            });

            if (!payload || payload.ok !== true) {
                expireSession('keep_alive_invalid_response');
                return;
            }

            touch('keep_alive', {
                force: true
            });
        } catch (error) {
            expireSession('keep_alive_error');
        } finally {
            state.keepAliveRunning = false;
        }
    }

    function expireSession(reason) {
        const settingsData = settings();

        if (state.timedOut) {
            return;
        }

        state.timedOut = true;
        state.warningVisible = false;

        dispatch('umkm:session:expired');

        UMKM.log?.('warn', 'session expired by idle guard', {
            reason: reason || 'idle_timeout'
        });

        const form = document.querySelector(settingsData.logoutFormSelector);

        if (form && typeof form.submit === 'function') {
            form.submit();
            return;
        }

        window.location.assign(settingsData.redirectUrl || '/');
    }

    function enforceIdleGuard() {
        const settingsData = settings();

        if (!settingsData.enabled || state.timedOut) {
            return;
        }

        updateCountdown();

        if (state.idleSeconds >= settingsData.lifetimeSeconds) {
            expireSession('idle_timeout');
            return;
        }

        if (warningThresholdReached() && !state.warningVisible) {
            showWarningModal();
        }
    }

    function bindActivityEvents() {
        ['click', 'keydown', 'mousemove', 'scroll', 'touchstart'].forEach(function (eventName) {
            window.addEventListener(eventName, function () {
                touch(eventName);
            }, {
                passive: true
            });
        });
    }

    function bindVisibilityAndNetworkEvents() {
        document.addEventListener('visibilitychange', function () {
            state.visible = document.visibilityState === 'visible';
            dispatch('umkm:session:visibility');

            if (state.visible) {
                updateIdle();
            }
        });

        window.addEventListener('online', function () {
            state.online = true;
            dispatch('umkm:session:network');
        });

        window.addEventListener('offline', function () {
            state.online = false;
            dispatch('umkm:session:network');
        });
    }

    function start() {
        if (timer) {
            return;
        }

        bindActivityEvents();
        bindVisibilityAndNetworkEvents();

        timer = window.setInterval(updateIdle, 1000);
        updateIdle();

        UMKM.log?.('info', 'session monitor started', {
            state: Object.assign({}, state),
            settings: settings()
        });
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