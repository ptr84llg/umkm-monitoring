(function () {
    'use strict';

    window.UMKM = window.UMKM || {};

    var UMKM = window.UMKM;

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
                kicker: 'Akses Akun',
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

    function bindForm(form) {
        if (!form || form.dataset.authPasswordResetBound === '1') {
            return;
        }

        form.dataset.authPasswordResetBound = '1';

        var submit = form.querySelector('[data-auth-password-reset-submit]');
        var originalText = submit ? submit.textContent : 'Kirim';

        form.addEventListener('submit', async function (event) {
            event.preventDefault();

            if (!UMKM.forms || typeof UMKM.forms.ajaxSubmit !== 'function') {
                showBackendError(form, {
                    message: 'Core form belum siap. Muat ulang halaman sebelum mengirim permintaan.'
                }, null);
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

            setSubmitState(submit, false, 'Memproses...');
            showLoading('Memproses Permintaan');

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
                            title: 'Permintaan diproses',
                            message: payload.message || 'Permintaan berhasil diproses.'
                        });
                    }

                    if (payload.redirect_url) {
                        window.setTimeout(function () {
                            window.location.assign(payload.redirect_url);
                        }, 900);
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
        document.documentElement.setAttribute('data-umkm-auth-password-reset', 'ready');
    });
}());
