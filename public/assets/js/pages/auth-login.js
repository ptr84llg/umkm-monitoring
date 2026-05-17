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
            element.textContent = text;
        }
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

    function bindPasswordToggle() {
        document.querySelectorAll('[data-auth-password-toggle]').forEach(function (button) {
            const field = document.querySelector('[data-auth-password]');

            if (!field) {
                return;
            }

            button.addEventListener('click', function () {
                const isPassword = field.getAttribute('type') === 'password';

                field.setAttribute('type', isPassword ? 'text' : 'password');
                button.setAttribute('aria-label', isPassword ? 'Sembunyikan password' : 'Tampilkan password');
                button.setAttribute('data-auth-password-visible', isPassword ? 'true' : 'false');
                field.focus({ preventScroll: true });
            });
        });
    }

    async function checkLocation(elements) {
        setSubmitState(elements, false);
        clearLocationInputs(elements);
        setLocationStatus(elements, 'checking', 'Memeriksa lokasi perangkat...');

        if (!UMKM.location || typeof UMKM.location.check !== 'function') {
            setLocationStatus(elements, 'blocked', 'Modul lokasi belum siap. Muat ulang halaman sebelum login.');
            return false;
        }

        try {
            const result = await UMKM.location.check({
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            });

            if (result && result.ok) {
                fillLocationInputs(elements, result.state);
                setLocationStatus(elements, 'granted', 'Lokasi aktif. Form login siap digunakan.');
                setSubmitState(elements, true);
                return true;
            }

            setLocationStatus(elements, 'blocked', 'Lokasi belum aktif. Aktifkan izin lokasi untuk melanjutkan login.');
            setSubmitState(elements, false);
            return false;
        } catch (error) {
            setLocationStatus(elements, 'blocked', 'Pemeriksaan lokasi gagal. Coba periksa kembali izin lokasi browser.');
            setSubmitState(elements, false);
            return false;
        }
    }

    function bindLocationGate() {
        const form = document.querySelector('[data-auth-login-form]');
        const elements = {
            form: form,
            submit: document.querySelector('[data-auth-submit]'),
            checkButton: document.querySelector('[data-auth-location-check]'),
            status: document.querySelector('[data-auth-location-status]'),
            statusText: document.querySelector('[data-auth-location-text]'),
            statusInput: document.querySelector('[data-auth-location-status-input]'),
            latitudeInput: document.querySelector('[data-auth-location-latitude-input]'),
            longitudeInput: document.querySelector('[data-auth-location-longitude-input]'),
            accuracyInput: document.querySelector('[data-auth-location-accuracy-input]'),
            checkedAtInput: document.querySelector('[data-auth-location-checked-at-input]')
        };

        if (!form) {
            return;
        }

        setSubmitState(elements, false);
        setLocationStatus(elements, 'checking', 'Menyiapkan pemeriksaan lokasi perangkat...');

        window.setTimeout(function () {
            checkLocation(elements);
        }, 520);

        if (elements.checkButton) {
            elements.checkButton.addEventListener('click', function () {
                checkLocation(elements);
            });
        }

        document.addEventListener('umkm:location:permission-change', function () {
            checkLocation(elements);
        });

        form.addEventListener('submit', function (event) {
            const isGranted = elements.statusInput && elements.statusInput.value === 'granted';

            if (!isGranted) {
                event.preventDefault();
                checkLocation(elements);
                return;
            }

            if (UMKM.loader && typeof UMKM.loader.button === 'function' && elements.submit) {
                UMKM.loader.button(elements.submit, {
                    text: 'Memproses login...'
                });
            } else if (elements.submit) {
                elements.submit.disabled = true;
                const submitText = elements.submit.querySelector('.auth-submit-text');
                setText(submitText, 'Memproses login...');
            }
        });
    }

    ready(function () {
        bindPasswordToggle();
        bindLocationGate();

        document.documentElement.setAttribute('data-umkm-auth-login', 'ready');

        if (UMKM.register && typeof UMKM.register === 'function') {
            UMKM.register('authLogin', {
                ready: true
            });
        }
    });
})();
