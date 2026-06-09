(function () {
  'use strict';

  var attempts = 0;
  var maxAttempts = 24;
  var chartKeys = ['category', 'types', 'marketing', 'readiness', 'area'];

  function one(selector, context) {
    return (context || document).querySelector(selector);
  }

  function all(selector, context) {
    return Array.prototype.slice.call((context || document).querySelectorAll(selector));
  }

  function escapeHtml(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function cleanText(value, fallback) {
    var safeFallback = fallback || 'Belum tersedia';

    if (value === null || value === undefined || value === '') return safeFallback;

    if (typeof value === 'string') {
      var trimmed = value.trim();
      return trimmed ? trimmed : safeFallback;
    }

    if (typeof value === 'number') return Number.isFinite(value) ? String(value) : safeFallback;

    if (value && typeof value === 'object') {
      return cleanText(value.name || value.label || value.title || value.text || value.value, safeFallback);
    }

    return safeFallback;
  }

  function numberValue(value) {
    if (typeof value === 'number') return Number.isFinite(value) ? value : 0;

    if (typeof value === 'string') {
      var parsed = Number(value.replace(/[^\d.-]/g, ''));
      return Number.isFinite(parsed) ? parsed : 0;
    }

    if (value && typeof value === 'object') {
      return numberValue(value.total || value.count || value.value || value.amount);
    }

    return 0;
  }

  function formatNumber(value) {
    return new Intl.NumberFormat('id-ID').format(Math.max(0, Math.round(numberValue(value))));
  }

  function normalizePayload(payload) {
    if (!payload) return null;
    if (payload.payload) return normalizePayload(payload.payload);
    if (payload.ok === true && payload.data) return payload;
    if (payload.data) return { ok: true, data: payload.data };
    if (payload.analytics) return { ok: true, data: payload };
    return null;
  }

  function analytics() {
    var safe = normalizePayload(window.PublicLandingAggregatePayload);
    return safe && safe.data && safe.data.analytics ? safe.data.analytics : null;
  }

  function filters() {
    return {
      category: (one('[data-public-analytics-filter="category"]') || {}).value || '',
      type: (one('[data-public-analytics-filter="type"]') || {}).value || '',
      marketing: (one('[data-public-analytics-filter="marketing"]') || {}).value || ''
    };
  }

  function rows(path) {
    var data = analytics();
    var current = data;

    path.forEach(function (key) {
      current = current && current[key] !== undefined ? current[key] : null;
    });

    return Array.isArray(current) ? current : [];
  }

  function categoryRows() {
    var active = filters().category;

    return rows(['business_structure', 'categories'])
      .map(function (row) {
        return {
          name: cleanText(row.name, 'Kategori'),
          total: numberValue(row.total)
        };
      })
      .filter(function (row) {
        return row.name !== 'Belum tersedia' && (!active || row.name === active);
      });
  }

  function typeRows() {
    var active = filters();

    return rows(['business_structure', 'types'])
      .map(function (row) {
        return {
          name: cleanText(row.name, 'Jenis usaha'),
          category_name: cleanText(row.category_name, ''),
          total: numberValue(row.total)
        };
      })
      .filter(function (row) {
        if (row.name === 'Belum tersedia') return false;
        if (active.category && row.category_name !== active.category) return false;
        if (active.type && row.name !== active.type) return false;
        return true;
      })
      .slice(0, 10);
  }

  function marketingRows() {
    var active = filters().marketing;

    return rows(['marketing', 'methods'])
      .map(function (row) {
        return {
          name: cleanText(row.name, 'Metode'),
          total: numberValue(row.total)
        };
      })
      .filter(function (row) {
        return !active || row.name === active;
      });
  }

  function readinessRows() {
    var location = (analytics() && analytics().data_readiness && analytics().data_readiness.location) || {};

    return [
      { name: 'Terpetakan', total: numberValue(location.mapped_total) },
      { name: 'Belum terpetakan', total: numberValue(location.unmapped_total) },
      { name: 'Perlu validasi', total: numberValue(location.needs_validation_total) },
      { name: 'Wilayah belum lengkap', total: numberValue(location.missing_village_total) }
    ].filter(function (row) {
      return row.total > 0 || row.name === 'Terpetakan' || row.name === 'Belum terpetakan';
    });
  }

  function areaRows() {
    return rows(['area_comparison', 'rows'])
      .map(function (row) {
        return {
          name: cleanText(row.name, 'Area'),
          total: numberValue(row.total_umkm)
        };
      })
      .filter(function (row) {
        return row.name !== 'Belum tersedia';
      })
      .slice(0, 10);
  }

  function ensureContainer(container) {
    if (!container) return false;

    container.style.display = 'block';
    container.style.width = '100%';

    if (container.getBoundingClientRect().height < 160) {
      container.style.height = '320px';
      container.style.minHeight = '320px';
    }

    return container.getBoundingClientRect().width > 60;
  }

  function empty(container, message) {
    if (!container) return;

    container.innerHTML = '<div class="public-chart-empty"><strong>Data belum cukup</strong><span>' + escapeHtml(message) + '</span></div>';
  }

  function fallback(container, chartRows, valueKey) {
    if (!container) return;

    if (!chartRows.length) {
      empty(container, 'Data agregat pada wilayah ini belum cukup untuk divisualisasikan.');
      return;
    }

    container.innerHTML = '<div class="public-chart-fallback-list">' + chartRows.map(function (row) {
      return '<div><span>' + escapeHtml(row.name) + '</span><strong>' + escapeHtml(formatNumber(row[valueKey || 'total'])) + '</strong></div>';
    }).join('') + '</div>';
  }

  function getChart(container) {
    if (!(window.echarts && typeof window.echarts.init === 'function')) return null;
    if (!ensureContainer(container)) return null;

    var chart = window.echarts.getInstanceByDom(container);

    if (!chart) {
      container.innerHTML = '';
      chart = window.echarts.init(container, null, {
        renderer: 'canvas',
        width: container.clientWidth,
        height: container.clientHeight
      });
    }

    return chart;
  }

  function pieOption(chartRows) {
    return {
      animationDuration: 550,
      tooltip: {
        trigger: 'item',
        formatter: function (params) {
          return params.name + '<br/>' + formatNumber(params.value) + ' UMKM';
        }
      },
      legend: { bottom: 0, type: 'scroll' },
      series: [{
        type: 'pie',
        radius: ['48%', '72%'],
        center: ['50%', '43%'],
        avoidLabelOverlap: true,
        label: { formatter: '{b}' },
        data: chartRows.map(function (row) {
          return { name: row.name, value: row.total };
        })
      }]
    };
  }

  function barOption(chartRows) {
    return {
      animationDuration: 550,
      tooltip: {
        trigger: 'axis',
        axisPointer: { type: 'shadow' },
        formatter: function (params) {
          var item = params && params[0] ? params[0] : null;
          return item ? item.name + '<br/>' + formatNumber(item.value) + ' UMKM' : '';
        }
      },
      grid: { left: 8, right: 24, top: 12, bottom: 8, containLabel: true },
      xAxis: { type: 'value', axisLabel: { formatter: function (value) { return formatNumber(value); } } },
      yAxis: { type: 'category', data: chartRows.map(function (row) { return row.name; }) },
      series: [{ type: 'bar', data: chartRows.map(function (row) { return row.total; }), barMaxWidth: 26 }]
    };
  }

  function renderOne(key, chartRows, optionFactory, emptyMessage) {
    var container = one('[data-public-analytics-chart="' + key + '"]');
    if (!container) return;

    if (!chartRows.length) {
      empty(container, emptyMessage);
      return;
    }

    if (!(window.echarts && typeof window.echarts.init === 'function')) {
      fallback(container, chartRows, 'total');
      return;
    }

    var chart = getChart(container);
    if (!chart) {
      fallback(container, chartRows, 'total');
      return;
    }

    try {
      chart.setOption(optionFactory(chartRows), true);
      window.requestAnimationFrame(function () {
        chart.resize({
          width: container.clientWidth,
          height: container.clientHeight
        });
      });
    } catch (error) {
      console.warn('[UMKM Analytics] Grafik belum dapat dimuat, memakai daftar ringkas.', error);
      fallback(container, chartRows, 'total');
    }
  }

  function renderCharts() {
    if (!analytics()) return;

    renderOne('category', categoryRows(), pieOption, 'Kategori usaha belum tersedia pada wilayah aktif.');
    renderOne('types', typeRows(), barOption, 'Jenis usaha belum tersedia pada wilayah aktif.');
    renderOne('marketing', marketingRows(), pieOption, 'Data metode pemasaran belum tersedia.');
    renderOne('readiness', readinessRows(), barOption, 'Data kesiapan lokasi belum tersedia.');
    renderOne('area', areaRows(), barOption, 'Area pembanding belum tersedia pada wilayah aktif.');

    window.setTimeout(resizeCharts, 120);
    window.setTimeout(resizeCharts, 360);
  }

  function resizeCharts() {
    chartKeys.forEach(function (key) {
      var container = one('[data-public-analytics-chart="' + key + '"]');
      if (!container || !(window.echarts && window.echarts.getInstanceByDom)) return;

      var chart = window.echarts.getInstanceByDom(container);
      if (chart && typeof chart.resize === 'function') {
        try {
          chart.resize({
            width: container.clientWidth,
            height: container.clientHeight
          });
        } catch (error) {
          /* Resize race ignored intentionally. */
        }
      }
    });
  }

  function waitAndRender() {
    if (analytics() && window.echarts && typeof window.echarts.init === 'function') {
      renderCharts();
      return;
    }

    attempts += 1;
    renderCharts();

    if (attempts < maxAttempts) {
      window.setTimeout(waitAndRender, 180);
    }
  }

  function bind() {
    document.addEventListener('umkm:landing-analytics:ready', function () {
      attempts = 0;
      window.setTimeout(waitAndRender, 80);
    });

    document.addEventListener('umkm:landing-region:changed', function () {
      attempts = 0;
      window.setTimeout(waitAndRender, 140);
    });

    document.addEventListener('change', function (event) {
      if (event.target && event.target.closest && event.target.closest('[data-public-analytics-filter]')) {
        window.setTimeout(renderCharts, 60);
      }
    });

    document.addEventListener('shown.bs.tab', function () {
      window.setTimeout(renderCharts, 120);
    });

    window.addEventListener('resize', function () {
      window.setTimeout(resizeCharts, 100);
    });
  }

  function boot() {
    bind();
    waitAndRender();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
  } else {
    boot();
  }
}());