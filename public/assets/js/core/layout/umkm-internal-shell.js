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
        var toggle = document.querySelector("[data-dashboard-sidebar-toggle]");
        var backdrop = document.querySelector("[data-dashboard-sidebar-backdrop]");
        var desktopQuery = window.matchMedia("(min-width: 992px)");
        var storageKey = "umkm.dashboard.sidebar.state";

        if (!frame || !toggle) {
            return;
        }

        function isDesktop() {
            return desktopQuery.matches;
        }

        function setExpanded(expanded) {
            frame.setAttribute("data-sidebar-state", expanded ? "expanded" : "collapsed");
            toggle.setAttribute("aria-expanded", expanded ? "true" : "false");

            try {
                window.localStorage.setItem(storageKey, expanded ? "expanded" : "collapsed");
            } catch (error) {
                // Storage can be unavailable in strict browser modes. Ignore safely.
            }
        }

        function closeMobileSidebar() {
            frame.setAttribute("data-sidebar-mobile", "closed");
            toggle.setAttribute("aria-expanded", "false");
        }

        function openMobileSidebar() {
            frame.setAttribute("data-sidebar-mobile", "open");
            toggle.setAttribute("aria-expanded", "true");
        }

        function initializeSidebar() {
            if (!isDesktop()) {
                closeMobileSidebar();
                return;
            }

            var stored = "expanded";

            try {
                stored = window.localStorage.getItem(storageKey) || "expanded";
            } catch (error) {
                stored = "expanded";
            }

            setExpanded(stored !== "collapsed");
            frame.setAttribute("data-sidebar-mobile", "closed");
        }

        toggle.addEventListener("click", function () {
            if (!isDesktop()) {
                var isOpen = frame.getAttribute("data-sidebar-mobile") === "open";

                if (isOpen) {
                    closeMobileSidebar();
                } else {
                    openMobileSidebar();
                }

                return;
            }

            var isExpanded = frame.getAttribute("data-sidebar-state") !== "collapsed";
            setExpanded(!isExpanded);
        });

        if (backdrop) {
            backdrop.addEventListener("click", closeMobileSidebar);
        }

        desktopQuery.addEventListener("change", initializeSidebar);
        initializeSidebar();

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape") {
                closeMobileSidebar();
                closeAllPanels();
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
    });
})();