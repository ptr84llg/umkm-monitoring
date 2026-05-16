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
 * Batch 2B-Fix1 Location Permission Status & Reset Guide
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
        const guideToggle = document.querySelector('[data-location-guide-toggle]');
        const guide = document.querySelector('[data-location-guide]');
        const permissionBox = document.querySelector('[data-location-permission-state]');
        const permissionLabel = document.querySelector('[data-location-permission-label]');
        const gatedLinks = document.querySelectorAll('[data-location-gated]');

        const state = {
            status: 'booting',
            permission: 'unknown',
            checkedAt: null,
            lastResult: null
        };

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
        }

        function setNotice(status, titleText, messageText, visible, permissionState) {
            document.documentElement.setAttribute('data-location-gate', status);

            if (noticeCard) {
                noticeCard.classList.remove('is-checking', 'is-granted', 'is-blocked', 'is-unsupported', 'is-denied', 'is-prompt');
                noticeCard.classList.add(`is-${status}`);
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

            if (notice) {
                notice.hidden = !visible;
            }
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
                'Website tidak dapat memunculkan ulang permintaan lokasi karena izin sudah diblokir. Ubah izin lokasi dari pengaturan situs pada browser, lalu refresh halaman.',
                true,
                permission && permission.state ? permission.state : 'denied'
            );

            setGuideVisible(true);
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
                    'Aktifkan izin lokasi pada browser untuk membuka tombol masuk ke sistem.',
                    true,
                    permissionState
                );
                setGuideVisible(true);
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
                    'Klik Izinkan pada permintaan lokasi browser agar tombol masuk ke sistem dapat ditampilkan.',
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

                const allowed = await checkLocationGate();

                if (allowed) {
                    window.location.assign(href);
                }
            });
        });

        if (retryButton) {
            retryButton.addEventListener('click', function () {
                checkLocationGate();
            });
        }

        if (guideToggle) {
            guideToggle.addEventListener('click', function () {
                if (!guide) {
                    return;
                }

                guide.hidden = !guide.hidden;
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
                permission: getPermissionStatus
            });
        }
    });
})();
