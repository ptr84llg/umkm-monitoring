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
        const heroCanvas = document.getElementById('heroMiniChart');
        const fallback = document.getElementById('chartFallback');
        const heroFallback = document.getElementById('heroChartFallback');

        let mainChart = null;
        let heroChart = null;

        const chartModes = {
            kinerja: {
                title: 'Tren Perkembangan UMKM',
                subtitle: 'Ringkasan data dalam periode pemantauan',
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul'],
                primary: [38, 52, 48, 64, 79, 86, 72],
                secondary: [28, 35, 33, 48, 61, 67, 58],
                summaryOne: 'Wilayah, bidang usaha, periode',
                summaryTwo: 'Grafik tren dan indikator utama',
                summaryThree: 'Perkembangan aktivitas UMKM'
            },
            wilayah: {
                title: 'Sebaran UMKM per Wilayah',
                subtitle: 'Perbandingan konsentrasi UMKM pada area pantauan',
                labels: ['Utara', 'Timur', 'Selatan', 'Barat', 'Tengah'],
                primary: [42, 68, 55, 47, 76],
                secondary: [22, 36, 28, 24, 42],
                summaryOne: 'Kecamatan, kelurahan, kategori usaha',
                summaryTwo: 'Grafik perbandingan wilayah',
                summaryThree: 'Konsentrasi dan persebaran UMKM'
            },
            legalitas: {
                title: 'Ringkasan Legalitas Usaha',
                subtitle: 'Gambaran kelengkapan legalitas UMKM',
                labels: ['Tercatat', 'Lengkap', 'Proses', 'Perlu Update'],
                primary: [84, 72, 38, 26],
                secondary: [56, 48, 24, 18],
                summaryOne: 'Status legalitas dan pembaruan data',
                summaryTwo: 'Grafik komposisi status',
                summaryThree: 'Kelengkapan informasi legalitas'
            }
        };

        function updateHeader() {
            if (!header) {
                return;
            }

            header.classList.toggle('is-scrolled', window.scrollY > 18);
        }

        window.addEventListener('scroll', updateHeader, { passive: true });
        updateHeader();

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

        window.addEventListener('scroll', () => {
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

            tiltCard.addEventListener('mousemove', (event) => {
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

            tiltCard.addEventListener('mouseleave', () => {
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

        function baseChartOptions() {
            return {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
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
                            font: {
                                family: 'Manrope',
                                weight: '700'
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(16, 33, 61, .94)',
                        padding: 12,
                        cornerRadius: 12,
                        titleFont: {
                            family: 'Manrope',
                            weight: '800'
                        },
                        bodyFont: {
                            family: 'Manrope',
                            weight: '700'
                        },
                        displayColors: false
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
                                family: 'Manrope',
                                weight: '800'
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        suggestedMax: 100,
                        grid: {
                            color: 'rgba(16, 33, 61, .07)',
                            drawBorder: false
                        },
                        ticks: {
                            color: '#64748b',
                            font: {
                                family: 'Manrope',
                                weight: '800'
                            }
                        }
                    }
                }
            };
        }

        function showFallback() {
            if (fallback) {
                fallback.hidden = false;
            }
        }

        function showHeroFallback() {
            if (heroFallback) {
                heroFallback.hidden = false;
            }
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
                showFallback();
                return;
            }

            if (fallback) {
                fallback.hidden = true;
            }

            const ctx = mainCanvas.getContext('2d');

            if (mainChart) {
                mainChart.destroy();
            }

            mainChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [
                        {
                            type: 'bar',
                            label: 'Jumlah UMKM',
                            data: data.primary,
                            borderRadius: 16,
                            borderSkipped: false,
                            backgroundColor: function (context) {
                                const chart = context.chart;
                                const area = chart.chartArea;

                                if (!area) {
                                    return 'rgba(15, 118, 101, .82)';
                                }

                                return makeGradient(
                                    chart.ctx,
                                    area,
                                    'rgba(15, 118, 101, .92)',
                                    'rgba(128, 202, 189, .35)'
                                );
                            }
                        },
                        {
                            type: 'line',
                            label: 'Pembanding',
                            data: data.secondary,
                            borderColor: '#f0a84a',
                            backgroundColor: 'rgba(240, 168, 74, .18)',
                            borderWidth: 3,
                            tension: .42,
                            fill: true,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            pointBackgroundColor: '#f0a84a',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2
                        }
                    ]
                },
                options: baseChartOptions()
            });
        }

        function renderHeroChart() {
            if (!window.Chart || !heroCanvas) {
                showHeroFallback();
                return;
            }

            const ctx = heroCanvas.getContext('2d');

            if (heroChart) {
                heroChart.destroy();
            }

            heroChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun'],
                    datasets: [
                        {
                            label: 'Aktivitas',
                            data: [28, 44, 39, 62, 76, 69],
                            borderColor: '#0f7665',
                            backgroundColor: function (context) {
                                const chart = context.chart;
                                const area = chart.chartArea;

                                if (!area) {
                                    return 'rgba(15, 118, 101, .12)';
                                }

                                return makeGradient(
                                    chart.ctx,
                                    area,
                                    'rgba(15, 118, 101, .28)',
                                    'rgba(15, 118, 101, .02)'
                                );
                            },
                            fill: true,
                            tension: .44,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            pointBackgroundColor: '#0f7665',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2
                        },
                        {
                            label: 'Sebaran',
                            data: [18, 32, 36, 44, 58, 63],
                            borderColor: '#f0a84a',
                            borderDash: [6, 6],
                            borderWidth: 2,
                            tension: .44,
                            pointRadius: 0
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(16, 33, 61, .94)',
                            padding: 10,
                            cornerRadius: 12,
                            displayColors: false
                        }
                    },
                    scales: {
                        x: {
                            display: false
                        },
                        y: {
                            display: false,
                            beginAtZero: true,
                            suggestedMax: 100
                        }
                    }
                }
            });
        }

        tabs.forEach((tab) => {
            tab.addEventListener('click', () => {
                const mode = tab.dataset.chartMode || 'kinerja';

                tabs.forEach((item) => item.classList.remove('active'));
                tab.classList.add('active');
                renderMainChart(mode);
            });
        });

        renderMainChart('kinerja');
        renderHeroChart();
    });
})();
