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
        var submenuTitle = document.querySelector("[data-dashboard-mega-submenu-title]");
        var submenuList = document.querySelector("[data-dashboard-mega-submenu-list]");
        var previewScope = document.querySelector("[data-dashboard-mega-preview-scope]");
        var serverPanel = document.querySelector("[data-dashboard-server-panel]");
        var serverClock = document.querySelector("[data-dashboard-server-clock]");
        var serverDate = document.querySelector("[data-dashboard-server-date]");
        var localTimezone = document.querySelector("[data-dashboard-local-timezone]");
        var localOffset = document.querySelector("[data-dashboard-local-offset]");
        var timeDifference = document.querySelector("[data-dashboard-time-difference]");
        var desktopQuery = window.matchMedia("(min-width: 992px)");

        if (!frame) {
            return;
        }

        function isDesktop() {
            return desktopQuery.matches;
        }

        var scrollStateFrameRequested = false;

        function updateDashboardScrollState() {
            var root = document.documentElement;
            var body = document.body;
            var scrollTop = Math.max(
                window.scrollY || 0,
                root ? root.scrollTop : 0,
                body ? body.scrollTop : 0
            );
            var scrollHeight = Math.max(
                root ? root.scrollHeight : 0,
                body ? body.scrollHeight : 0
            );
            var maxScroll = Math.max(0, scrollHeight - window.innerHeight);
            var scrollState = "top";

            if (maxScroll <= 4 || scrollTop <= 4) {
                scrollState = "top";
            } else if (scrollTop >= maxScroll - 4) {
                scrollState = "bottom";
            } else {
                scrollState = "scrolling";
            }

            frame.setAttribute("data-scroll-state", scrollState);
        }

        function requestDashboardScrollState() {
            if (scrollStateFrameRequested) {
                return;
            }

            scrollStateFrameRequested = true;

            window.requestAnimationFrame(function () {
                scrollStateFrameRequested = false;
                updateDashboardScrollState();
            });
        }

        window.addEventListener("scroll", requestDashboardScrollState, { passive: true });
        window.addEventListener("resize", requestDashboardScrollState);
        window.addEventListener("orientationchange", requestDashboardScrollState);
        updateDashboardScrollState();
        frame.setAttribute("data-scroll-aware", "INTERNALLAYOUT_UI3_SCROLL_STATE");

        function openMegaMenu() {
            if (!megaMenu || !megaToggle || !isDesktop()) {
                return;
            }

            closeMobileMenu();

            frame.setAttribute("data-mega-menu", "open");
            megaMenu.hidden = false;

            if (megaBackdrop) {
                megaBackdrop.hidden = false;
            }

            megaToggle.setAttribute("aria-expanded", "true");
        }

        function closeMegaMenu() {
            if (!megaMenu || !megaToggle) {
                return;
            }

            frame.setAttribute("data-mega-menu", "closed");
            megaMenu.hidden = true;

            if (megaBackdrop) {
                megaBackdrop.hidden = true;
            }

            megaToggle.setAttribute("aria-expanded", "false");
        }

        function openMobileMenu() {
            if (!offcanvas || isDesktop()) {
                return;
            }

            closeMegaMenu();

            frame.setAttribute("data-mobile-menu", "open");
            offcanvas.hidden = false;

            if (offcanvasBackdrop) {
                offcanvasBackdrop.hidden = false;
            }

            if (menuToggle) {
                menuToggle.setAttribute("aria-expanded", "true");
            }
        }

        function closeMobileMenu() {
            if (!offcanvas) {
                return;
            }

            frame.setAttribute("data-mobile-menu", "closed");
            offcanvas.hidden = true;

            if (offcanvasBackdrop) {
                offcanvasBackdrop.hidden = true;
            }

            if (menuToggle) {
                menuToggle.setAttribute("aria-expanded", "false");
            }
        }

        if (megaToggle) {
            megaToggle.addEventListener("click", function (event) {
                event.preventDefault();

                if (frame.getAttribute("data-mega-menu") === "open") {
                    closeMegaMenu();
                    return;
                }

                openMegaMenu();
            });
        }

        if (megaBackdrop) {
            megaBackdrop.addEventListener("click", closeMegaMenu);
        }

        if (menuToggle) {
            menuToggle.addEventListener("click", function (event) {
                event.preventDefault();

                if (frame.getAttribute("data-mobile-menu") === "open") {
                    closeMobileMenu();
                    return;
                }

                openMobileMenu();
            });
        }

        if (menuClose) {
            menuClose.addEventListener("click", function (event) {
                event.preventDefault();
                closeMobileMenu();
            });
        }

        if (offcanvasBackdrop) {
            offcanvasBackdrop.addEventListener("click", closeMobileMenu);
        }

        function padTimePart(value) {
            return String(value).padStart(2, "0");
        }

        function parseOffsetMinutes(offsetText) {
            var match = String(offsetText || "").match(/^([+-])(\d{2}):(\d{2})$/);

            if (!match) {
                return null;
            }

            var sign = match[1] === "-" ? -1 : 1;
            return sign * ((parseInt(match[2], 10) * 60) + parseInt(match[3], 10));
        }

        function formatOffset(minutes) {
            var sign = minutes < 0 ? "-" : "+";
            var absolute = Math.abs(minutes);

            return "UTC" + sign + padTimePart(Math.floor(absolute / 60)) + ":" + padTimePart(absolute % 60);
        }

        function describeTimeDifference(localMinutes, serverMinutes) {
            if (serverMinutes === null) {
                return "Zona server tidak terbaca.";
            }

            var difference = localMinutes - serverMinutes;

            if (difference === 0) {
                return "Sama dengan waktu server.";
            }

            var absolute = Math.abs(difference);
            var hours = Math.floor(absolute / 60);
            var minutes = absolute % 60;
            var parts = [];

            if (hours > 0) {
                parts.push(hours + " jam");
            }

            if (minutes > 0) {
                parts.push(minutes + " menit");
            }

            return "Perangkat " + (difference > 0 ? "lebih cepat " : "lebih lambat ") + parts.join(" ") + " dari server.";
        }

        var pageLoadedAt = Date.now();

        function formatDateWithTimezone(date, timezone) {
            try {
                return new Intl.DateTimeFormat("id-ID", {
                    timeZone: timezone || undefined,
                    weekday: "long",
                    day: "2-digit",
                    month: "long",
                    year: "numeric"
                }).format(date);
            } catch (error) {
                return date.toLocaleDateString("id-ID");
            }
        }

        function formatTimeWithTimezone(date, timezone) {
            try {
                return new Intl.DateTimeFormat("id-ID", {
                    timeZone: timezone || undefined,
                    hour: "2-digit",
                    minute: "2-digit",
                    second: "2-digit",
                    hour12: false
                }).format(date).replace(/\./g, ":");
            } catch (error) {
                return [
                    padTimePart(date.getHours()),
                    padTimePart(date.getMinutes()),
                    padTimePart(date.getSeconds())
                ].join(":");
            }
        }

        function updateServerClockPanel() {
            if (!serverPanel || !serverClock) {
                return;
            }

            var serverEpoch = parseInt(serverPanel.getAttribute("data-server-epoch-ms") || "", 10);

            if (!Number.isFinite(serverEpoch)) {
                return;
            }

            var serverOffset = serverPanel.getAttribute("data-server-offset") || "+00:00";
            var serverTimezone = serverPanel.getAttribute("data-server-timezone") || "UTC";
            var nowServer = new Date(serverEpoch + Date.now() - pageLoadedAt);
            var localZone = "Zona lokal";
            var localOffsetMinutes = -new Date().getTimezoneOffset();
            var serverOffsetMinutes = parseOffsetMinutes(serverOffset);

            try {
                localZone = Intl.DateTimeFormat().resolvedOptions().timeZone || localZone;
            } catch (error) {
                localZone = "Zona lokal";
            }

            serverClock.textContent = formatTimeWithTimezone(nowServer, serverTimezone);

            if (serverDate) {
                serverDate.textContent = formatDateWithTimezone(nowServer, serverTimezone);
            }

            if (localTimezone) {
                localTimezone.textContent = localZone;
            }

            if (localOffset) {
                localOffset.textContent = formatOffset(localOffsetMinutes);
            }

            if (timeDifference) {
                timeDifference.textContent = describeTimeDifference(localOffsetMinutes, serverOffsetMinutes);
            }

            serverPanel.setAttribute("data-server-clock-active", "INTERNALMEGAMENU_1A_FIX3_FIX1_JS_RECOVERY");
            serverPanel.setAttribute("data-server-timezone-label", serverTimezone);
        }

        function decodeBase64Utf8(value) {
            var binary = window.atob(value);
            var bytes = Array.prototype.map.call(binary, function (character) {
                return "%" + ("00" + character.charCodeAt(0).toString(16)).slice(-2);
            }).join("");

            return decodeURIComponent(bytes);
        }

        function parseMenuSubmenus(item) {
            var raw = item.getAttribute("data-menu-submenus") || "[]";

            try {
                if (raw && raw.charAt(0) !== "[") {
                    raw = decodeBase64Utf8(raw);
                }

                var parsed = JSON.parse(raw);
                return Array.isArray(parsed) ? parsed : [];
            } catch (error) {
                return [];
            }
        }

        frame.setAttribute("data-mega-menu-submenu-decoder", "INTERNALMEGAMENU_1A_FIX1_BASE64_SUBMENUS");

        function clearNode(node) {
            while (node && node.firstChild) {
                node.removeChild(node.firstChild);
            }
        }

        function submenuSymbolFor(title) {
            var value = String(title || "").toLowerCase();

            if (value.indexOf("akun") !== -1) {
                return "A";
            }

            if (value.indexOf("peran") !== -1 || value.indexOf("role") !== -1) {
                return "P";
            }

            if (value.indexOf("izin") !== -1 || value.indexOf("permission") !== -1) {
                return "I";
            }

            if (value.indexOf("sesi") !== -1 || value.indexOf("perangkat") !== -1) {
                return "S";
            }

            if (value.indexOf("audit") !== -1) {
                return "L";
            }

            if (value.indexOf("tema") !== -1) {
                return "T";
            }

            if (value.indexOf("keamanan") !== -1) {
                return "K";
            }

            return "•";
        }

        function renderSubmenuList(item) {
            if (!submenuList) {
                return;
            }

            var submenus = parseMenuSubmenus(item);

            clearNode(submenuList);

            if (submenuTitle) {
                submenuTitle.textContent = item.getAttribute("data-menu-title") || "Ruang Kerja";
            }

            if (submenus.length === 0) {
                var empty = document.createElement("div");
                var emptyBody = document.createElement("div");
                var emptyIcon = document.createElement("span");
                var emptyCopy = document.createElement("div");
                var emptyTitle = document.createElement("strong");
                var emptyDescription = document.createElement("small");

                empty.className = "list-group-item border rounded-3 mb-2 p-2 dashboard-mega-submenu-row";
                emptyBody.className = "d-flex align-items-start gap-2";
                emptyIcon.className = "dashboard-mega-submenu-symbol flex-shrink-0";
                emptyIcon.setAttribute("aria-hidden", "true");
                emptyIcon.textContent = "•";
                emptyCopy.className = "min-w-0 flex-grow-1";
                emptyTitle.className = "d-block text-truncate";
                emptyTitle.textContent = "Submenu belum tersedia";
                emptyDescription.className = "text-muted d-block dashboard-mega-two-line";
                emptyDescription.textContent = "Cakupan menu akan ditampilkan setelah modul siap.";

                emptyCopy.appendChild(emptyTitle);
                emptyCopy.appendChild(emptyDescription);
                emptyBody.appendChild(emptyIcon);
                emptyBody.appendChild(emptyCopy);
                empty.appendChild(emptyBody);
                submenuList.appendChild(empty);
                return;
            }

            submenus.forEach(function (submenu) {
                var row = document.createElement("div");
                var body = document.createElement("div");
                var icon = document.createElement("span");
                var copy = document.createElement("div");
                var titleRow = document.createElement("div");
                var title = document.createElement("strong");
                var state = document.createElement("span");
                var description = document.createElement("small");

                row.className = "list-group-item border rounded-3 mb-2 p-2 dashboard-mega-submenu-row";
                body.className = "d-flex align-items-start gap-2";
                icon.className = "dashboard-mega-submenu-symbol flex-shrink-0";
                icon.setAttribute("aria-hidden", "true");
                icon.textContent = submenuSymbolFor(submenu.title || "");
                copy.className = "min-w-0 flex-grow-1";
                titleRow.className = "d-flex align-items-center justify-content-between gap-2";
                title.className = "d-block text-truncate";
                title.textContent = submenu.title || "Submenu";
                state.className = "badge rounded-pill dashboard-mega-soft-badge flex-shrink-0";
                state.textContent = submenu.state || "Info";
                description.className = "text-muted d-block dashboard-mega-two-line";
                description.textContent = submenu.description || "Cakupan menu mengikuti kewenangan pengguna.";

                titleRow.appendChild(title);
                titleRow.appendChild(state);
                copy.appendChild(titleRow);
                copy.appendChild(description);
                body.appendChild(icon);
                body.appendChild(copy);
                row.appendChild(body);
                submenuList.appendChild(row);
            });

            frame.setAttribute("data-mega-menu-submenu-bootstrap", "INTERNALMEGAMENU_1A_FIX3_BOOTSTRAP_FIRST");
        }

        function renderPreviewScope(item) {
            if (!previewScope) {
                return;
            }

            var submenus = parseMenuSubmenus(item);

            clearNode(previewScope);

            submenus.slice(0, 6).forEach(function (submenu) {
                var chip = document.createElement("span");
                chip.className = "badge rounded-pill dashboard-mega-scope-badge";
                chip.textContent = submenu.title || "Cakupan";
                previewScope.appendChild(chip);
            });
        }

        function setMegaMenuPreview(item) {
            if (!item) {
                return;
            }

            Array.prototype.slice.call(document.querySelectorAll("[data-dashboard-mega-item]")).forEach(function (candidate) {
                var isCurrent = candidate === item;
                candidate.classList.toggle("is-previewed", isCurrent);
                candidate.classList.toggle("active", isCurrent || candidate.classList.contains("is-active"));
            });

            if (previewTitle) {
                previewTitle.textContent = item.getAttribute("data-menu-title") || "Ruang Kerja";
            }

            if (previewDescription) {
                previewDescription.textContent = item.getAttribute("data-menu-detail") || item.getAttribute("data-menu-description") || "Pilih menu untuk melihat cakupan kerja dan informasi singkat.";
            }

            renderSubmenuList(item);
            renderPreviewScope(item);
        }

        updateServerClockPanel();
        window.setInterval(updateServerClockPanel, 1000);

        var megaItems = Array.prototype.slice.call(document.querySelectorAll("[data-dashboard-mega-item]"));

        megaItems.forEach(function (item) {
            item.addEventListener("mouseenter", function () {
                setMegaMenuPreview(item);
            });

            item.addEventListener("focus", function () {
                setMegaMenuPreview(item);
            });

            item.addEventListener("click", function () {
                setMegaMenuPreview(item);
            });
        });

        setMegaMenuPreview(
            megaItems.find(function (item) {
                return item.classList.contains("is-active") || item.classList.contains("active");
            }) || megaItems[0]
        );

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

            var disabledMenu = target.closest(".dashboard-mega-main-item.disabled, .dashboard-mega-item.is-disabled, .dashboard-offcanvas-item.is-disabled");

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