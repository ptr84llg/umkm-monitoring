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

