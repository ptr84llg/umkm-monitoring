(function () {
    'use strict';

    var loadedAt = Date.now();

    function updateTimeToSubmit(form) {
        if (!form) {
            return;
        }

        var target = form.querySelector('[data-umkm-login-tts]');

        if (!target) {
            return;
        }

        var elapsedSeconds = Math.floor((Date.now() - loadedAt) / 1000);

        if (elapsedSeconds < 1) {
            elapsedSeconds = 1;
        }

        target.value = String(elapsedSeconds);
    }

    function bindForm(form) {
        if (!form || form.dataset.umkmAntiBotBound === '1') {
            return;
        }

        form.dataset.umkmAntiBotBound = '1';

        updateTimeToSubmit(form);

        form.addEventListener('submit', function () {
            updateTimeToSubmit(form);
        }, true);
    }

    function boot() {
        var forms = document.querySelectorAll('form[data-umkm-auth-login-form]');

        if (!forms.length) {
            forms = document.querySelectorAll('form[action*="/login"]');
        }

        Array.prototype.forEach.call(forms, bindForm);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot, { once: true });
    } else {
        boot();
    }
}());
