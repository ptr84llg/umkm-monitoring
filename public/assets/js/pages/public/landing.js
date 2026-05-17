(function () {
    'use strict';

    window.UMKM = window.UMKM || {};

    const UMKM = window.UMKM;

    const SELECTORS = {
        header: '[data-landing-header]',
        reveal: '.reveal',
        parallax: '[data-parallax]',
        counter: '.count-up',
        tiltCard: '[data-tilt-card]',
        menuCanvas: '[data-menu-canvas]',
        menuOpen: '[data-menu-open]',
        menuClose: '[data-menu-close], [data-menu-link]',
        toTop: '[data-to-top]',
        chartTab: '[data-chart-mode]',
        chartCanvas: '#landingMainChart',
        mainChartTitle: '#mainChartTitle',
        mainChartSubtitle: '#mainChartSubtitle',
        chartSummaryOne: '#chartSummaryOne',
        chartSummaryTwo: '#chartSummaryTwo',
        chartSummaryThree: '#chartSummaryThree',
        publicChartRegion: '[data-public-chart-region]',
        publicRegionSource: '[data-public-region-source]',
        publicWatchedLabel: '[data-public-watched-label]',
        publicDominantLabel: '[data-public-dominant-label]',
        publicFieldList: '[data-public-field-list]',
        publicAreaList: '[data-public-area-list]',
        publicEmptyState: '[data-public-empty-state]',
        publicEmptyTitle: '[data-public-empty-title]',
        publicEmptyMessage: '[data-public-empty-message]',
        regionModalShell: '[data-region-modal]',
        regionModalPanel: '[data-region-modal] .landing-region-modal',
        regionModalOpen: '[data-region-modal-open]',
        regionModalClose: '[data-region-modal-close]',
        regionModalApply: '[data-region-modal-apply]',
        regionModalAlert: '[data-region-modal-alert]',
        regionModalCurrent: '[data-region-modal-current]',
        provinceSelect: '[data-landing-region-province]',
        citySelect: '[data-landing-region-city]',
        districtSelect: '[data-landing-region-district]',
        villageSelect: '[data-landing-region-village]',
        locationNotice: '[data-location-gate-notice]',
        locationTitle: '[data-location-gate-title]',
        locationMessage: '[data-location-gate-message]',
        locationRetry: '[data-location-retry]',
        locationClose: '[data-location-gate-close]',
        locationGuideToggle: '[data-location-guide-toggle]',
        locationGuide: '[data-location-guide]',
        locationPermissionBox: '[data-location-permission-state]',
        locationPermissionLabel: '[data-location-permission-label]',
        locationGatedLink: '[data-location-gated]'
    };

    const API = {
        regionContext: '/api/public/landing-regions/context',
        regionChildren: '/api/public/landing-regions/children'
    };

    const DEFAULT_CONTEXT = {
        province: {
            code: '16',
            name: 'Sumatera Selatan',
            level: 'province'
        },
        city: {
            code: '16.73',
            name: 'Kota Lubuklinggau',
            level: 'city'
        },
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

    const DEFAULT_SELECTION = {
        province: DEFAULT_CONTEXT.province,
        city: DEFAULT_CONTEXT.city,
        district: null,
        village: null,
        districtAll: true,
        villageAll: true,
        hasPublicUmkmData: null,
        label: 'Kota Lubuklinggau',
        scope: 'city'
    };

    const DEFAULT_CHART_MODES = {
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

    const regionState = {
        loading: false,
        contextLoaded: false,
        context: DEFAULT_CONTEXT,
        districts: [],
        villages: [],
        applied: Object.assign({}, DEFAULT_SELECTION)
    };

    const locationGateState = {
        status: 'booting',
        permission: 'unknown',
        checkedAt: null,
        lastResult: null,
        dismissed: false
    };

    let mainChart = null;
    let activeMode = 'kinerja';
    let locationDismissedByUser = false;
    let locationChecking = false;

    function ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
            return;
        }

        callback();
    }

    function qs(selector, root) {
        return (root || document).querySelector(selector);
    }

    function qsa(selector, root) {
        return Array.from((root || document).querySelectorAll(selector));
    }

    function setText(selector, value, root) {
        const element = qs(selector, root);

        if (!element) {
            return;
        }

        element.textContent = value == null ? '' : String(value);
    }

    function formatNumber(value) {
        return Number(value || 0).toLocaleString('id-ID');
    }

    function cleanRegionLabel(value) {
        return String(value || '')
            .replace(/Sumber\s+data\s*:\s*/gi, '')
            .trim();
    }

    function cleanName(value) {
        return String(value || '')
            .replace(/^Kecamatan\s+/i, '')
            .replace(/^Kelurahan\s+/i, '')
            .replace(/^Desa\s+/i, '')
            .trim();
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

    function log(level, message, data) {
        if (UMKM.log && typeof UMKM.log === 'function') {
            UMKM.log(level, message, data);
        }
    }

    function emit(name, detail) {
        document.dispatchEvent(new CustomEvent(name, {
            detail: detail || {}
        }));
    }

    function initHeaderAndNavigation() {
        const header = qs(SELECTORS.header);
        const canvasMenu = qs(SELECTORS.menuCanvas);
        const toTop = qs(SELECTORS.toTop);

        function updateHeader() {
            if (header) {
                header.classList.toggle('is-scrolled', window.scrollY > 18);
            }

            if (toTop) {
                toTop.classList.toggle('is-visible', window.scrollY > 420);
            }
        }

        function closeBootstrapOffcanvas() {
            if (!canvasMenu || !window.bootstrap || !window.bootstrap.Offcanvas) {
                return;
            }

            const instance = window.bootstrap.Offcanvas.getInstance(canvasMenu);

            if (instance) {
                instance.hide();
            }
        }

        window.addEventListener('scroll', updateHeader, { passive: true });
        updateHeader();

        if (canvasMenu) {
            qsa(SELECTORS.menuClose, canvasMenu).forEach(function (item) {
                item.addEventListener('click', closeBootstrapOffcanvas);
            });

            qsa('[data-menu-link]', canvasMenu).forEach(function (item) {
                item.addEventListener('click', closeBootstrapOffcanvas);
            });
        }

        if (toTop) {
            toTop.addEventListener('click', function () {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        }
    }

    function initRevealAnimation() {
        const items = qsa(SELECTORS.reveal);

        if (!items.length) {
            return;
        }

        if (!('IntersectionObserver' in window)) {
            items.forEach(function (item) {
                item.classList.add('is-visible');
            });
            return;
        }

        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) {
                    return;
                }

                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            });
        }, {
            threshold: 0.14
        });

        items.forEach(function (item) {
            observer.observe(item);
        });
    }

    function initParallax() {
        const items = qsa(SELECTORS.parallax);
        let ticking = false;

        if (!items.length) {
            return;
        }

        function updateParallax() {
            const scrollY = window.scrollY || window.pageYOffset || 0;

            items.forEach(function (item) {
                const speed = Number(item.dataset.parallax || 0);

                item.style.transform = 'translate3d(0, ' + (scrollY * speed) + 'px, 0)';
            });

            ticking = false;
        }

        window.addEventListener('scroll', function () {
            if (ticking) {
                return;
            }

            window.requestAnimationFrame(updateParallax);
            ticking = true;
        }, { passive: true });
    }

    function initCounters() {
        const counters = qsa(SELECTORS.counter);

        if (!counters.length || !('IntersectionObserver' in window)) {
            return;
        }

        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
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

                    element.textContent = formatNumber(value);

                    if (progress < 1) {
                        window.requestAnimationFrame(animate);
                    }
                }

                window.requestAnimationFrame(animate);
                observer.unobserve(element);
            });
        }, {
            threshold: 0.62
        });

        counters.forEach(function (counter) {
            observer.observe(counter);
        });
    }

    function initTiltCard() {
        const tiltCard = qs(SELECTORS.tiltCard);

        if (!tiltCard) {
            return;
        }

        const boardWindow = tiltCard.querySelector('.board-window');

        if (!boardWindow) {
            return;
        }

        tiltCard.addEventListener('mousemove', function (event) {
            const rect = tiltCard.getBoundingClientRect();
            const x = event.clientX - rect.left;
            const y = event.clientY - rect.top;
            const rotateY = ((x / rect.width) - 0.5) * 7;
            const rotateX = ((y / rect.height) - 0.5) * -7;

            boardWindow.style.transform = 'rotateX(' + rotateX + 'deg) rotateY(' + rotateY + 'deg)';
        });

        tiltCard.addEventListener('mouseleave', function () {
            boardWindow.style.transform = 'rotateX(0deg) rotateY(0deg)';
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

    function initChart() {
        const canvas = qs(SELECTORS.chartCanvas);

        qsa(SELECTORS.chartTab).forEach(function (tab) {
            tab.addEventListener('click', function () {
                activeMode = tab.dataset.chartMode || 'kinerja';

                qsa(SELECTORS.chartTab).forEach(function (item) {
                    item.classList.remove('active');
                });

                tab.classList.add('active');

                const selection = regionState.applied || Object.assign({}, DEFAULT_SELECTION);
                const preview = buildPreview(selection);

                renderChart(selection, preview);
                applyMobileChartMode();
            });
        });

        if (!window.Chart || !canvas) {
            const data = DEFAULT_CHART_MODES.kinerja;

            setText(SELECTORS.mainChartTitle, data.title);
            setText(SELECTORS.mainChartSubtitle, data.subtitle);
            setText(SELECTORS.chartSummaryOne, data.summaryOne);
            setText(SELECTORS.chartSummaryTwo, data.summaryTwo);
            setText(SELECTORS.chartSummaryThree, data.summaryThree);
            return;
        }

        renderChart(DEFAULT_SELECTION, buildPreview(DEFAULT_SELECTION));
    }

    function makeChartPayload(selection, preview) {
        const mode = DEFAULT_CHART_MODES[activeMode] ? activeMode : 'kinerja';
        const seed = hash((selection.village?.code || selection.district?.code || selection.city?.code || '16.73') + mode);
        const label = cleanRegionLabel(selection.label || 'Kota Lubuklinggau');

        if (preview && preview.empty) {
            return {
                title: 'Data UMKM ' + label + ' belum tersedia',
                subtitle: 'Belum ada data agregat publik untuk wilayah yang dipilih',
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul'],
                unitLabel: 'Jumlah UMKM',
                percentLabel: 'Persentase (%)',
                unitData: [0, 0, 0, 0, 0, 0, 0],
                percentData: [0, 0, 0, 0, 0, 0, 0],
                summaryOne: 'Wilayah tanpa data agregat',
                summaryTwo: 'Grafik belum tersedia',
                summaryThree: 'Pilih wilayah lain'
            };
        }

        if (mode === 'wilayah') {
            const source = selection.scope === 'city' ? regionState.districts : regionState.villages;
            const labels = source.length
                ? source.slice(0, 8).map(function (item) {
                    return cleanName(item.name || 'Wilayah');
                })
                : DEFAULT_CHART_MODES.wilayah.labels;

            return {
                title: 'Sebaran UMKM ' + label,
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
                summaryThree: 'Sebaran ' + label
            };
        }

        if (mode === 'legalitas') {
            return {
                title: 'Legalitas dan Kelengkapan Data ' + label,
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
            title: 'Kinerja UMKM ' + label,
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
            summaryOne: label + ', bidang usaha, periode',
            summaryTwo: 'Jumlah UMKM dan persentase pertumbuhan',
            summaryThree: 'Monitoring kinerja wilayah'
        };
    }

    function renderChart(selection, preview) {
        const canvas = qs(SELECTORS.chartCanvas);
        const payload = makeChartPayload(selection, preview);

        setText(SELECTORS.mainChartTitle, payload.title);
        setText(SELECTORS.mainChartSubtitle, payload.subtitle);
        setText(SELECTORS.chartSummaryOne, payload.summaryOne);
        setText(SELECTORS.chartSummaryTwo, payload.summaryTwo);
        setText(SELECTORS.chartSummaryThree, payload.summaryThree);
        setText(SELECTORS.publicChartRegion, cleanRegionLabel(selection.label || 'Kota Lubuklinggau'));

        if (!window.Chart || !canvas) {
            return;
        }

        const ctx = canvas.getContext('2d');

        if (!mainChart && typeof Chart.getChart === 'function') {
            mainChart = Chart.getChart(canvas) || null;
        }

        if (!mainChart) {
            mainChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: payload.labels,
                    datasets: [
                        {
                            label: payload.unitLabel,
                            data: payload.unitData,
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
                            tension: 0.42,
                            borderWidth: 3,
                            pointRadius: 4,
                            pointHoverRadius: 7,
                            pointBackgroundColor: '#0f7665',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2
                        },
                        {
                            label: payload.percentLabel,
                            data: payload.percentData,
                            yAxisID: 'y1',
                            borderColor: '#f0a84a',
                            backgroundColor: 'rgba(240, 168, 74, .16)',
                            fill: false,
                            tension: 0.42,
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

            applyMobileChartMode();
            return;
        }

        mainChart.data.labels = payload.labels;
        mainChart.data.datasets[0].label = payload.unitLabel;
        mainChart.data.datasets[0].data = payload.unitData;
        mainChart.data.datasets[1].label = payload.percentLabel;
        mainChart.data.datasets[1].data = payload.percentData;

        if (mainChart.options?.scales?.y?.title) {
            mainChart.options.scales.y.title.text = payload.unitLabel;
        }

        if (mainChart.options?.scales?.y1?.title) {
            mainChart.options.scales.y1.title.text = payload.percentLabel;
        }

        mainChart.update();
        applyMobileChartMode();
    }

    function applyMobileChartMode() {
        const chart = mainChart;

        if (!chart || !chart.options || !window.matchMedia) {
            return;
        }

        const compact = window.matchMedia('(max-width: 575.98px)').matches;
        const veryCompact = window.matchMedia('(max-width: 420px)').matches;

        if (chart.options.plugins?.legend) {
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

        if (chart.options.scales?.x?.ticks) {
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

        ['y', 'y1'].forEach(function (axis) {
            if (!chart.options.scales?.[axis]) {
                return;
            }

            if (chart.options.scales[axis].title) {
                chart.options.scales[axis].title.display = !compact;
            }

            if (chart.options.scales[axis].ticks) {
                chart.options.scales[axis].ticks.maxTicksLimit = compact ? 5 : 8;
                chart.options.scales[axis].ticks.font = Object.assign(
                    {},
                    chart.options.scales[axis].ticks.font || {},
                    {
                        size: compact ? 10 : 12,
                        weight: '700'
                    }
                );
            }
        });

        chart.update('none');
    }

    function initChartResponsiveEvents() {
        let resizeTimer = null;

        window.addEventListener('resize', function () {
            window.clearTimeout(resizeTimer);
            resizeTimer = window.setTimeout(applyMobileChartMode, 160);
        }, { passive: true });
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

    function createOption(region) {
        const option = document.createElement('option');

        option.value = region.code || '';
        option.textContent = region.name || region.code || '';
        option.dataset.regionName = region.name || '';
        option.dataset.regionLevel = region.level || '';
        option.dataset.virtual = region.is_virtual ? '1' : '0';
        option.dataset.hasPublicUmkmData = region.has_public_umkm_data === false
            ? '0'
            : (region.has_public_umkm_data === true ? '1' : 'unknown');

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

    function selectedOption(select) {
        if (!select || !select.value) {
            return null;
        }

        const option = select.options[select.selectedIndex];

        return {
            code: option.value,
            name: option.dataset.regionName || option.textContent || option.value,
            level: option.dataset.regionLevel || '',
            isVirtual: option.dataset.virtual === '1',
            hasPublicUmkmData: option.dataset.hasPublicUmkmData === '0'
                ? false
                : (option.dataset.hasPublicUmkmData === '1' ? true : null)
        };
    }

    function setRegionLoading(isLoading, message) {
        regionState.loading = Boolean(isLoading);

        const modal = qs(SELECTORS.regionModalPanel);
        const applyButton = qs(SELECTORS.regionModalApply);

        if (modal) {
            modal.classList.toggle('is-loading', regionState.loading);

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
            applyButton.disabled = regionState.loading;
            applyButton.classList.toggle('is-disabled', regionState.loading);
        }
    }

    function setRegionAlert(message) {
        const alert = qs(SELECTORS.regionModalAlert);

        if (!alert) {
            return;
        }

        alert.hidden = !message;
        alert.textContent = message || '';
    }

    function setModalCurrent(text) {
        setText(SELECTORS.regionModalCurrent, cleanRegionLabel(text || 'Kota Lubuklinggau'));
    }

    function openRegionModal() {
        const shell = qs(SELECTORS.regionModalShell);

        if (!shell) {
            return;
        }

        shell.hidden = false;
        document.body.classList.add('is-region-modal-open');
        setRegionAlert('');
        ensureRegionContext();
    }

    function closeRegionModal() {
        if (regionState.loading) {
            return;
        }

        const shell = qs(SELECTORS.regionModalShell);

        if (!shell) {
            return;
        }

        shell.hidden = true;
        document.body.classList.remove('is-region-modal-open');
    }

    async function loadChildren(parentCode, level) {
        const url = API.regionChildren + '?parent_code=' + encodeURIComponent(parentCode) + '&level=' + encodeURIComponent(level);
        const result = await UMKM.ajax.get(url);
        const payload = unwrap(result);

        if (!resultOk(result) || !payload || !payload.data) {
            throw new Error(payload && payload.message ? payload.message : 'Data wilayah tidak dapat dimuat.');
        }

        return payload.data;
    }

    async function ensureRegionContext() {
        if (!ajaxReady()) {
            setRegionAlert('Modul AJAX sistem belum siap. Muat ulang halaman lalu coba kembali.');
            return;
        }

        if (regionState.contextLoaded) {
            return;
        }

        try {
            setRegionLoading(true, 'Memuat kecamatan Kota Lubuklinggau...');
            setRegionAlert('');

            const result = await UMKM.ajax.get(API.regionContext);
            const payload = unwrap(result);

            if (!resultOk(result) || !payload || !payload.data) {
                throw new Error(payload && payload.message ? payload.message : 'Konteks wilayah tidak dapat dimuat.');
            }

            regionState.context = {
                province: payload.data.province || DEFAULT_CONTEXT.province,
                city: payload.data.city || DEFAULT_CONTEXT.city,
                options: Object.assign({}, DEFAULT_CONTEXT.options, payload.data.options || {})
            };

            fillLockedSelect(qs(SELECTORS.provinceSelect), regionState.context.province);
            fillLockedSelect(qs(SELECTORS.citySelect), regionState.context.city);

            const districtData = await loadChildren(regionState.context.city.code, 'district');

            regionState.districts = districtData.regions || [];
            regionState.villages = [];

            fillDistricts(
                qs(SELECTORS.districtSelect),
                districtData.all_option || regionState.context.options.district_all,
                regionState.districts
            );

            fillVillages(
                qs(SELECTORS.villageSelect),
                regionState.context.options.village_all,
                [],
                true
            );

            regionState.contextLoaded = true;
            setModalCurrent(regionState.applied.label || 'Kota Lubuklinggau');
        } catch (error) {
            setRegionAlert(error.message || 'Wilayah tidak dapat dimuat.');
        } finally {
            setRegionLoading(false);
        }
    }

    async function onDistrictChanged() {
        const districtSelect = qs(SELECTORS.districtSelect);
        const villageSelect = qs(SELECTORS.villageSelect);
        const district = selectedOption(districtSelect);

        regionState.villages = [];

        if (!district || district.isVirtual) {
            fillVillages(villageSelect, regionState.context.options?.village_all, [], true);
            setModalCurrent('Kota Lubuklinggau');
            setRegionAlert('');
            return;
        }

        try {
            setRegionLoading(true, 'Memuat desa/kelurahan...');
            setRegionAlert('');

            const villageData = await loadChildren(district.code, 'village');

            regionState.villages = villageData.regions || [];

            fillVillages(
                villageSelect,
                villageData.all_option || regionState.context.options?.village_all,
                regionState.villages,
                false
            );

            setModalCurrent(district.name);
        } catch (error) {
            fillVillages(villageSelect, regionState.context.options?.village_all, [], true);
            setRegionAlert(error.message || 'Desa/kelurahan tidak dapat dimuat.');
        } finally {
            setRegionLoading(false);
        }
    }

    function getAppliedSelection() {
        const district = selectedOption(qs(SELECTORS.districtSelect));
        const village = selectedOption(qs(SELECTORS.villageSelect));

        const districtAll = !district || district.isVirtual || district.code === '__ALL_DISTRICTS__';
        const villageAll = !village || village.isVirtual || village.code === '__ALL_VILLAGES__';

        let label = regionState.context.city.name || 'Kota Lubuklinggau';
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
            province: regionState.context.province,
            city: regionState.context.city,
            district: districtAll ? null : district,
            village: villageAll ? null : village,
            districtAll: districtAll,
            villageAll: villageAll,
            hasPublicUmkmData: !districtAll && !villageAll
                ? village.hasPublicUmkmData
                : (!districtAll ? district.hasPublicUmkmData : null),
            label: cleanRegionLabel(label),
            scope: scope
        };
    }

    function buildPreview(selection) {
        const key = selection.village?.code || selection.district?.code || selection.city?.code || '16.73';
        const seed = hash(key + selection.scope);

        if (selection.hasPublicUmkmData === false) {
            return {
                empty: true,
                total: 0,
                active: 0,
                validation: 0,
                watched: 'Belum tersedia',
                dominant: 'Belum tersedia',
                fields: [],
                message: 'Belum ada data agregat UMKM untuk wilayah ini.'
            };
        }

        if (selection.scope === 'city') {
            return {
                total: 1248,
                active: 1086,
                validation: 36,
                watched: (regionState.districts.length || 8) + ' Kecamatan',
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
                watched: (regionState.villages.length || 'Semua') + ' Kelurahan',
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

    function updateMetric(selector, value) {
        const element = qs(selector);

        if (!element) {
            return;
        }

        element.dataset.count = String(value);
        element.textContent = formatNumber(value);
    }

    function renderIndicatorDetails(preview) {
        const container = qs(SELECTORS.publicFieldList);

        if (!container || !preview || !Array.isArray(preview.fields)) {
            return;
        }

        container.innerHTML = '';

        if (preview.empty || !preview.fields.length) {
            const empty = document.createElement('div');
            const title = document.createElement('strong');
            const message = document.createElement('small');

            empty.className = 'preview-empty-inline';
            title.textContent = 'Indikator belum tersedia';
            message.textContent = 'Data bidang usaha akan tampil setelah terdapat data UMKM pada wilayah ini.';

            empty.appendChild(title);
            empty.appendChild(message);
            container.appendChild(empty);
            return;
        }

        preview.fields.slice(0, 3).forEach(function (field, index) {
            const percent = Math.max(1, Math.min(100, Math.round(Number(field.percent || 0))));
            const count = Math.max(index === 0 ? 1 : 0, Math.round(Number(preview.total || 0) * (percent / 100)));

            const row = document.createElement('div');
            const label = document.createElement('span');
            const indicatorName = document.createElement('span');
            const indicatorMeta = document.createElement('span');
            const bar = document.createElement('b');

            indicatorName.className = 'indicator-name';
            indicatorName.textContent = field.name || 'Indikator';

            indicatorMeta.className = 'indicator-meta';
            indicatorMeta.textContent = formatNumber(count) + ' UMKM • ' + percent + '%';

            bar.style.width = Math.max(24, Math.min(95, percent)) + '%';

            label.appendChild(indicatorName);
            label.appendChild(indicatorMeta);
            row.appendChild(label);
            row.appendChild(bar);
            container.appendChild(row);
        });
    }

    function getRegionOptions(selector) {
        const select = qs(selector);

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
                {
                    name: cleanName(selection.village?.name || selection.label || 'Wilayah terpilih')
                }
            ];
        }

        if (selection && selection.scope === 'district') {
            const villages = getRegionOptions(SELECTORS.villageSelect);

            if (villages.length) {
                return villages.slice(0, 3);
            }

            return [
                {
                    name: cleanName(selection.district?.name || selection.label || 'Kecamatan terpilih')
                }
            ];
        }

        const districts = getRegionOptions(SELECTORS.districtSelect);

        if (districts.length) {
            return districts.slice(0, 3);
        }

        return [
            { name: 'Lubuk Linggau Timur II' },
            { name: 'Lubuk Linggau Utara II' },
            { name: 'Lubuk Linggau Barat II' }
        ];
    }

    function renderAreaStats(selection, preview) {
        const container = qs(SELECTORS.publicAreaList);

        if (!container || !preview || !Array.isArray(preview.fields)) {
            return;
        }

        container.innerHTML = '';

        if (preview.empty || !preview.fields.length) {
            const empty = document.createElement('div');
            const title = document.createElement('strong');
            const message = document.createElement('small');

            empty.className = 'preview-empty-inline';
            title.textContent = 'Data wilayah belum tersedia';
            message.textContent = 'Belum ada ringkasan UMKM publik untuk wilayah yang dipilih.';

            empty.appendChild(title);
            empty.appendChild(message);
            container.appendChild(empty);
            return;
        }

        const areaNames = pickAreaNames(selection);
        const distributions = areaNames.length === 1 ? [1] : [0.42, 0.34, 0.24];

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

    function togglePreviewEmptyState(preview, label) {
        const emptyState = qs(SELECTORS.publicEmptyState);
        const emptyTitle = qs(SELECTORS.publicEmptyTitle);
        const emptyMessage = qs(SELECTORS.publicEmptyMessage);
        const isEmpty = Boolean(preview && preview.empty);

        if (emptyState) {
            emptyState.hidden = !isEmpty;
        }

        if (emptyTitle) {
            emptyTitle.textContent = 'Data UMKM ' + cleanRegionLabel(label || 'wilayah ini') + ' belum tersedia';
        }

        if (emptyMessage) {
            emptyMessage.textContent = preview && preview.message
                ? preview.message + ' Pilih wilayah lain atau kembali ke Kota Lubuklinggau untuk melihat preview agregat.'
                : 'Belum ada data agregat UMKM untuk wilayah yang dipilih. Pilih wilayah lain atau kembali ke Kota Lubuklinggau untuk melihat preview agregat.';
        }
    }
    function applyRegionSelection(selection) {
        const safeSelection = Object.assign({}, DEFAULT_SELECTION, selection || {});
        const preview = buildPreview(safeSelection);
        const label = cleanRegionLabel(safeSelection.label || 'Kota Lubuklinggau');

        safeSelection.label = label;
        regionState.applied = safeSelection;

        setText(SELECTORS.publicRegionSource, label);
        setText(SELECTORS.publicChartRegion, label);
        setText(SELECTORS.regionModalCurrent, label);
        setText(SELECTORS.publicWatchedLabel, preview.watched);
        setText(SELECTORS.publicDominantLabel, preview.dominant);
        togglePreviewEmptyState(preview, label);

        updateMetric('[data-public-metric="total"]', preview.total);
        updateMetric('[data-public-metric="active"]', preview.active);
        updateMetric('[data-public-metric="validation"]', preview.validation);

        renderIndicatorDetails(preview);
        renderAreaStats(safeSelection, preview);
        renderChart(safeSelection, preview);

        emit('umkm:landing-region:changed', {
            selection: safeSelection,
            preview: preview
        });

        log('info', 'landing region preview applied', {
            label: label,
            scope: safeSelection.scope
        });
    }

    function initRegionModal() {
        qsa(SELECTORS.regionModalOpen).forEach(function (button) {
            button.addEventListener('click', openRegionModal);
        });

        qsa(SELECTORS.regionModalClose).forEach(function (button) {
            button.addEventListener('click', closeRegionModal);
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !regionState.loading) {
                closeRegionModal();
            }
        });

        qs(SELECTORS.districtSelect)?.addEventListener('change', onDistrictChanged);

        qs(SELECTORS.villageSelect)?.addEventListener('change', function () {
            const selection = getAppliedSelection();
            setModalCurrent(selection.label);
        });

        qs(SELECTORS.regionModalApply)?.addEventListener('click', function () {
            if (regionState.loading) {
                return;
            }

            const selection = getAppliedSelection();

            applyRegionSelection(selection);
            closeRegionModal();
        });
    }

    function setPermissionLabel(permissionState) {
        locationGateState.permission = permissionState || 'unknown';

        const permissionBox = qs(SELECTORS.locationPermissionBox);
        const permissionLabel = qs(SELECTORS.locationPermissionLabel);

        if (permissionBox) {
            permissionBox.hidden = false;
        }

        if (permissionLabel) {
            permissionLabel.textContent = locationGateState.permission;
        }
    }

    function setGuideVisible(visible) {
        const guide = qs(SELECTORS.locationGuide);
        const guideToggle = qs(SELECTORS.locationGuideToggle);

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

    function updateLocationState(status, result, permissionState) {
        locationGateState.status = status;
        locationGateState.permission = permissionState || locationGateState.permission || 'unknown';
        locationGateState.checkedAt = new Date().toISOString();
        locationGateState.lastResult = result || null;

        emit('umkm:location-gate:updated', Object.assign({}, locationGateState));

        log(status === 'granted' ? 'info' : 'warn', 'location gate updated', Object.assign({}, locationGateState));
    }

    function setLocationNotice(status, titleText, messageText, visible, permissionState, forceVisible) {
        const notice = qs(SELECTORS.locationNotice);
        const noticeCard = notice ? notice.querySelector('.location-gate-card') : null;
        const title = qs(SELECTORS.locationTitle);
        const message = qs(SELECTORS.locationMessage);

        document.documentElement.setAttribute('data-location-gate', status);

        if (noticeCard) {
            noticeCard.classList.remove(
                'is-checking',
                'is-granted',
                'is-blocked',
                'is-unsupported',
                'is-denied',
                'is-prompt'
            );
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

        const shouldShow = Boolean(visible && (forceVisible || !locationDismissedByUser));

        if (notice) {
            notice.hidden = !shouldShow;
        }

        document.body.classList.toggle('is-location-gate-open', shouldShow);
    }

    function showLocationGateAgain() {
        locationDismissedByUser = false;
        locationGateState.dismissed = false;
    }

    function closeLocationGate() {
        const notice = qs(SELECTORS.locationNotice);

        locationDismissedByUser = true;
        locationGateState.dismissed = true;

        if (notice) {
            notice.hidden = true;
        }

        document.body.classList.remove('is-location-gate-open');

        emit('umkm:location-gate:dismissed', Object.assign({}, locationGateState));
        log('info', 'location gate dismissed by user', Object.assign({}, locationGateState));
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

    function showLocationDenied(permission) {
        setLocationNotice(
            'denied',
            'Izin lokasi diblokir oleh browser',
            'Website tidak dapat menampilkan ulang permintaan lokasi karena izin sudah diblokir. Ubah izin lokasi dari pengaturan situs pada browser, lalu refresh halaman.',
            true,
            permission && permission.state ? permission.state : 'denied',
            true
        );

        setGuideVisible(false);
        updateLocationState('denied', null, 'denied');
    }

    function showLocationUnsupported(permission) {
        setLocationNotice(
            'unsupported',
            'Validasi lokasi belum dapat digunakan',
            'Browser atau perangkat belum mendukung pemeriksaan izin lokasi yang dibutuhkan untuk membuka akses masuk sistem.',
            true,
            permission && permission.state ? permission.state : 'unsupported',
            true
        );

        setGuideVisible(false);
        updateLocationState('unsupported', null, permission && permission.state ? permission.state : 'unsupported');
    }

    function blockLocationByResult(result) {
        const permission = result && result.permission ? result.permission : null;
        const locationState = result && result.state ? result.state : {};
        const lastError = locationState.lastError || {};
        const permissionState = permission && permission.state ? permission.state : locationState.permission || 'unknown';
        const type = lastError.type || locationState.status || 'blocked';

        if (permissionState === 'denied' || type === 'permission_denied_browser') {
            showLocationDenied(permission || { state: 'denied' });
            return;
        }

        if (permissionState === 'unsupported' || type === 'unsupported') {
            showLocationUnsupported(permission || { state: 'unsupported' });
            return;
        }

        if (type === 'permission_denied') {
            setLocationNotice(
                'blocked',
                'Lokasi belum aktif',
                'Aktifkan izin lokasi pada browser untuk membuka akses masuk ke sistem.',
                true,
                permissionState,
                true
            );
            setGuideVisible(false);
            updateLocationState('blocked', result, permissionState);
            return;
        }

        if (type === 'timeout') {
            setLocationNotice(
                'blocked',
                'Pemeriksaan lokasi terlalu lama',
                'Pastikan layanan lokasi aktif, lalu klik cek ulang lokasi.',
                true,
                permissionState,
                true
            );
            setGuideVisible(false);
            updateLocationState('blocked', result, permissionState);
            return;
        }

        setLocationNotice(
            'blocked',
            'Lokasi belum dapat diverifikasi',
            'Akses masuk sistem dibuka setelah lokasi berhasil diperiksa.',
            true,
            permissionState,
            true
        );
        setGuideVisible(false);
        updateLocationState('blocked', result, permissionState);
    }

    async function checkLocationGate(options) {
        if (locationChecking) {
            return false;
        }

        if (!UMKM.location || typeof UMKM.location.check !== 'function') {
            showLocationUnsupported({ state: 'unsupported' });
            return false;
        }

        locationChecking = true;

        try {
            const permission = await getPermissionStatus();

            setPermissionLabel(permission.state || 'unknown');

            if (permission.state === 'denied') {
                showLocationDenied(permission);
                return false;
            }

            if (permission.state === 'unsupported') {
                showLocationUnsupported(permission);
                return false;
            }

            if (permission.state === 'prompt') {
                setLocationNotice(
                    'prompt',
                    'Izin lokasi diperlukan',
                    'Pilih Izinkan pada permintaan lokasi browser agar akses masuk ke sistem dapat ditampilkan.',
                    true,
                    'prompt',
                    options && options.forceVisible
                );
                setGuideVisible(false);
                updateLocationState('prompt', null, 'prompt');
            } else {
                setLocationNotice(
                    'checking',
                    'Memeriksa status lokasi',
                    'Mohon tunggu, sistem sedang memastikan lokasi aktif sebelum membuka akses masuk.',
                    true,
                    permission.state || 'unknown',
                    options && options.forceVisible
                );
                setGuideVisible(false);
                updateLocationState('checking', null, permission.state || 'unknown');
            }

            const result = await UMKM.location.check({
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            });

            if (result && result.ok) {
                setLocationNotice(
                    'granted',
                    'Lokasi aktif',
                    'Akses masuk sistem sudah dibuka.',
                    false,
                    'granted',
                    false
                );
                setGuideVisible(false);
                updateLocationState('granted', result, 'granted');
                return true;
            }

            blockLocationByResult(result);
            return false;
        } catch (error) {
            setLocationNotice(
                'blocked',
                'Validasi lokasi terganggu',
                'Pemeriksaan lokasi belum berhasil. Muat ulang halaman atau klik cek ulang lokasi.',
                true,
                'unknown',
                true
            );
            updateLocationState('blocked', { error: error.message || 'location check failed' }, 'unknown');
            return false;
        } finally {
            locationChecking = false;
        }
    }

    function initLocationGate() {
        qsa(SELECTORS.locationGatedLink).forEach(function (link) {
            link.addEventListener('click', async function (event) {
                const href = link.getAttribute('href');

                if (!href || href === '#') {
                    return;
                }

                event.preventDefault();
                showLocationGateAgain();

                const allowed = await checkLocationGate({
                    forceVisible: true
                });

                if (allowed) {
                    window.location.assign(href);
                }
            });
        });

        qs(SELECTORS.locationRetry)?.addEventListener('click', function () {
            showLocationGateAgain();
            checkLocationGate({
                forceVisible: true
            });
        });

        qs(SELECTORS.locationClose)?.addEventListener('click', closeLocationGate);

        qs(SELECTORS.locationGuideToggle)?.addEventListener('click', function () {
            const guide = qs(SELECTORS.locationGuide);

            if (!guide) {
                return;
            }

            setGuideVisible(Boolean(guide.hidden));
        });

        if (UMKM.location && typeof UMKM.location.watchPermission === 'function') {
            UMKM.location.watchPermission(function (permission) {
                setPermissionLabel(permission.state || 'unknown');

                if (permission.state === 'granted') {
                    showLocationGateAgain();
                    checkLocationGate({
                        forceVisible: false
                    });
                    return;
                }

                if (permission.state === 'denied') {
                    showLocationDenied(permission);
                    return;
                }

                if (permission.state === 'prompt') {
                    setLocationNotice(
                        'prompt',
                        'Izin lokasi diperlukan',
                        'Klik cek ulang lokasi, lalu pilih Izinkan pada permintaan lokasi browser.',
                        true,
                        'prompt',
                        true
                    );
                    setGuideVisible(false);
                    updateLocationState('prompt', null, 'prompt');
                }
            });
        }

        window.setTimeout(function () {
            checkLocationGate({
                forceVisible: false
            });
        }, 450);

        if (UMKM.register) {
            UMKM.register('locationGate', {
                check: checkLocationGate,
                state: function () {
                    return Object.assign({}, locationGateState);
                },
                close: closeLocationGate,
                open: function () {
                    showLocationGateAgain();
                    return checkLocationGate({
                        forceVisible: true
                    });
                },
                permission: getPermissionStatus
            });
        }
    }

    function boot() {
        initHeaderAndNavigation();
        initRevealAnimation();
        initParallax();
        initCounters();
        initTiltCard();
        initChart();
        initChartResponsiveEvents();
        initRegionModal();
        initLocationGate();

        window.setTimeout(function () {
            applyRegionSelection(Object.assign({}, DEFAULT_SELECTION));
        }, 180);
    }

    ready(boot);
})();


