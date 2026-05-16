(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const header = document.querySelector('[data-landing-header]');
        const revealItems = document.querySelectorAll('.reveal');
        const parallaxItems = document.querySelectorAll('[data-parallax]');
        const counters = document.querySelectorAll('.count-up');
        const tiltCard = document.querySelector('[data-tilt-card]');
        const tabs = document.querySelectorAll('[data-chart-mode]');
        const mainTitle = document.getElementById('mainChartTitle');
        const mainSubtitle = document.getElementById('mainChartSubtitle');
        const summaryOne = document.getElementById('chartSummaryOne');
        const summaryTwo = document.getElementById('chartSummaryTwo');
        const summaryThree = document.getElementById('chartSummaryThree');
        const mainCanvas = document.getElementById('landingMainChart');
        const canvasMenu = document.querySelector('[data-menu-canvas]');
        const menuOpen = document.querySelector('[data-menu-open]');
        const menuCloseItems = document.querySelectorAll('[data-menu-close], [data-menu-link]');
        const toTop = document.querySelector('[data-to-top]');

        let mainChart = null;

        const chartModes = {
            kinerja: {
                title: 'Kinerja dan Pertumbuhan UMKM',
                subtitle: 'Perbandingan jumlah UMKM aktif dan estimasi pertumbuhan kinerja bulanan',
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul'],
                unitLabel: 'UMKM aktif',
                percentLabel: 'Pertumbuhan kinerja (%)',
                unitData: [118, 126, 133, 141, 152, 164, 173],
                percentData: [3.2, 4.1, 3.8, 5.2, 6.4, 7.1, 6.7],
                summaryOne: 'UMKM aktif, periode, bidang usaha',
                summaryTwo: 'Multi-axis: jumlah dan persentase',
                summaryThree: 'Kinerja usaha dan pertumbuhan'
            },
            wilayah: {
                title: 'Sebaran dan Konsentrasi Wilayah',
                subtitle: 'Perbandingan jumlah UMKM dan rasio konsentrasi pada wilayah pantauan',
                labels: ['Barat I', 'Barat II', 'Timur I', 'Timur II', 'Selatan I', 'Selatan II', 'Utara I', 'Utara II'],
                unitLabel: 'Jumlah UMKM',
                percentLabel: 'Konsentrasi wilayah (%)',
                unitData: [92, 118, 146, 132, 104, 96, 88, 122],
                percentData: [9.5, 12.1, 15.2, 13.6, 10.7, 9.9, 8.7, 12.8],
                summaryOne: 'Kecamatan, kategori usaha, lokasi',
                summaryTwo: 'Multi-axis: jumlah dan konsentrasi',
                summaryThree: 'Persebaran UMKM per wilayah'
            },
            legalitas: {
                title: 'Legalitas dan Pembaruan Data',
                subtitle: 'Perbandingan jumlah UMKM berlegalitas dan rasio kelengkapan data',
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul'],
                unitLabel: 'UMKM berlegalitas',
                percentLabel: 'Kelengkapan data (%)',
                unitData: [68, 74, 82, 91, 104, 116, 128],
                percentData: [48, 52, 57, 62, 68, 73, 78],
                summaryOne: 'NIB, izin usaha, pembaruan profil',
                summaryTwo: 'Multi-axis: legalitas dan kelengkapan',
                summaryThree: 'Kesiapan data untuk monitoring'
            }
        };

        function updateHeader() {
            if (!header) {
                return;
            }

            header.classList.toggle('is-scrolled', window.scrollY > 18);

            if (toTop) {
                toTop.classList.toggle('is-visible', window.scrollY > 420);
            }
        }

        window.addEventListener('scroll', updateHeader, { passive: true });
        updateHeader();

        if (menuOpen && canvasMenu) {
            menuOpen.addEventListener('click', function () {
                canvasMenu.classList.add('is-open');
                canvasMenu.setAttribute('aria-hidden', 'false');
                document.body.classList.add('is-canvas-open');
            });
        }

        function closeCanvasMenu() {
            if (!canvasMenu) {
                return;
            }

            canvasMenu.classList.remove('is-open');
            canvasMenu.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('is-canvas-open');
        }

        menuCloseItems.forEach(function (item) {
            item.addEventListener('click', closeCanvasMenu);
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeCanvasMenu();
            }
        });

        if (toTop) {
            toTop.addEventListener('click', function () {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }

        if ('IntersectionObserver' in window) {
            const revealObserver = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) {
                        return;
                    }

                    entry.target.classList.add('is-visible');
                    revealObserver.unobserve(entry.target);
                });
            }, { threshold: 0.14 });

            revealItems.forEach((item) => revealObserver.observe(item));
        } else {
            revealItems.forEach((item) => item.classList.add('is-visible'));
        }

        let ticking = false;

        function updateParallax() {
            const scrollY = window.scrollY || window.pageYOffset || 0;

            parallaxItems.forEach((item) => {
                const speed = Number(item.dataset.parallax || 0);
                item.style.transform = `translate3d(0, ${scrollY * speed}px, 0)`;
            });

            ticking = false;
        }

        window.addEventListener('scroll', function () {
            if (!ticking) {
                window.requestAnimationFrame(updateParallax);
                ticking = true;
            }
        }, { passive: true });

        if ('IntersectionObserver' in window && counters.length) {
            const counterObserver = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) {
                        return;
                    }

                    const element = entry.target;
                    const target = Number(element.dataset.count || 0);
                    const duration = 1100;
                    const startedAt = performance.now();

                    function animate(now) {
                        const progress = Math.min((now - startedAt) / duration, 1);
                        const eased = 1 - Math.pow(1 - progress, 3);
                        const value = Math.round(target * eased);

                        element.textContent = value.toLocaleString('id-ID');

                        if (progress < 1) {
                            window.requestAnimationFrame(animate);
                        }
                    }

                    window.requestAnimationFrame(animate);
                    counterObserver.unobserve(element);
                });
            }, { threshold: 0.62 });

            counters.forEach((counter) => counterObserver.observe(counter));
        }

        if (tiltCard) {
            const boardWindow = tiltCard.querySelector('.board-window');

            tiltCard.addEventListener('mousemove', function (event) {
                if (!boardWindow) {
                    return;
                }

                const rect = tiltCard.getBoundingClientRect();
                const x = event.clientX - rect.left;
                const y = event.clientY - rect.top;
                const rotateY = ((x / rect.width) - 0.5) * 7;
                const rotateX = ((y / rect.height) - 0.5) * -7;

                boardWindow.style.transform = `rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
            });

            tiltCard.addEventListener('mouseleave', function () {
                if (boardWindow) {
                    boardWindow.style.transform = 'rotateX(0deg) rotateY(0deg)';
                }
            });
        }

        function makeGradient(ctx, area, from, to) {
            const gradient = ctx.createLinearGradient(0, area.top || 0, 0, area.bottom || 320);
            gradient.addColorStop(0, from);
            gradient.addColorStop(1, to);
            return gradient;
        }

        function chartOptions() {
            return {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 900,
                    easing: 'easeOutQuart'
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                stacked: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle',
                            boxWidth: 8,
                            boxHeight: 8,
                            color: '#475569',
                            padding: 18,
                            font: {
                                family: 'Segoe UI',
                                weight: '700'
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(16, 33, 61, .94)',
                        padding: 12,
                        cornerRadius: 12,
                        titleFont: {
                            family: 'Segoe UI',
                            weight: '800'
                        },
                        bodyFont: {
                            family: 'Segoe UI',
                            weight: '700'
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#64748b',
                            font: {
                                family: 'Segoe UI',
                                weight: '800'
                            }
                        }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(16, 33, 61, .07)',
                            drawBorder: false
                        },
                        ticks: {
                            color: '#0f7665',
                            font: {
                                family: 'Segoe UI',
                                weight: '800'
                            }
                        },
                        title: {
                            display: true,
                            text: 'Jumlah UMKM',
                            color: '#0f7665',
                            font: {
                                family: 'Segoe UI',
                                weight: '800'
                            }
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        beginAtZero: true,
                        suggestedMax: 100,
                        grid: {
                            drawOnChartArea: false
                        },
                        ticks: {
                            color: '#f0a84a',
                            callback: function (value) {
                                return value + '%';
                            },
                            font: {
                                family: 'Segoe UI',
                                weight: '800'
                            }
                        },
                        title: {
                            display: true,
                            text: 'Persentase',
                            color: '#f0a84a',
                            font: {
                                family: 'Segoe UI',
                                weight: '800'
                            }
                        }
                    }
                }
            };
        }

        function renderMainChart(mode) {
            const data = chartModes[mode] || chartModes.kinerja;

            if (mainTitle) {
                mainTitle.textContent = data.title;
            }

            if (mainSubtitle) {
                mainSubtitle.textContent = data.subtitle;
            }

            if (summaryOne) {
                summaryOne.textContent = data.summaryOne;
            }

            if (summaryTwo) {
                summaryTwo.textContent = data.summaryTwo;
            }

            if (summaryThree) {
                summaryThree.textContent = data.summaryThree;
            }

            if (!window.Chart || !mainCanvas) {
                return;
            }

            const ctx = mainCanvas.getContext('2d');

            if (mainChart) {
                mainChart.destroy();
            }

            mainChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [
                        {
                            label: data.unitLabel,
                            data: data.unitData,
                            yAxisID: 'y',
                            borderColor: '#0f7665',
                            backgroundColor: function (context) {
                                const chart = context.chart;
                                const area = chart.chartArea;

                                if (!area) {
                                    return 'rgba(15, 118, 101, .16)';
                                }

                                return makeGradient(
                                    chart.ctx,
                                    area,
                                    'rgba(15, 118, 101, .24)',
                                    'rgba(15, 118, 101, .02)'
                                );
                            },
                            fill: true,
                            tension: .42,
                            borderWidth: 3,
                            pointRadius: 4,
                            pointHoverRadius: 7,
                            pointBackgroundColor: '#0f7665',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2
                        },
                        {
                            label: data.percentLabel,
                            data: data.percentData,
                            yAxisID: 'y1',
                            borderColor: '#f0a84a',
                            backgroundColor: 'rgba(240, 168, 74, .16)',
                            fill: false,
                            tension: .42,
                            borderWidth: 3,
                            borderDash: [8, 6],
                            pointRadius: 4,
                            pointHoverRadius: 7,
                            pointBackgroundColor: '#f0a84a',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2
                        }
                    ]
                },
                options: chartOptions()
            });
        }

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                const mode = tab.dataset.chartMode || 'kinerja';

                tabs.forEach(function (item) {
                    item.classList.remove('active');
                });

                tab.classList.add('active');
                renderMainChart(mode);
            });
        });

        renderMainChart('kinerja');
    });
})();

/**
 * Batch 2B-Fix2 Location Gate Modal Overlay & Responsive Bottom Sheet
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const UMKM = window.UMKM || {};
        const notice = document.querySelector('[data-location-gate-notice]');
        const noticeCard = notice ? notice.querySelector('.location-gate-card') : null;
        const title = document.querySelector('[data-location-gate-title]');
        const message = document.querySelector('[data-location-gate-message]');
        const retryButton = document.querySelector('[data-location-retry]');
        const closeButton = document.querySelector('[data-location-gate-close]');
        const guideToggle = document.querySelector('[data-location-guide-toggle]');
        const guide = document.querySelector('[data-location-guide]');
        const permissionBox = document.querySelector('[data-location-permission-state]');
        const permissionLabel = document.querySelector('[data-location-permission-label]');
        const gatedLinks = document.querySelectorAll('[data-location-gated]');

        const state = {
            status: 'booting',
            permission: 'unknown',
            checkedAt: null,
            lastResult: null,
            dismissed: false
        };

        let dismissedByUser = false;

        function setPermissionLabel(permissionState) {
            state.permission = permissionState || 'unknown';

            if (permissionBox) {
                permissionBox.hidden = false;
            }

            if (permissionLabel) {
                permissionLabel.textContent = state.permission;
            }
        }

        function setGuideVisible(visible) {
            if (!guide) {
                return;
            }

            guide.hidden = !visible;

            if (guideToggle) {
                const text = guideToggle.querySelector('span');

                if (text) {
                    text.textContent = visible ? 'Sembunyikan panduan' : 'Cara mengaktifkan izin';
                }
            }
        }

        function setNotice(status, titleText, messageText, visible, permissionState, forceVisible) {
            document.documentElement.setAttribute('data-location-gate', status);

            if (noticeCard) {
                noticeCard.classList.remove('is-checking', 'is-granted', 'is-blocked', 'is-unsupported', 'is-denied', 'is-prompt');
                noticeCard.classList.add('is-' + status);
            }

            if (title) {
                title.textContent = titleText;
            }

            if (message) {
                message.textContent = messageText;
            }

            if (permissionState) {
                setPermissionLabel(permissionState);
            }

            const shouldShow = Boolean(visible && (forceVisible || !dismissedByUser));

            if (notice) {
                notice.hidden = !shouldShow;
            }

            document.body.classList.toggle('is-location-gate-open', shouldShow);
        }

        function updateState(status, result, permissionState) {
            state.status = status;
            state.permission = permissionState || state.permission || 'unknown';
            state.checkedAt = new Date().toISOString();
            state.lastResult = result || null;

            document.dispatchEvent(new CustomEvent('umkm:location-gate:updated', {
                detail: Object.assign({}, state)
            }));

            if (UMKM.log) {
                UMKM.log(status === 'granted' ? 'info' : 'warn', 'location gate updated', state);
            }
        }

        function showGateAgain() {
            dismissedByUser = false;
            state.dismissed = false;
        }

        function closeLocationGate() {
            dismissedByUser = true;
            state.dismissed = true;

            if (notice) {
                notice.hidden = true;
            }

            document.body.classList.remove('is-location-gate-open');

            document.dispatchEvent(new CustomEvent('umkm:location-gate:dismissed', {
                detail: Object.assign({}, state)
            }));

            if (UMKM.log) {
                UMKM.log('info', 'location gate dismissed by user', state);
            }
        }
        async function getPermissionStatus() {
            if (!UMKM.location || typeof UMKM.location.permissionStatus !== 'function') {
                return {
                    supported: false,
                    permissionSupported: false,
                    state: 'unsupported',
                    message: 'Modul lokasi belum tersedia.'
                };
            }

            return await UMKM.location.permissionStatus();
        }

        function showDenied(permission) {
            setNotice(
                'denied',
                'Izin lokasi diblokir oleh browser',
                'Website tidak dapat menampilkan ulang permintaan lokasi karena izin sudah diblokir. Ubah izin lokasi dari pengaturan situs pada browser, lalu refresh halaman.',
                true,
                permission && permission.state ? permission.state : 'denied'
            );

            setGuideVisible(false);
            updateState('denied', null, 'denied');
        }

        function showUnsupported(permission) {
            setNotice(
                'unsupported',
                'Validasi lokasi belum dapat digunakan',
                'Browser atau perangkat belum mendukung pemeriksaan izin lokasi yang dibutuhkan untuk membuka akses masuk sistem.',
                true,
                permission && permission.state ? permission.state : 'unsupported'
            );

            setGuideVisible(false);
            updateState('unsupported', null, permission && permission.state ? permission.state : 'unsupported');
        }

        function blockByResult(result) {
            const permission = result && result.permission ? result.permission : null;
            const locationState = result && result.state ? result.state : {};
            const lastError = locationState.lastError || {};
            const permissionState = permission && permission.state ? permission.state : locationState.permission || 'unknown';
            const type = lastError.type || locationState.status || 'blocked';

            if (permissionState === 'denied' || type === 'permission_denied_browser') {
                showDenied(permission || {
                    state: 'denied'
                });
                return;
            }

            if (permissionState === 'unsupported' || type === 'unsupported') {
                showUnsupported(permission || {
                    state: 'unsupported'
                });
                return;
            }

            if (type === 'permission_denied') {
                setNotice(
                    'blocked',
                    'Lokasi belum aktif',
                    'Aktifkan izin lokasi pada browser untuk membuka akses masuk ke sistem.',
                    true,
                    permissionState
                );
                setGuideVisible(false);
                updateState('blocked', result, permissionState);
                return;
            }

            if (type === 'timeout') {
                setNotice(
                    'blocked',
                    'Pemeriksaan lokasi terlalu lama',
                    'Pastikan layanan lokasi aktif, lalu klik cek ulang lokasi.',
                    true,
                    permissionState
                );
                setGuideVisible(false);
                updateState('blocked', result, permissionState);
                return;
            }

            setNotice(
                'blocked',
                'Lokasi belum dapat diverifikasi',
                'Akses masuk sistem dibuka setelah lokasi berhasil diperiksa.',
                true,
                permissionState
            );
            setGuideVisible(false);
            updateState('blocked', result, permissionState);
        }

        async function checkLocationGate() {
            if (!UMKM.location || typeof UMKM.location.check !== 'function') {
                showUnsupported({
                    state: 'unsupported'
                });
                return false;
            }

            const permission = await getPermissionStatus();

            setPermissionLabel(permission.state || 'unknown');

            if (permission.state === 'denied') {
                showDenied(permission);
                return false;
            }

            if (permission.state === 'unsupported') {
                showUnsupported(permission);
                return false;
            }

            if (permission.state === 'prompt') {
                setNotice(
                    'prompt',
                    'Izin lokasi diperlukan',
                    'Pilih Izinkan pada permintaan lokasi browser agar akses masuk ke sistem dapat ditampilkan.',
                    true,
                    'prompt'
                );
                setGuideVisible(false);
                updateState('prompt', null, 'prompt');
            } else {
                setNotice(
                    'checking',
                    'Memeriksa status lokasi',
                    'Mohon tunggu, sistem sedang memastikan lokasi aktif sebelum membuka akses masuk.',
                    true,
                    permission.state || 'unknown'
                );
                setGuideVisible(false);
                updateState('checking', null, permission.state || 'unknown');
            }

            const result = await UMKM.location.check({
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            });

            if (result && result.ok) {
                setNotice(
                    'granted',
                    'Lokasi aktif',
                    'Akses masuk sistem sudah dibuka.',
                    false,
                    'granted'
                );
                setGuideVisible(false);
                updateState('granted', result, 'granted');
                return true;
            }

            blockByResult(result);
            return false;
        }

        gatedLinks.forEach(function (link) {
            link.addEventListener('click', async function (event) {
                const href = link.getAttribute('href');

                if (!href || href === '#') {
                    return;
                }

                event.preventDefault();
                showGateAgain();

                const allowed = await checkLocationGate();

                if (allowed) {
                    window.location.assign(href);
                }
            });
        });

        if (retryButton) {
            retryButton.addEventListener('click', function () {
                showGateAgain();
                checkLocationGate();
            });
        }

        if (closeButton) {
            closeButton.addEventListener('click', function () {
                closeLocationGate();
            });
        }

        if (guideToggle) {
            guideToggle.addEventListener('click', function () {
                if (!guide) {
                    return;
                }

                setGuideVisible(Boolean(guide.hidden));
            });
        }

        if (UMKM.location && typeof UMKM.location.watchPermission === 'function') {
            UMKM.location.watchPermission(function (permission) {
                setPermissionLabel(permission.state || 'unknown');

                if (permission.state === 'granted') {
                    checkLocationGate();
                    return;
                }

                if (permission.state === 'denied') {
                    showDenied(permission);
                    return;
                }

                if (permission.state === 'prompt') {
                    setNotice(
                        'prompt',
                        'Izin lokasi diperlukan',
                        'Klik cek ulang lokasi, lalu pilih Izinkan pada permintaan lokasi browser.',
                        true,
                        'prompt'
                    );
                    setGuideVisible(false);
                    updateState('prompt', null, 'prompt');
                }
            });
        }

        window.setTimeout(function () {
            checkLocationGate();
        }, 450);

        if (UMKM.register) {
            UMKM.register('locationGate', {
                check: checkLocationGate,
                state: function () {
                    return Object.assign({}, state);
                },
                close: closeLocationGate,
                open: function () {
                    showGateAgain();
                    checkLocationGate();
                },
                permission: getPermissionStatus
            });
        }
    });
})();

/* Batch Landing-Regional-2C START */

/**
 * Landing public-safe region modal final UX.
 * - Memakai UMKM.ajax, bukan fetch langsung.
 * - Endpoint public-safe hanya untuk Sumatera Selatan → Kota Lubuklinggau.
 * - Loading dan error state dijaga di modal.
 * - Card dan chart tetap preview agregat.
 */
(function () {
    'use strict';

    window.UMKM = window.UMKM || {};

    const UMKM = window.UMKM;

    const API = {
        context: '/api/public/landing-regions/context',
        children: '/api/public/landing-regions/children'
    };

    const DEFAULT_CONTEXT = {
        province: { code: '16', name: 'Sumatera Selatan', level: 'province' },
        city: { code: '16.73', name: 'Kota Lubuklinggau', level: 'city' },
        options: {
            district_all: {
                code: '__ALL_DISTRICTS__',
                name: 'Semua Kecamatan',
                level: 'district',
                is_virtual: true
            },
            village_all: {
                code: '__ALL_VILLAGES__',
                name: 'Semua Kelurahan',
                level: 'village',
                is_virtual: true
            }
        }
    };

    const state = {
        loading: false,
        contextLoaded: false,
        context: DEFAULT_CONTEXT,
        districts: [],
        villages: [],
        applied: {
            province: DEFAULT_CONTEXT.province,
            city: DEFAULT_CONTEXT.city,
            district: null,
            village: null,
            districtAll: true,
            villageAll: true,
            label: 'Kota Lubuklinggau',
            scope: 'city'
        }
    };

    function ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
            return;
        }

        callback();
    }

    function ajaxReady() {
        return Boolean(UMKM.ajax && typeof UMKM.ajax.get === 'function');
    }

    function unwrap(result) {
        if (!result) {
            return null;
        }

        if (result.payload && typeof result.payload === 'object') {
            return result.payload;
        }

        return result;
    }

    function resultOk(result) {
        return Boolean(result && result.ok);
    }

    function formatNumber(value) {
        return Number(value || 0).toLocaleString('id-ID');
    }

    function hash(input) {
        const text = String(input || 'city');
        let value = 0;

        for (let i = 0; i < text.length; i += 1) {
            value = ((value << 5) - value) + text.charCodeAt(i);
            value |= 0;
        }

        return Math.abs(value);
    }

    function getModal() {
        return document.querySelector('[data-region-modal] .landing-region-modal');
    }

    function setLoading(isLoading, message) {
        state.loading = Boolean(isLoading);

        const modal = getModal();
        const applyButton = document.querySelector('[data-region-modal-apply]');

        if (modal) {
            modal.classList.toggle('is-loading', state.loading);

            let loading = modal.querySelector('[data-region-loading]');

            if (!loading) {
                loading = document.createElement('div');
                loading.className = 'landing-region-loading';
                loading.dataset.regionLoading = 'true';
                loading.textContent = 'Memuat data wilayah...';

                const form = modal.querySelector('.landing-region-form');
                if (form) {
                    form.insertAdjacentElement('afterend', loading);
                }
            }

            loading.textContent = message || 'Memuat data wilayah...';
        }

        if (applyButton) {
            applyButton.disabled = state.loading;
            applyButton.classList.toggle('is-disabled', state.loading);
        }
    }

    function setText(selector, value) {
        const element = document.querySelector(selector);

        if (element) {
            element.textContent = value;
        }
    }

    function setAlert(message) {
        const alert = document.querySelector('[data-region-modal-alert]');

        if (!alert) {
            return;
        }

        alert.hidden = !message;
        alert.textContent = message || '';
    }

    function setModalCurrent(text) {
        setText('[data-region-modal-current]', text || 'Kota Lubuklinggau');
    }

    function updateMetric(selector, value) {
        const element = document.querySelector(selector);

        if (!element) {
            return;
        }

        element.dataset.count = String(value);
        element.textContent = formatNumber(value);
    }

    function activeChartMode() {
        const active = document.querySelector('[data-chart-mode].active');

        return active ? active.dataset.chartMode || 'kinerja' : 'kinerja';
    }

    function selectedOption(select) {
        if (!select || !select.value) {
            return null;
        }

        const option = select.options[select.selectedIndex];

        return {
            code: option.value,
            name: option.dataset.regionName || option.textContent || option.value,
            level: option.dataset.regionLevel || '',
            isVirtual: option.dataset.virtual === '1'
        };
    }

    function createOption(region) {
        const option = document.createElement('option');

        option.value = region.code || '';
        option.textContent = region.name || region.code || '';
        option.dataset.regionName = region.name || '';
        option.dataset.regionLevel = region.level || '';
        option.dataset.virtual = region.is_virtual ? '1' : '0';

        return option;
    }

    function fillLockedSelect(select, region) {
        if (!select || !region) {
            return;
        }

        select.innerHTML = '';
        select.appendChild(createOption(region));
        select.value = region.code;
        select.disabled = true;
    }

    function fillDistricts(select, allOption, districts) {
        if (!select) {
            return;
        }

        select.innerHTML = '';
        select.appendChild(createOption(allOption || DEFAULT_CONTEXT.options.district_all));

        (districts || []).forEach(function (region) {
            select.appendChild(createOption(region));
        });

        select.value = (allOption || DEFAULT_CONTEXT.options.district_all).code;
        select.disabled = false;
    }

    function fillVillages(select, allOption, villages, disabled) {
        if (!select) {
            return;
        }

        select.innerHTML = '';
        select.appendChild(createOption(allOption || DEFAULT_CONTEXT.options.village_all));

        (villages || []).forEach(function (region) {
            select.appendChild(createOption(region));
        });

        select.value = (allOption || DEFAULT_CONTEXT.options.village_all).code;
        select.disabled = Boolean(disabled);
    }

    function modalShell() {
        return document.querySelector('[data-region-modal]');
    }

    function openModal() {
        const shell = modalShell();

        if (!shell) {
            return;
        }

        shell.hidden = false;
        document.body.classList.add('is-region-modal-open');
        setAlert('');

        ensureContext();
    }

    function closeModal() {
        if (state.loading) {
            return;
        }

        const shell = modalShell();

        if (!shell) {
            return;
        }

        shell.hidden = true;
        document.body.classList.remove('is-region-modal-open');
    }

    async function loadChildren(parentCode, level) {
        const url = `${API.children}?parent_code=${encodeURIComponent(parentCode)}&level=${encodeURIComponent(level)}`;
        const result = await UMKM.ajax.get(url);
        const payload = unwrap(result);

        if (!resultOk(result) || !payload || !payload.data) {
            throw new Error(payload && payload.message ? payload.message : 'Data wilayah tidak dapat dimuat.');
        }

        return payload.data;
    }

    async function ensureContext() {
        if (!ajaxReady()) {
            setAlert('Modul AJAX sistem belum siap. Muat ulang halaman lalu coba kembali.');
            return;
        }

        if (state.contextLoaded) {
            return;
        }

        try {
            setLoading(true, 'Memuat kecamatan Kota Lubuklinggau...');
            setAlert('');

            const result = await UMKM.ajax.get(API.context);
            const payload = unwrap(result);

            if (!resultOk(result) || !payload || !payload.data) {
                throw new Error(payload && payload.message ? payload.message : 'Konteks wilayah tidak dapat dimuat.');
            }

            state.context = {
                province: payload.data.province || DEFAULT_CONTEXT.province,
                city: payload.data.city || DEFAULT_CONTEXT.city,
                options: Object.assign({}, DEFAULT_CONTEXT.options, payload.data.options || {})
            };

            fillLockedSelect(document.querySelector('[data-landing-region-province]'), state.context.province);
            fillLockedSelect(document.querySelector('[data-landing-region-city]'), state.context.city);

            const districtData = await loadChildren(state.context.city.code, 'district');

            state.districts = districtData.regions || [];
            state.villages = [];

            fillDistricts(
                document.querySelector('[data-landing-region-district]'),
                districtData.all_option || state.context.options.district_all,
                state.districts
            );

            fillVillages(
                document.querySelector('[data-landing-region-village]'),
                state.context.options.village_all,
                [],
                true
            );

            state.contextLoaded = true;
            setModalCurrent(state.applied.label || 'Kota Lubuklinggau');
        } catch (error) {
            setAlert(error.message || 'Wilayah tidak dapat dimuat.');
        } finally {
            setLoading(false);
        }
    }

    async function onDistrictChanged() {
        const districtSelect = document.querySelector('[data-landing-region-district]');
        const villageSelect = document.querySelector('[data-landing-region-village]');
        const district = selectedOption(districtSelect);

        state.villages = [];

        if (!district || district.isVirtual) {
            fillVillages(villageSelect, state.context.options?.village_all, [], true);
            setModalCurrent('Kota Lubuklinggau');
            setAlert('');
            return;
        }

        try {
            setLoading(true, 'Memuat desa/kelurahan...');
            setAlert('');

            const villageData = await loadChildren(district.code, 'village');
            state.villages = villageData.regions || [];

            fillVillages(
                villageSelect,
                villageData.all_option || state.context.options?.village_all,
                state.villages,
                false
            );

            setModalCurrent(district.name);
        } catch (error) {
            fillVillages(villageSelect, state.context.options?.village_all, [], true);
            setAlert(error.message || 'Desa/kelurahan tidak dapat dimuat.');
        } finally {
            setLoading(false);
        }
    }

    function getAppliedSelection() {
        const district = selectedOption(document.querySelector('[data-landing-region-district]'));
        const village = selectedOption(document.querySelector('[data-landing-region-village]'));

        const districtAll = !district || district.isVirtual || district.code === '__ALL_DISTRICTS__';
        const villageAll = !village || village.isVirtual || village.code === '__ALL_VILLAGES__';

        let label = state.context.city.name || 'Kota Lubuklinggau';
        let scope = 'city';

        if (!districtAll && villageAll) {
            label = district.name;
            scope = 'district';
        }

        if (!districtAll && !villageAll) {
            label = village.name;
            scope = 'village';
        }

        return {
            province: state.context.province,
            city: state.context.city,
            district: districtAll ? null : district,
            village: villageAll ? null : village,
            districtAll: districtAll,
            villageAll: villageAll,
            label: label,
            scope: scope
        };
    }

    function buildPreview(selection) {
        const key = selection.village?.code || selection.district?.code || selection.city?.code || '16.73';
        const seed = hash(key + selection.scope);

        if (selection.scope === 'city') {
            return {
                total: 1248,
                active: 1086,
                validation: 36,
                watched: `${state.districts.length || 8} Kecamatan`,
                dominant: 'Perdagangan',
                fields: [
                    { name: 'Perdagangan', percent: 82 },
                    { name: 'Kuliner', percent: 74 },
                    { name: 'Jasa', percent: 64 }
                ]
            };
        }

        if (selection.scope === 'district') {
            const total = 110 + (seed % 115);
            const active = Math.round(total * (0.81 + ((seed % 8) / 100)));
            const validation = 3 + (seed % 7);
            const dominant = ['Perdagangan', 'Kuliner', 'Jasa', 'Industri Rumah Tangga'][seed % 4];

            return {
                total: total,
                active: active,
                validation: validation,
                watched: `${state.villages.length || 'Semua'} Kelurahan`,
                dominant: dominant,
                fields: [
                    { name: dominant, percent: 76 + (seed % 12) },
                    { name: dominant === 'Kuliner' ? 'Perdagangan' : 'Kuliner', percent: 66 + (seed % 10) },
                    { name: 'Jasa', percent: 56 + (seed % 9) }
                ]
            };
        }

        const total = 18 + (seed % 54);
        const active = Math.round(total * (0.78 + ((seed % 10) / 100)));
        const validation = 1 + (seed % 4);
        const dominant = ['Perdagangan', 'Kuliner', 'Jasa'][seed % 3];

        return {
            total: total,
            active: active,
            validation: validation,
            watched: '1 Kelurahan',
            dominant: dominant,
            fields: [
                { name: dominant, percent: 70 + (seed % 16) },
                { name: dominant === 'Perdagangan' ? 'Kuliner' : 'Perdagangan', percent: 58 + (seed % 14) },
                { name: 'Jasa', percent: 48 + (seed % 12) }
            ]
        };
    }

    function renderFields(fields) {
        const container = document.querySelector('[data-public-field-list]');

        if (!container) {
            return;
        }

        container.innerHTML = '';

        fields.forEach(function (field) {
            const row = document.createElement('div');
            const label = document.createElement('span');
            const bar = document.createElement('b');

            label.textContent = field.name;
            bar.style.width = `${Math.max(24, Math.min(95, Number(field.percent || 50)))}%`;

            row.appendChild(label);
            row.appendChild(bar);
            container.appendChild(row);
        });
    }

    function makeChartPayload(selection, preview, mode) {
        const seed = hash((selection.village?.code || selection.district?.code || selection.city?.code || '16.73') + mode);
        const label = selection.label;

        if (mode === 'wilayah') {
            const source = selection.scope === 'city' ? state.districts : state.villages;
            const labels = source.length
                ? source.slice(0, 8).map(function (item) {
                    return String(item.name || '').replace(/^Lubuk Linggau\s+/i, '');
                })
                : ['Wilayah A', 'Wilayah B', 'Wilayah C', 'Wilayah D'];

            return {
                title: `Sebaran UMKM ${label}`,
                subtitle: 'Preview distribusi agregat berdasarkan wilayah terpilih',
                labels: labels,
                unitLabel: 'Jumlah UMKM',
                percentLabel: 'Konsentrasi (%)',
                unitData: labels.map(function (_, index) {
                    return 18 + ((seed + index * 11) % 56);
                }),
                percentData: labels.map(function (_, index) {
                    return 8 + ((seed + index * 7) % 18);
                }),
                summaryOne: selection.scope === 'city' ? 'Kecamatan terpantau' : 'Kelurahan terpantau',
                summaryTwo: 'Jumlah dan konsentrasi wilayah',
                summaryThree: `Sebaran ${label}`
            };
        }

        if (mode === 'legalitas') {
            return {
                title: `Legalitas dan Kelengkapan Data ${label}`,
                subtitle: 'Preview rasio legalitas dan kelengkapan data UMKM',
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul'],
                unitLabel: 'UMKM berlegalitas',
                percentLabel: 'Kelengkapan data (%)',
                unitData: [44, 51, 58, 66, 74, 82, 91].map(function (value) {
                    return Math.max(4, Math.round(value * (preview.total / 180)));
                }),
                percentData: [46, 52, 58, 63, 69, 74, 79].map(function (value) {
                    return Math.min(92, value + (seed % 7));
                }),
                summaryOne: 'Legalitas, profil, lokasi',
                summaryTwo: 'Jumlah dan rasio kelengkapan',
                summaryThree: 'Kesiapan data monitoring'
            };
        }

        return {
            title: `Kinerja UMKM ${label}`,
            subtitle: 'Preview agregat perkembangan UMKM aktif pada wilayah terpilih',
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul'],
            unitLabel: 'UMKM aktif',
            percentLabel: 'Pertumbuhan kinerja (%)',
            unitData: [62, 69, 76, 84, 93, 101, 110].map(function (value) {
                return Math.max(3, Math.round(value * (preview.active / 160)));
            }),
            percentData: [2.4, 3.1, 3.8, 4.5, 5.1, 5.7, 6.2].map(function (value) {
                return Number((value + ((seed % 5) / 10)).toFixed(1));
            }),
            summaryOne: `${label}, bidang usaha, periode`,
            summaryTwo: 'Jumlah UMKM dan persentase pertumbuhan',
            summaryThree: 'Monitoring kinerja wilayah'
        };
    }

    function updateChart(selection, preview) {
        const canvas = document.getElementById('landingMainChart');

        if (!window.Chart || !canvas) {
            return;
        }

        const mode = activeChartMode();
        const payload = makeChartPayload(selection, preview, mode);
        const chart = typeof Chart.getChart === 'function' ? Chart.getChart(canvas) : null;

        setText('#mainChartTitle', payload.title);
        setText('#mainChartSubtitle', payload.subtitle);
        setText('#chartSummaryOne', payload.summaryOne);
        setText('#chartSummaryTwo', payload.summaryTwo);
        setText('#chartSummaryThree', payload.summaryThree);
        setText('[data-public-chart-region]', selection.label);

        if (!chart) {
            return;
        }

        chart.data.labels = payload.labels;
        chart.data.datasets[0].label = payload.unitLabel;
        chart.data.datasets[0].data = payload.unitData;
        chart.data.datasets[1].label = payload.percentLabel;
        chart.data.datasets[1].data = payload.percentData;

        if (chart.options?.scales?.y?.title) {
            chart.options.scales.y.title.text = payload.unitLabel;
        }

        if (chart.options?.scales?.y1?.title) {
            chart.options.scales.y1.title.text = payload.percentLabel;
        }

        chart.update();
    }

    function applySelection(selection) {
        const preview = buildPreview(selection);

        state.applied = selection;

        setText('[data-public-region-source]', `Sumber data: ${selection.label}`);
        setText('[data-public-chart-region]', selection.label);
        setText('[data-region-modal-current]', selection.label);
        setText('[data-public-watched-label]', preview.watched);
        setText('[data-public-dominant-label]', preview.dominant);

        updateMetric('[data-public-metric="total"]', preview.total);
        updateMetric('[data-public-metric="active"]', preview.active);
        updateMetric('[data-public-metric="validation"]', preview.validation);

        renderFields(preview.fields);
        updateChart(selection, preview);

        document.dispatchEvent(new CustomEvent('umkm:landing-region:changed', {
            detail: {
                selection: selection,
                preview: preview
            }
        }));

        UMKM.log?.('info', 'landing region preview applied', {
            label: selection.label,
            scope: selection.scope
        });
    }

    ready(function () {
        document.querySelectorAll('[data-region-modal-open]').forEach(function (button) {
            button.addEventListener('click', openModal);
        });

        document.querySelectorAll('[data-region-modal-close]').forEach(function (button) {
            button.addEventListener('click', closeModal);
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !state.loading) {
                closeModal();
            }
        });

        document.querySelector('[data-landing-region-district]')?.addEventListener('change', onDistrictChanged);

        document.querySelector('[data-landing-region-village]')?.addEventListener('change', function () {
            const selection = getAppliedSelection();
            setModalCurrent(selection.label);
        });

        document.querySelector('[data-region-modal-apply]')?.addEventListener('click', function () {
            if (state.loading) {
                return;
            }

            const selection = getAppliedSelection();

            applySelection(selection);
            closeModal();
        });

        document.querySelectorAll('[data-chart-mode]').forEach(function (tab) {
            tab.addEventListener('click', function () {
                window.setTimeout(function () {
                    applySelection(state.applied);
                }, 0);
            });
        });

        window.setTimeout(function () {
            applySelection(state.applied);
        }, 180);
    });
})();

/* Batch Landing-Regional-2C END */

/* Batch Landing-Mobile-1 START */

/**
 * Compact Chart.js pada mobile.
 * Tujuan: chart dashboard interaktif tetap terbaca pada lebar 400px.
 */
(function () {
    'use strict';

    function ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
            return;
        }

        callback();
    }

    function getLandingChart() {
        const canvas = document.getElementById('landingMainChart');

        if (!canvas || !window.Chart || typeof Chart.getChart !== 'function') {
            return null;
        }

        return Chart.getChart(canvas);
    }

    function applyMobileChartMode() {
        const chart = getLandingChart();

        if (!chart || !chart.options) {
            return;
        }

        const compact = window.matchMedia('(max-width: 575.98px)').matches;
        const veryCompact = window.matchMedia('(max-width: 420px)').matches;

        if (chart.options.plugins && chart.options.plugins.legend) {
            chart.options.plugins.legend.display = !veryCompact;
            chart.options.plugins.legend.position = 'bottom';

            if (chart.options.plugins.legend.labels) {
                chart.options.plugins.legend.labels.padding = compact ? 10 : 18;

                chart.options.plugins.legend.labels.font = Object.assign(
                    {},
                    chart.options.plugins.legend.labels.font || {},
                    {
                        size: compact ? 10 : 12,
                        weight: '700'
                    }
                );
            }
        }

        if (chart.options.scales) {
            if (chart.options.scales.x && chart.options.scales.x.ticks) {
                chart.options.scales.x.ticks.autoSkip = true;
                chart.options.scales.x.ticks.maxTicksLimit = compact ? 4 : 8;
                chart.options.scales.x.ticks.maxRotation = compact ? 0 : 50;
                chart.options.scales.x.ticks.minRotation = 0;

                chart.options.scales.x.ticks.font = Object.assign(
                    {},
                    chart.options.scales.x.ticks.font || {},
                    {
                        size: compact ? 10 : 12,
                        weight: '700'
                    }
                );
            }

            if (chart.options.scales.y) {
                if (chart.options.scales.y.title) {
                    chart.options.scales.y.title.display = !compact;
                }

                if (chart.options.scales.y.ticks) {
                    chart.options.scales.y.ticks.maxTicksLimit = compact ? 5 : 8;
                    chart.options.scales.y.ticks.font = Object.assign(
                        {},
                        chart.options.scales.y.ticks.font || {},
                        {
                            size: compact ? 10 : 12,
                            weight: '700'
                        }
                    );
                }
            }

            if (chart.options.scales.y1) {
                if (chart.options.scales.y1.title) {
                    chart.options.scales.y1.title.display = !compact;
                }

                if (chart.options.scales.y1.ticks) {
                    chart.options.scales.y1.ticks.maxTicksLimit = compact ? 5 : 8;
                    chart.options.scales.y1.ticks.font = Object.assign(
                        {},
                        chart.options.scales.y1.ticks.font || {},
                        {
                            size: compact ? 10 : 12,
                            weight: '700'
                        }
                    );
                }
            }
        }

        chart.update('none');
    }

    ready(function () {
        window.setTimeout(applyMobileChartMode, 160);
        window.setTimeout(applyMobileChartMode, 420);

        document.querySelectorAll('[data-chart-mode]').forEach(function (tab) {
            tab.addEventListener('click', function () {
                window.setTimeout(applyMobileChartMode, 120);
            });
        });

        document.addEventListener('umkm:landing-region:changed', function () {
            window.setTimeout(applyMobileChartMode, 120);
        });

        window.addEventListener('resize', function () {
            window.clearTimeout(window.__landingMobileChartTimer);
            window.__landingMobileChartTimer = window.setTimeout(applyMobileChartMode, 160);
        }, { passive: true });
    });
})();

/* Batch Landing-Mobile-1 END */

/* Batch Landing-Content-1 START */

/**
 * Penyempurnaan card Monitoring UMKM:
 * - Data wilayah pada card ditampilkan sebagai angka.
 * - Indikator menampilkan nominal UMKM dan persentase.
 * - Mengikuti event dari region modal agar tidak mengganggu logic existing.
 */
(function () {
    'use strict';

    function ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
            return;
        }

        callback();
    }

    function formatNumber(value) {
        return Number(value || 0).toLocaleString('id-ID');
    }

    function cleanName(value) {
        return String(value || '')
            .replace(/^Kecamatan\s+/i, '')
            .replace(/^Kelurahan\s+/i, '')
            .replace(/^Desa\s+/i, '')
            .trim();
    }

    function getOptions(selector) {
        const select = document.querySelector(selector);

        if (!select) {
            return [];
        }

        return Array.from(select.options)
            .filter(function (option) {
                return option.value
                    && option.dataset.virtual !== '1'
                    && !option.value.startsWith('__');
            })
            .map(function (option) {
                return {
                    code: option.value,
                    name: cleanName(option.dataset.regionName || option.textContent || option.value)
                };
            })
            .filter(function (item) {
                return item.name;
            });
    }

    function pickAreaNames(selection) {
        if (selection && selection.scope === 'village') {
            return [
                { name: cleanName(selection.village?.name || selection.label || 'Wilayah terpilih') }
            ];
        }

        if (selection && selection.scope === 'district') {
            const villages = getOptions('[data-landing-region-village]');

            if (villages.length) {
                return villages.slice(0, 3);
            }

            return [
                { name: cleanName(selection.district?.name || selection.label || 'Kecamatan terpilih') }
            ];
        }

        const districts = getOptions('[data-landing-region-district]');

        if (districts.length) {
            return districts.slice(0, 3);
        }

        return [
            { name: 'Lubuk Linggau Timur II' },
            { name: 'Lubuk Linggau Utara II' },
            { name: 'Lubuk Linggau Barat II' }
        ];
    }

    function indicatorCount(total, percent, index) {
        const basePercent = Math.max(1, Math.min(100, Number(percent || 0)));
        const value = Math.round(Number(total || 0) * (basePercent / 100));

        return Math.max(index === 0 ? 1 : 0, value);
    }

    function renderIndicatorDetails(preview) {
        const container = document.querySelector('[data-public-field-list]');

        if (!container || !preview || !Array.isArray(preview.fields)) {
            return;
        }

        container.innerHTML = '';

        preview.fields.forEach(function (field, index) {
            const percent = Math.max(1, Math.min(100, Math.round(Number(field.percent || 0))));
            const count = field.count || indicatorCount(preview.total, percent, index);

            const row = document.createElement('div');
            const label = document.createElement('span');
            const bar = document.createElement('b');

            label.innerHTML = '<span class="indicator-name">' + field.name + '</span><span class="indicator-meta">' + formatNumber(count) + ' UMKM • ' + percent + '%</span>';
            bar.style.width = Math.max(24, Math.min(95, percent)) + '%';

            row.appendChild(label);
            row.appendChild(bar);
            container.appendChild(row);
        });
    }

    function renderAreaStats(selection, preview) {
        const container = document.querySelector('[data-public-area-list]');

        if (!container || !preview || !Array.isArray(preview.fields)) {
            return;
        }

        const areaNames = pickAreaNames(selection);
        const distributions = areaNames.length === 1 ? [1] : [0.42, 0.34, 0.24];

        container.innerHTML = '';

        areaNames.slice(0, 3).forEach(function (area, index) {
            const field = preview.fields[index % preview.fields.length] || preview.fields[0];
            const percent = Math.max(1, Math.min(100, Math.round(Number(field.percent || 0))));
            const count = Math.max(1, Math.round(Number(preview.total || 0) * (distributions[index] || 0.2)));

            const row = document.createElement('div');
            const name = document.createElement('span');
            const value = document.createElement('strong');
            const sector = document.createElement('small');

            name.textContent = area.name;
            value.textContent = formatNumber(count) + ' UMKM';
            sector.textContent = field.name + ' ' + percent + '%';

            row.appendChild(name);
            row.appendChild(value);
            row.appendChild(sector);
            container.appendChild(row);
        });
    }

    function applyCardDetails(selection, preview) {
        renderIndicatorDetails(preview);
        renderAreaStats(selection, preview);
    }

    ready(function () {
        document.addEventListener('umkm:landing-region:changed', function (event) {
            const detail = event.detail || {};

            applyCardDetails(detail.selection, detail.preview);
        });

        window.setTimeout(function () {
            const totalElement = document.querySelector('[data-public-metric="total"]');
            const total = totalElement ? Number(totalElement.dataset.count || totalElement.textContent.replace(/\D/g, '') || 1248) : 1248;

            applyCardDetails(
                {
                    scope: 'city',
                    label: 'Kota Lubuklinggau'
                },
                {
                    total: total,
                    fields: [
                        { name: 'Perdagangan', percent: 42 },
                        { name: 'Kuliner', percent: 35 },
                        { name: 'Jasa', percent: 23 }
                    ]
                }
            );
        }, 260);
    });
})();

/* Batch Landing-Content-1 END */
