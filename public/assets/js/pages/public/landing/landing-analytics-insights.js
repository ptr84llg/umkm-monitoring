(function () {
  'use strict';

  var state = {
    payload: null,
    charts: {},
    table: null,
    filters: {
      category: '',
      type: '',
      marketing: ''
    }
  };

  function root() {
    return document.querySelector('[data-public-analytics-insight-root]');
  }

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
    if (typeof value === 'boolean') return value ? 'Ya' : 'Tidak';

    if (typeof value === 'object') {
      return cleanText(value.name || value.label || value.title || value.text || value.value, safeFallback);
    }

    return safeFallback;
  }

  function numberValue(value, fallback) {
    var safeFallback = Number.isFinite(Number(fallback)) ? Number(fallback) : 0;

    if (typeof value === 'number') return Number.isFinite(value) ? value : safeFallback;

    if (typeof value === 'string') {
      var parsed = Number(value.replace(/[^\d.-]/g, ''));
      return Number.isFinite(parsed) ? parsed : safeFallback;
    }

    if (value && typeof value === 'object') {
      return numberValue(value.total || value.count || value.value || value.amount, safeFallback);
    }

    return safeFallback;
  }

  function formatNumber(value) {
    return new Intl.NumberFormat('id-ID').format(Math.max(0, Math.round(numberValue(value, 0))));
  }

  function formatPercent(value) {
    var number = numberValue(value, 0);
    var rounded = Math.max(0, Math.min(100, Math.round(number * 100) / 100));
    return String(rounded).replace('.', ',') + '%';
  }

  function normalizePayload(payload) {
    if (!payload) return null;
    if (payload.payload) return normalizePayload(payload.payload);
    if (payload.ok === true && payload.data) return payload;
    if (payload.data) return { ok: true, data: payload.data };
    if (payload.analytics) return { ok: true, data: payload };
    return null;
  }

  function analyticsData() {
    var safe = normalizePayload(state.payload || window.PublicLandingAggregatePayload);
    return safe && safe.data && safe.data.analytics ? safe.data.analytics : null;
  }

  function analyticsContext() {
    var analytics = analyticsData();
    return analytics && analytics.context ? analytics.context : {};
  }

  function businessStructure() {
    var analytics = analyticsData();
    return analytics && analytics.business_structure ? analytics.business_structure : {};
  }

  function marketingData() {
    var analytics = analyticsData();
    return analytics && analytics.marketing ? analytics.marketing : {};
  }

  function readinessData() {
    var analytics = analyticsData();
    return analytics && analytics.data_readiness ? analytics.data_readiness : {};
  }

  function areaData() {
    var analytics = analyticsData();
    return analytics && analytics.area_comparison ? analytics.area_comparison : {};
  }

  function decisionNotes() {
    var analytics = analyticsData();
    var notes = analytics && analytics.decision_notes && Array.isArray(analytics.decision_notes.recommendations)
      ? analytics.decision_notes.recommendations
      : [];
    return notes;
  }

  function setText(selector, value, context) {
    var element = one(selector, context);
    if (element) element.textContent = cleanText(value, '');
  }

  function arrayOf(value) {
    return Array.isArray(value) ? value : [];
  }

  function categories() {
    return arrayOf(businessStructure().categories)
      .map(function (item) {
        return {
          id: item.id,
          name: cleanText(item.name, 'Kategori'),
          total: numberValue(item.total, 0),
          percentage: numberValue(item.percentage, 0)
        };
      })
      .filter(function (item) {
        return item.name && item.name !== 'Belum tersedia' && item.total >= 0;
      });
  }

  function types() {
    var selectedCategory = state.filters.category;

    return arrayOf(businessStructure().types)
      .map(function (item) {
        return {
          id: item.id,
          name: cleanText(item.name, 'Jenis usaha'),
          category_name: cleanText(item.category_name, ''),
          total: numberValue(item.total, 0),
          percentage: numberValue(item.percentage, 0)
        };
      })
      .filter(function (item) {
        if (!item.name || item.name === 'Belum tersedia') return false;
        if (selectedCategory && item.category_name !== selectedCategory) return false;
        if (state.filters.type && item.name !== state.filters.type) return false;
        return item.total >= 0;
      });
  }

  function marketingMethods() {
    return arrayOf(marketingData().methods)
      .map(function (item) {
        return {
          name: cleanText(item.name, 'Belum tersedia'),
          total: numberValue(item.total, 0),
          percentage: numberValue(item.percentage, 0)
        };
      })
      .filter(function (item) {
        if (state.filters.marketing && item.name !== state.filters.marketing) return false;
        return item.total >= 0;
      });
  }

  function areaRows() {
    return arrayOf(areaData().rows)
      .map(function (item) {
        return {
          name: cleanText(item.name, 'Wilayah'),
          total_umkm: numberValue(item.total_umkm, 0),
          mapped_total: numberValue(item.mapped_total, 0),
          mapped_percentage: numberValue(item.mapped_percentage, 0),
          open_quality_notes: numberValue(item.open_quality_notes, 0)
        };
      })
      .filter(function (item) {
        return item.name && item.name !== 'Belum tersedia';
      });
  }

  function locationReadiness() {
    return readinessData().location || {};
  }

  function qualityNotes() {
    return arrayOf(readinessData().quality_notes);
  }

  function totalUmkm() {
    return numberValue(businessStructure().total_umkm || locationReadiness().total_umkm, 0);
  }

  function selectedCategoryRow() {
    var rows = categories();
    if (state.filters.category) {
      return rows.find(function (item) { return item.name === state.filters.category; }) || null;
    }
    return rows.length ? rows[0] : null;
  }

  function selectedTypeRow() {
    var rows = types();
    if (state.filters.type) {
      return rows.find(function (item) { return item.name === state.filters.type; }) || null;
    }
    return rows.length ? rows[0] : null;
  }

  function selectedMarketingRow() {
    var rows = marketingMethods();
    if (state.filters.marketing) {
      return rows.find(function (item) { return item.name === state.filters.marketing; }) || null;
    }
    return rows.length ? rows[0] : null;
  }

  function contextLabel() {
    var context = analyticsContext();
    return cleanText(context.label, 'Kota Lubuklinggau');
  }

  function updateContext() {
    var label = contextLabel();
    var category = cleanText(businessStructure().dominant_category, 'Belum tersedia');

    setText('[data-public-analytics-context-label]', label);
    setText('[data-public-analytics-context-path]', category === 'Belum tersedia' ? label : label + ' • Kategori dominan: ' + category);
  }

  function makeOption(value, label) {
    var option = document.createElement('option');
    option.value = value || '';
    option.textContent = label || 'Semua';
    return option;
  }

  function updateSelect(selector, placeholder, values, current) {
    var select = one(selector);
    if (!select) return '';

    select.innerHTML = '';
    select.appendChild(makeOption('', placeholder));

    values.forEach(function (value) {
      select.appendChild(makeOption(value, value));
    });

    if (current && values.indexOf(current) !== -1) {
      select.value = current;
      return current;
    }

    select.value = '';
    return '';
  }

  function updateFilters() {
    var categoryValues = categories().map(function (item) { return item.name; });
    state.filters.category = updateSelect('[data-public-analytics-filter="category"]', 'Semua kategori', categoryValues, state.filters.category);

    var typeValues = arrayOf(businessStructure().types)
      .filter(function (item) {
        return !state.filters.category || cleanText(item.category_name, '') === state.filters.category;
      })
      .map(function (item) { return cleanText(item.name, ''); })
      .filter(function (name, index, list) {
        return name && list.indexOf(name) === index;
      });
    state.filters.type = updateSelect('[data-public-analytics-filter="type"]', 'Semua jenis usaha', typeValues, state.filters.type);

    var marketingValues = arrayOf(marketingData().methods)
      .map(function (item) { return cleanText(item.name, ''); })
      .filter(function (name, index, list) {
        return name && list.indexOf(name) === index;
      });
    state.filters.marketing = updateSelect('[data-public-analytics-filter="marketing"]', 'Semua metode', marketingValues, state.filters.marketing);

    updateFilterStrip();
  }

  function updateFilterStrip() {
    var strip = one('[data-public-analytics-filter-strip]');
    if (!strip) return;

    var active = [];
    if (state.filters.category) active.push('Kategori: ' + state.filters.category);
    if (state.filters.type) active.push('Jenis: ' + state.filters.type);
    if (state.filters.marketing) active.push('Pemasaran: ' + state.filters.marketing);

    if (active.length < 1) {
      strip.innerHTML = '<span>Semua data agregat ditampilkan.</span>';
      return;
    }

    strip.innerHTML = active.map(function (item) {
      return '<span>' + escapeHtml(item) + '</span>';
    }).join('');
  }

  function setInsight(key, value, context) {
    var card = one('[data-public-insight-card="' + key + '"]');
    if (!card) return;

    setText('[data-public-insight-value]', value, card);
    setText('[data-public-insight-context]', context, card);
  }

  function updateInsights() {
    var total = totalUmkm();
    var category = selectedCategoryRow();
    var type = selectedTypeRow();
    var marketing = selectedMarketingRow();
    var location = locationReadiness();
    var mapped = numberValue(location.mapped_total, 0);
    var mappedPercent = numberValue(location.mapped_percentage, 0);

    setInsight('total_umkm', formatNumber(total), 'Jumlah UMKM pada wilayah aktif.');
    setInsight(
      'dominant_category',
      category ? category.name : 'Belum tersedia',
      category ? formatNumber(category.total) + ' UMKM atau ' + formatPercent(category.percentage) + ' dari wilayah aktif.' : 'Data kategori belum tersedia.'
    );
    setInsight(
      'dominant_type',
      type ? type.name : 'Belum tersedia',
      type ? formatNumber(type.total) + ' UMKM atau ' + formatPercent(type.percentage) + ' dari wilayah aktif.' : 'Data jenis usaha belum tersedia.'
    );
    setInsight(
      'dominant_marketing',
      marketing ? marketing.name : 'Belum tersedia',
      marketing ? formatNumber(marketing.total) + ' UMKM atau ' + formatPercent(marketing.percentage) + ' dari wilayah aktif.' : 'Data pemasaran belum tersedia.'
    );
    setInsight(
      'mapped_readiness',
      formatPercent(mappedPercent),
      formatNumber(mapped) + ' UMKM sudah memiliki titik lokasi.'
    );
  }

  function emptyChart(container, message) {
    if (!container) return;
    if (container.__echartInstance) {
      container.__echartInstance.dispose();
      container.__echartInstance = null;
    }
    container.innerHTML = '<div class="public-chart-empty"><strong>Data belum cukup</strong><span>' + escapeHtml(message || 'Data agregat pada wilayah ini belum cukup untuk divisualisasikan.') + '</span></div>';
  }

  function fallbackList(container, rows, valueKey) {
    if (!container) return;
    if (!rows.length) {
      emptyChart(container);
      return;
    }

    container.innerHTML = '<div class="public-chart-fallback-list">' + rows.map(function (row) {
      return '<div><span>' + escapeHtml(row.name) + '</span><strong>' + escapeHtml(formatNumber(row[valueKey || 'total'])) + '</strong></div>';
    }).join('') + '</div>';
  }

  function renderChart(selector, key, rows, optionFactory, emptyMessage) {
    var container = one(selector);
    if (!container) return;

    if (!rows.length) {
      emptyChart(container, emptyMessage);
      return;
    }

    if (!(window.echarts && typeof window.echarts.init === 'function')) {
      fallbackList(container, rows, key === 'area' ? 'total_umkm' : 'total');
      return;
    }

    container.innerHTML = '';
    var chart = state.charts[key];

    if (!chart || chart.isDisposed && chart.isDisposed()) {
      chart = window.echarts.init(container);
      state.charts[key] = chart;
      container.__echartInstance = chart;
    }

    chart.setOption(optionFactory(rows), true);
  }

  function categoryRowsForChart() {
    var rows = categories();
    if (state.filters.category) {
      rows = rows.filter(function (item) { return item.name === state.filters.category; });
    }
    return rows;
  }

  function readinessRowsForChart() {
    var location = locationReadiness();
    return [
      { name: 'Terpetakan', total: numberValue(location.mapped_total, 0) },
      { name: 'Belum terpetakan', total: numberValue(location.unmapped_total, 0) },
      { name: 'Perlu validasi', total: numberValue(location.needs_validation_total, 0) },
      { name: 'Wilayah belum lengkap', total: numberValue(location.missing_village_total, 0) }
    ].filter(function (item) {
      return item.total > 0 || item.name === 'Terpetakan' || item.name === 'Belum terpetakan';
    });
  }

  function updateCharts() {
    renderChart('[data-public-analytics-chart="category"]', 'category', categoryRowsForChart(), function (rows) {
      return {
        tooltip: {
          trigger: 'item',
          formatter: function (params) {
            return params.name + '<br/>' + formatNumber(params.value) + ' UMKM';
          }
        },
        legend: {
          bottom: 0,
          type: 'scroll'
        },
        series: [{
          type: 'pie',
          radius: ['48%', '72%'],
          center: ['50%', '43%'],
          avoidLabelOverlap: true,
          label: { formatter: '{b}' },
          data: rows.map(function (item) {
            return { name: item.name, value: item.total };
          })
        }]
      };
    }, 'Kategori usaha belum tersedia pada wilayah aktif.');

    renderChart('[data-public-analytics-chart="types"]', 'types', types().slice(0, 10), function (rows) {
      return {
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
        yAxis: { type: 'category', data: rows.map(function (item) { return item.name; }) },
        series: [{ type: 'bar', data: rows.map(function (item) { return item.total; }), barMaxWidth: 24 }]
      };
    }, 'Jenis usaha belum tersedia pada wilayah aktif.');

    renderChart('[data-public-analytics-chart="marketing"]', 'marketing', marketingMethods(), function (rows) {
      return {
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
          label: { formatter: '{b}' },
          data: rows.map(function (item) { return { name: item.name, value: item.total }; })
        }]
      };
    }, 'Data metode pemasaran belum tersedia.');

    renderChart('[data-public-analytics-chart="readiness"]', 'readiness', readinessRowsForChart(), function (rows) {
      return {
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
        yAxis: { type: 'category', data: rows.map(function (item) { return item.name; }) },
        series: [{ type: 'bar', data: rows.map(function (item) { return item.total; }), barMaxWidth: 28 }]
      };
    }, 'Data kesiapan lokasi belum tersedia.');

    renderChart('[data-public-analytics-chart="area"]', 'area', areaRows().slice(0, 10), function (rows) {
      return {
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
        yAxis: { type: 'category', data: rows.map(function (item) { return item.name; }) },
        series: [{ type: 'bar', data: rows.map(function (item) { return item.total_umkm; }), barMaxWidth: 24 }]
      };
    }, 'Area pembanding belum tersedia pada wilayah aktif.');
  }

  function updateMarketingNote() {
    var node = one('[data-public-analytics-marketing-note]');
    if (!node) return;

    var methods = marketingMethods();
    var dominant = selectedMarketingRow();
    var unavailable = methods.find(function (item) { return item.name === 'Belum tersedia'; });

    node.innerHTML = '<span>Ringkasan Pemasaran</span>'
      + '<p>Metode pemasaran dominan pada wilayah aktif adalah <b>' + escapeHtml(dominant ? dominant.name : 'Belum tersedia') + '</b>.'
      + (dominant ? ' Kelompok ini mencakup <b>' + escapeHtml(formatNumber(dominant.total)) + '</b> UMKM.' : '')
      + '</p>'
      + (unavailable && unavailable.total > 0
        ? '<p class="mb-0">Masih terdapat <b>' + escapeHtml(formatNumber(unavailable.total)) + '</b> UMKM dengan metode pemasaran belum tersedia.</p>'
        : '<p class="mb-0">Data metode pemasaran sudah tersedia pada ringkasan wilayah ini.</p>');
  }

  function updateReadinessStack() {
    var location = locationReadiness();
    var mapped = numberValue(location.mapped_total, 0);
    var unmapped = numberValue(location.unmapped_total, 0);
    var notes = qualityNotes().reduce(function (sum, item) {
      return sum + numberValue(item.total, 0);
    }, 0);

    setText('[data-public-readiness-mapped]', formatNumber(mapped));
    setText('[data-public-readiness-unmapped]', formatNumber(unmapped));
    setText('[data-public-readiness-note]', formatNumber(notes));
  }

  function tablePriority(row) {
    if (row.mapped_percentage < 10 || row.open_quality_notes > 0) return 'Perlu perhatian';
    if (row.mapped_percentage < 50) return 'Perlu dilengkapi';
    return 'Relatif siap';
  }

  function fallbackAreaTable(container, rows) {
    if (!container) return;

    if (!rows.length) {
      container.innerHTML = '<div class="public-chart-empty"><strong>Data belum cukup</strong><span>Area pembanding belum tersedia pada wilayah aktif.</span></div>';
      return;
    }

    container.innerHTML = '<div class="table-responsive"><table class="table table-sm align-middle public-analytics-fallback-table mb-0">'
      + '<thead><tr><th>Area</th><th class="text-end">UMKM</th><th class="text-end">Terpetakan</th><th>Status</th></tr></thead>'
      + '<tbody>'
      + rows.map(function (row) {
        return '<tr>'
          + '<td>' + escapeHtml(row.name) + '</td>'
          + '<td class="text-end">' + escapeHtml(formatNumber(row.total_umkm)) + '</td>'
          + '<td class="text-end">' + escapeHtml(formatPercent(row.mapped_percentage)) + '</td>'
          + '<td>' + escapeHtml(tablePriority(row)) + '</td>'
          + '</tr>';
      }).join('')
      + '</tbody></table></div>';
  }

  function updateAreaTable() {
    var container = one('[data-public-analytics-table="area"]');
    if (!container) return;

    var rows = areaRows().slice(0, 10).map(function (row) {
      return {
        name: row.name,
        total_umkm: row.total_umkm,
        mapped_percentage: row.mapped_percentage,
        open_quality_notes: row.open_quality_notes,
        priority: tablePriority(row)
      };
    });

    if (!rows.length) {
      if (state.table && typeof state.table.destroy === 'function') {
        state.table.destroy();
        state.table = null;
      }
      fallbackAreaTable(container, rows);
      return;
    }

    if (window.Tabulator) {
      if (!state.table) {
        container.innerHTML = '';
        state.table = new window.Tabulator(container, {
          layout: 'fitColumns',
          height: '310px',
          reactiveData: true,
          placeholder: 'Area pembanding belum tersedia.',
          columns: [
            { title: 'Area', field: 'name', minWidth: 150 },
            { title: 'UMKM', field: 'total_umkm', hozAlign: 'right', width: 100, formatter: function (cell) { return formatNumber(cell.getValue()); } },
            { title: 'Terpetakan', field: 'mapped_percentage', hozAlign: 'right', width: 120, formatter: function (cell) { return formatPercent(cell.getValue()); } },
            { title: 'Status', field: 'priority', minWidth: 130 }
          ]
        });
      }

      state.table.setData(rows);
      return;
    }

    fallbackAreaTable(container, rows);
  }

  function updateNarrative() {
    var node = one('[data-public-analytics-narrative]');
    if (!node) return;

    var label = contextLabel();
    var category = selectedCategoryRow();
    var type = selectedTypeRow();
    var marketing = selectedMarketingRow();
    var location = locationReadiness();
    var notes = decisionNotes();
    var mappedPercent = numberValue(location.mapped_percentage, 0);

    var narrative = '<span>Ringkasan Wawasan</span><p>'
      + 'Pada <b>' + escapeHtml(label) + '</b>, kategori terbesar adalah <b>' + escapeHtml(category ? category.name : 'Belum tersedia') + '</b>'
      + (type ? ' dengan jenis usaha dominan <b>' + escapeHtml(type.name) + '</b>' : '')
      + (marketing ? '. Metode pemasaran dominan adalah <b>' + escapeHtml(marketing.name) + '</b>' : '')
      + '. Keterpetaan lokasi berada pada <b>' + escapeHtml(formatPercent(mappedPercent)) + '</b>.'
      + '</p>';

    if (notes.length) {
      narrative += '<ul>' + notes.slice(0, 3).map(function (note) {
        return '<li>' + escapeHtml(note) + '</li>';
      }).join('') + '</ul>';
    }

    node.innerHTML = narrative;
  }

  function render() {
    if (!root()) return;
    if (!analyticsData()) {
      updateContext();
      return;
    }

    updateContext();
    updateFilters();
    updateInsights();
    updateCharts();
    updateMarketingNote();
    updateReadinessStack();
    updateAreaTable();
    updateNarrative();
  }

  function handleFilterChange(select) {
    var key = select.getAttribute('data-public-analytics-filter') || '';
    state.filters[key] = select.value || '';

    if (key === 'category') {
      state.filters.type = '';
    }

    render();
  }

  function bind() {
    document.addEventListener('change', function (event) {
      var select = event.target && event.target.closest ? event.target.closest('[data-public-analytics-filter]') : null;
      if (!select) return;
      handleFilterChange(select);
    });

    document.addEventListener('click', function (event) {
      var reset = event.target && event.target.closest ? event.target.closest('[data-public-analytics-reset]') : null;
      if (!reset) return;

      state.filters = { category: '', type: '', marketing: '' };
      render();
    });

    document.addEventListener('shown.bs.tab', function () {
      window.setTimeout(function () {
        Object.keys(state.charts).forEach(function (key) {
          if (state.charts[key] && typeof state.charts[key].resize === 'function') {
            state.charts[key].resize();
          }
        });
        if (state.table && typeof state.table.redraw === 'function') {
          state.table.redraw(true);
        }
      }, 80);
    });

    document.addEventListener('umkm:landing-analytics:ready', function (event) {
      state.payload = event && event.detail ? event.detail.payload : window.PublicLandingAggregatePayload;
      render();
    });

    document.addEventListener('umkm:landing-region:changed', function (event) {
      if (event && event.detail && event.detail.response) {
        state.payload = event.detail.response;
      } else {
        state.payload = window.PublicLandingAggregatePayload;
      }
      window.setTimeout(render, 80);
    });

    window.addEventListener('resize', function () {
      Object.keys(state.charts).forEach(function (key) {
        if (state.charts[key] && typeof state.charts[key].resize === 'function') {
          state.charts[key].resize();
        }
      });
      if (state.table && typeof state.table.redraw === 'function') {
        state.table.redraw(true);
      }
    });
  }

  function boot() {
    bind();
    state.payload = window.PublicLandingAggregatePayload;
    window.setTimeout(render, 120);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
  } else {
    boot();
  }
}());