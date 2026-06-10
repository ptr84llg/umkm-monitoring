(function () {
  'use strict';

  var API_URL = '/api/public/landing-preview/data';

  var state = {
    payload: null,
    rendered: false,
    loading: false,
    charts: {},
    table: null,
    renderedTabs: {
      business: false,
      marketing: false,
      readiness: false,
      area: false
    },
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

  function currentSafePayload() {
    return normalizePayload(state.payload || window.PublicLandingAggregatePayload);
  }

  function analyticsData() {
    var safe = currentSafePayload();
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
    return analytics && analytics.decision_notes && Array.isArray(analytics.decision_notes.recommendations)
      ? analytics.decision_notes.recommendations
      : [];
  }

  function setText(selector, value, context) {
    var element = one(selector, context);
    if (element) element.textContent = cleanText(value, '');
  }

  function arrayOf(value) {
    return Array.isArray(value) ? value : [];
  }

  function contextLabel() {
    var context = analyticsContext();
    return cleanText(context.label, 'Kota Lubuklinggau')
      .replace(/\s*\/\s*Belum tersedia/g, '')
      .replace(/\s*•\s*Belum tersedia/g, '');
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
    if (state.filters.category) return rows.find(function (item) { return item.name === state.filters.category; }) || null;
    return rows.length ? rows[0] : null;
  }

  function selectedTypeRow() {
    var rows = types();
    if (state.filters.type) return rows.find(function (item) { return item.name === state.filters.type; }) || null;
    return rows.length ? rows[0] : null;
  }

  function selectedMarketingRow() {
    var rows = marketingMethods();
    if (state.filters.marketing) return rows.find(function (item) { return item.name === state.filters.marketing; }) || null;
    return rows.length ? rows[0] : null;
  }

  function queryParams() {
    var safe = currentSafePayload();
    var data = safe && safe.data ? safe.data : {};
    var region = data.region || {};
    var selection = data.selection || {};
    var context = analyticsContext();
    var q = new URLSearchParams();

    q.set('city_code', selection.city_code || region.city_code || (context.city && context.city.code) || '16.73');
    q.set('scope', 'city');

    if (selection.district_code || region.district_code || (context.district && context.district.code)) {
      q.set('district_code', selection.district_code || region.district_code || context.district.code);
      q.set('scope', 'district');
    }

    if (selection.village_code || region.village_code || (context.village && context.village.code)) {
      q.set('village_code', selection.village_code || region.village_code || context.village.code);
      q.set('scope', 'village');
    }

    if (state.filters.category) q.set('category', state.filters.category);
    if (state.filters.type) q.set('business_type', state.filters.type);
    if (state.filters.marketing) q.set('marketing_method', state.filters.marketing);

    return q;
  }

  function requestJson() {
    if (!(window.UMKM && window.UMKM.ajax && typeof window.UMKM.ajax.get === 'function')) {
      return Promise.reject(new Error('AJAX internal belum siap.'));
    }

    var url = API_URL + '?' + queryParams().toString();

    return Promise.resolve(window.UMKM.ajax.get(url, {
      headers: {
        'X-UMKM-Preview': 'landing-public-safe',
        'X-UMKM-Internal-Request': '1'
      }
    })).then(function (payload) {
      return payload && payload.payload ? payload.payload : payload;
    }).then(function (payload) {
      var safe = normalizePayload(payload);
      if (!safe || !safe.data || !safe.data.analytics) {
        throw new Error('Data analitik belum tersedia.');
      }

      state.payload = safe;
      window.PublicLandingAggregatePayload = safe;
      return safe;
    });
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

  function resetRenderedTabs() {
    state.renderedTabs = {
      business: false,
      marketing: false,
      readiness: false,
      area: false
    };
  }

  function destroyVisuals() {
    Object.keys(state.charts).forEach(function (key) {
      var chart = state.charts[key];
      if (chart && typeof chart.dispose === 'function') {
        try { chart.dispose(); } catch (error) { /* ignore dispose race */ }
      }
    });

    state.charts = {};

    if (state.table && typeof state.table.destroy === 'function') {
      try { state.table.destroy(); } catch (error) { /* ignore table destroy race */ }
    }

    state.table = null;
    resetRenderedTabs();
  }

  function setStateNote(message) {
    setText('[data-public-analytics-state-note]', message || '', root());
  }

  function showPlaceholder(message) {
    var placeholder = one('[data-public-analytics-placeholder]');
    var loader = one('[data-public-analytics-loader]');
    var target = one('[data-public-analytics-render-target]');

    destroyVisuals();
    state.rendered = false;

    if (target) target.innerHTML = '';
    if (loader) loader.hidden = true;
    if (placeholder) {
      placeholder.hidden = false;
      var paragraph = placeholder.querySelector('p');
      if (paragraph && message) paragraph.textContent = message;
    }

    setStateNote('Pilih filter bila diperlukan, lalu klik tombol untuk memuat visual analitik.');
  }

  function setLoaderStep(index, title, desc) {
    var loader = one('[data-public-analytics-loader]');
    if (!loader) return;

    var titleNode = one('[data-public-analytics-loader-title]', loader);
    var descNode = one('[data-public-analytics-loader-desc]', loader);
    var bar = one('[data-public-loader-progress-bar]', loader);
    var steps = all('[data-public-loader-step]', loader);
    var percent = Math.max(0, Math.min(100, ((index + 1) / 5) * 100));

    if (titleNode) titleNode.textContent = title || 'Memuat visual analitik';
    if (descNode) descNode.textContent = desc || 'Mohon tunggu sebentar.';
    if (bar) bar.style.width = percent + '%';

    steps.forEach(function (step, stepIndex) {
      step.classList.remove('is-running', 'is-done');
      if (stepIndex < index) step.classList.add('is-done');
      if (stepIndex === index) step.classList.add('is-running');
      if (stepIndex <= index) step.setAttribute('aria-current', stepIndex === index ? 'step' : 'false');
    });
  }

  function showLoader() {
    var placeholder = one('[data-public-analytics-placeholder]');
    var loader = one('[data-public-analytics-loader]');
    var target = one('[data-public-analytics-render-target]');

    destroyVisuals();
    state.rendered = false;
    state.loading = true;

    if (target) target.innerHTML = '';
    if (placeholder) placeholder.hidden = true;
    if (loader) loader.hidden = false;

    setLoaderStep(0, 'Menyiapkan konteks wilayah', 'Membaca wilayah dan filter aktif.');
    setStateNote('Memuat visual analitik terbaru...');
  }

  function hideLoader() {
    var loader = one('[data-public-analytics-loader]');
    if (loader) loader.hidden = true;
    state.loading = false;
  }

  function showTabLoader(key, message) {
    var pane = tabPane(key);
    if (!pane) return;

    var existing = one('[data-public-tab-loader]', pane);
    if (existing) existing.remove();

    var loader = document.createElement('div');
    loader.className = 'public-tab-lazy-loader';
    loader.setAttribute('data-public-tab-loader', key);
    loader.innerHTML = '<span class="public-tab-lazy-orb" aria-hidden="true"></span><div><strong>Menyusun visual</strong><p>' + escapeHtml(message || 'Grafik sedang disiapkan dari data agregat terbaru.') + '</p></div>';
    pane.prepend(loader);
  }

  function hideTabLoader(key) {
    var pane = tabPane(key);
    if (!pane) return;

    var loader = one('[data-public-tab-loader]', pane);
    if (loader) loader.remove();
  }

  function renderTemplate() {
    var template = one('[data-public-analytics-template]');
    var target = one('[data-public-analytics-render-target]');
    var placeholder = one('[data-public-analytics-placeholder]');

    if (!template || !target) throw new Error('Template visual analitik belum tersedia.');

    target.innerHTML = '';
    target.appendChild(template.content.cloneNode(true));

    if (placeholder) placeholder.hidden = true;

    state.rendered = true;
    resetRenderedTabs();
  }

  function htmlList(container, rows, valueKey) {
    if (!container) return;

    if (!rows.length) {
      container.innerHTML = '<div class="public-chart-empty"><strong>Data belum cukup</strong><span>Data agregat pada wilayah ini belum cukup untuk divisualisasikan.</span></div>';
      return;
    }

    container.innerHTML = '<div class="public-chart-fallback-list">' + rows.map(function (row) {
      return '<div><span>' + escapeHtml(row.name) + '</span><strong>' + escapeHtml(formatNumber(row[valueKey || 'total'])) + '</strong></div>';
    }).join('') + '</div>';
  }

  function ensureChartContainer(container) {
    if (!container) return false;

    if (container.clientHeight < 160) {
      container.style.height = '340px';
      container.style.minHeight = '340px';
    }

    return container.clientWidth > 80 && container.clientHeight > 160;
  }

  function renderChart(selector, key, rows, optionFactory, valueKey) {
    var container = one(selector);
    if (!container) return;

    if (!rows.length) {
      htmlList(container, rows, valueKey);
      return;
    }

    if (!(window.echarts && typeof window.echarts.init === 'function') || !ensureChartContainer(container)) {
      htmlList(container, rows, valueKey);
      return;
    }

    container.innerHTML = '';

    var chart = window.echarts.init(container, null, {
      renderer: 'canvas',
      width: container.clientWidth,
      height: container.clientHeight
    });

    state.charts[key] = chart;
    chart.setOption(optionFactory(rows), true);

    window.requestAnimationFrame(function () {
      try { chart.resize(); } catch (error) { /* ignore resize race */ }
    });
  }

  function categoryRowsForChart() {
    var rows = categories();
    if (state.filters.category) rows = rows.filter(function (item) { return item.name === state.filters.category; });
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

  function updateBusinessSummary() {
    var node = one('[data-public-tab-summary="business"]');
    if (!node) return;

    var category = selectedCategoryRow();
    var type = selectedTypeRow();

    node.innerHTML = [
      '<div><span>Total UMKM</span><strong>' + escapeHtml(formatNumber(totalUmkm())) + '</strong><p>Jumlah pada wilayah aktif.</p></div>',
      '<div><span>Kategori dominan</span><strong>' + escapeHtml(category ? category.name : 'Belum tersedia') + '</strong><p>' + escapeHtml(category ? (formatNumber(category.total) + ' UMKM atau ' + formatPercent(category.percentage)) : 'Data belum tersedia.') + '</p></div>',
      '<div><span>Jenis usaha dominan</span><strong>' + escapeHtml(type ? type.name : 'Belum tersedia') + '</strong><p>' + escapeHtml(type ? (formatNumber(type.total) + ' UMKM atau ' + formatPercent(type.percentage)) : 'Data belum tersedia.') + '</p></div>'
    ].join('');
  }

  function categoryChartOption(rows) {
    return {
      animationDuration: 650,
      tooltip: {
        trigger: 'item',
        formatter: function (params) { return params.name + '<br/>' + formatNumber(params.value) + ' UMKM (' + params.percent + '%)'; }
      },
      legend: { bottom: 0, type: 'scroll' },
      series: [{
        type: 'pie',
        radius: ['46%', '70%'],
        center: ['50%', '43%'],
        avoidLabelOverlap: true,
        label: {
          show: true,
          formatter: function (params) {
            return params.name + '\n' + formatNumber(params.value) + ' (' + params.percent + '%)';
          },
          lineHeight: 16
        },
        labelLine: { show: true, length: 14, length2: 12 },
        data: rows.map(function (item) { return { name: item.name, value: item.total }; })
      }]
    };
  }

  function horizontalBarOption(rows, valueKey) {
    var key = valueKey || 'total';

    return {
      animationDuration: 650,
      animationEasing: 'cubicOut',
      tooltip: {
        trigger: 'axis',
        axisPointer: { type: 'shadow' },
        formatter: function (params) {
          var item = params && params[0] ? params[0] : null;
          var row = item ? rows[item.dataIndex] || {} : {};
          if (!item) return '';

          var percent = numberValue(row.percentage, 0);
          return item.name + '<br/>' + formatNumber(item.value) + ' UMKM' + (percent > 0 ? ' (' + formatPercent(percent) + ')' : '');
        }
      },
      grid: { left: 8, right: 112, top: 14, bottom: 8, containLabel: true },
      xAxis: { type: 'value', axisLabel: { formatter: function (value) { return formatNumber(value); } } },
      yAxis: { type: 'category', data: rows.map(function (item) { return item.name; }) },
      series: [{
        type: 'bar',
        data: rows.map(function (item) { return { value: numberValue(item[key], 0) }; }),
        barMaxWidth: 26,
        label: {
          show: true,
          position: 'right',
          formatter: function (params) {
            var row = rows[params.dataIndex] || {};
            var percent = numberValue(row.percentage, 0);
            return formatNumber(params.value) + (percent > 0 ? ' • ' + formatPercent(percent) : '');
          }
        }
      }]
    };
  }

  function renderBusinessTab() {
    updateBusinessSummary();
    renderChart('[data-public-analytics-chart="category"]', 'category', categoryRowsForChart(), categoryChartOption, 'total');
    renderChart('[data-public-analytics-chart="types"]', 'types', types().slice(0, 10), function (rows) {
      return horizontalBarOption(rows, 'total');
    }, 'total');
  }

  function marketingChartOption(rows) {
    return {
      animationDuration: 650,
      tooltip: {
        trigger: 'item',
        formatter: function (params) { return params.name + '<br/>' + formatNumber(params.value) + ' UMKM (' + params.percent + '%)'; }
      },
      legend: { bottom: 0, type: 'scroll' },
      series: [{
        type: 'pie',
        radius: ['46%', '70%'],
        center: ['50%', '43%'],
        label: {
          show: true,
          formatter: function (params) {
            return params.name + '\n' + formatNumber(params.value) + ' (' + params.percent + '%)';
          },
          lineHeight: 16
        },
        labelLine: { show: true, length: 14, length2: 12 },
        data: rows.map(function (item) { return { name: item.name, value: item.total }; })
      }]
    };
  }

  function updateMarketingNote() {
    var node = one('[data-public-analytics-marketing-note]');
    if (!node) return;

    var methods = marketingMethods();
    var dominant = selectedMarketingRow();
    var unavailable = methods.find(function (item) { return item.name === 'Belum tersedia'; });

    node.innerHTML = '<span>Ringkasan Pemasaran</span>'
      + '<p>Metode pemasaran dominan pada wilayah aktif adalah <b>' + escapeHtml(dominant ? dominant.name : 'Belum tersedia') + '</b>.'
      + (dominant ? ' Kelompok ini mencakup <b>' + escapeHtml(formatNumber(dominant.total)) + '</b> UMKM atau <b>' + escapeHtml(formatPercent(dominant.percentage)) + '</b>.' : '')
      + '</p>'
      + (unavailable && unavailable.total > 0
        ? '<p class="mb-0">Masih terdapat <b>' + escapeHtml(formatNumber(unavailable.total)) + '</b> UMKM dengan metode pemasaran belum tersedia.</p>'
        : '<p class="mb-0">Data metode pemasaran sudah tersedia pada ringkasan wilayah ini.</p>');
  }

  function marketingAreaRows() {
    return arrayOf(marketingData().by_area && marketingData().by_area.rows)
      .map(function (area) {
        return {
          name: cleanText(area.name, 'Wilayah'),
          total_umkm: numberValue(area.total_umkm, 0),
          dominant_method: cleanText(area.dominant_method, 'Belum tersedia'),
          methods: arrayOf(area.methods).map(function (method) {
            return {
              name: cleanText(method.name, 'Belum tersedia'),
              total: numberValue(method.total, 0),
              percentage: numberValue(method.percentage, 0)
            };
          })
        };
      })
      .filter(function (area) {
        return area.name && area.total_umkm > 0;
      });
  }

  function readinessAreaRows() {
    return arrayOf(readinessData().by_area && readinessData().by_area.rows)
      .map(function (area) {
        return {
          name: cleanText(area.name, 'Wilayah'),
          total_umkm: numberValue(area.total_umkm, 0),
          mapped_total: numberValue(area.mapped_total, 0),
          unmapped_total: numberValue(area.unmapped_total, 0),
          needs_validation_total: numberValue(area.needs_validation_total, 0),
          missing_region_total: numberValue(area.missing_region_total, 0),
          open_quality_notes: numberValue(area.open_quality_notes, 0),
          mapped_percentage: numberValue(area.mapped_percentage, 0),
          items: arrayOf(area.items).map(function (item) {
            return {
              name: cleanText(item.name, 'Kesiapan data'),
              total: numberValue(item.total, 0),
              percentage: numberValue(item.percentage, 0)
            };
          })
        };
      })
      .filter(function (area) {
        return area.name && area.total_umkm > 0;
      });
  }

  function methodSeriesNames(rows) {
    var names = [];

    rows.forEach(function (area) {
      area.methods.forEach(function (method) {
        if (method.name && names.indexOf(method.name) === -1) names.push(method.name);
      });
    });

    var order = ['Konvensional', 'Digital', 'Both', 'Belum tersedia'];
    names.sort(function (a, b) {
      var ai = order.indexOf(a);
      var bi = order.indexOf(b);
      if (ai === -1) ai = 99;
      if (bi === -1) bi = 99;
      if (ai !== bi) return ai - bi;
      return a.localeCompare(b);
    });

    return names;
  }

  function percentStackedAreaOption(rows, seriesNames, rowItemsKey, totalKey) {
    return {
      animationDuration: 700,
      animationEasing: 'cubicOut',
      tooltip: {
        trigger: 'axis',
        axisPointer: { type: 'shadow' },
        formatter: function (params) {
          var row = rows[params && params.length ? params[0].dataIndex : 0] || {};
          var total = numberValue(row[totalKey || 'total_umkm'], 0);
          var lines = ['<b>' + escapeHtml(row.name || 'Wilayah') + '</b>', 'Total: ' + formatNumber(total) + ' UMKM'];

          arrayOf(params).forEach(function (item) {
            var data = item.data || {};
            var count = numberValue(data.count, 0);
            var value = numberValue(data.value, 0);
            if (count < 1 && value <= 0) return;
            lines.push(escapeHtml(item.seriesName) + ': ' + formatNumber(count) + ' UMKM (' + formatPercent(value) + ')');
          });

          if (row.open_quality_notes > 0) lines.push('Catatan kualitas: ' + formatNumber(row.open_quality_notes));
          if (row.missing_region_total > 0) lines.push('Wilayah belum lengkap: ' + formatNumber(row.missing_region_total));

          return lines.join('<br/>');
        }
      },
      legend: { top: 0, type: 'scroll' },
      grid: { left: 8, right: 96, top: 42, bottom: 8, containLabel: true },
      xAxis: {
        type: 'value',
        max: 100,
        axisLabel: { formatter: function (value) { return formatPercent(value); } }
      },
      yAxis: { type: 'category', data: rows.map(function (row) { return row.name; }) },
      series: seriesNames.map(function (name) {
        return {
          name: name,
          type: 'bar',
          stack: 'total',
          barMaxWidth: 24,
          emphasis: { focus: 'series' },
          label: {
            show: true,
            position: 'inside',
            formatter: function (params) {
              var data = params.data || {};
              var value = numberValue(data.value, 0);
              return value >= 7 ? formatPercent(value) : '';
            }
          },
          data: rows.map(function (row) {
            var total = numberValue(row[totalKey || 'total_umkm'], 0);
            var item = arrayOf(row[rowItemsKey]).find(function (entry) { return entry.name === name; });
            var count = item ? numberValue(item.total, 0) : 0;
            var percent = total > 0 ? (count / total) * 100 : 0;

            return {
              value: percent,
              count: count,
              total: total
            };
          })
        };
      })
    };
  }

  function marketingAreaChartOption(rows) {
    return percentStackedAreaOption(rows, methodSeriesNames(rows), 'methods', 'total_umkm');
  }

  function readinessAreaChartOption(rows) {
    return percentStackedAreaOption(rows, ['Terpetakan', 'Belum terpetakan'], 'items', 'total_umkm');
  }
  function renderMarketingTab() {
    updateMarketingNote();
    renderChart('[data-public-analytics-chart="marketing-area"]', 'marketing-area', marketingAreaRows(), marketingAreaChartOption, 'total_umkm');
    renderChart('[data-public-analytics-chart="marketing"]', 'marketing', marketingMethods(), marketingChartOption, 'total');
  }

  function updateReadinessNote() {
    var node = one('[data-public-analytics-readiness-note]');
    if (!node) return;

    var location = locationReadiness();
    var mapped = numberValue(location.mapped_total, 0);
    var unmapped = numberValue(location.unmapped_total, 0);
    var notes = qualityNotes().reduce(function (sum, item) { return sum + numberValue(item.total, 0); }, 0);
    var mappedPercent = numberValue(location.mapped_percentage, 0);

    node.innerHTML = '<span>Ringkasan Kesiapan Data</span>'
      + '<p><b>' + escapeHtml(formatNumber(mapped)) + '</b> UMKM sudah memiliki titik lokasi atau <b>' + escapeHtml(formatPercent(mappedPercent)) + '</b> dari wilayah aktif.</p>'
      + '<p><b>' + escapeHtml(formatNumber(unmapped)) + '</b> UMKM belum memiliki titik lokasi.</p>'
      + (notes > 0
        ? '<p class="mb-0">Terdapat <b>' + escapeHtml(formatNumber(notes)) + '</b> catatan kualitas data yang perlu diperhatikan.</p>'
        : '<p class="mb-0">Belum ada catatan kualitas data terbuka pada ringkasan ini.</p>');
  }

  function renderReadinessTab() {
    updateReadinessNote();
    renderChart('[data-public-analytics-chart="readiness-area"]', 'readiness-area', readinessAreaRows(), readinessAreaChartOption, 'total_umkm');
    renderChart('[data-public-analytics-chart="readiness"]', 'readiness', readinessRowsForChart(), function (rows) {
      return horizontalBarOption(rows, 'total');
    }, 'total');
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
        return '<tr><td>' + escapeHtml(row.name) + '</td><td class="text-end">' + escapeHtml(formatNumber(row.total_umkm)) + '</td><td class="text-end">' + escapeHtml(formatPercent(row.mapped_percentage)) + '</td><td>' + escapeHtml(tablePriority(row)) + '</td></tr>';
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
      fallbackAreaTable(container, rows);
      return;
    }

    if (state.table && typeof state.table.destroy === 'function') {
      try { state.table.destroy(); } catch (error) { /* ignore redraw race */ }
      state.table = null;
    }

    if (window.Tabulator) {
      state.table = new window.Tabulator(container, {
        layout: 'fitColumns',
        height: '310px',
        data: rows,
        placeholder: 'Area pembanding belum tersedia.',
        columns: [
          { title: 'Area', field: 'name', minWidth: 150 },
          { title: 'UMKM', field: 'total_umkm', hozAlign: 'right', width: 100, formatter: function (cell) { return formatNumber(cell.getValue()); } },
          { title: 'Terpetakan', field: 'mapped_percentage', hozAlign: 'right', width: 120, formatter: function (cell) { return formatPercent(cell.getValue()); } },
          { title: 'Status', field: 'priority', minWidth: 130 }
        ]
      });
      return;
    }

    fallbackAreaTable(container, rows);
  }

  function areaPriorityScatterOption(rows) {
    var maxTotal = rows.reduce(function (max, row) {
      return Math.max(max, numberValue(row.total_umkm, 0));
    }, 0);

    return {
      animationDuration: 720,
      animationEasing: 'cubicOut',
      tooltip: {
        trigger: 'item',
        formatter: function (params) {
          var row = params.data && params.data.raw ? params.data.raw : {};
          return '<b>' + escapeHtml(row.name || 'Area') + '</b>'
            + '<br/>Total UMKM: ' + formatNumber(row.total_umkm)
            + '<br/>Terpetakan: ' + formatPercent(row.mapped_percentage)
            + '<br/>Catatan kualitas: ' + formatNumber(row.open_quality_notes)
            + '<br/>Status: ' + escapeHtml(tablePriority(row));
        }
      },
      grid: { left: 56, right: 24, top: 22, bottom: 52, containLabel: true },
      xAxis: {
        type: 'value',
        name: 'Total UMKM',
        nameLocation: 'middle',
        nameGap: 34,
        axisLabel: { formatter: function (value) { return formatNumber(value); } },
        splitLine: { lineStyle: { type: 'dashed' } }
      },
      yAxis: {
        type: 'value',
        name: 'Terpetakan',
        max: 100,
        axisLabel: { formatter: function (value) { return formatPercent(value); } },
        splitLine: { lineStyle: { type: 'dashed' } }
      },
      visualMap: {
        show: false,
        min: 0,
        max: Math.max(1, maxTotal),
        dimension: 0
      },
      series: [{
        type: 'scatter',
        symbolSize: function (value, params) {
          var raw = params && params.data ? params.data.raw || {} : {};
          var total = numberValue(raw.total_umkm, 0);
          if (maxTotal <= 0) return 18;
          return Math.max(18, Math.min(54, 18 + (total / maxTotal) * 36));
        },
        label: {
          show: true,
          formatter: function (params) {
            var raw = params.data && params.data.raw ? params.data.raw : {};
            return raw.name || '';
          },
          position: 'top',
          fontSize: 11
        },
        emphasis: {
          focus: 'self',
          label: { show: true }
        },
        data: rows.map(function (row) {
          return {
            value: [row.total_umkm, row.mapped_percentage],
            raw: row
          };
        })
      }]
    };
  }

  function renderAreaTab() {
    var rows = areaRows().slice(0, 10);
    renderChart('[data-public-analytics-chart="area"]', 'area', rows, areaPriorityScatterOption, 'total_umkm');
    updateAreaTable();
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

  function resizeVisuals() {
    Object.keys(state.charts).forEach(function (key) {
      var chart = state.charts[key];
      if (chart && typeof chart.resize === 'function') {
        try { chart.resize(); } catch (error) { /* ignore resize race */ }
      }
    });

    if (state.table && typeof state.table.redraw === 'function') {
      try { state.table.redraw(true); } catch (error) { /* ignore redraw race */ }
    }
  }

  function tabPane(key) {
    if (key === 'marketing') return one('#analytics-marketing-pane');
    if (key === 'readiness') return one('#analytics-readiness-pane');
    if (key === 'area') return one('#analytics-area-pane');
    return one('#analytics-business-pane');
  }

  function activeTabKey() {
    if (one('#analytics-marketing-pane.show.active')) return 'marketing';
    if (one('#analytics-readiness-pane.show.active')) return 'readiness';
    if (one('#analytics-area-pane.show.active')) return 'area';
    return 'business';
  }

  function renderTab(key) {
    if (!state.rendered || !analyticsData()) return;
    if (state.renderedTabs[key]) {
      resizeVisuals();
      return;
    }

    showTabLoader(key, 'Menyusun grafik ' + (key === 'business' ? 'struktur usaha' : key === 'marketing' ? 'pemasaran' : key === 'readiness' ? 'kesiapan data' : 'area pembanding') + '.');

    window.setTimeout(function () {
      if (key === 'marketing') renderMarketingTab();
      else if (key === 'readiness') renderReadinessTab();
      else if (key === 'area') renderAreaTab();
      else renderBusinessTab();

      state.renderedTabs[key] = true;
      hideTabLoader(key);
      updateNarrative();
      resizeVisuals();
    }, 220);
  }

  function renderAnalyticsContent() {
    if (!analyticsData()) throw new Error('Data analitik belum tersedia.');

    setLoaderStep(2, 'Menyiapkan wadah grafik', 'Membuat ruang visual dan ringkasan agregat.');
    renderTemplate();

    setLoaderStep(3, 'Menyusun grafik awal', 'Menampilkan struktur usaha sebagai tab utama.');
    renderTab('business');

    setLoaderStep(4, 'Menampilkan ringkasan wawasan', 'Menyelesaikan narasi ringkasan agregat.');
    updateNarrative();

    window.setTimeout(resizeVisuals, 300);
    window.setTimeout(resizeVisuals, 700);
  }

  function renderControlOnly() {
    updateContext();
    updateFilters();
  }

  function showError(message) {
    hideLoader();

    var target = one('[data-public-analytics-render-target]');
    var placeholder = one('[data-public-analytics-placeholder]');
    if (target) {
      target.innerHTML = '<div class="public-analytics-error"><strong>Visual analitik belum dapat dimuat.</strong><p>' + escapeHtml(message || 'Terjadi kendala saat memuat data agregat.') + '</p></div>';
    }
    if (placeholder) placeholder.hidden = true;

    setStateNote('Visual analitik belum dapat dimuat. Silakan ulangi.');
  }

  function showAnalytics() {
    if (state.loading) return;

    showLoader();

    Promise.resolve()
      .then(function () {
        setLoaderStep(1, 'Memuat data agregat', 'Mengambil data terbaru dari wilayah dan filter aktif.');
        return requestJson();
      })
      .then(function () {
        renderControlOnly();
        renderAnalyticsContent();
      })
      .then(function () {
        window.setTimeout(function () {
          hideLoader();
          setStateNote('Visual analitik sudah dimuat berdasarkan wilayah dan filter aktif.');
        }, 280);
      })
      .catch(function (error) {
        showError(error && error.message ? error.message : 'Visual analitik gagal dimuat.');
      });
  }

  function invalidateAnalytics(reason) {
    showPlaceholder(reason || 'Wilayah atau filter berubah. Klik Tampilkan Analitik untuk memuat data terbaru.');
  }

  function handleFilterChange(select) {
    var key = select.getAttribute('data-public-analytics-filter') || '';
    state.filters[key] = select.value || '';

    if (key === 'category') state.filters.type = '';

    updateFilters();
    invalidateAnalytics('Filter berubah. Klik Tampilkan Analitik untuk memuat ulang grafik dan tabel.');
  }

  function bind() {
    document.addEventListener('click', function (event) {
      var showButton = event.target && event.target.closest ? event.target.closest('[data-public-analytics-show]') : null;
      if (showButton) {
        event.preventDefault();
        showAnalytics();
        return;
      }

      var reset = event.target && event.target.closest ? event.target.closest('[data-public-analytics-reset]') : null;
      if (reset) {
        event.preventDefault();
        state.filters = { category: '', type: '', marketing: '' };
        updateFilters();
        invalidateAnalytics('Filter direset. Klik Tampilkan Analitik untuk memuat ulang grafik dan tabel.');
      }
    });

    document.addEventListener('change', function (event) {
      var select = event.target && event.target.closest ? event.target.closest('[data-public-analytics-filter]') : null;
      if (!select) return;
      handleFilterChange(select);
    });

    document.addEventListener('shown.bs.tab', function (event) {
      var target = event && event.target ? event.target.getAttribute('data-bs-target') : '';
      var key = target === '#analytics-marketing-pane'
        ? 'marketing'
        : target === '#analytics-readiness-pane'
          ? 'readiness'
          : target === '#analytics-area-pane'
            ? 'area'
            : 'business';

      renderTab(key);
      window.setTimeout(resizeVisuals, 180);
    });

    document.addEventListener('umkm:landing-analytics:ready', function (event) {
      var payload = event && event.detail ? event.detail.payload : window.PublicLandingAggregatePayload;
      var safe = normalizePayload(payload);
      if (safe) {
        state.payload = safe;
        renderControlOnly();
        invalidateAnalytics('Data wilayah berubah. Klik Tampilkan Analitik untuk memuat visual terbaru.');
      }
    });

    document.addEventListener('umkm:landing-region:changed', function (event) {
      var response = event && event.detail ? event.detail.response : null;
      var safe = normalizePayload(response || window.PublicLandingAggregatePayload);
      if (safe) {
        state.payload = safe;
        renderControlOnly();
      }
      invalidateAnalytics('Wilayah aktif berubah. Klik Tampilkan Analitik untuk memuat visual terbaru.');
    });

    window.addEventListener('resize', function () {
      window.setTimeout(resizeVisuals, 80);
    });
  }

  function waitForInitialPayload(attempt) {
    var safe = normalizePayload(window.PublicLandingAggregatePayload);
    if (safe && safe.data && safe.data.analytics) {
      state.payload = safe;
      renderControlOnly();
      return;
    }

    if ((attempt || 0) < 16) {
      window.setTimeout(function () { waitForInitialPayload((attempt || 0) + 1); }, 200);
    }
  }

  function boot() {
    bind();
    showPlaceholder('Area ini akan memuat grafik, tabel, dan ringkasan setelah data agregat terbaru berhasil dimuat.');
    waitForInitialPayload(0);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
  } else {
    boot();
  }
}());