(function () {
    "use strict";

    function ready(callback) {
        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", callback, { once: true });
            return;
        }

        callback();
    }

    ready(function () {
        var form = document.querySelector("[data-theme-manager]");

        if (!form) {
            return;
        }

        var root = document.documentElement;
        var endpoint = form.getAttribute("data-theme-endpoint") || "";
        var csrfInput = form.querySelector('input[name="_token"]');
        var csrfMeta = document.querySelector('meta[name="csrf-token"]');
        var csrfToken = csrfInput ? csrfInput.value : (csrfMeta ? csrfMeta.getAttribute("content") : "");
        var modalElement = document.querySelector("[data-theme-confirm-modal]");
        var modalInstance = null;
        var activeLabelElement = document.querySelector("[data-theme-active-label]");
        var pendingLabelElement = document.querySelector("[data-theme-pending-label]");
        var confirmButton = document.querySelector("[data-theme-confirm-save]");
        var feedback = form.querySelector("[data-theme-feedback]");
        var pendingTheme = null;
        var pendingLabel = "";
        var modalConfirmed = false;
        var activeTheme = form.getAttribute("data-active-theme") || root.getAttribute("data-umkm-theme") || "green";
        var activeLabel = form.getAttribute("data-active-label") || (activeLabelElement ? activeLabelElement.textContent.trim() : activeTheme);

        if (window.bootstrap && modalElement) {
            modalInstance = new window.bootstrap.Modal(modalElement, {
                backdrop: "static",
                keyboard: true
            });
        }

        function findRadio(themeKey) {
            return form.querySelector('input[name="theme_key"][value="' + window.CSS.escape(themeKey) + '"]');
        }

        function setChecked(themeKey) {
            var radio = findRadio(themeKey);

            if (radio) {
                radio.checked = true;
            }
        }

        function updateCards(themeKey) {
            Array.prototype.slice.call(form.querySelectorAll("[data-theme-option]")).forEach(function (option) {
                var key = option.getAttribute("data-theme-option");
                var card = option.querySelector(".umkm-theme-card");
                var status = option.querySelector("[data-theme-status]");

                if (card) {
                    card.classList.toggle("is-active", key === themeKey);
                }

                if (status) {
                    status.classList.toggle("d-none", key !== themeKey);
                }
            });
        }

        function showFeedback(type, message) {
            if (!feedback) {
                return;
            }

            feedback.classList.remove("d-none", "alert-success", "alert-danger", "alert-warning");
            feedback.classList.add(type === "success" ? "alert-success" : "alert-danger");
            feedback.textContent = message;
        }

        function clearFeedback() {
            if (!feedback) {
                return;
            }

            feedback.classList.add("d-none");
            feedback.classList.remove("alert-success", "alert-danger", "alert-warning");
            feedback.textContent = "";
        }

        function previewTheme(themeKey) {
            root.setAttribute("data-umkm-theme", themeKey);
            setChecked(themeKey);
            updateCards(themeKey);
        }

        function restoreActiveTheme() {
            pendingTheme = null;
            pendingLabel = "";
            modalConfirmed = false;
            previewTheme(activeTheme);

            if (activeLabelElement) {
                activeLabelElement.textContent = activeLabel;
            }
        }

        function openConfirm(themeKey, label) {
            pendingTheme = themeKey;
            pendingLabel = label;
            modalConfirmed = false;

            if (pendingLabelElement) {
                pendingLabelElement.textContent = label;
            }

            if (modalInstance) {
                modalInstance.show();
                return;
            }

            if (window.confirm("Gunakan theme " + label + "?")) {
                saveTheme();
                return;
            }

            restoreActiveTheme();
        }

        async function saveTheme() {
            if (!pendingTheme || !endpoint) {
                restoreActiveTheme();
                return;
            }

            if (confirmButton) {
                confirmButton.disabled = true;
                confirmButton.textContent = "Menyimpan...";
            }

            clearFeedback();

            try {
                var response = await window.fetch(endpoint, {
                    method: "POST",
                    credentials: "same-origin",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                        "X-UMKM-Request": "internal",
                        "X-CSRF-TOKEN": csrfToken
                    },
                    body: JSON.stringify({
                        theme_key: pendingTheme
                    })
                });

                var data = null;

                try {
                    data = await response.json();
                } catch (error) {
                    data = null;
                }

                if (!response.ok || !data || data.ok !== true) {
                    var message = data && data.message ? data.message : "Theme belum dapat disimpan. Pastikan permission dan migration sudah aktif.";
                    throw new Error(message);
                }

                activeTheme = data.theme_key || pendingTheme;
                activeLabel = pendingLabel || activeTheme;
                form.setAttribute("data-active-theme", activeTheme);
                form.setAttribute("data-active-label", activeLabel);
                root.setAttribute("data-umkm-theme", activeTheme);
                setChecked(activeTheme);
                updateCards(activeTheme);

                if (activeLabelElement) {
                    activeLabelElement.textContent = activeLabel;
                }

                modalConfirmed = true;
                pendingTheme = null;
                pendingLabel = "";

                if (modalInstance) {
                    modalInstance.hide();
                }

                showFeedback("success", data.message || "Theme sistem berhasil diperbarui.");
            } catch (error) {
                restoreActiveTheme();
                showFeedback("danger", error.message || "Theme belum dapat disimpan.");

                if (modalInstance) {
                    modalInstance.hide();
                }
            } finally {
                if (confirmButton) {
                    confirmButton.disabled = false;
                    confirmButton.textContent = "Gunakan Theme";
                }
            }
        }

        form.addEventListener("submit", function (event) {
            event.preventDefault();
        });

        Array.prototype.slice.call(form.querySelectorAll('input[name="theme_key"]')).forEach(function (radio) {
            radio.addEventListener("change", function () {
                if (!radio.checked) {
                    return;
                }

                var selectedTheme = radio.value;
                var selectedLabel = radio.getAttribute("data-theme-label") || selectedTheme;

                if (selectedTheme === activeTheme) {
                    previewTheme(activeTheme);
                    return;
                }

                clearFeedback();
                previewTheme(selectedTheme);
                openConfirm(selectedTheme, selectedLabel);
            });
        });

        if (confirmButton) {
            confirmButton.addEventListener("click", saveTheme);
        }

        Array.prototype.slice.call(document.querySelectorAll("[data-theme-cancel]")).forEach(function (button) {
            button.addEventListener("click", function () {
                if (!modalConfirmed) {
                    restoreActiveTheme();
                }
            });
        });

        if (modalElement) {
            modalElement.addEventListener("hidden.bs.modal", function () {
                if (!modalConfirmed && pendingTheme) {
                    restoreActiveTheme();
                }

                modalConfirmed = false;
            });
        }

        previewTheme(activeTheme);
    });
})();