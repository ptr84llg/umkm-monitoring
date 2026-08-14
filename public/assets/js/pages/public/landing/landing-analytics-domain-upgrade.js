(function () {
  'use strict';

  var chartRegistry = [];
  var scheduledUpgrade = null;
  var bootPoll = null;
  var bootPollCount = 0;

  function one(selector, context) { return (context || document).querySelector(selector); }
  function all(selector, context) { return Array.prototype.slice.call((context || document).querySelectorAll(selector)); }
  function arrayOf(value) { return Array.isArray(value) ? value : []; }

  function n(value) {
    if (typeof value === 'number') return Number.isFinite(value) ? value : 0;
    if (typeof value === 'string') {
      var parsed = Number(value.replace(/[^\d.-]/g, ''));
      return Number.isFinite(parsed) ? parsed : 0;
    }
    return value && typeof value === 'object'
      ? n(value.total || value.count || value.value || value.amount || value.total_umkm || value.total_workers)
      : 0;
  }

  function t(value, fallback) {
    if (value === null || value === undefined || value === '') return fallback || 'Belum tersedia';
    if (typeof value === 'string') return value.trim() || (fallback || 'Belum tersedia');
    if (typeof value === 'number') return String(value);
    if (typeof value === 'boolean') return value ? 'Ya' : 'Tidak';
    if (typeof value === 'object') return t(value.name || value.label || value.title || value.text || value.value, fallback);
    return fallback || 'Belum tersedia';
  }

  function esc(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function fmt(value) { return new Intl.NumberFormat('id-ID').format(Math.max(0, Math.round(n(value)))); }
  function fmtDecimal(value) { return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(Math.max(0, n(value))); }
  function pct(value) { return fmtDecimal(Math.max(0, Math.min(100, n(value)))) + '%'; }
  function compact(value) { return new Intl.NumberFormat('id-ID', { notation: 'compact', maximumFractionDigits: 1 }).format(Math.max(0, n(value))); }

  function payload() {
    var raw = window.PublicLandingAggregatePayload || null;
    if (raw && raw.payload) raw = raw.payload;
    if (raw && raw.ok && raw.data) return raw.data;
    if (raw && raw.data) return raw.data;
    return raw || {};
  }

  function analytics() { return payload().analytics || {}; }
  function business() { return analytics().business_structure || {}; }
  function marketing() { return analytics().marketing || {}; }
  function marketAccess() { return analytics().market_access || {}; }
  function workforce() { return analytics().workforce || {}; }
  function economy() { return analytics().economy || {}; }
  function legality() { return analytics().legality || {}; }
  function readiness() { return analytics().data_readiness || {}; }
  function summary() { return payload().summary || {}; }

  function contextLabel() {
    return t((analytics().context || {}).label || payload().context_label, 'Kota Lubuklinggau');
  }

  function categories() {
    return arrayOf(business().categories).map(function (item) {
      return { name: t(item.name, 'Kategori'), total: n(item.total), percentage: n(item.percentage), id: item.id };
    }).filter(function (item) { return item.total > 0; });
  }

  function types() {
    return arrayOf(business().types).map(function (item) {
      return {
        name: t(item.name, 'Jenis usaha'),
        category_name: t(item.category_name, ''),
        total: n(item.total),
        percentage: n(item.percentage),
        id: item.id
      };
    }).filter(function (item) { return item.total > 0; });
  }

  function marketingMethods() {
    return arrayOf(marketing().methods).map(function (item) {
      return { name: t(item.name, 'Belum tersedia'), total: n(item.total), percentage: n(item.percentage) };
    });
  }

  function marketCategoryRows() {
    return arrayOf(marketAccess().categories).map(function (category) {
      return {
        name: t(category.name, 'Kategori'),
        total_umkm: n(category.total_umkm),
        digital_total: n(category.digital_total),
        digital_percentage: n(category.digital_percentage),
        methods: arrayOf(category.methods).map(function (method) {
          return { name: t(method.name, 'Belum tersedia'), total: n(method.total), percentage: n(method.percentage) };
        })
      };
    }).filter(function (row) { return row.total_umkm > 0; });
  }

  function readinessAreaRows() {
    return arrayOf(readiness().by_area && readiness().by_area.rows).map(function (area) {
      return {
        name: t(area.name, 'Wilayah'),
        total_umkm: n(area.total_umkm),
        mapped_total: n(area.mapped_total),
        unmapped_total: n(area.unmapped_total),
        mapped_percentage: n(area.mapped_percentage),
        open_quality_notes: n(area.open_quality_notes)
      };
    }).filter(function (area) { return area.total_umkm > 0; });
  }

  function qualityNoteRows() {
    var rows = arrayOf(readiness().quality_notes).map(function (row) {
      var label = t(row.label, '');
      var group = t(row.group || row.name, 'Catatan mutu');
      var severity = t(row.severity, 'info');
      return { name: label || (group + ' • ' + severity), total: n(row.total) };
    }).filter(function (row) { return row.total > 0; });

    if (rows.length) return rows;

    var loc = readiness().location || {};
    return [
      { name: 'Belum terpetakan', total: n(loc.unmapped_total) },
      { name: 'Perlu validasi', total: n(loc.needs_validation_total) },
      { name: 'Wilayah belum lengkap', total: Math.max(n(loc.missing_district_total), n(loc.missing_village_total)) }
    ].filter(function (row) { return row.total > 0; });
  }

  function disposeCharts() {
    chartRegistry.forEach(function (chartItem) {
      if (chartItem && typeof chartItem.dispose === 'function') {
        try { chartItem.dispose(); } catch (error) {}
      }
    });
    chartRegistry = [];
  }

  function chart(target, option, fallbackRows, fallbackKey) {
    var element = typeof target === 'string' ? one(target) : target;
    if (!element) return null;

    element.innerHTML = '';
    element.style.minHeight = element.classList.contains('public-chart-canvas-tall') ? '390px' : '340px';

    if (!window.echarts || typeof window.echarts.init !== 'function') {
      listFallback(element, fallbackRows || [], fallbackKey || 'total');
      return null;
    }

    var instance = window.echarts.init(element, null, { renderer: 'canvas' });
    chartRegistry.push(instance);

    try {
      instance.setOption(option, true);
      window.setTimeout(function () { try { instance.resize(); } catch (error) {} }, 80);
      window.setTimeout(function () { try { instance.resize(); } catch (error) {} }, 500);
      return instance;
    } catch (error) {
      try { instance.dispose(); } catch (disposeError) {}
      listFallback(element, fallbackRows || [], fallbackKey || 'total');
      return null;
    }
  }

  function listFallback(element, rows, key) {
    if (!rows.length) {
      element.innerHTML = '<div class="public-chart-empty"><strong>Data belum cukup</strong><span>Data agregat belum tersedia untuk domain ini.</span></div>';
      return;
    }

    element.innerHTML = '<div class="public-chart-fallback-list">' + rows.map(function (row) {
      return '<div><span>' + esc(row.name) + '</span><strong>' + esc(fmt(row[key || 'total'])) + '</strong></div>';
    }).join('') + '</div>';
  }

  function kpiGrid(items) {
    return '<div class="row g-3 mb-3">' + items.map(function (item) {
      return '<div class="col-12 col-md-4">'
        + '<div class="card border-0 h-100 public-kpi-card">'
        + '<div class="card-body p-3">'
        + '<span class="text-uppercase small text-muted fw-semibold">' + esc(item.label) + '</span>'
        + '<strong class="d-block mt-1">' + esc(item.value) + '</strong>'
        + '<p class="small text-muted mb-0 mt-1">' + esc(item.note || '') + '</p>'
        + '</div></div></div>';
    }).join('') + '</div>';
  }

  function panel(title, subtitle, chartKey, col, badge) {
    return '<div class="col-12 ' + (col || 'col-xl-6') + '">'
      + '<div class="card border-0 h-100 public-chart-panel">'
      + '<div class="card-body p-3 p-xl-4">'
      + '<div class="d-flex align-items-start justify-content-between gap-3 mb-3 public-chart-head">'
      + '<div><strong>' + esc(title) + '</strong><small class="d-block">' + esc(subtitle) + '</small></div>'
      + (badge ? '<span class="badge text-bg-light rounded-pill">' + esc(badge) + '</span>' : '')
      + '</div>'
      + '<div class="public-chart-canvas public-chart-canvas-tall" data-domain-upgrade-chart="' + chartKey + '"></div>'
      + '</div></div></div>';
  }

  function horizontalRanking(rows, key, unit, percentMode) {
    var selected = rows.slice().sort(function (a, b) { return n(b[key]) - n(a[key]); }).slice(0, 10);
    return {
      animationDuration: 700,
      animationDurationUpdate: 900,
      animationEasingUpdate: 'quinticInOut',
      tooltip: {
        trigger: 'axis',
        axisPointer: { type: 'shadow' },
        formatter: function (params) {
          var item = params && params[0];
          if (!item) return '';
          return '<b>' + esc(item.name) + '</b><br/>' + (percentMode ? pct(item.value) : fmt(item.value) + (unit ? ' ' + unit : ''));
        }
      },
      grid: { left: 8, right: 110, top: 12, bottom: 8, containLabel: true },
      xAxis: {
        type: 'value',
        max: percentMode ? 100 : undefined,
        axisLabel: { formatter: percentMode ? pct : compact },
        splitLine: { lineStyle: { type: 'dashed' } }
      },
      yAxis: {
        type: 'category',
        inverse: true,
        data: selected.map(function (row) { return row.name; }),
        axisLabel: { width: 180, overflow: 'truncate' }
      },
      series: [{
        type: 'bar',
        realtimeSort: true,
        universalTransition: true,
        barMaxWidth: 28,
        data: selected.map(function (row) { return n(row[key]); }),
        label: {
          show: true,
          position: 'right',
          formatter: function (params) { return percentMode ? pct(params.value) : compact(params.value); }
        }
      }]
    };
  }

  function verticalBar(rows, key, unit) {
    var selected = rows.slice();
    return {
      animationDuration: 750,
      animationDurationUpdate: 850,
      animationEasingUpdate: 'cubicInOut',
      tooltip: {
        trigger: 'axis',
        axisPointer: { type: 'shadow' },
        formatter: function (params) {
          var item = params && params[0];
          return item ? '<b>' + esc(item.name) + '</b><br/>' + fmt(item.value) + (unit ? ' ' + unit : '') : '';
        }
      },
      grid: { left: 50, right: 20, top: 24, bottom: 78, containLabel: true },
      xAxis: {
        type: 'category',
        data: selected.map(function (row) { return row.name; }),
        axisLabel: { interval: 0, rotate: 22, width: 95, overflow: 'truncate' }
      },
      yAxis: {
        type: 'value',
        axisLabel: { formatter: compact },
        splitLine: { lineStyle: { type: 'dashed' } }
      },
      series: [{
        type: 'bar',
        universalTransition: true,
        barMaxWidth: 42,
        data: selected.map(function (row) { return n(row[key]); }),
        label: { show: true, position: 'top', formatter: function (params) { return compact(params.value); } }
      }]
    };
  }

  function sunburstRows() {
    var typeRows = types();
    return categories().map(function (category) {
      var children = typeRows.filter(function (type) { return type.category_name === category.name; }).slice(0, 10).map(function (type) {
        return { name: type.name, value: type.total };
      });
      return { name: category.name, value: category.total, children: children.length ? children : undefined };
    });
  }

  function sunburst(rows) {
    return {
      animationDuration: 750,
      tooltip: { trigger: 'item', formatter: function (params) { return '<b>' + esc(params.name) + '</b><br/>' + fmt(params.value) + ' UMKM'; } },
      series: [{
        type: 'sunburst',
        radius: [0, '92%'],
        sort: 'desc',
        emphasis: { focus: 'ancestor' },
        nodeClick: 'rootToNode',
        label: { rotate: 'radial', minAngle: 6 },
        data: rows
      }]
    };
  }

  function doughnut(rows, key) {
    return {
      animationDuration: 750,
      tooltip: { trigger: 'item', formatter: function (params) { return params.name + '<br/>' + fmt(params.value) + ' UMKM (' + params.percent + '%)'; } },
      legend: { bottom: 0, type: 'scroll' },
      series: [{
        type: 'pie',
        radius: ['46%', '70%'],
        center: ['50%', '43%'],
        label: { show: true, formatter: function (params) { return params.name + '\n' + compact(params.value) + ' (' + params.percent + '%)'; } },
        data: rows.map(function (row) { return { name: row.name, value: n(row[key || 'total']) }; })
      }]
    };
  }

  function funnel(rows) {
    return {
      animationDuration: 750,
      tooltip: { trigger: 'item', formatter: function (params) { return params.name + '<br/>' + fmt(params.value) + ' UMKM'; } },
      series: [{
        type: 'funnel',
        left: '8%',
        top: 20,
        bottom: 20,
        width: '84%',
        minSize: '18%',
        maxSize: '100%',
        sort: 'descending',
        gap: 3,
        label: { show: true, position: 'inside', formatter: function (params) { return params.name + '\n' + compact(params.value); } },
        data: rows.map(function (row) { return { name: row.name, value: n(row.total) }; })
      }]
    };
  }

  function marketAccessSunburst(rows) {
    var selected = rows.slice(0, 8);
    var data = selected.map(function (row) {
      var children = arrayOf(row.methods)
        .filter(function (method) { return n(method.total) > 0; })
        .sort(function (a, b) { return n(b.total) - n(a.total); })
        .map(function (method) {
          return {
            name: method.name,
            value: n(method.total),
            percentage: n(method.percentage),
            category_name: row.name
          };
        });

      return {
        name: row.name,
        value: n(row.total_umkm),
        children: children
      };
    }).filter(function (row) {
      return row.value > 0 && row.children && row.children.length;
    });

    return {
      animationDuration: 800,
      animationDurationUpdate: 900,
      animationEasingUpdate: 'cubicInOut',
      tooltip: {
        trigger: 'item',
        formatter: function (params) {
          var path = arrayOf(params.treePathInfo)
            .map(function (item) { return item.name; })
            .filter(Boolean);

          var lines = [
            '<b>' + esc(path.join(' → ') || params.name || 'Akses Pasar') + '</b>',
            fmt(params.value) + ' UMKM'
          ];

          if (params.data && params.data.percentage !== undefined) {
            lines.push(pct(params.data.percentage) + ' dari kategori');
          }

          return lines.join('<br/>');
        }
      },
      series: [{
        type: 'sunburst',
        radius: ['10%', '92%'],
        sort: 'desc',
        nodeClick: 'rootToNode',
        emphasis: { focus: 'ancestor' },
        universalTransition: true,
        label: {
          minAngle: 8,
          overflow: 'truncate'
        },
        levels: [
          {},
          {
            r0: '10%',
            r: '48%',
            itemStyle: {
              borderWidth: 2,
              borderColor: '#ffffff'
            },
            label: {
              rotate: 0,
              fontSize: 11,
              overflow: 'truncate',
              width: 95
            }
          },
          {
            r0: '50%',
            r: '92%',
            itemStyle: {
              borderWidth: 1,
              borderColor: '#ffffff'
            },
            label: {
              position: 'outside',
              rotate: 'radial',
              fontSize: 10,
              minAngle: 8
            }
          }
        ],
        data: data
      }]
    };
  }
  function marketPercentStack(rows) {
    var selected = rows.slice(0, 8);
    var methodNames = arrayOf(marketAccess().method_names);
    if (!methodNames.length) methodNames = ['Konvensional', 'Digital', 'Both', 'Belum tersedia'];

    return {
      animationDuration: 750,
      tooltip: {
        trigger: 'axis',
        axisPointer: { type: 'shadow' },
        formatter: function (params) {
          var index = params && params.length ? params[0].dataIndex : 0;
          var row = selected[index] || {};
          var lines = ['<b>' + esc(row.name || 'Kategori') + '</b>', 'Total: ' + fmt(row.total_umkm) + ' UMKM'];
          arrayOf(params).forEach(function (item) { lines.push(esc(item.seriesName) + ': ' + pct(item.value)); });
          return lines.join('<br/>');
        }
      },
      legend: { top: 0, type: 'scroll' },
      grid: { left: 55, right: 20, top: 58, bottom: 82, containLabel: true },
      xAxis: {
        type: 'category',
        data: selected.map(function (row) { return row.name; }),
        axisLabel: { interval: 0, rotate: 24, width: 105, overflow: 'truncate' }
      },
      yAxis: { type: 'value', max: 100, axisLabel: { formatter: pct }, splitLine: { lineStyle: { type: 'dashed' } } },
      series: methodNames.map(function (methodName) {
        return {
          name: methodName,
          type: 'bar',
          stack: 'total',
          barMaxWidth: 42,
          emphasis: { focus: 'series' },
          data: selected.map(function (row) {
            var method = arrayOf(row.methods).find(function (item) { return item.name === methodName; });
            return method ? n(method.percentage) : 0;
          })
        };
      })
    };
  }

  function readinessStack(rows) {
    var selected = rows.slice(0, 10);
    return {
      animationDuration: 750,
      tooltip: {
        trigger: 'axis',
        axisPointer: { type: 'shadow' },
        formatter: function (params) {
          var row = selected[params && params.length ? params[0].dataIndex : 0] || {};
          return '<b>' + esc(row.name || 'Wilayah') + '</b><br/>'
            + 'Total: ' + fmt(row.total_umkm) + ' UMKM'
            + '<br/>Terpetakan: ' + fmt(row.mapped_total) + ' UMKM (' + pct(row.mapped_percentage) + ')'
            + '<br/>Belum terpetakan: ' + fmt(row.unmapped_total) + ' UMKM';
        }
      },
      legend: { top: 0 },
      grid: { left: 8, right: 30, top: 42, bottom: 8, containLabel: true },
      xAxis: { type: 'value', max: 100, axisLabel: { formatter: pct }, splitLine: { lineStyle: { type: 'dashed' } } },
      yAxis: { type: 'category', inverse: true, data: selected.map(function (row) { return row.name; }) },
      series: ['Terpetakan', 'Belum terpetakan'].map(function (seriesName) {
        return {
          name: seriesName,
          type: 'bar',
          stack: 'total',
          barMaxWidth: 26,
          label: {
            show: true,
            position: 'inside',
            formatter: function (params) { return n(params.value) >= 7 ? pct(params.value) : ''; }
          },
          data: selected.map(function (row) {
            var total = n(row.total_umkm);
            var count = seriesName === 'Terpetakan' ? n(row.mapped_total) : n(row.unmapped_total);
            return total > 0 ? (count / total) * 100 : 0;
          })
        };
      })
    };
  }

  function workspaceHtml() {
    return '<div class="card border-0 public-analytics-workspace mb-4" data-domain-upgrade-workspace data-domain-upgrade-stage="simplified-tahap3">'
      + '<div class="card-body p-3 p-xl-4">'
      + '<div class="d-flex flex-column flex-xl-row align-items-xl-start justify-content-between gap-3 mb-3 public-workspace-head">'
      + '<div><span>Visual Analitik Wilayah Aktif</span><strong>Enam domain indikator dengan visual yang saling melengkapi</strong><p>Struktur tetap Bootstrap-first; setiap chart menjawab satu pertanyaan analitik.</p></div>'
      + '<span class="badge text-bg-success rounded-pill align-self-start">Visual sederhana</span>'
      + '</div>'
      + '<ul class="nav nav-pills public-analytics-tabs mb-4" role="tablist">'
      + tabButton('sector', 'Profil Sektor', true)
      + tabButton('workforce', 'Tenaga Kerja')
      + tabButton('economy', 'Ekonomi Usaha')
      + tabButton('market', 'Akses Pasar')
      + tabButton('legality', 'Legalitas')
      + tabButton('quality', 'Mutu Data')
      + '</ul>'
      + '<div data-domain-upgrade-pane></div>'
      + '<aside class="alert alert-light border public-analytics-narrative mt-4 mb-0" data-domain-upgrade-narrative></aside>'
      + '</div></div>';
  }

  function tabButton(key, label, active) {
    return '<li class="nav-item" role="presentation"><button class="nav-link ' + (active ? 'active' : '') + '" type="button" data-domain-upgrade-tab="' + key + '">' + label + '</button></li>';
  }

  function renderDomain(key) {
    var pane = one('[data-domain-upgrade-pane]');
    if (!pane) return;

    disposeCharts();
    all('[data-domain-upgrade-tab]').forEach(function (button) {
      button.classList.toggle('active', button.getAttribute('data-domain-upgrade-tab') === key);
    });

    if (key === 'workforce') renderWorkforce(pane);
    else if (key === 'economy') renderEconomy(pane);
    else if (key === 'market') renderMarket(pane);
    else if (key === 'legality') renderLegality(pane);
    else if (key === 'quality') renderQuality(pane);
    else renderSector(pane);

    renderNarrative(key);
  }

  function renderSector(pane) {
    var category = categories()[0] || {};
    var type = types()[0] || {};
    var typeRows = types().slice(0, 10);

    pane.innerHTML = kpiGrid([
      { label: 'Total UMKM', value: fmt(business().total_umkm || summary().total), note: 'Jumlah pada wilayah aktif.' },
      { label: 'Kategori dominan', value: t(category.name), note: category.total ? fmt(category.total) + ' UMKM • ' + pct(category.percentage) : 'Data belum tersedia.' },
      { label: 'Jenis dominan', value: t(type.name), note: type.total ? fmt(type.total) + ' UMKM • ' + pct(type.percentage) : 'Data belum tersedia.' }
    ]) + '<div class="row g-3 g-xl-4">'
      + panel('Hirarki kategori dan jenis usaha', 'Membaca struktur kategori hingga jenis usaha.', 'sector-sunburst', 'col-xl-5', 'Struktur')
      + panel('Top 10 jenis usaha', 'Ranking jenis usaha dengan jumlah UMKM terbesar.', 'sector-ranking', 'col-xl-7', 'Ranking')
      + '</div>';

    chart('[data-domain-upgrade-chart="sector-sunburst"]', sunburst(sunburstRows()), sunburstRows(), 'value');
    chart('[data-domain-upgrade-chart="sector-ranking"]', horizontalRanking(typeRows, 'total', ' UMKM', false), typeRows, 'total');
  }

  function renderWorkforce(pane) {
    var data = workforce();
    var top = arrayOf(data.top_sectors).slice(0, 10);
    var buckets = arrayOf(data.buckets);
    var excludedNote = n(data.excluded_total) > 0
      ? fmt(data.excluded_total) + ' nilai di atas batas analitik dipisahkan dari agregat publik.'
      : 'Tidak ada nilai yang dipisahkan oleh batas kewajaran.';

    pane.innerHTML = kpiGrid([
      { label: 'Total pekerja terhitung', value: fmt(data.total_workers), note: 'Akumulasi setelah penerapan batas analitik pekerja.' },
      { label: 'Median pekerja', value: fmtDecimal(data.median_workers), note: 'Nilai tengah pekerja per UMKM.' },
      { label: 'Data pekerja terpakai', value: fmt(data.valid_filled_total || data.filled_total), note: excludedNote }
    ]) + '<div class="row g-3 g-xl-4">'
      + panel('Sektor penyerap pekerja', 'Ranking total pekerja terhitung berdasarkan jenis usaha.', 'workforce-ranking', 'col-xl-7', 'Ranking')
      + panel('Distribusi jumlah pekerja', 'Komposisi UMKM berdasarkan rentang jumlah pekerja.', 'workforce-buckets', 'col-xl-5', 'Distribusi')
      + '</div>';

    chart('[data-domain-upgrade-chart="workforce-ranking"]', horizontalRanking(top, 'total_workers', ' pekerja', false), top, 'total_workers');
    chart('[data-domain-upgrade-chart="workforce-buckets"]', horizontalRanking(buckets, 'total', ' UMKM', false), buckets, 'total');
  }

  function renderEconomy(pane) {
    var data = economy();
    var capital = arrayOf(data.capital_buckets);
    var sales = arrayOf(data.annual_sales_buckets);

    pane.innerHTML = kpiGrid([
      { label: 'Modal terdata', value: fmt(data.capital_filled), note: 'UMKM dengan nilai modal usaha tersedia dari sumber.' },
      { label: 'Penjualan terdata', value: fmt(data.annual_sales_filled), note: 'UMKM dengan nilai penjualan tahunan tersedia dari sumber.' },
      { label: 'Sumber pinjaman', value: fmt(data.loan_source_filled), note: 'UMKM dengan sumber pinjaman terdata.' }
    ]) + '<div class="row g-3 g-xl-4">'
      + panel('Distribusi rentang modal', 'Distribusi nilai modal yang tersedia; nilai sumber tidak dikoreksi otomatis.', 'economy-capital', 'col-xl-6', 'Distribusi')
      + panel('Distribusi rentang penjualan', 'Distribusi nilai penjualan yang tersedia; nilai sumber tidak dikoreksi otomatis.', 'economy-sales', 'col-xl-6', 'Distribusi')
      + '</div>';

    chart('[data-domain-upgrade-chart="economy-capital"]', verticalBar(capital, 'total', ' UMKM'), capital, 'total');
    chart('[data-domain-upgrade-chart="economy-sales"]', verticalBar(sales, 'total', ' UMKM'), sales, 'total');
  }

  function renderMarket(pane) {
    var dominant = marketingMethods()[0] || {};
    var rows = marketCategoryRows().slice(0, 8);
    var categoryCount = n(marketAccess().category_count) || marketCategoryRows().length;
    var methodCoverageTotal = n(marketAccess().method_coverage_total);

    pane.innerHTML = kpiGrid([
      { label: 'Metode dominan', value: t(dominant.name), note: dominant.total ? fmt(dominant.total) + ' UMKM • ' + pct(dominant.percentage) : 'Data belum tersedia.' },
      { label: 'Kategori tercakup', value: fmt(categoryCount), note: 'Jumlah kategori yang memiliki relasi metode pemasaran.' },
      { label: 'UMKM metode terdata', value: fmt(methodCoverageTotal), note: 'UMKM dengan metode pemasaran yang teridentifikasi.' }
    ]) + '<div class="row g-3 g-xl-4">'
      + panel('Struktur kategori dan metode pemasaran', 'Hirarki kategori usaha menuju metode pemasaran.', 'market-composition', 'col-xl-7', 'Sunburst')
      + panel('Adopsi digital per kategori', 'Ranking proporsi Digital + Both pada setiap kategori.', 'market-digital', 'col-xl-5', 'Ranking')
      + '</div>';

    chart('[data-domain-upgrade-chart="market-composition"]', marketAccessSunburst(rows), rows, 'total_umkm');
    chart('[data-domain-upgrade-chart="market-digital"]', horizontalRanking(rows, 'digital_percentage', '', true), rows, 'digital_percentage');
  }

  function renderLegality(pane) {
    var data = legality();
    var stages = arrayOf(data.stages);
    var statusRows = [
      { name: 'Legalitas terdata', total: n(data.legalities_total) },
      { name: 'Belum teridentifikasi', total: Math.max(0, n(data.total_umkm || summary().total) - n(data.legalities_total)) }
    ];

    pane.innerHTML = kpiGrid([
      { label: 'Legalitas terdata', value: fmt(data.legalities_total), note: pct(data.legalities_percentage) + ' dari UMKM aktif.' },
      { label: 'NIB teridentifikasi', value: fmt(data.nib_identified_total), note: 'Jumlah UMKM dengan NIB teridentifikasi tanpa menampilkan nomornya.' },
      { label: 'Belum teridentifikasi', value: fmt(data.unidentified_total), note: 'UMKM tanpa catatan legalitas pada data agregat.' }
    ]) + '<div class="row g-3 g-xl-4">'
      + panel('Cakupan formalisasi usaha', 'Perbandingan total UMKM, legalitas terdata, dan NIB teridentifikasi.', 'legality-funnel', 'col-xl-7', 'Tahapan')
      + panel('Status legalitas agregat', 'Proporsi legalitas terdata dan belum teridentifikasi.', 'legality-status', 'col-xl-5', 'Proporsi')
      + '</div>';

    chart('[data-domain-upgrade-chart="legality-funnel"]', funnel(stages), stages, 'total');
    chart('[data-domain-upgrade-chart="legality-status"]', doughnut(statusRows, 'total'), statusRows, 'total');
  }

  function renderQuality(pane) {
    var location = readiness().location || {};
    var areaRows = readinessAreaRows().slice(0, 10);
    var notes = qualityNoteRows().slice(0, 10);
    var qualitySummary = readiness().quality_summary || {};
    var noteTotal = n(qualitySummary.flag_count);
    var affectedUmkm = n(qualitySummary.affected_umkm_count);

    pane.innerHTML = kpiGrid([
      { label: 'Terpetakan', value: fmt(location.mapped_total), note: pct(location.mapped_percentage) + ' dari wilayah aktif.' },
      { label: 'Belum terpetakan', value: fmt(location.unmapped_total), note: 'Perlu pengayaan koordinat.' },
      { label: 'Catatan mutu terbuka', value: fmt(noteTotal), note: fmt(affectedUmkm) + ' UMKM memiliki sedikitnya satu catatan mutu.' }
    ]) + '<div class="row g-3 g-xl-4">'
      + panel('Kesiapan spasial per subwilayah', 'Perbandingan terpetakan dan belum terpetakan dalam persentase.', 'quality-readiness', 'col-xl-7', '100% stacked')
      + panel('Jenis kekurangan data', 'Ranking jumlah UMKM terdampak menurut kelompok catatan mutu.', 'quality-notes', 'col-xl-5', 'Ranking')
      + '</div>';

    chart('[data-domain-upgrade-chart="quality-readiness"]', readinessStack(areaRows), areaRows, 'total_umkm');
    chart('[data-domain-upgrade-chart="quality-notes"]', horizontalRanking(notes, 'total', ' UMKM', false), notes, 'total');
  }

  function renderNarrative(key) {
    var node = one('[data-domain-upgrade-narrative]');
    if (!node) return;

    var category = categories()[0] || {};
    var type = types()[0] || {};
    var method = marketingMethods()[0] || {};
    var workforceData = workforce();
    var economyData = economy();
    var legalityData = legality();
    var location = readiness().location || {};
    var text = '';

    if (key === 'workforce') {
      text = 'Median pekerja pada data yang terpakai adalah <b>' + esc(fmtDecimal(workforceData.median_workers)) + '</b>. '
        + 'Sebanyak <b>' + esc(fmt(workforceData.valid_filled_total || workforceData.filled_total)) + '</b> UMKM memiliki data pekerja yang digunakan dalam agregat.';
      if (n(workforceData.excluded_total) > 0) {
        text += ' Terdapat <b>' + esc(fmt(workforceData.excluded_total)) + '</b> nilai di atas batas analitik yang dipisahkan dari agregat publik dan tetap memerlukan audit.';
      }
    } else if (key === 'economy') {
      text = 'Data ekonomi yang dapat dibaca mencakup <b>' + esc(fmt(economyData.capital_filled)) + '</b> UMKM dengan modal terdata dan <b>'
        + esc(fmt(economyData.annual_sales_filled)) + '</b> UMKM dengan penjualan tahunan terdata. Nilai sumber dipertahankan apa adanya dan catatan mutu ditangani terpisah.';
    } else if (key === 'market') {
      var marketRows = marketCategoryRows().slice().sort(function (a, b) { return n(b.digital_percentage) - n(a.digital_percentage); });
      var topDigital = marketRows[0] || {};
      text = 'Metode pemasaran dominan adalah <b>' + esc(t(method.name)) + '</b>. '
        + (topDigital.name ? 'Kategori dengan proporsi adopsi digital tertinggi adalah <b>' + esc(topDigital.name) + '</b> sebesar <b>' + esc(pct(topDigital.digital_percentage)) + '</b>.' : 'Data adopsi digital per kategori belum tersedia.');
    } else if (key === 'legality') {
      text = 'Legalitas telah teridentifikasi pada <b>' + esc(fmt(legalityData.legalities_total)) + '</b> UMKM atau <b>'
        + esc(pct(legalityData.legalities_percentage)) + '</b> dari wilayah aktif. Nomor legalitas tidak ditampilkan pada area publik.';
    } else if (key === 'quality') {
      text = 'Keterpetaan lokasi berada pada <b>' + esc(pct(location.mapped_percentage)) + '</b>. Sebanyak <b>'
        + esc(fmt(location.unmapped_total)) + '</b> UMKM masih memerlukan pengayaan koordinat.';
    } else {
      text = 'Pada <b>' + esc(contextLabel()) + '</b>, kategori terbesar adalah <b>' + esc(t(category.name)) + '</b>'
        + (type.name ? ' dengan jenis usaha dominan <b>' + esc(t(type.name)) + '</b>.' : '.');
    }

    node.innerHTML = '<div class="d-flex align-items-start gap-2">'
      + '<span class="badge text-bg-light rounded-pill">Insight</span>'
      + '<div><strong class="d-block mb-1">Ringkasan wilayah aktif</strong><p class="mb-0">' + text + '</p></div>'
      + '</div>';
  }

  function upgrade() {
    var target = one('[data-public-analytics-render-target]');
    if (!target || !analytics()) return;

    var existing = one('[data-domain-upgrade-workspace]', target);
    if (existing && existing.dataset.domainUpgradeStage === 'simplified-tahap3') return;

    var legacyWorkspace = one('.public-analytics-workspace', target);
    if (!legacyWorkspace) return;

    legacyWorkspace.insertAdjacentHTML('beforebegin', workspaceHtml());
    legacyWorkspace.remove();
    renderDomain('sector');
  }

  function scheduleUpgrade(delay) {
    if (scheduledUpgrade) window.clearTimeout(scheduledUpgrade);
    scheduledUpgrade = window.setTimeout(function () {
      scheduledUpgrade = null;
      attachTargetObserver();
      upgrade();
    }, typeof delay === 'number' ? delay : 80);
  }

  function attachTargetObserver() {
    var target = one('[data-public-analytics-render-target]');
    if (!target) return false;
    if (target.dataset.domainSimplifiedObserved === 'true') return true;

    target.dataset.domainSimplifiedObserved = 'true';
    var observer = new MutationObserver(function () { scheduleUpgrade(60); });
    observer.observe(target, { childList: true, subtree: true });
    return true;
  }

  function bind() {
    document.addEventListener('click', function (event) {
      var domainButton = event.target && event.target.closest ? event.target.closest('[data-domain-upgrade-tab]') : null;
      if (domainButton) {
        event.preventDefault();
        renderDomain(domainButton.getAttribute('data-domain-upgrade-tab') || 'sector');
        return;
      }

      var showButton = event.target && event.target.closest ? event.target.closest('[data-public-analytics-show]') : null;
      if (showButton) {
        window.setTimeout(function () { scheduleUpgrade(80); }, 260);
        window.setTimeout(function () { scheduleUpgrade(80); }, 650);
        window.setTimeout(function () { scheduleUpgrade(80); }, 1200);
      }
    });

    document.addEventListener('shown.bs.tab', function () { scheduleUpgrade(120); });
    document.addEventListener('umkm:landing-region:changed', function () { scheduleUpgrade(220); });
    document.addEventListener('umkm:landing-analytics:ready', function () { scheduleUpgrade(220); });

    if (document.body) {
      var bodyObserver = new MutationObserver(function () {
        attachTargetObserver();
        if (one('.public-analytics-workspace') || one('[data-public-analytics-render-target]')) scheduleUpgrade(90);
      });
      bodyObserver.observe(document.body, { childList: true, subtree: true });
    }

    window.addEventListener('resize', function () {
      chartRegistry.forEach(function (chartItem) {
        try { chartItem.resize(); } catch (error) {}
      });
    });
  }

  function boot() {
    bind();
    attachTargetObserver();
    scheduleUpgrade(300);

    bootPoll = window.setInterval(function () {
      bootPollCount += 1;
      attachTargetObserver();
      scheduleUpgrade(80);
      if (bootPollCount >= 40 || one('[data-domain-upgrade-workspace]')) {
        window.clearInterval(bootPoll);
        bootPoll = null;
      }
    }, 300);

    if (window.console && typeof window.console.info === 'function') {
      window.console.info('SISFODA UMKM: simplified visual analytics TAHAP3 ready');
    }
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot, { once: true });
  else boot();
}());
