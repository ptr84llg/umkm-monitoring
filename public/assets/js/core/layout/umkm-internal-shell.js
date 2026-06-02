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
        var frame = document.querySelector("[data-dashboard-shell-frame]");
        var megaToggle = document.querySelector("[data-dashboard-mega-toggle]");
        var megaMenu = document.querySelector("[data-dashboard-mega-menu]");
        var megaBackdrop = document.querySelector("[data-dashboard-mega-backdrop]");
        var menuToggle = document.querySelector("[data-dashboard-menu-toggle]");
        var menuClose = document.querySelector("[data-dashboard-menu-close]");
        var offcanvas = document.querySelector("[data-dashboard-offcanvas]");
        var offcanvasBackdrop = document.querySelector("[data-dashboard-offcanvas-backdrop]");
        var previewTitle = document.querySelector("[data-dashboard-mega-preview-title]");
        var previewDescription = document.querySelector("[data-dashboard-mega-preview-description]");
        var desktopQuery = window.matchMedia("(min-width: 992px)");

        if (!frame) {
            return;
        }

        function isDesktop() {
            return desktopQuery.matches;
        }

        function openMegaMenu() {
            if (!isDesktop() || !megaMenu) {
                return;
            }

            frame.setAttribute("data-mega-menu", "open");
            megaMenu.setAttribute("aria-hidden", "false");

            if (megaToggle) {
                megaToggle.setAttribute("aria-expanded", "true");
            }

            closeMobileMenu();
        }

        function closeMegaMenu() {
            if (!megaMenu) {
                return;
            }

            frame.setAttribute("data-mega-menu", "closed");
            megaMenu.setAttribute("aria-hidden", "true");

            if (megaToggle) {
                megaToggle.setAttribute("aria-expanded", "false");
            }
        }

        function toggleMegaMenu() {
            if (frame.getAttribute("data-mega-menu") === "open") {
                closeMegaMenu();
                return;
            }

            openMegaMenu();
        }

        function openMobileMenu() {
            if (!offcanvas) {
                return;
            }

            frame.setAttribute("data-mobile-menu", "open");
            offcanvas.setAttribute("aria-hidden", "false");

            if (menuToggle) {
                menuToggle.setAttribute("aria-expanded", "true");
            }

            document.documentElement.classList.add("dashboard-menu-open");
            closeMegaMenu();
        }

        function closeMobileMenu() {
            if (!offcanvas) {
                return;
            }

            frame.setAttribute("data-mobile-menu", "closed");
            offcanvas.setAttribute("aria-hidden", "true");

            if (menuToggle) {
                menuToggle.setAttribute("aria-expanded", "false");
            }

            document.documentElement.classList.remove("dashboard-menu-open");
        }

        function toggleMobileMenu() {
            if (frame.getAttribute("data-mobile-menu") === "open") {
                closeMobileMenu();
                return;
            }

            openMobileMenu();
        }

        if (megaToggle) {
            megaToggle.addEventListener("click", function (event) {
                event.stopPropagation();
                toggleMegaMenu();
            });
        }

        if (megaBackdrop) {
            megaBackdrop.addEventListener("click", closeMegaMenu);
        }

        if (menuToggle) {
            menuToggle.addEventListener("click", function () {
                toggleMobileMenu();
            });
        }

        if (menuClose) {
            menuClose.addEventListener("click", closeMobileMenu);
        }

        if (offcanvasBackdrop) {
            offcanvasBackdrop.addEventListener("click", closeMobileMenu);
        }

        if (offcanvas) {
            offcanvas.addEventListener("click", function (event) {
                var target = event.target;

                if (!(target instanceof Element)) {
                    return;
                }

                var item = target.closest(".dashboard-offcanvas-item");

                if (!item) {
                    return;
                }

                if (item.classList.contains("is-disabled")) {
                    event.preventDefault();
                    return;
                }

                closeMobileMenu();
            });
        }

        if (megaMenu) {
            megaMenu.addEventListener("click", function (event) {
                var target = event.target;

                if (!(target instanceof Element)) {
                    return;
                }

                var item = target.closest(".dashboard-mega-item");

                if (!item) {
                    return;
                }

                if (item.classList.contains("is-disabled")) {
                    event.preventDefault();
                    return;
                }

                closeMegaMenu();
            });
        }

        Array.prototype.slice.call(document.querySelectorAll("[data-dashboard-mega-item]")).forEach(function (item) {
            item.addEventListener("mouseenter", function () {
                if (!previewTitle || !previewDescription) {
                    return;
                }

                previewTitle.textContent = item.getAttribute("data-menu-title") || "Ruang Kerja";
                previewDescription.textContent = item.getAttribute("data-menu-detail") || item.getAttribute("data-menu-description") || "Pilih modul untuk melihat ringkasan fungsi.";
            });

            item.addEventListener("focus", function () {
                if (!previewTitle || !previewDescription) {
                    return;
                }

                previewTitle.textContent = item.getAttribute("data-menu-title") || "Ruang Kerja";
                previewDescription.textContent = item.getAttribute("data-menu-detail") || item.getAttribute("data-menu-description") || "Pilih modul untuk melihat ringkasan fungsi.";
            });
        });

        desktopQuery.addEventListener("change", function () {
            if (isDesktop()) {
                closeMobileMenu();
                return;
            }

            closeMegaMenu();
        });

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape") {
                closeMegaMenu();
                closeMobileMenu();
                closeAllPanels();
            }
        });

        document.addEventListener("click", function (event) {
            var target = event.target;

            if (!(target instanceof Element)) {
                return;
            }

            var disabledMenu = target.closest(".dashboard-mega-item.is-disabled, .dashboard-offcanvas-item.is-disabled");

            if (disabledMenu) {
                event.preventDefault();
                return;
            }

            if (
                megaMenu &&
                frame.getAttribute("data-mega-menu") === "open" &&
                !target.closest("[data-dashboard-mega-menu]") &&
                !target.closest("[data-dashboard-mega-toggle]")
            ) {
                closeMegaMenu();
            }
        });

        var panelButtons = Array.prototype.slice.call(document.querySelectorAll("[data-dashboard-panel-toggle]"));
        var panels = Array.prototype.slice.call(document.querySelectorAll("[data-dashboard-panel]"));

        function closeAllPanels(exceptName) {
            panels.forEach(function (panel) {
                var panelName = panel.getAttribute("data-dashboard-panel");

                if (exceptName && panelName === exceptName) {
                    return;
                }

                panel.hidden = true;
            });

            panelButtons.forEach(function (button) {
                var buttonName = button.getAttribute("data-dashboard-panel-toggle");

                if (exceptName && buttonName === exceptName) {
                    return;
                }

                button.setAttribute("aria-expanded", "false");
            });
        }

        panelButtons.forEach(function (button) {
            button.addEventListener("click", function (event) {
                event.stopPropagation();

                var targetName = button.getAttribute("data-dashboard-panel-toggle");
                var panel = document.querySelector('[data-dashboard-panel="' + targetName + '"]');

                if (!panel) {
                    return;
                }

                var shouldOpen = panel.hidden;
                closeAllPanels(targetName);

                panel.hidden = !shouldOpen;
                button.setAttribute("aria-expanded", shouldOpen ? "true" : "false");
            });
        });

        document.addEventListener("click", function (event) {
            var target = event.target;

            if (!(target instanceof Element)) {
                return;
            }

            if (target.closest("[data-dashboard-panel]") || target.closest("[data-dashboard-panel-toggle]")) {
                return;
            }

            closeAllPanels();
        });

        closeMegaMenu();
        closeMobileMenu();
    });
})();