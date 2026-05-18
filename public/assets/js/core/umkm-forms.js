(function () {
    'use strict';

    window.UMKM = window.UMKM || {};

    const UMKM = window.UMKM;
    let lastErrorTarget = null;

    function qs(selector, root) {
        return (root || document).querySelector(selector);
    }

    function qsa(selector, root) {
        return Array.from((root || document).querySelectorAll(selector));
    }

    function isField(element) {
        return element && element.matches('input, select, textarea');
    }

    function labelOf(field) {
        if (!field) {
            return 'Field';
        }

        if (field.dataset.umkmLabel) {
            return field.dataset.umkmLabel;
        }

        const id = field.getAttribute('id');
        const label = id ? qs('label[for="' + id + '"]') : null;

        if (label) {
            return label.textContent.trim();
        }

        if (field.getAttribute('name')) {
            return field.getAttribute('name').replace(/_/g, ' ');
        }

        return 'Field';
    }

    function valueOf(field) {
        if (!field) {
            return '';
        }

        if (field.type === 'checkbox' || field.type === 'radio') {
            return field.checked ? field.value : '';
        }

        return String(field.value || '').trim();
    }

    function markField(field, status, message) {
        if (!isField(field)) {
            return;
        }

        field.classList.remove('is-invalid', 'is-valid', 'umkm-field-filled', 'umkm-field-corrected');

        if (status === 'invalid') {
            field.classList.add('is-invalid');
            field.dataset.umkmFieldStatus = 'invalid';
        } else if (status === 'filled') {
            field.classList.add('umkm-field-filled');
            field.dataset.umkmFieldStatus = 'filled';
        } else if (status === 'corrected') {
            field.classList.add('umkm-field-corrected');
            field.dataset.umkmFieldStatus = 'corrected';
        } else {
            field.dataset.umkmFieldStatus = 'normal';
        }

        if (message) {
            field.dataset.umkmErrorMessage = message;
        } else {
            delete field.dataset.umkmErrorMessage;
        }
    }

    function clearFieldError(field) {
        if (!isField(field)) {
            return;
        }

        if (valueOf(field) !== '') {
            markField(field, 'corrected');
            return;
        }

        markField(field, 'normal');
    }

    function validateField(field) {
        if (!isField(field) || field.disabled || field.type === 'hidden') {
            return null;
        }

        const label = labelOf(field);
        const value = valueOf(field);

        if (field.hasAttribute('required') && value === '') {
            return {
                field: field,
                name: field.name || field.id || '',
                label: label,
                message: label + ' wajib diisi.'
            };
        }

        if (value !== '' && field.type === 'email') {
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (!emailPattern.test(value)) {
                return {
                    field: field,
                    name: field.name || field.id || '',
                    label: label,
                    message: label + ' harus menggunakan format email yang valid.'
                };
            }
        }

        const minLength = Number.parseInt(field.getAttribute('minlength') || '', 10);

        if (value !== '' && Number.isFinite(minLength) && value.length < minLength) {
            return {
                field: field,
                name: field.name || field.id || '',
                label: label,
                message: label + ' minimal ' + minLength + ' karakter.'
            };
        }

        const maxLength = Number.parseInt(field.getAttribute('maxlength') || '', 10);

        if (value !== '' && Number.isFinite(maxLength) && value.length > maxLength) {
            return {
                field: field,
                name: field.name || field.id || '',
                label: label,
                message: label + ' maksimal ' + maxLength + ' karakter.'
            };
        }

        const pattern = field.getAttribute('pattern');

        if (value !== '' && pattern) {
            try {
                const regex = new RegExp('^(?:' + pattern + ')$');

                if (!regex.test(value)) {
                    return {
                        field: field,
                        name: field.name || field.id || '',
                        label: label,
                        message: field.dataset.umkmPatternMessage || (label + ' belum sesuai format yang ditentukan.')
                    };
                }
            } catch (error) {
                return null;
            }
        }

        if (value !== '') {
            markField(field, 'filled');
        }

        return null;
    }

    function validate(form) {
        const errors = [];

        qsa('input, select, textarea', form).forEach(function (field) {
            const error = validateField(field);

            if (error) {
                markField(field, 'invalid', error.message);
                errors.push(error);
            }
        });

        return errors;
    }

    function ensureValidationModal() {
        let modal = qs('#umkmFormValidationModal');

        if (modal) {
            return modal;
        }

        const wrapper = document.createElement('div');
        wrapper.innerHTML = [
            '<div class="modal fade umkm-form-validation-modal" id="umkmFormValidationModal" tabindex="-1" aria-labelledby="umkmFormValidationModalTitle" aria-hidden="true">',
            '  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">',
            '    <section class="modal-content umkm-form-validation-card">',
            '      <div class="modal-header umkm-form-validation-head">',
            '        <div>',
            '          <span class="umkm-form-validation-kicker">Validasi Form</span>',
            '          <h5 class="modal-title" id="umkmFormValidationModalTitle">Data belum lengkap</h5>',
            '        </div>',
            '        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>',
            '      </div>',
            '      <div class="modal-body">',
            '        <p class="umkm-form-validation-message" data-umkm-validation-message>Periksa kembali isian form berikut.</p>',
            '        <div class="umkm-form-validation-list" data-umkm-validation-list></div>',
            '      </div>',
            '      <div class="modal-footer">',
            '        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Perbaiki Data</button>',
            '      </div>',
            '    </section>',
            '  </div>',
            '</div>'
        ].join('');

        modal = wrapper.firstElementChild;
        document.body.appendChild(modal);

        if (UMKM.modal && typeof UMKM.modal.bindFocusGuard === 'function') {
            UMKM.modal.bindFocusGuard(modal, {
                returnFocus: false,
                setInertWhenHidden: true
            });
        }

        modal.addEventListener('hidden.bs.modal', function () {
            focusLastError();
        });

        return modal;
    }

    function focusLastError() {
        if (!lastErrorTarget || !document.contains(lastErrorTarget)) {
            return;
        }

        window.requestAnimationFrame(function () {
            try {
                lastErrorTarget.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
                lastErrorTarget.focus({
                    preventScroll: true
                });
            } catch (error) {
                try {
                    lastErrorTarget.focus();
                } catch (innerError) {
                    // no-op
                }
            }
        });
    }

    function showValidationModal(errors, options) {
        const settings = Object.assign({
            title: 'Data belum lengkap',
            message: 'Periksa kembali isian form berikut.'
        }, options || {});

        const normalizedErrors = Array.isArray(errors) ? errors : [];

        if (normalizedErrors.length && normalizedErrors[0].field) {
            lastErrorTarget = normalizedErrors[0].field;
        }

        const modal = ensureValidationModal();
        const title = qs('#umkmFormValidationModalTitle', modal);
        const message = qs('[data-umkm-validation-message]', modal);
        const list = qs('[data-umkm-validation-list]', modal);

        if (title) {
            title.textContent = settings.title;
        }

        if (message) {
            message.textContent = settings.message;
        }

        if (list) {
            list.innerHTML = '';

            normalizedErrors.forEach(function (error, index) {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'umkm-form-validation-item';
                item.dataset.umkmErrorIndex = String(index);
                item.innerHTML = '<strong>' + (error.label || 'Field') + '</strong><span>' + (error.message || 'Belum valid.') + '</span>';

                item.addEventListener('click', function () {
                    if (error.field) {
                        lastErrorTarget = error.field;
                    }

                    if (UMKM.modal && typeof UMKM.modal.hide === 'function') {
                        UMKM.modal.hide(modal);
                    } else if (window.bootstrap && window.bootstrap.Modal) {
                        window.bootstrap.Modal.getOrCreateInstance(modal).hide();
                    }
                });

                list.appendChild(item);
            });
        }

        if (UMKM.modal && typeof UMKM.modal.show === 'function') {
            UMKM.modal.show(modal, {
                backdrop: 'static',
                keyboard: true,
                focus: true
            });
            return;
        }

        if (window.bootstrap && window.bootstrap.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(modal, {
                backdrop: 'static',
                keyboard: true,
                focus: true
            }).show();
        }
    }

    function errorsFromBackend(payload, form) {
        const errors = payload && payload.errors ? payload.errors : {};
        const mapped = [];

        Object.keys(errors || {}).forEach(function (name) {
            const field = form ? qs('[name="' + CSS.escape(name) + '"]', form) : null;
            const messages = Array.isArray(errors[name]) ? errors[name] : [String(errors[name])];

            messages.forEach(function (message) {
                mapped.push({
                    field: field,
                    name: name,
                    label: field ? labelOf(field) : name.replace(/_/g, ' '),
                    message: message
                });

                if (field) {
                    markField(field, 'invalid', message);
                }
            });
        });

        return mapped;
    }

    async function ajaxSubmit(form, options) {
        const settings = Object.assign({
            validateFirst: true,
            onSuccess: null,
            onError: null
        }, options || {});

        if (!form || !UMKM.ajax || typeof UMKM.ajax.request !== 'function') {
            return {
                ok: false,
                reason: 'ajax_unavailable'
            };
        }

        if (settings.validateFirst) {
            const localErrors = validate(form);

            if (localErrors.length) {
                showValidationModal(localErrors, {
                    title: 'Data belum lengkap',
                    message: 'Lengkapi atau perbaiki isian berikut sebelum data dikirim.'
                });

                return {
                    ok: false,
                    reason: 'local_validation_failed',
                    errors: localErrors
                };
            }
        }

        const result = await UMKM.ajax.request(form.action, {
            method: String(form.method || 'POST').toUpperCase(),
            body: new FormData(form)
        });

        const payload = result ? result.payload : null;

        if (!result || !result.ok) {
            const backendErrors = errorsFromBackend(payload, form);

            if (backendErrors.length) {
                showValidationModal(backendErrors, {
                    title: 'Validasi belum terpenuhi',
                    message: payload && payload.message ? payload.message : 'Periksa kembali isian form berikut.'
                });
            }

            if (typeof settings.onError === 'function') {
                settings.onError(result, backendErrors);
            }

            return result;
        }

        if (typeof settings.onSuccess === 'function') {
            settings.onSuccess(result);
        }

        return result;
    }

    function bindFieldStates(root) {
        qsa('input, select, textarea', root || document).forEach(function (field) {
            if (!isField(field) || field.dataset.umkmFormStateBound === 'true') {
                return;
            }

            field.dataset.umkmFormStateBound = 'true';

            field.addEventListener('focus', function () {
                field.classList.add('umkm-field-focused');
            });

            field.addEventListener('blur', function () {
                field.classList.remove('umkm-field-focused');

                if (valueOf(field) !== '' && !field.classList.contains('is-invalid')) {
                    markField(field, 'filled');
                }
            });

            field.addEventListener('input', function () {
                if (field.classList.contains('is-invalid')) {
                    clearFieldError(field);
                    return;
                }

                if (valueOf(field) !== '') {
                    markField(field, 'filled');
                } else {
                    markField(field, 'normal');
                }
            });

            field.addEventListener('change', function () {
                if (field.classList.contains('is-invalid')) {
                    clearFieldError(field);
                    return;
                }

                if (valueOf(field) !== '') {
                    markField(field, 'filled');
                } else {
                    markField(field, 'normal');
                }
            });
        });
    }

    function bind(root) {
        bindFieldStates(root || document);
    }

    document.addEventListener('DOMContentLoaded', function () {
        bind(document);
    });

    UMKM.forms = {
        bind: bind,
        bindFieldStates: bindFieldStates,
        validate: validate,
        validateField: validateField,
        showValidationModal: showValidationModal,
        ajaxSubmit: ajaxSubmit,
        errorsFromBackend: errorsFromBackend,
        focusLastError: focusLastError
    };

    UMKM.register?.('forms', UMKM.forms);
})();
