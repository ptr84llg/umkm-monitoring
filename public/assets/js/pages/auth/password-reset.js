(function () {
    'use strict';

    window.UMKM = window.UMKM || {};

    var UMKM = window.UMKM;
    var resetCountdownTimer = null;

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

    function page() {
        return document.querySelector('[data-auth-password-page]');
    }

    function parseTime(value) {
        var timestamp = Date.parse(value || '');

        return Number.isFinite(timestamp) ? timestamp : 0;
    }

    function pad(value) {
        return value < 10 ? '0' + value : String(value);
    }

    function formatDuration(milliseconds) {
        var totalSeconds = Math.max(0, Math.ceil(milliseconds / 1000));
        var minutes = Math.floor(totalSeconds / 60);
        var seconds = totalSeconds % 60;

        return pad(minutes) + ':' + pad(seconds);
    }

    function setText(element, text) {
        if (element) {
            element.textContent = text == null ? '' : String(text);
        }
    }

    function setSubmitState(button, enabled, text) {
        if (!button) {
            return;
        }

        button.disabled = !enabled;
        button.setAttribute('aria-disabled', enabled ? 'false' : 'true');

        if (text) {
            button.textContent = text;
        }
    }

    function showLoading(title) {
        if (UMKM.modal && typeof UMKM.modal.showLoading === 'function') {
            UMKM.modal.showLoading({
                kicker: 'Reset Password',
                title: title,
                message: 'Sistem sedang memproses permintaan secara aman.',
                caption: 'Mohon tunggu dan jangan menutup halaman sampai proses selesai.'
            });
        }
    }

    function hideLoading() {
        if (UMKM.modal && typeof UMKM.modal.hideLoading === 'function') {
            UMKM.modal.hideLoading();
        }
    }

    function showBackendError(form, payload, response) {
        var message = payload && payload.message ? payload.message : 'Permintaan belum dapat diproses.';
        var errors = [];

        if (UMKM.forms && typeof UMKM.forms.errorsFromBackend === 'function') {
            errors = UMKM.forms.errorsFromBackend(payload, form);
        }

        if (!errors.length) {
            errors = [{
                field: form.querySelector('input[name="email"]'),
                label: 'Permintaan akun',
                message: message
            }];
        }

        if (UMKM.forms && typeof UMKM.forms.showValidationModal === 'function') {
            UMKM.forms.showValidationModal(errors, {
                title: response && response.status === 429 ? 'Terlalu banyak percobaan' : 'Permintaan belum berhasil',
                message: message
            });
        }
    }

    function expireResetForm() {
        var root = page();
        var validPanel = document.querySelector('[data-auth-reset-valid-panel]');
        var expiredPanel = document.querySelector('[data-auth-reset-expired-panel]');
        var badge = document.querySelector('[data-auth-reset-link-status]');
        var form = document.querySelector('form[data-auth-password-reset-form]');

        if (root) {
            root.dataset.authResetLinkInvalid = '1';
        }

        setText(badge, 'Link Kedaluwarsa');

        if (form) {
            Array.prototype.forEach.call(form.elements, function (element) {
                element.disabled = true;
            });
        }

        if (validPanel) {
            validPanel.hidden = true;
        }

        if (expiredPanel) {
            expiredPanel.hidden = false;
        }
    }

    function updateResetCountdown() {
        var root = page();

        if (!root || root.dataset.authResetLinkInvalid === '1') {
            return;
        }

        var expiresAt = parseTime(root.dataset.authResetLinkExpiresAt);
        var label = document.querySelector('[data-auth-reset-link-countdown]');
        var badge = document.querySelector('[data-auth-reset-link-status]');

        if (!expiresAt) {
            setText(label, '--:--');
            return;
        }

        var remaining = expiresAt - Date.now();

        if (remaining <= 0) {
            setText(label, 'Kedaluwarsa');
            expireResetForm();
            return;
        }

        setText(label, formatDuration(remaining));
        setText(badge, 'Link Aktif');
    }

    function startResetCountdown() {
        if (resetCountdownTimer) {
            window.clearInterval(resetCountdownTimer);
        }

        updateResetCountdown();
        resetCountdownTimer = window.setInterval(updateResetCountdown, 1000);
    }

    function bindForm(form) {
        if (!form || form.dataset.authPasswordResetBound === '1') {
            return;
        }

        form.dataset.authPasswordResetBound = '1';

        var submit = form.querySelector('[data-auth-password-reset-submit]');
        var originalText = submit ? submit.textContent : 'Kirim';

        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            updateResetCountdown();

            var root = page();

            if (root && root.dataset.authResetLinkInvalid === '1') {
                showBackendError(form, { message: 'Tautan reset sudah kedaluwarsa. Silakan minta tautan baru.' }, null);
                return;
            }

            if (!UMKM.forms || typeof UMKM.forms.ajaxSubmit !== 'function') {
                showBackendError(form, { message: 'Core form belum siap. Muat ulang halaman sebelum mengirim permintaan.' }, null);
                return;
            }

            if (UMKM.forms && typeof UMKM.forms.validate === 'function') {
                var validationErrors = UMKM.forms.validate(form);

                if (validationErrors.length && typeof UMKM.forms.showValidationModal === 'function') {
                    UMKM.forms.showValidationModal(validationErrors, {
                        title: 'Form belum lengkap',
                        message: 'Lengkapi atau perbaiki isian sebelum melanjutkan.'
                    });
                    return;
                }
            }

            setSubmitState(submit, false, 'Mengirim OTP...');
            showLoading('Menyiapkan Verifikasi OTP');

            var result = await UMKM.forms.ajaxSubmit(form, {
                validateFirst: false,
                onSuccess: function (response) {
                    var payload = response && response.payload ? response.payload : {};

                    hideLoading();
                    setSubmitState(submit, true, originalText);

                    if (payload && payload.ok === false) {
                        showBackendError(form, payload, response);
                        return;
                    }

                    if (UMKM.toast && typeof UMKM.toast.success === 'function') {
                        UMKM.toast.success({
                            title: 'OTP reset dikirim',
                            message: payload.message || 'Silakan lanjutkan verifikasi OTP.',
                            delay: 1000
                        });
                    }

                    if (payload.redirect_url) {
                        window.setTimeout(function () {
                            window.location.assign(payload.redirect_url);
                        }, 650);
                    }
                },
                onError: function (response) {
                    var payload = response && response.payload ? response.payload : {};

                    hideLoading();
                    setSubmitState(submit, true, originalText);
                    showBackendError(form, payload, response);
                }
            });

            if (!result || !result.ok) {
                hideLoading();
                setSubmitState(submit, true, originalText);
            }
        });
    }

    ready(function () {
        var forms = document.querySelectorAll('form[data-auth-password-reset-form]');
        Array.prototype.forEach.call(forms, bindForm);
        startResetCountdown();
        document.documentElement.setAttribute('data-umkm-auth-password-reset', 'ready');
    });
}());