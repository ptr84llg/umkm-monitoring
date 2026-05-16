(function () {
    'use strict';

    const header = document.querySelector('[data-landing-header]');
    const revealItems = document.querySelectorAll('.reveal');
    const parallaxItems = document.querySelectorAll('[data-parallax]');
    const counters = document.querySelectorAll('.count-up');
    const tiltCard = document.querySelector('[data-tilt-card]');
    const tabs = document.querySelectorAll('[data-chart-mode]');
    const mainTitle = document.getElementById('mainChartTitle');
    const mainSubtitle = document.getElementById('mainChartSubtitle');
    const mainCanvas = document.getElementById('landingMainChart');
    const heroCanvas = document.getElementById('heroMiniChart');
    const fallback = document.getElementById('chartFallback');

    const chartData = {
        kinerja: {
            title: 'Tren Perkembangan UMKM',
            subtitle: 'Ringkasan data dalam periode pemantauan',
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul'],
            values: [38, 52, 48, 64, 79, 86, 72]
        },
        wilayah: {
            title: 'Sebaran UMKM per Wilayah',
            subtitle: 'Perbandingan jumlah UMKM pada area pantauan',
            labels: ['Utara', 'Timur', 'Selatan', 'Barat', 'Tengah'],
            values: [42, 68, 55, 47, 76]
        },
        legalitas: {
            title: 'Ringkasan Legalitas Usaha',
            subtitle: 'Gambaran kelengkapan legalitas UMKM',
            labels: ['Tercatat', 'Lengkap', 'Proses', 'Perlu Update'],
            values: [84, 72, 38, 26]
        }
    };

    let mainChart = null;
    let heroChart = null;

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

                entry.target.classList.add('is-visible', 'animate__animated', 'animate__fadeInUp');
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

    function chartOptions() {
        return {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(16, 33, 61, .92)',
                    padding: 12,
                    titleFont: {
                        weight: '700'
                    },
                    bodyFont: {
                        weight: '600'
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
                            weight: '700'
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    suggestedMax: 100,
                    grid: {
                        color: 'rgba(16, 33, 61, .07)'
                    },
                    ticks: {
                        color: '#64748b',
                        font: {
                            weight: '700'
                        }
                    }
                }
            }
        };
    }

    function buildGradient(ctx) {
        const gradient = ctx.createLinearGradient(0, 0, 0, 260);
        gradient.addColorStop(0, 'rgba(15, 118, 101, .92)');
        gradient.addColorStop(1, 'rgba(128, 202, 189, .35)');
        return gradient;
    }

    function renderMainChart(mode) {
        const data = chartData[mode] || chartData.kinerja;

        if (mainTitle) {
            mainTitle.textContent = data.title;
        }

        if (mainSubtitle) {
            mainSubtitle.textContent = data.subtitle;
        }

        if (!window.Chart || !mainCanvas) {
            if (fallback) {
                fallback.hidden = false;
            }
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
                datasets: [{
                    data: data.values,
                    borderRadius: 14,
                    backgroundColor: buildGradient(ctx),
                    borderSkipped: false
                }]
            },
            options: chartOptions()
        });
    }

    function renderHeroChart() {
        if (!window.Chart || !heroCanvas) {
            return;
        }

        const ctx = heroCanvas.getContext('2d');

        heroChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun'],
                datasets: [{
                    data: [28, 44, 39, 62, 76, 69],
                    borderColor: '#0f7665',
                    backgroundColor: 'rgba(15, 118, 101, .12)',
                    fill: true,
                    tension: .42,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#0f7665'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: true }
                },
                scales: {
                    x: { display: false },
                    y: { display: false, beginAtZero: true }
                }
            }
        });
    }

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            tabs.forEach((item) => item.classList.remove('active'));
            tab.classList.add('active');
            renderMainChart(tab.dataset.chartMode || 'kinerja');
        });
    });

    renderMainChart('kinerja');
    renderHeroChart();
})();
