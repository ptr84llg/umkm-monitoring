(function () {
    'use strict';

    window.UMKM = window.UMKM || {};

    const UMKM = window.UMKM;

    function ready(callback) {
        if (UMKM.ready && typeof UMKM.ready === 'function') {
            UMKM.ready(callback);
            return;
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
            return;
        }

        callback();
    }

    function setText(element, text) {
        if (element) {
            element.textContent = text == null ? '' : String(text);
        }
    }

    function toInt(value, fallback) {
        const parsed = Number.parseInt(value, 10);

        return Number.isFinite(parsed) && parsed > 0 ? parsed : fallback;
    }

    function loginPage() {
        return document.querySelector('[data-auth-login-page]');
    }

    function getLandingUrl() {
        const page = loginPage();

        return page && page.dataset.authLandingUrl ? page.dataset.authLandingUrl : '/';
    }

    function getMaxFailures() {
        const page = loginPage();

        return toInt(page && page.dataset.authLocationMaxFailures, 3);
    }

    function setTimeToSubmit(elements) {
        if (!elements || !elements.ttsInput || !elements.formReadyAt) {
            return;
        }

        const elapsedSeconds = Math.max(1, Math.ceil((Date.now() - elements.formReadyAt) / 1000));
        elements.ttsInput.value = String(elapsedSeconds);
    }

    function setLocationStatus(elements, status, message) {
        if (elements.status) {
            elements.status.setAttribute('data-auth-location-status', status);
        }

        if (elements.statusInput) {
            elements.statusInput.value = status;
        }

        setText(elements.statusText, message);
    }

    function clearLocationInputs(elements) {
        if (elements.latitudeInput) {
            elements.latitudeInput.value = '';
        }

        if (elements.longitudeInput) {
            elements.longitudeInput.value = '';
        }

        if (elements.accuracyInput) {
            elements.accuracyInput.value = '';
        }

        if (elements.checkedAtInput) {
            elements.checkedAtInput.value = '';
        }
    }

    function clearSensitiveInputs(elements) {
        if (elements.password) {
            elements.password.value = '';
            elements.password.setAttribute('type', 'password');
        }

        document.querySelectorAll('[data-auth-password-toggle]').forEach(function (button) {
            button.setAttribute('aria-label', 'Tampilkan password');
            button.setAttribute('data-auth-password-visible', 'false');
        });
    }

    function fillLocationInputs(elements, locationState) {
        const position = locationState && locationState.lastPosition ? locationState.lastPosition : null;

        if (!position) {
            clearLocationInputs(elements);
            return;
        }

        if (elements.latitudeInput) {
            elements.latitudeInput.value = String(position.latitude || '');
        }

        if (elements.longitudeInput) {
            elements.longitudeInput.value = String(position.longitude || '');
        }

        if (elements.accuracyInput) {
            elements.accuracyInput.value = String(position.accuracy || '');
        }

        if (elements.checkedAtInput) {
            elements.checkedAtInput.value = locationState.checkedAt || new Date().toISOString();
        }
    }

    function setSubmitState(elements, enabled) {
        if (!elements.submit) {
            return;
        }

        elements.submit.disabled = !enabled;
        elements.submit.setAttribute('aria-disabled', enabled ? 'false' : 'true');
    }

    function setFormVisible(elements, visible) {
        if (!elements.formShell) {
            return;
        }

        elements.formShell.classList.remove('is-location-hidden');
        elements.formShell.classList.toggle('is-location-locked', !visible);
        elements.formShell.setAttribute('aria-hidden', 'false');
    }

    function setReadingVisible(elements, visible) {
        if (!elements.reading) {
            return;
        }

        elements.reading.hidden = !visible;
        elements.reading.classList.toggle('is-active', Boolean(visible));
    }

    function setReadingCopy(elements, title, message, attemptText) {
        setText(elements.readingTitle, title);
        setText(elements.readingMessage, message);
        setText(elements.readingAttempt, attemptText);
    }

    function lockLoginForLocationCheck(elements, message, attemptText) {
        setSubmitState(elements, false);
        setFormVisible(elements, true);
        setReadingVisible(elements, true);
        clearLocationInputs(elements);
        setLocationStatus(elements, 'checking', message || 'Membaca lokasi perangkat...');
        setReadingCopy(
            elements,
            'Sedang membaca lokasi perangkat',
            'Sistem sedang memastikan lokasi aktif sebelum form login ditampilkan.',
            attemptText || ''
        );
    }

    function unlockLoginAfterLocationGranted(elements, locationState) {
        fillLocationInputs(elements, locationState);
        setLocationStatus(elements, 'granted', 'Lokasi aktif. Form login siap digunakan.');
        setReadingCopy(
            elements,
            'Lokasi berhasil dibaca',
            'Form login telah dibuka karena lokasi perangkat aktif.',
            ''
        );
        setReadingVisible(elements, true);
        setFormVisible(elements, true);
        setSubmitState(elements, true);

        if (elements.email && !elements.email.value) {
            elements.email.focus({ preventScroll: true });
        }
    }

    function redirectToLanding(elements) {
        if (elements.redirecting) {
            return;
        }

        elements.redirecting = true;
        setSubmitState(elements, false);
        setFormVisible(elements, true);
        clearSensitiveInputs(elements);
        setLocationStatus(elements, 'blocked', 'Lokasi tidak aktif. Anda akan diarahkan ke halaman awal.');
        setReadingVisible(elements, true);
        setReadingCopy(
            elements,
            'Lokasi belum aktif',
            'Sistem tidak dapat melanjutkan halaman login karena lokasi gagal dibaca sebanyak 3 kali.',
            'Mengalihkan ke halaman awal...'
        );

        window.setTimeout(function () {
            window.location.assign(getLandingUrl());
        }, 900);
    }

    function scheduleRetry(elements) {
        if (elements.retryTimer) {
            window.clearTimeout(elements.retryTimer);
        }

        elements.retryTimer = window.setTimeout(function () {
            checkLocation(elements, {
                reason: 'retry'
            });
        }, 1100);
    }

    function handleLocationFailure(elements, message) {
        const maxFailures = getMaxFailures();

        elements.failureCount += 1;

        setSubmitState(elements, false);
        setFormVisible(elements, true);
        setReadingVisible(elements, true);
        clearLocationInputs(elements);

        const attemptText = 'Percobaan ' + Math.min(elements.failureCount, maxFailures) + ' dari ' + maxFailures;

        setLocationStatus(
            elements,
            'blocked',
            message || 'Lokasi belum aktif. Sistem sedang mencoba membaca ulang lokasi perangkat.'
        );

        setReadingCopy(
            elements,
            'Lokasi belum terbaca',
            'Aktifkan izin lokasi pada browser/perangkat. Sistem akan mencoba membaca ulang secara otomatis.',
            attemptText
        );

        if (elements.failureCount >= maxFailures) {
            redirectToLanding(elements);
            return;
        }

        scheduleRetry(elements);
    }

    function bindPasswordToggle() {
        document.querySelectorAll('[data-auth-password-toggle]').forEach(function (button) {
            const field = document.querySelector('[data-auth-password]');

            if (!field || button.dataset.authPasswordToggleBound === 'true') {
                return;
            }

            button.dataset.authPasswordToggleBound = 'true';

            button.addEventListener('click', function () {
                const isPassword = field.getAttribute('type') === 'password';

                field.setAttribute('type', isPassword ? 'text' : 'password');
                button.setAttribute('aria-label', isPassword ? 'Sembunyikan password' : 'Tampilkan password');
                button.setAttribute('data-auth-password-visible', isPassword ? 'true' : 'false');
                field.focus({ preventScroll: true });
            });
        });
    }

    async function checkLocation(elements, options) {
        const settings = Object.assign({
            reason: 'manual'
        }, options || {});

        if (elements.redirecting || elements.checking) {
            return false;
        }

        elements.checking = true;

        const maxFailures = getMaxFailures();
        const nextAttempt = Math.min(elements.failureCount + 1, maxFailures);

        lockLoginForLocationCheck(
            elements,
            settings.reason === 'initial'
                ? 'Membaca lokasi perangkat sebelum form login ditampilkan...'
                : 'Memeriksa ulang lokasi perangkat...',
            'Percobaan ' + nextAttempt + ' dari ' + maxFailures
        );

        if (!UMKM.location || typeof UMKM.location.check !== 'function') {
            elements.checking = false;
            handleLocationFailure(elements, 'Modul lokasi belum siap. Sistem mencoba membaca ulang lokasi.');
            return false;
        }

        try {
            const result = await UMKM.location.check({
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            });

            elements.checking = false;

            if (result && result.ok) {
                elements.failureCount = 0;

                if (elements.retryTimer) {
                    window.clearTimeout(elements.retryTimer);
                    elements.retryTimer = null;
                }

                unlockLoginAfterLocationGranted(elements, result.state);
                return true;
            }

            handleLocationFailure(elements, 'Lokasi belum aktif. Aktifkan izin lokasi untuk melanjutkan login.');
            return false;
        } catch (error) {
            elements.checking = false;
            handleLocationFailure(elements, 'Pemeriksaan lokasi gagal. Sistem mencoba membaca ulang lokasi.');
            return false;
        }
    }

    function bindLocationGuard() {
        const form = document.querySelector('[data-auth-login-form]');
        const elements = {
            form: form,
            formShell: document.querySelector('[data-auth-login-form-shell]'),
            reading: document.querySelector('[data-auth-location-reading]'),
            readingTitle: document.querySelector('[data-auth-location-reading-title]'),
            readingMessage: document.querySelector('[data-auth-location-reading-message]'),
            readingAttempt: document.querySelector('[data-auth-location-attempt]'),
            submit: document.querySelector('[data-auth-submit]'),
            checkButton: document.querySelector('[data-auth-location-check]'),
            status: document.querySelector('[data-auth-location-status]'),
            statusText: document.querySelector('[data-auth-location-text]'),
            statusInput: document.querySelector('[data-auth-location-status-input]'),
            latitudeInput: document.querySelector('[data-auth-location-latitude-input]'),
            longitudeInput: document.querySelector('[data-auth-location-longitude-input]'),
            accuracyInput: document.querySelector('[data-auth-location-accuracy-input]'),
            checkedAtInput: document.querySelector('[data-auth-location-checked-at-input]'),
            ttsInput: document.querySelector('[data-auth-tts-input]'),
            email: document.querySelector('#email'),
            password: document.querySelector('[data-auth-password]'),
            formReadyAt: Date.now(),
            failureCount: 0,
            checking: false,
            redirecting: false,
            retryTimer: null,
            intervalTimer: null
        };

        if (!form) {
            return;
        }

        lockLoginForLocationCheck(
            elements,
            'Menyiapkan pemeriksaan lokasi perangkat...',
            'Percobaan 1 dari ' + getMaxFailures()
        );

        window.setTimeout(function () {
            checkLocation(elements, {
                reason: 'initial'
            });
        }, 420);

        if (elements.checkButton) {
            elements.checkButton.addEventListener('click', function () {
                elements.failureCount = 0;

                if (elements.retryTimer) {
                    window.clearTimeout(elements.retryTimer);
                    elements.retryTimer = null;
                }

                checkLocation(elements, {
                    reason: 'manual'
                });
            });
        }

        document.addEventListener('umkm:location:permission-change', function () {
            elements.failureCount = 0;
            checkLocation(elements, {
                reason: 'permission-change'
            });
        });

        window.addEventListener('focus', function () {
            if (!elements.redirecting) {
                checkLocation(elements, {
                    reason: 'focus'
                });
            }
        });

        document.addEventListener('visibilitychange', function () {
            if (!document.hidden && !elements.redirecting) {
                checkLocation(elements, {
                    reason: 'visibility'
                });
            }
        });

        elements.intervalTimer = window.setInterval(function () {
            if (!elements.redirecting && !elements.checking) {
                checkLocation(elements, {
                    reason: 'interval'
                });
            }
        }, 30000);

        form.addEventListener('submit', async function (event) {
            event.preventDefault();

            const isGranted = elements.statusInput && elements.statusInput.value === 'granted';

            if (!isGranted) {
                checkLocation(elements, {
                    reason: 'submit'
                });
                return;
            }

            if (UMKM.forms && typeof UMKM.forms.validate === 'function') {
                const errors = UMKM.forms.validate(form);

                if (errors.length) {
                    if (typeof UMKM.forms.showValidationModal === 'function') {
                        UMKM.forms.showValidationModal(errors, {
                            title: 'Login belum lengkap',
                            message: 'Lengkapi atau perbaiki isian berikut sebelum masuk ke sistem.'
                        });
                    }

                    return;
                }
            }

            const submitText = elements.submit ? elements.submit.querySelector('.auth-submit-text') : null;
            const originalSubmitText = submitText ? submitText.textContent : 'Masuk ke Sistem';

            setSubmitState(elements, false);
            setText(submitText, 'Memproses login...');

            if (!UMKM.forms || typeof UMKM.forms.ajaxSubmit !== 'function') {
                setText(submitText, originalSubmitText);
                setSubmitState(elements, true);

                if (UMKM.forms && typeof UMKM.forms.showValidationModal === 'function') {
                    UMKM.forms.showValidationModal([
                        {
                            field: elements.email,
                            label: 'Sistem login',
                            message: 'Core form belum siap. Muat ulang halaman sebelum login.'
                        }
                    ], {
                        title: 'Login belum dapat diproses',
                        message: 'Sistem belum siap memproses login melalui AJAX.'
                    });
                }

                return;
            }

            setTimeToSubmit(elements);

            const result = await UMKM.forms.ajaxSubmit(form, {
                validateFirst: false,
                onSuccess: function (response) {
                    const payload = response && response.payload ? response.payload : {};
                    const redirectUrl = payload.redirect_url || '/dashboard/interaktif';

                    window.location.assign(redirectUrl);
                },
                onError: function (response, backendErrors) {
                    const payload = response && response.payload ? response.payload : {};
                    const message = payload.message || 'Login belum berhasil. Periksa kembali data dan kesiapan lokasi.';

                    setText(submitText, originalSubmitText);

                    const stillGranted = elements.statusInput && elements.statusInput.value === 'granted';
                    setSubmitState(elements, Boolean(stillGranted));

                    if (response && response.status === 403) {
                        if (UMKM.forms && typeof UMKM.forms.showValidationModal === 'function') {
                            UMKM.forms.showValidationModal([
                                {
                                    field: elements.email,
                                    label: 'Akses login',
                                    message: message
                                }
                            ], {
                                title: 'Akses login memerlukan lokasi',
                                message: 'Anda akan diarahkan kembali ke halaman awal untuk memvalidasi lokasi.'
                            });
                        }

                        window.setTimeout(function () {
                            window.location.assign(getLandingUrl());
                        }, 1100);

                        return;
                    }

                    if ((!backendErrors || !backendErrors.length) && UMKM.forms && typeof UMKM.forms.showValidationModal === 'function') {
                        UMKM.forms.showValidationModal([
                            {
                                field: elements.email,
                                label: 'Login',
                                message: message
                            }
                        ], {
                            title: 'Login belum berhasil',
                            message: 'Periksa kembali data login Anda.'
                        });
                    }
                }
            });

            if (!result || !result.ok) {
                setText(submitText, originalSubmitText);

                const stillGranted = elements.statusInput && elements.statusInput.value === 'granted';
                setSubmitState(elements, Boolean(stillGranted));
            }
        });
    }

    ready(function () {
        bindPasswordToggle();
        bindLocationGuard();

        document.documentElement.setAttribute('data-umkm-auth-login', 'ready');

        if (UMKM.register && typeof UMKM.register === 'function') {
            UMKM.register('authLogin', {
                ready: true
            });
        }
    });
})();





