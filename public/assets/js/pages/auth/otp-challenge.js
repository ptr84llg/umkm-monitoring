(function () {
    'use strict';

    window.UMKM = window.UMKM || {};

    var UMKM = window.UMKM;
    var countdownTimer = null;
    var pageLoadedAt = Date.now();

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
        return document.querySelector('[data-auth-otp-page]');
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

    function setButtonState(button, enabled) {
        if (!button) {
            return;
        }

        button.disabled = !enabled;
        button.setAttribute('aria-disabled', enabled ? 'false' : 'true');
    }

    function showLoading(title, message) {
        if (UMKM.modal && typeof UMKM.modal.showLoading === 'function') {
            UMKM.modal.showLoading({
                kicker: 'OTP',
                title: title,
                message: message || 'Sistem sedang memproses verifikasi secara aman.',
                caption: 'Mohon tunggu dan jangan menutup halaman.'
            });
        }
    }

    function hideLoading() {
        if (UMKM.modal && typeof UMKM.modal.hideLoading === 'function') {
            UMKM.modal.hideLoading();
        }
    }

    function showError(form, payload, response) {
        var message = payload && payload.message ? payload.message : 'Verifikasi belum dapat diproses.';
        var errors = [];

        if (payload && payload.force_relogin && payload.redirect_url) {
            if (UMKM.toast && typeof UMKM.toast.warning === 'function') {
                UMKM.toast.warning({
                    title: 'Sesi verifikasi berakhir',
                    message: message,
                    delay: 1200
                });
            }

            window.setTimeout(function () {
                window.location.assign(payload.redirect_url);
            }, 900);
            return;
        }

        if (UMKM.forms && typeof UMKM.forms.errorsFromBackend === 'function') {
            errors = UMKM.forms.errorsFromBackend(payload, form);
        }

        if (!errors.length) {
            errors = [{
                field: form ? form.querySelector('[data-auth-otp-digit]') : null,
                label: 'Kode OTP',
                message: message
            }];
        }

        if (UMKM.forms && typeof UMKM.forms.showValidationModal === 'function') {
            UMKM.forms.showValidationModal(errors, {
                title: response && response.status === 429 ? 'Tunggu sebelum kirim ulang' : 'Verifikasi belum berhasil',
                message: message
            });
        }
    }

    function getDigits() {
        return Array.prototype.slice.call(document.querySelectorAll('[data-auth-otp-digit]'));
    }

    function codeFromDigits() {
        return getDigits().map(function (input) {
            return input.value || '';
        }).join('');
    }

    function syncHiddenCode() {
        var hidden = document.querySelector('[data-auth-otp-code]');

        if (hidden) {
            hidden.value = codeFromDigits();
        }
    }

    function clearDigits() {
        getDigits().forEach(function (input) {
            input.value = '';
            input.classList.remove('is-filled');
        });

        syncHiddenCode();

        var first = document.querySelector('[data-auth-otp-digit]');
        if (first) {
            first.focus({ preventScroll: true });
        }
    }

    function distributeDigits(value) {
        var digits = String(value || '').replace(/\D/g, '').slice(0, 6).split('');
        var inputs = getDigits();

        inputs.forEach(function (input, index) {
            input.value = digits[index] || '';
            input.classList.toggle('is-filled', Boolean(input.value));
        });

        syncHiddenCode();
        updateVerifyState();

        var nextIndex = Math.min(digits.length, inputs.length - 1);
        if (inputs[nextIndex]) {
            inputs[nextIndex].focus({ preventScroll: true });
            inputs[nextIndex].select();
        }
    }

    function isExpired() {
        var root = page();
        var expiryTime = parseTime(root ? root.dataset.authOtpExpiresAt : '');

        return expiryTime > 0 && Date.now() >= expiryTime;
    }

    function updateVerifyState() {
        var submit = document.querySelector('[data-auth-otp-submit]');
        var code = codeFromDigits();
        var enabled = /^\d{6}$/.test(code) && !isExpired();

        setButtonState(submit, enabled);
    }

    function bindDigitInputs() {
        var inputs = getDigits();

        inputs.forEach(function (input, index) {
            if (input.dataset.authOtpDigitBound === '1') {
                return;
            }

            input.dataset.authOtpDigitBound = '1';

            input.addEventListener('input', function () {
                var value = input.value.replace(/\D/g, '');

                if (value.length > 1) {
                    distributeDigits(value);
                    return;
                }

                input.value = value;
                input.classList.toggle('is-filled', Boolean(value));
                syncHiddenCode();
                updateVerifyState();

                if (value && inputs[index + 1]) {
                    inputs[index + 1].focus({ preventScroll: true });
                    inputs[index + 1].select();
                }
            });

            input.addEventListener('keydown', function (event) {
                if (event.key === 'Backspace' && !input.value && inputs[index - 1]) {
                    inputs[index - 1].focus({ preventScroll: true });
                    inputs[index - 1].value = '';
                    inputs[index - 1].classList.remove('is-filled');
                    syncHiddenCode();
                    updateVerifyState();
                    event.preventDefault();
                }
            });

            input.addEventListener('paste', function (event) {
                var pasted = event.clipboardData ? event.clipboardData.getData('text') : '';

                if (pasted) {
                    event.preventDefault();
                    distributeDigits(pasted);
                }
            });
        });
    }

    function updateChallengeTokens(payload) {
        if (!payload || !payload.challenge_token) {
            return;
        }

        document.querySelectorAll('[data-auth-otp-challenge-token], [data-auth-otp-resend-token]').forEach(function (input) {
            input.value = payload.challenge_token;
        });

        var area = document.querySelector('[data-auth-otp-resend-area]');

        if (area) {
            area.dataset.authOtpResendTokenValue = payload.challenge_token;
        }
    }

    function updateAntiBotTime(form) {
        if (!form) {
            return;
        }

        var target = form.querySelector('[data-umkm-login-tts]');

        if (!target) {
            return;
        }

        var elapsedSeconds = Math.max(1, Math.floor((Date.now() - pageLoadedAt) / 1000));
        target.value = String(elapsedSeconds);
    }

    function removeResendForm() {
        var form = document.querySelector('[data-auth-otp-resend-form]');

        if (form && form.parentNode) {
            form.parentNode.removeChild(form);
        }
    }

    function showResendStatus(message, inlineCountdown) {
        var status = document.querySelector('[data-auth-otp-resend-status]');
        var statusText = document.querySelector('[data-auth-otp-resend-status-text]');
        var statusCountdown = document.querySelector('[data-auth-otp-resend-inline-countdown]');

        if (status) {
            status.hidden = false;
        }

        setText(statusText, message);
        setText(statusCountdown, inlineCountdown || '');
    }

    function hideResendStatus() {
        var status = document.querySelector('[data-auth-otp-resend-status]');

        if (status) {
            status.hidden = true;
        }
    }

    function renderResendButton() {
        var area = document.querySelector('[data-auth-otp-resend-area]');

        if (!area || document.querySelector('[data-auth-otp-resend-form]')) {
            return;
        }

        hideResendStatus();

        var form = document.createElement('form');
        form.method = 'POST';
        form.action = area.dataset.authOtpResendAction || '';
        form.className = 'mt-3';
        form.setAttribute('data-auth-otp-resend-form', '');
        form.setAttribute('data-umkm-anti-bot-form', '');
        form.noValidate = true;

        var csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = '_token';
        csrf.value = area.dataset.authOtpResendCsrf || '';
        form.appendChild(csrf);

        var token = document.createElement('input');
        token.type = 'hidden';
        token.name = 'challenge_token';
        token.value = area.dataset.authOtpResendTokenValue || '';
        token.setAttribute('data-auth-otp-resend-token', '');
        form.appendChild(token);

        var tts = document.createElement('input');
        tts.type = 'hidden';
        tts.name = 'tts';
        tts.value = '0';
        tts.setAttribute('data-umkm-login-tts', '');
        form.appendChild(tts);

        var honeypotWrap = document.createElement('div');
        honeypotWrap.className = 'visually-hidden';
        honeypotWrap.setAttribute('aria-hidden', 'true');
        honeypotWrap.innerHTML = '<label for="otp_resend_website">Website</label><input type="text" id="otp_resend_website" name="website" value="" tabindex="-1" autocomplete="off">';
        form.appendChild(honeypotWrap);

        var button = document.createElement('button');
        button.type = 'submit';
        button.className = 'auth-return-action border-0 w-100';
        button.setAttribute('data-auth-otp-resend-submit', '');
        button.innerHTML = '<span class="auth-action-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 5V2L7 7l5 5V8c3.31 0 6 2.69 6 6a6 6 0 0 1-9.2 5.08l-1.45 1.45A8 8 0 1 0 12 5Z"/></svg></span><span data-auth-otp-resend-text>Kirim ulang kode OTP</span>';
        form.appendChild(button);

        area.appendChild(form);
        updateAntiBotTime(form);
        bindResendForm();
    }

    function updateCountdowns() {
        var root = page();

        if (!root) {
            return;
        }

        var expiryTime = parseTime(root.dataset.authOtpExpiresAt);
        var resendTime = parseTime(root.dataset.authOtpResendAvailableAt);
        var now = Date.now();
        var expiryRemaining = expiryTime - now;
        var resendRemaining = resendTime - now;
        var expiryLabel = document.querySelector('[data-auth-otp-expiry-countdown]');
        var resendLabel = document.querySelector('[data-auth-otp-resend-countdown]');
        var badge = document.querySelector('[data-auth-otp-status-badge]');
        var helper = document.querySelector('[data-auth-otp-helper]');

        if (expiryTime > 0 && expiryRemaining <= 0) {
            setText(expiryLabel, 'Kedaluwarsa');
            setText(badge, 'OTP Kedaluwarsa');
            setText(helper, 'Kode OTP sudah kedaluwarsa. Kirim ulang kode untuk melanjutkan.');
            root.classList.add('is-otp-expired');
        } else if (expiryTime > 0) {
            setText(expiryLabel, formatDuration(expiryRemaining));
            setText(badge, 'OTP Aktif');
            root.classList.remove('is-otp-expired');
        } else {
            setText(expiryLabel, '--:--');
        }

        if (resendTime > 0 && resendRemaining > 0) {
            var resendDuration = formatDuration(resendRemaining);
            setText(resendLabel, resendDuration);
            removeResendForm();
            showResendStatus('Kirim ulang tersedia dalam', resendDuration);
        } else {
            setText(resendLabel, 'Siap');
            renderResendButton();
        }

        updateVerifyState();
    }

    function restartCountdowns() {
        if (countdownTimer) {
            window.clearInterval(countdownTimer);
        }

        updateCountdowns();
        countdownTimer = window.setInterval(updateCountdowns, 1000);
    }

    function bindVerifyForm() {
        var form = document.querySelector('[data-auth-otp-form]');

        if (!form || form.dataset.authOtpBound === '1') {
            return;
        }

        form.dataset.authOtpBound = '1';

        var submit = form.querySelector('[data-auth-otp-submit]');
        var submitText = form.querySelector('[data-auth-otp-submit-text]');
        var originalText = submitText ? submitText.textContent : 'Verifikasi dan Masuk';

        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            syncHiddenCode();

            var code = codeFromDigits();

            if (isExpired()) {
                showError(form, {
                    message: 'Kode OTP sudah kedaluwarsa. Kirim ulang kode OTP untuk melanjutkan.'
                }, null);
                return;
            }

            if (!/^\d{6}$/.test(code)) {
                showError(form, {
                    message: 'Masukkan kode OTP 6 digit sebelum melanjutkan.'
                }, null);
                return;
            }

            if (!UMKM.forms || typeof UMKM.forms.ajaxSubmit !== 'function') {
                showError(form, {
                    message: 'Core form belum siap. Muat ulang halaman sebelum verifikasi.'
                }, null);
                return;
            }

            var root = page();
            var verifyLoadingTitle = root && root.dataset.authOtpVerifyLoadingTitle ? root.dataset.authOtpVerifyLoadingTitle : 'Memverifikasi OTP';
            var verifyLoadingMessage = root && root.dataset.authOtpVerifyLoadingMessage ? root.dataset.authOtpVerifyLoadingMessage : 'Sistem sedang memvalidasi kode OTP.';

            setButtonState(submit, false);
            setText(submitText, 'Memverifikasi...');
            showLoading(verifyLoadingTitle, verifyLoadingMessage);

            var result = await UMKM.forms.ajaxSubmit(form, {
                validateFirst: false,
                onSuccess: function (response) {
                    var payload = response && response.payload ? response.payload : {};

                    hideLoading();
                    setText(submitText, originalText);
                    updateVerifyState();

                    if (payload && payload.ok === false) {
                        showError(form, payload, response);
                        return;
                    }

                    var successTitle = root && root.dataset.authOtpSuccessTitle ? root.dataset.authOtpSuccessTitle : 'Verifikasi berhasil';
                    var successMessage = root && root.dataset.authOtpSuccessMessage ? root.dataset.authOtpSuccessMessage : 'Mengalihkan ke dashboard.';

                    if (UMKM.toast && typeof UMKM.toast.success === 'function') {
                        UMKM.toast.success({
                            title: successTitle,
                            message: payload.message || successMessage,
                            delay: 1000
                        });
                    }

                    window.setTimeout(function () {
                        window.location.assign(payload.redirect_url || '/');
                    }, 850);
                },
                onError: function (response) {
                    var payload = response && response.payload ? response.payload : {};

                    hideLoading();
                    setText(submitText, originalText);
                    updateVerifyState();
                    showError(form, payload, response);
                }
            });

            if (!result || !result.ok) {
                hideLoading();
                setText(submitText, originalText);
                updateVerifyState();
            }
        });
    }

    function bindResendForm() {
        var form = document.querySelector('[data-auth-otp-resend-form]');

        if (!form || form.dataset.authOtpResendBound === '1') {
            return;
        }

        form.dataset.authOtpResendBound = '1';

        var submit = form.querySelector('[data-auth-otp-resend-submit]');
        var resendText = form.querySelector('[data-auth-otp-resend-text]');
        var originalText = resendText ? resendText.textContent : 'Kirim ulang kode OTP';

        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            updateCountdowns();

            if (!submit || submit.disabled) {
                return;
            }

            if (!UMKM.forms || typeof UMKM.forms.ajaxSubmit !== 'function') {
                return;
            }

            var rootForResend = page();
            var resendLoadingTitle = rootForResend && rootForResend.dataset.authOtpResendLoadingTitle ? rootForResend.dataset.authOtpResendLoadingTitle : 'Mengirim OTP Baru';
            var resendLoadingMessage = rootForResend && rootForResend.dataset.authOtpResendLoadingMessage ? rootForResend.dataset.authOtpResendLoadingMessage : 'Sistem sedang mengirim ulang kode OTP.';

            updateAntiBotTime(form);
            setButtonState(submit, false);
            setText(resendText, 'Mengirim ulang...');
            showLoading(resendLoadingTitle, resendLoadingMessage);

            var result = await UMKM.forms.ajaxSubmit(form, {
                validateFirst: false,
                onSuccess: function (response) {
                    var payload = response && response.payload ? response.payload : {};
                    var root = page();

                    hideLoading();
                    setText(resendText, originalText);
                    updateChallengeTokens(payload);

                    if (payload && payload.ok === false) {
                        showError(form, payload, response);
                        updateCountdowns();
                        return;
                    }

                    if (root && payload.expires_at) {
                        root.dataset.authOtpExpiresAt = payload.expires_at;
                    }

                    if (root && payload.resend_available_at) {
                        root.dataset.authOtpResendAvailableAt = payload.resend_available_at;
                    }

                    clearDigits();
                    removeResendForm();
                    restartCountdowns();

                    if (UMKM.toast && typeof UMKM.toast.success === 'function') {
                        UMKM.toast.success({
                            title: 'Kode baru dikirim',
                            message: payload.message || 'Periksa kembali log/email untuk kode OTP terbaru.',
                            delay: 1400
                        });
                    }
                },
                onError: function (response) {
                    var payload = response && response.payload ? response.payload : {};
                    var root = page();

                    hideLoading();
                    setText(resendText, originalText);

                    if (root && payload.resend_available_at) {
                        root.dataset.authOtpResendAvailableAt = payload.resend_available_at;
                    }

                    updateCountdowns();
                    showError(form, payload, response);
                }
            });

            if (!result || !result.ok) {
                hideLoading();
                setText(resendText, originalText);
                updateCountdowns();
            }
        });
    }

    ready(function () {
        bindDigitInputs();
        bindVerifyForm();
        bindResendForm();
        restartCountdowns();
        document.documentElement.setAttribute('data-umkm-auth-otp', 'ready');
    });
}());