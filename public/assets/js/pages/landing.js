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

/* Batch Landing-Regional-1 START */

/**
 * Public multi-wilayah preview.
 * Catatan:
 * - Tidak memakai API internal.
 * - Tidak membuka data sensitif.
 * - Hanya mengubah preview agregat pada landing page.
 */
(function () {
    'use strict';

    const previewRegions = {
        lubuklinggau: {
            name: 'Kota Lubuklinggau',
            source: 'Sumber data: Kota Lubuklinggau',
            level: 'Kota/Kabupaten',
            total: 1248,
            active: 1086,
            validation: 36,
            watched: '8 Kecamatan',
            dominant: 'Perdagangan',
            legality: 68,
            completeness: 74,
            fields: [
                { name: 'Perdagangan', percent: 82 },
                { name: 'Kuliner', percent: 74 },
                { name: 'Jasa', percent: 64 }
            ],
            charts: {
                kinerja: {
                    title: 'Kinerja dan Pertumbuhan UMKM Kota Lubuklinggau',
                    subtitle: 'Preview agregat perkembangan UMKM aktif pada wilayah kota',
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul'],
                    unitLabel: 'UMKM aktif',
                    percentLabel: 'Pertumbuhan kinerja (%)',
                    unitData: [118, 126, 133, 141, 152, 164, 173],
                    percentData: [3.2, 4.1, 3.8, 5.2, 6.4, 7.1, 6.7],
                    summaryOne: 'Kota Lubuklinggau, bidang usaha, periode',
                    summaryTwo: 'Jumlah UMKM dan persentase pertumbuhan',
                    summaryThree: 'Monitoring kinerja wilayah kota'
                },
                wilayah: {
                    title: 'Sebaran UMKM per Kecamatan',
                    subtitle: 'Preview konsentrasi UMKM pada kecamatan di Kota Lubuklinggau',
                    labels: ['Barat I', 'Barat II', 'Timur I', 'Timur II', 'Selatan I', 'Selatan II', 'Utara I', 'Utara II'],
                    unitLabel: 'Jumlah UMKM',
                    percentLabel: 'Konsentrasi wilayah (%)',
                    unitData: [92, 118, 146, 132, 104, 96, 88, 122],
                    percentData: [9.5, 12.1, 15.2, 13.6, 10.7, 9.9, 8.7, 12.8],
                    summaryOne: 'Ranking kecamatan',
                    summaryTwo: 'Jumlah dan konsentrasi wilayah',
                    summaryThree: 'Sebaran UMKM tingkat kota'
                },
                legalitas: {
                    title: 'Legalitas dan Kelengkapan Data Kota Lubuklinggau',
                    subtitle: 'Preview rasio legalitas dan kelengkapan data UMKM',
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul'],
                    unitLabel: 'UMKM berlegalitas',
                    percentLabel: 'Kelengkapan data (%)',
                    unitData: [68, 74, 82, 91, 104, 116, 128],
                    percentData: [48, 52, 57, 62, 68, 73, 78],
                    summaryOne: 'Legalitas, profil, lokasi',
                    summaryTwo: 'Jumlah dan rasio kelengkapan',
                    summaryThree: 'Kesiapan data monitoring'
                }
            }
        },
        timur1: {
            name: 'Lubuk Linggau Timur I',
            source: 'Sumber data: Lubuk Linggau Timur I',
            level: 'Kecamatan',
            total: 186,
            active: 163,
            validation: 5,
            watched: 'Kelurahan terpantau',
            dominant: 'Kuliner',
            legality: 71,
            completeness: 76,
            fields: [
                { name: 'Kuliner', percent: 84 },
                { name: 'Perdagangan', percent: 78 },
                { name: 'Jasa', percent: 61 }
            ],
            charts: {
                kinerja: {
                    title: 'Kinerja UMKM Lubuk Linggau Timur I',
                    subtitle: 'Preview perkembangan UMKM aktif pada tingkat kecamatan',
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul'],
                    unitLabel: 'UMKM aktif',
                    percentLabel: 'Pertumbuhan (%)',
                    unitData: [132, 137, 142, 148, 153, 159, 163],
                    percentData: [2.4, 3.1, 3.5, 4.2, 4.8, 5.2, 5.6],
                    summaryOne: 'Timur I, kelurahan, bidang usaha',
                    summaryTwo: 'Jumlah aktif dan tren',
                    summaryThree: 'Kinerja kecamatan'
                },
                wilayah: {
                    title: 'Sebaran UMKM Lubuk Linggau Timur I',
                    subtitle: 'Preview distribusi UMKM pada wilayah turunan kecamatan',
                    labels: ['Kel. A', 'Kel. B', 'Kel. C', 'Kel. D', 'Kel. E'],
                    unitLabel: 'Jumlah UMKM',
                    percentLabel: 'Konsentrasi (%)',
                    unitData: [34, 42, 29, 31, 27],
                    percentData: [18.2, 22.6, 15.6, 16.7, 14.5],
                    summaryOne: 'Wilayah turunan kecamatan',
                    summaryTwo: 'Jumlah dan konsentrasi',
                    summaryThree: 'Sebaran tingkat kecamatan'
                },
                legalitas: {
                    title: 'Legalitas UMKM Lubuk Linggau Timur I',
                    subtitle: 'Preview status legalitas dan kelengkapan data kecamatan',
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul'],
                    unitLabel: 'UMKM berlegalitas',
                    percentLabel: 'Kelengkapan (%)',
                    unitData: [76, 82, 89, 96, 103, 111, 118],
                    percentData: [52, 56, 60, 64, 68, 72, 76],
                    summaryOne: 'NIB, profil, lokasi',
                    summaryTwo: 'Legalitas dan kelengkapan',
                    summaryThree: 'Kesiapan data kecamatan'
                }
            }
        },
        barat1: {
            name: 'Lubuk Linggau Barat I',
            source: 'Sumber data: Lubuk Linggau Barat I',
            level: 'Kecamatan',
            total: 142,
            active: 124,
            validation: 4,
            watched: 'Kelurahan terpantau',
            dominant: 'Perdagangan',
            legality: 66,
            completeness: 72,
            fields: [
                { name: 'Perdagangan', percent: 81 },
                { name: 'Jasa', percent: 68 },
                { name: 'Kuliner', percent: 62 }
            ],
            charts: {
                kinerja: {
                    title: 'Kinerja UMKM Lubuk Linggau Barat I',
                    subtitle: 'Preview perkembangan aktivitas UMKM pada wilayah barat',
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul'],
                    unitLabel: 'UMKM aktif',
                    percentLabel: 'Pertumbuhan (%)',
                    unitData: [94, 99, 105, 111, 116, 121, 124],
                    percentData: [2.1, 2.7, 3.4, 3.8, 4.1, 4.6, 4.9],
                    summaryOne: 'Barat I, bidang usaha, periode',
                    summaryTwo: 'Aktivitas dan pertumbuhan',
                    summaryThree: 'Kinerja kecamatan'
                },
                wilayah: {
                    title: 'Sebaran UMKM Lubuk Linggau Barat I',
                    subtitle: 'Preview konsentrasi UMKM pada wilayah turunan',
                    labels: ['Kel. A', 'Kel. B', 'Kel. C', 'Kel. D'],
                    unitLabel: 'Jumlah UMKM',
                    percentLabel: 'Konsentrasi (%)',
                    unitData: [32, 28, 36, 28],
                    percentData: [22.5, 19.7, 25.3, 19.7],
                    summaryOne: 'Wilayah turunan',
                    summaryTwo: 'Jumlah dan konsentrasi',
                    summaryThree: 'Sebaran kecamatan'
                },
                legalitas: {
                    title: 'Legalitas UMKM Lubuk Linggau Barat I',
                    subtitle: 'Preview legalitas dan kelengkapan data',
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul'],
                    unitLabel: 'UMKM berlegalitas',
                    percentLabel: 'Kelengkapan (%)',
                    unitData: [48, 54, 59, 65, 72, 78, 84],
                    percentData: [45, 49, 54, 58, 62, 68, 72],
                    summaryOne: 'Legalitas dan profil',
                    summaryTwo: 'Rasio kelengkapan',
                    summaryThree: 'Kesiapan data'
                }
            }
        },
        selatan1: {
            name: 'Lubuk Linggau Selatan I',
            source: 'Sumber data: Lubuk Linggau Selatan I',
            level: 'Kecamatan',
            total: 128,
            active: 109,
            validation: 6,
            watched: 'Kelurahan terpantau',
            dominant: 'Jasa',
            legality: 62,
            completeness: 70,
            fields: [
                { name: 'Jasa', percent: 75 },
                { name: 'Perdagangan', percent: 69 },
                { name: 'Kuliner', percent: 58 }
            ],
            charts: {
                kinerja: {
                    title: 'Kinerja UMKM Lubuk Linggau Selatan I',
                    subtitle: 'Preview perkembangan aktivitas UMKM wilayah selatan',
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul'],
                    unitLabel: 'UMKM aktif',
                    percentLabel: 'Pertumbuhan (%)',
                    unitData: [86, 91, 95, 98, 102, 106, 109],
                    percentData: [1.9, 2.4, 2.9, 3.3, 3.8, 4.2, 4.5],
                    summaryOne: 'Selatan I, bidang usaha',
                    summaryTwo: 'Aktif dan tren',
                    summaryThree: 'Kinerja kecamatan'
                },
                wilayah: {
                    title: 'Sebaran UMKM Lubuk Linggau Selatan I',
                    subtitle: 'Preview distribusi UMKM pada wilayah turunan',
                    labels: ['Kel. A', 'Kel. B', 'Kel. C', 'Kel. D'],
                    unitLabel: 'Jumlah UMKM',
                    percentLabel: 'Konsentrasi (%)',
                    unitData: [24, 31, 29, 25],
                    percentData: [18.8, 24.2, 22.7, 19.5],
                    summaryOne: 'Wilayah turunan',
                    summaryTwo: 'Jumlah dan konsentrasi',
                    summaryThree: 'Sebaran kecamatan'
                },
                legalitas: {
                    title: 'Legalitas UMKM Lubuk Linggau Selatan I',
                    subtitle: 'Preview legalitas dan kelengkapan data',
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul'],
                    unitLabel: 'UMKM berlegalitas',
                    percentLabel: 'Kelengkapan (%)',
                    unitData: [41, 46, 51, 57, 62, 67, 72],
                    percentData: [42, 47, 51, 56, 61, 66, 70],
                    summaryOne: 'Legalitas dan profil',
                    summaryTwo: 'Rasio kelengkapan',
                    summaryThree: 'Kesiapan data'
                }
            }
        },
        utara1: {
            name: 'Lubuk Linggau Utara I',
            source: 'Sumber data: Lubuk Linggau Utara I',
            level: 'Kecamatan',
            total: 116,
            active: 98,
            validation: 5,
            watched: 'Kelurahan terpantau',
            dominant: 'Perdagangan',
            legality: 60,
            completeness: 68,
            fields: [
                { name: 'Perdagangan', percent: 76 },
                { name: 'Jasa', percent: 64 },
                { name: 'Kuliner', percent: 57 }
            ],
            charts: {
                kinerja: {
                    title: 'Kinerja UMKM Lubuk Linggau Utara I',
                    subtitle: 'Preview perkembangan UMKM aktif wilayah utara',
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul'],
                    unitLabel: 'UMKM aktif',
                    percentLabel: 'Pertumbuhan (%)',
                    unitData: [78, 82, 85, 89, 92, 95, 98],
                    percentData: [1.8, 2.2, 2.7, 3.1, 3.5, 3.9, 4.2],
                    summaryOne: 'Utara I, bidang usaha',
                    summaryTwo: 'Aktif dan tren',
                    summaryThree: 'Kinerja kecamatan'
                },
                wilayah: {
                    title: 'Sebaran UMKM Lubuk Linggau Utara I',
                    subtitle: 'Preview distribusi UMKM pada wilayah turunan',
                    labels: ['Kel. A', 'Kel. B', 'Kel. C'],
                    unitLabel: 'Jumlah UMKM',
                    percentLabel: 'Konsentrasi (%)',
                    unitData: [36, 31, 31],
                    percentData: [31.0, 26.7, 26.7],
                    summaryOne: 'Wilayah turunan',
                    summaryTwo: 'Jumlah dan konsentrasi',
                    summaryThree: 'Sebaran kecamatan'
                },
                legalitas: {
                    title: 'Legalitas UMKM Lubuk Linggau Utara I',
                    subtitle: 'Preview legalitas dan kelengkapan data',
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul'],
                    unitLabel: 'UMKM berlegalitas',
                    percentLabel: 'Kelengkapan (%)',
                    unitData: [35, 39, 44, 48, 54, 59, 64],
                    percentData: [40, 44, 49, 54, 59, 64, 68],
                    summaryOne: 'Legalitas dan profil',
                    summaryTwo: 'Rasio kelengkapan',
                    summaryThree: 'Kesiapan data'
                }
            }
        },
        timur2: {
            name: 'Lubuk Linggau Timur II',
            source: 'Sumber data: Lubuk Linggau Timur II',
            level: 'Kecamatan',
            total: 174,
            active: 153,
            validation: 4,
            watched: 'Kelurahan terpantau',
            dominant: 'Perdagangan',
            legality: 73,
            completeness: 78,
            fields: [
                { name: 'Perdagangan', percent: 87 },
                { name: 'Kuliner', percent: 79 },
                { name: 'Jasa', percent: 66 }
            ],
            charts: {
                kinerja: {
                    title: 'Kinerja UMKM Lubuk Linggau Timur II',
                    subtitle: 'Preview perkembangan UMKM aktif pada wilayah timur',
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul'],
                    unitLabel: 'UMKM aktif',
                    percentLabel: 'Pertumbuhan (%)',
                    unitData: [121, 127, 133, 139, 145, 149, 153],
                    percentData: [2.8, 3.3, 3.9, 4.5, 5.0, 5.4, 5.7],
                    summaryOne: 'Timur II, bidang usaha',
                    summaryTwo: 'Aktif dan tren',
                    summaryThree: 'Kinerja kecamatan'
                },
                wilayah: {
                    title: 'Sebaran UMKM Lubuk Linggau Timur II',
                    subtitle: 'Preview konsentrasi UMKM pada wilayah turunan',
                    labels: ['Kel. A', 'Kel. B', 'Kel. C', 'Kel. D', 'Kel. E'],
                    unitLabel: 'Jumlah UMKM',
                    percentLabel: 'Konsentrasi (%)',
                    unitData: [39, 34, 28, 31, 21],
                    percentData: [22.4, 19.5, 16.1, 17.8, 12.1],
                    summaryOne: 'Wilayah turunan',
                    summaryTwo: 'Jumlah dan konsentrasi',
                    summaryThree: 'Sebaran kecamatan'
                },
                legalitas: {
                    title: 'Legalitas UMKM Lubuk Linggau Timur II',
                    subtitle: 'Preview legalitas dan kelengkapan data',
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul'],
                    unitLabel: 'UMKM berlegalitas',
                    percentLabel: 'Kelengkapan (%)',
                    unitData: [81, 88, 96, 104, 112, 119, 127],
                    percentData: [55, 59, 64, 68, 72, 75, 78],
                    summaryOne: 'Legalitas dan profil',
                    summaryTwo: 'Rasio kelengkapan',
                    summaryThree: 'Kesiapan data'
                }
            }
        },
        barat2: {
            name: 'Lubuk Linggau Barat II',
            source: 'Sumber data: Lubuk Linggau Barat II',
            level: 'Kecamatan',
            total: 158,
            active: 137,
            validation: 3,
            watched: 'Kelurahan terpantau',
            dominant: 'Kuliner',
            legality: 69,
            completeness: 75,
            fields: [
                { name: 'Kuliner', percent: 83 },
                { name: 'Perdagangan', percent: 77 },
                { name: 'Jasa', percent: 63 }
            ],
            charts: {
                kinerja: {
                    title: 'Kinerja UMKM Lubuk Linggau Barat II',
                    subtitle: 'Preview perkembangan UMKM aktif wilayah barat',
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul'],
                    unitLabel: 'UMKM aktif',
                    percentLabel: 'Pertumbuhan (%)',
                    unitData: [104, 110, 116, 123, 128, 133, 137],
                    percentData: [2.3, 2.9, 3.5, 4.1, 4.5, 4.9, 5.1],
                    summaryOne: 'Barat II, bidang usaha',
                    summaryTwo: 'Aktif dan tren',
                    summaryThree: 'Kinerja kecamatan'
                },
                wilayah: {
                    title: 'Sebaran UMKM Lubuk Linggau Barat II',
                    subtitle: 'Preview konsentrasi UMKM pada wilayah turunan',
                    labels: ['Kel. A', 'Kel. B', 'Kel. C', 'Kel. D'],
                    unitLabel: 'Jumlah UMKM',
                    percentLabel: 'Konsentrasi (%)',
                    unitData: [42, 31, 35, 29],
                    percentData: [26.6, 19.6, 22.2, 18.4],
                    summaryOne: 'Wilayah turunan',
                    summaryTwo: 'Jumlah dan konsentrasi',
                    summaryThree: 'Sebaran kecamatan'
                },
                legalitas: {
                    title: 'Legalitas UMKM Lubuk Linggau Barat II',
                    subtitle: 'Preview legalitas dan kelengkapan data',
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul'],
                    unitLabel: 'UMKM berlegalitas',
                    percentLabel: 'Kelengkapan (%)',
                    unitData: [62, 68, 74, 81, 88, 95, 101],
                    percentData: [50, 55, 60, 64, 69, 72, 75],
                    summaryOne: 'Legalitas dan profil',
                    summaryTwo: 'Rasio kelengkapan',
                    summaryThree: 'Kesiapan data'
                }
            }
        },
        selatan2: {
            name: 'Lubuk Linggau Selatan II',
            source: 'Sumber data: Lubuk Linggau Selatan II',
            level: 'Kecamatan',
            total: 132,
            active: 113,
            validation: 5,
            watched: 'Kelurahan terpantau',
            dominant: 'Perdagangan',
            legality: 64,
            completeness: 71,
            fields: [
                { name: 'Perdagangan', percent: 79 },
                { name: 'Kuliner', percent: 67 },
                { name: 'Jasa', percent: 60 }
            ],
            charts: {
                kinerja: {
                    title: 'Kinerja UMKM Lubuk Linggau Selatan II',
                    subtitle: 'Preview perkembangan UMKM aktif wilayah selatan',
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul'],
                    unitLabel: 'UMKM aktif',
                    percentLabel: 'Pertumbuhan (%)',
                    unitData: [89, 94, 98, 102, 106, 110, 113],
                    percentData: [2.0, 2.5, 2.9, 3.3, 3.7, 4.0, 4.3],
                    summaryOne: 'Selatan II, bidang usaha',
                    summaryTwo: 'Aktif dan tren',
                    summaryThree: 'Kinerja kecamatan'
                },
                wilayah: {
                    title: 'Sebaran UMKM Lubuk Linggau Selatan II',
                    subtitle: 'Preview konsentrasi UMKM pada wilayah turunan',
                    labels: ['Kel. A', 'Kel. B', 'Kel. C', 'Kel. D'],
                    unitLabel: 'Jumlah UMKM',
                    percentLabel: 'Konsentrasi (%)',
                    unitData: [29, 33, 27, 24],
                    percentData: [22.0, 25.0, 20.5, 18.2],
                    summaryOne: 'Wilayah turunan',
                    summaryTwo: 'Jumlah dan konsentrasi',
                    summaryThree: 'Sebaran kecamatan'
                },
                legalitas: {
                    title: 'Legalitas UMKM Lubuk Linggau Selatan II',
                    subtitle: 'Preview legalitas dan kelengkapan data',
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul'],
                    unitLabel: 'UMKM berlegalitas',
                    percentLabel: 'Kelengkapan (%)',
                    unitData: [45, 51, 56, 61, 67, 72, 77],
                    percentData: [43, 48, 53, 58, 63, 67, 71],
                    summaryOne: 'Legalitas dan profil',
                    summaryTwo: 'Rasio kelengkapan',
                    summaryThree: 'Kesiapan data'
                }
            }
        },
        utara2: {
            name: 'Lubuk Linggau Utara II',
            source: 'Sumber data: Lubuk Linggau Utara II',
            level: 'Kecamatan',
            total: 212,
            active: 189,
            validation: 4,
            watched: 'Kelurahan terpantau',
            dominant: 'Perdagangan',
            legality: 76,
            completeness: 80,
            fields: [
                { name: 'Perdagangan', percent: 88 },
                { name: 'Jasa', percent: 72 },
                { name: 'Kuliner', percent: 65 }
            ],
            charts: {
                kinerja: {
                    title: 'Kinerja UMKM Lubuk Linggau Utara II',
                    subtitle: 'Preview perkembangan UMKM aktif pada wilayah utara',
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul'],
                    unitLabel: 'UMKM aktif',
                    percentLabel: 'Pertumbuhan (%)',
                    unitData: [154, 161, 167, 174, 181, 186, 189],
                    percentData: [3.0, 3.6, 4.1, 4.8, 5.3, 5.8, 6.1],
                    summaryOne: 'Utara II, bidang usaha',
                    summaryTwo: 'Aktif dan tren',
                    summaryThree: 'Kinerja kecamatan'
                },
                wilayah: {
                    title: 'Sebaran UMKM Lubuk Linggau Utara II',
                    subtitle: 'Preview konsentrasi UMKM pada wilayah turunan',
                    labels: ['Kel. A', 'Kel. B', 'Kel. C', 'Kel. D', 'Kel. E'],
                    unitLabel: 'Jumlah UMKM',
                    percentLabel: 'Konsentrasi (%)',
                    unitData: [46, 39, 35, 37, 32],
                    percentData: [21.7, 18.4, 16.5, 17.5, 15.1],
                    summaryOne: 'Wilayah turunan',
                    summaryTwo: 'Jumlah dan konsentrasi',
                    summaryThree: 'Sebaran kecamatan'
                },
                legalitas: {
                    title: 'Legalitas UMKM Lubuk Linggau Utara II',
                    subtitle: 'Preview legalitas dan kelengkapan data',
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul'],
                    unitLabel: 'UMKM berlegalitas',
                    percentLabel: 'Kelengkapan (%)',
                    unitData: [98, 106, 114, 123, 132, 141, 149],
                    percentData: [58, 62, 66, 70, 74, 77, 80],
                    summaryOne: 'Legalitas dan profil',
                    summaryTwo: 'Rasio kelengkapan',
                    summaryThree: 'Kesiapan data'
                }
            }
        }
    };

    function onReady(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
            return;
        }

        callback();
    }

    function formatNumber(value) {
        return Number(value || 0).toLocaleString('id-ID');
    }

    function currentMode() {
        const activeTab = document.querySelector('[data-chart-mode].active');

        return activeTab ? activeTab.dataset.chartMode || 'kinerja' : 'kinerja';
    }

    function setText(selector, value) {
        const element = document.querySelector(selector);

        if (element) {
            element.textContent = value;
        }
    }

    function updateMetric(selector, value) {
        const element = document.querySelector(selector);

        if (!element) {
            return;
        }

        element.dataset.count = String(value);
        element.textContent = formatNumber(value);
    }

    function renderFields(region) {
        const container = document.querySelector('[data-public-field-list]');

        if (!container) {
            return;
        }

        container.innerHTML = '';

        region.fields.forEach(function (field) {
            const row = document.createElement('div');
            const label = document.createElement('span');
            const bar = document.createElement('b');

            label.textContent = field.name;
            bar.className = 'progress-fill-' + Math.max(50, Math.min(90, Number(field.percent || 50)));

            row.appendChild(label);
            row.appendChild(bar);
            container.appendChild(row);
        });
    }

    function updateChart(region, mode) {
        const canvas = document.getElementById('landingMainChart');

        if (!window.Chart || !canvas || !region.charts || !region.charts[mode]) {
            return;
        }

        const data = region.charts[mode];
        const chart = typeof Chart.getChart === 'function' ? Chart.getChart(canvas) : null;

        setText('#mainChartTitle', data.title);
        setText('#mainChartSubtitle', data.subtitle);
        setText('#chartSummaryOne', data.summaryOne);
        setText('#chartSummaryTwo', data.summaryTwo);
        setText('#chartSummaryThree', data.summaryThree);
        setText('[data-public-chart-region]', region.name);

        const chartData = {
            labels: data.labels,
            datasets: [
                {
                    label: data.unitLabel,
                    data: data.unitData,
                    yAxisID: 'y',
                    borderColor: '#0f7665',
                    backgroundColor: 'rgba(15, 118, 101, .16)',
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
        };

        if (chart) {
            chart.data = chartData;

            if (chart.options && chart.options.scales && chart.options.scales.y && chart.options.scales.y.title) {
                chart.options.scales.y.title.text = data.unitLabel;
            }

            if (chart.options && chart.options.scales && chart.options.scales.y1 && chart.options.scales.y1.title) {
                chart.options.scales.y1.title.text = data.percentLabel;
            }

            chart.update();
        }
    }

    function applyRegion(key) {
        const region = previewRegions[key] || previewRegions.lubuklinggau;
        const mode = currentMode();

        setText('[data-public-region-label]', region.name);
        setText('[data-public-region-source]', region.source);
        setText('[data-public-watched-label]', region.watched);
        setText('[data-public-dominant-label]', region.dominant);

        updateMetric('[data-public-metric="total"]', region.total);
        updateMetric('[data-public-metric="active"]', region.active);
        updateMetric('[data-public-metric="validation"]', region.validation);

        renderFields(region);
        updateChart(region, mode);

        document.dispatchEvent(new CustomEvent('umkm:landing-region:changed', {
            detail: {
                key: key,
                region: {
                    name: region.name,
                    level: region.level,
                    total: region.total,
                    active: region.active,
                    validation: region.validation,
                    legality: region.legality,
                    completeness: region.completeness
                }
            }
        }));

        if (window.UMKM && UMKM.log) {
            UMKM.log('info', 'landing regional preview changed', {
                key: key,
                name: region.name
            });
        }
    }

    onReady(function () {
        const selector = document.querySelector('[data-public-region-select]');

        if (!selector) {
            return;
        }

        selector.addEventListener('change', function () {
            applyRegion(selector.value || 'lubuklinggau');
        });

        document.querySelectorAll('[data-chart-mode]').forEach(function (tab) {
            tab.addEventListener('click', function () {
                window.setTimeout(function () {
                    applyRegion(selector.value || 'lubuklinggau');
                }, 0);
            });
        });

        window.setTimeout(function () {
            applyRegion(selector.value || 'lubuklinggau');
        }, 80);
    });
})();

/* Batch Landing-Regional-1 END */
