(function () {
    'use strict';

    const Landing = window.UMKMLanding;
    const S = Landing.SELECTORS;

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
            animation: { duration: 900, easing: 'easeOutQuart' },
            interaction: { intersect: false, mode: 'index' },
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
                        font: { family: 'Segoe UI', weight: '700' }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(16, 33, 61, .94)',
                    padding: 12,
                    cornerRadius: 12,
                    titleFont: { family: 'Segoe UI', weight: '800' },
                    bodyFont: { family: 'Segoe UI', weight: '700' }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: '#64748b', font: { family: 'Segoe UI', weight: '800' } }
                },
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    beginAtZero: true,
                    grid: { color: 'rgba(16, 33, 61, .07)', drawBorder: false },
                    ticks: { color: '#0f7665', font: { family: 'Segoe UI', weight: '800' } },
                    title: { display: true, text: 'Jumlah UMKM', color: '#0f7665', font: { family: 'Segoe UI', weight: '800' } }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    beginAtZero: true,
                    suggestedMax: 100,
                    grid: { drawOnChartArea: false },
                    ticks: {
                        color: '#f0a84a',
                        callback: function (value) { return value + '%'; },
                        font: { family: 'Segoe UI', weight: '800' }
                    },
                    title: { display: true, text: 'Persentase', color: '#f0a84a', font: { family: 'Segoe UI', weight: '800' } }
                }
            }
        };
    }

    function normalizeChartPayload(chart) {
        return {
            title: chart?.title || 'Preview data UMKM',
            subtitle: chart?.subtitle || 'Ringkasan data dalam periode pemantauan',
            labels: Array.isArray(chart?.labels) ? chart.labels : [],
            unitLabel: chart?.unit_label || 'Jumlah UMKM',
            percentLabel: chart?.percent_label || 'Persentase (%)',
            unitData: Array.isArray(chart?.unit_data) ? chart.unit_data : [],
            percentData: Array.isArray(chart?.percent_data) ? chart.percent_data : [],
            summaryOne: chart?.summary_one || 'Wilayah, bidang usaha, periode',
            summaryTwo: chart?.summary_two || 'Grafik, indikator, dan ringkasan',
            summaryThree: chart?.summary_three || 'Perkembangan UMKM'
        };
    }

    Landing.renderChart = function (selection, previewResponse) {
        const canvas = Landing.qs(S.chartCanvas);
        const payload = normalizeChartPayload(previewResponse?.chart);
        const label = Landing.cleanRegionLabel(selection?.label || previewResponse?.selection?.label || 'Kota Lubuklinggau');

        Landing.setText(S.mainChartTitle, payload.title);
        Landing.setText(S.mainChartSubtitle, payload.subtitle);
        Landing.setText(S.chartSummaryOne, payload.summaryOne);
        Landing.setText(S.chartSummaryTwo, payload.summaryTwo);
        Landing.setText(S.chartSummaryThree, payload.summaryThree);
        Landing.setText(S.publicChartRegion, label);

        if (!window.Chart || !canvas) {
            return;
        }

        if (Landing.mainChart && Landing.mainChart.canvas !== canvas && typeof Landing.mainChart.destroy === 'function') {
            Landing.mainChart.destroy();
            Landing.mainChart = null;
        }

        const ctx = canvas.getContext('2d');

        if (!Landing.mainChart && typeof Chart.getChart === 'function') {
            Landing.mainChart = Chart.getChart(canvas) || null;
        }

        if (!Landing.mainChart) {
            Landing.mainChart = new Chart(ctx, {
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

                                return makeGradient(chart.ctx, area, 'rgba(15, 118, 101, .24)', 'rgba(15, 118, 101, .02)');
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

            Landing.applyMobileChartMode();
            return;
        }

        Landing.mainChart.data.labels = payload.labels;
        Landing.mainChart.data.datasets[0].label = payload.unitLabel;
        Landing.mainChart.data.datasets[0].data = payload.unitData;
        Landing.mainChart.data.datasets[1].label = payload.percentLabel;
        Landing.mainChart.data.datasets[1].data = payload.percentData;

        if (Landing.mainChart.options?.scales?.y?.title) {
            Landing.mainChart.options.scales.y.title.text = payload.unitLabel;
        }

        if (Landing.mainChart.options?.scales?.y1?.title) {
            Landing.mainChart.options.scales.y1.title.text = payload.percentLabel;
        }

        Landing.mainChart.update();
        Landing.applyMobileChartMode();
    };

    Landing.applyMobileChartMode = function () {
        const chart = Landing.mainChart;

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
                chart.options.plugins.legend.labels.font = Object.assign({}, chart.options.plugins.legend.labels.font || {}, {
                    size: compact ? 10 : 12,
                    weight: '700'
                });
            }
        }

        if (chart.options.scales?.x?.ticks) {
            chart.options.scales.x.ticks.autoSkip = true;
            chart.options.scales.x.ticks.maxTicksLimit = compact ? 4 : 8;
            chart.options.scales.x.ticks.maxRotation = compact ? 0 : 50;
            chart.options.scales.x.ticks.minRotation = 0;
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
            }
        });

        chart.update('none');
    };

    Landing.initChart = function () {
        Landing.qsa(S.chartTab).forEach(function (tab) {
            if (tab.dataset.chartTabBound === 'true') {
                return;
            }

            tab.dataset.chartTabBound = 'true';

            tab.addEventListener('click', function () {
                Landing.activeMode = tab.dataset.chartMode || 'kinerja';

                Landing.qsa(S.chartTab).forEach(function (item) {
                    item.classList.remove('active');
                });

                tab.classList.add('active');

                Landing.loadPreviewData?.(Landing.regionState.applied || Object.assign({}, Landing.DEFAULT_SELECTION));
            });
        });

        Landing.loadPreviewData?.(Landing.regionState.applied || Object.assign({}, Landing.DEFAULT_SELECTION));
    };

    Landing.initChartResponsiveEvents = function () {
        if (Landing.chartResponsiveBound) {
            return;
        }

        Landing.chartResponsiveBound = true;
        let resizeTimer = null;

        window.addEventListener('resize', function () {
            window.clearTimeout(resizeTimer);
            resizeTimer = window.setTimeout(Landing.applyMobileChartMode, 160);
        }, { passive: true });
    };
})();
