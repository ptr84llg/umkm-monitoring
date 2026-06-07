(function () {
  'use strict';

  var API_URL = '/api/public/landing-preview/data';
  var CARD_KEYS = ['total_umkm', 'mapped_umkm', 'dominant_category', 'active_regions'];
  var state = { payload: null, loading: false, ready: false };

  function toArray(value) { return Array.prototype.slice.call(value || []); }
  function escapeHtml(value) {
    return String(value ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
  }
  function clampPercent(value) { return Math.max(0, Math.min(100, Number(value || 0))); }
  function widthClass(value) {
    var percent = clampPercent(value);
    if (percent <= 0) return 'w-0';
    if (percent <= 25) return 'w-25';
    if (percent <= 50) return 'w-50';
    if (percent <= 75) return 'w-75';
    return 'w-100';
  }
  function cardNode(key) { return document.querySelector('[data-public-aggregate-card="' + key + '"]'); }
  function normalizePayload(payload) {
    if (!payload) return null;
    if (payload.payload) return normalizePayload(payload.payload);
    if (payload.ok === true && payload.data) return payload;
    if (payload.data && payload.data.aggregate_cards) return { ok: true, data: payload.data };
    if (payload.aggregate_cards) return { ok: true, data: payload };
    return null;
  }
  function payloadCards(payload) {
    var safe = normalizePayload(payload);
    var data = safe && safe.data ? safe.data : {};
    var map = data.aggregate_card_map || {};
    var list = Array.isArray(data.aggregate_cards) ? data.aggregate_cards : [];
    if (Object.keys(map).length < 1) {
      list.forEach(function (item) { if (item && item.key) map[item.key] = item; });
    }
    return map;
  }
  function query(extra) {
    var data = state.payload && state.payload.data ? state.payload.data : {};
    var region = data.region || {};
    var selection = data.selection || {};
    var q = new URLSearchParams();
    q.set('city_code', selection.city_code || region.city_code || '16.73');
    q.set('scope', 'city');
    if (selection.district_code || region.district_code) {
      q.set('district_code', selection.district_code || region.district_code);
      q.set('scope', 'district');
    }
    if (selection.village_code || region.village_code) {
      q.set('village_code', selection.village_code || region.village_code);
      q.set('scope', 'village');
    }
    Object.keys(extra || {}).forEach(function (key) {
      if (extra[key] !== null && extra[key] !== undefined && String(extra[key]) !== '') q.set(key, extra[key]);
    });
    return q;
  }
  function requestJson(q) {
    if (!(window.UMKM && window.UMKM.ajax && typeof window.UMKM.ajax.get === 'function')) {
      return Promise.reject(new Error('AJAX internal belum siap.'));
    }
    return Promise.resolve(window.UMKM.ajax.get(API_URL + '?' + q.toString(), {
      headers: { 'X-UMKM-Preview': 'landing-public-safe', 'X-UMKM-Internal-Request': '1' }
    })).then(function (payload) { return payload && payload.payload ? payload.payload : payload; });
  }
  function firstByText(card, text) {
    return toArray(card.querySelectorAll('*')).find(function (element) {
      return String(element.textContent || '').replace(/\s+/g, ' ').trim() === text;
    }) || null;
  }

function aggregateIconMeta(key) {
    var icons = {
      total_umkm: {
        className: 'is-green',
        path: 'M4 5h16v14H4V5Zm2 2v10h12V7H6Zm2 8h2v-4H8v4Zm3 0h2V9h-2v6Zm3 0h2v-2h-2v2Z'
      },
      mapped_umkm: {
        className: 'is-blue',
        path: 'M12 2.75A7.25 7.25 0 0 0 4.75 10c0 5.15 7.25 11.25 7.25 11.25S19.25 15.15 19.25 10A7.25 7.25 0 0 0 12 2.75Zm0 9.65a2.4 2.4 0 1 1 0-4.8 2.4 2.4 0 0 1 0 4.8Z'
      },
      dominant_category: {
        className: 'is-gold',
        path: 'M12 3 3 7.5 12 12l9-4.5L12 3Zm-7.5 8.25L12 15.5l7.5-4.25L21 12l-9 5-9-5 1.5-.75Zm0 4L12 19.5l7.5-4.25L21 16l-9 5-9-5 1.5-.75Z'
      },
      active_regions: {
        className: 'is-purple',
        path: 'M4 5h16v4H4V5Zm0 6h16v4H4v-4Zm0 6h16v2H4v-2Z'
      }
    };

    return icons[key] || icons.total_umkm;
  }

  function clearCardIcon(icon) {
    if (!icon) return;
    icon.classList.remove('is-green', 'is-blue', 'is-gold', 'is-purple');
    icon.innerHTML = '';
  }

  function renderCardIcon(icon, key) {
    if (!icon) return;
    var meta = aggregateIconMeta(key);
    icon.classList.remove('is-green', 'is-blue', 'is-gold', 'is-purple');
    icon.classList.add(meta.className);
    icon.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="' + meta.path + '"/></svg>';
  }

function ensureTargets(card) {
    if (!card) return {};
    var targets = {
      icon: card.querySelector('[data-public-aggregate-icon], .hero-stat-icon'),
      badge: card.querySelector('[data-public-aggregate-badge]'),
      label: card.querySelector('[data-public-aggregate-label]'),
      value: card.querySelector('[data-public-aggregate-value]'),
      context: card.querySelector('[data-public-aggregate-context]'),
      percent: card.querySelector('[data-public-aggregate-percent]'),
      progress: card.querySelector('[data-public-aggregate-progress]'),
      footerLabel: card.querySelector('[data-public-aggregate-footer-label]'),
      footerValue: card.querySelector('[data-public-aggregate-footer-value]')
    };
    if (!targets.badge) {
      targets.badge = card.querySelector('.badge, [class*="badge"]');
      if (targets.badge) targets.badge.setAttribute('data-public-aggregate-badge', '');
    }
    if (!targets.label) {
      targets.label = firstByText(card, 'Memuat agregat');
      if (targets.label) targets.label.setAttribute('data-public-aggregate-label', '');
    }
    if (!targets.value) {
      targets.value = firstByText(card, '—') || card.querySelector('strong, .h1, .h2, .h3, .display-1, .display-2, .display-3, .display-4');
      if (targets.value) targets.value.setAttribute('data-public-aggregate-value', '');
    }
    if (!targets.context) {
      targets.context = firstByText(card, 'Mengambil agregat publik...') || card.querySelector('p, small, .text-muted');
      if (targets.context) targets.context.setAttribute('data-public-aggregate-context', '');
    }
    if (!targets.percent) {
      targets.percent = firstByText(card, 'Menunggu sinkronisasi');
      if (targets.percent) targets.percent.setAttribute('data-public-aggregate-percent', '');
    }
    if (!targets.progress) {
      targets.progress = card.querySelector('.stat-progress-fill, .progress-bar, [class*="progress"] > span, [class*="progress"] > b, [class*="progress"] > i');
      if (targets.progress) targets.progress.setAttribute('data-public-aggregate-progress', '');
    }
    if (!targets.footerLabel) {
      targets.footerLabel = card.querySelector('.stat-card-foot span');
      if (targets.footerLabel) targets.footerLabel.setAttribute('data-public-aggregate-footer-label', '');
    }
    if (!targets.footerValue) {
      targets.footerValue = card.querySelector('.stat-card-foot b');
      if (targets.footerValue) targets.footerValue.setAttribute('data-public-aggregate-footer-value', '');
    }
    return targets;
  }

function unmountRegionActions() {
    var mounts = toArray(document.querySelectorAll('[data-public-region-action-mount]'));
    mounts.forEach(function (mount) {
      mount.innerHTML = '';
      mount.hidden = true;
      mount.setAttribute('aria-hidden', 'true');
      mount.removeAttribute('data-public-region-action-ready');
    });
  }

function aggregateCardsReadyForRegionActions() {
    if (!state.ready || state.loading) return false;

    return CARD_KEYS.every(function (key) {
      var card = cardNode(key);
      return !!(
        card &&
        card.getAttribute('data-public-aggregate-ready') === 'true' &&
        card.getAttribute('data-public-aggregate-detail') === key &&
        card.classList.contains('public-aggregate-clickable')
      );
    });
  }

function mountRegionActions() {
    if (!aggregateCardsReadyForRegionActions()) {
      unmountRegionActions();
      return;
    }

    var mounts = toArray(document.querySelectorAll('[data-public-region-action-mount]'));
    if (!mounts.length) return;

    mounts.forEach(function (mount) {
      var type = mount.getAttribute('data-public-region-action-mount');
      if (!type) return;

      if (mount.getAttribute('data-public-region-action-ready') !== 'true') {
        if (type === 'hero-primary') {
          mount.innerHTML = [
            '<button type="button" class="btn btn-danger btn-lg landing-main-btn" data-region-open data-region-modal-open>',
            '  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.75A7.25 7.25 0 0 0 4.75 10c0 5.15 7.25 11.25 7.25 11.25S19.25 15.15 19.25 10A7.25 7.25 0 0 0 12 2.75Zm0 9.65a2.4 2.4 0 1 1 0-4.8 2.4 2.4 0 0 1 0 4.8Z"/></svg>',
            '  <span>Pilih Wilayah Preview</span>',
            '</button>'
          ].join('');
        }

        if (type === 'map-toolbar') {
          mount.innerHTML = [
            '<button type="button" class="btn btn-sm btn-light public-map-select" data-region-open data-region-modal-open>',
            '  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.75A7.25 7.25 0 0 0 4.75 10c0 5.15 7.25 11.25 7.25 11.25S19.25 15.15 19.25 10A7.25 7.25 0 0 0 12 2.75Z"/></svg>',
            '  <span>Pilih Wilayah</span>',
            '</button>',
            '<button type="button" class="btn btn-sm btn-light public-map-filter" data-region-open data-region-modal-open aria-label="Filter wilayah">',
            '  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16l-6 7v5l-4 2v-7L4 5Z"/></svg>',
            '</button>'
          ].join('');
        }

        mount.setAttribute('data-public-region-action-ready', 'true');
      }

      mount.hidden = false;
      mount.removeAttribute('aria-hidden');
    });
  }

function setInitialNoData() {
    unmountRegionActions();
    state.loading = false;
    state.ready = false;
    CARD_KEYS.forEach(function (key) {
      var card = cardNode(key);
      if (!card) return;
      var targets = ensureTargets(card);
      card.setAttribute('data-public-aggregate-ready', 'false');
      card.setAttribute('aria-disabled', 'true');
      card.removeAttribute('role');
      card.removeAttribute('tabindex');
      card.removeAttribute('data-public-aggregate-detail');
      card.classList.remove('is-ready', 'is-loading', 'public-aggregate-clickable');
      card.classList.add('is-limited');
      clearCardIcon(targets.icon);
      if (targets.badge) targets.badge.textContent = 'Belum dimuat';
      if (targets.value) targets.value.textContent = '—';
      if (targets.context) targets.context.textContent = 'Data belum dimuat. Menunggu konteks wilayah aktif.';
      if (targets.percent) targets.percent.textContent = '0,00%';
      if (targets.progress) {
        targets.progress.style.width = '0%';
        targets.progress.className = targets.progress.className.replace(/progress-w-\d+/g, '').trim();
        targets.progress.classList.add('progress-w-0');
      }
      if (targets.footerLabel) targets.footerLabel.textContent = 'Status';
      if (targets.footerValue) targets.footerValue.textContent = 'Data belum dimuat';
    });
  }

function setLoading() {
    unmountRegionActions();
    state.loading = true;
    CARD_KEYS.forEach(function (key) {
      var card = cardNode(key);
      var targets = ensureTargets(card);
      if (!card) return;
      card.setAttribute('data-public-aggregate-ready', 'false');
      card.setAttribute('aria-disabled', 'true');
      card.removeAttribute('data-public-aggregate-detail');
      card.classList.remove('is-ready', 'public-aggregate-clickable');
      card.classList.add('is-loading');
      clearCardIcon(targets.icon);
      if (targets.badge) targets.badge.textContent = 'Memuat';
      if (targets.label) targets.label.textContent = 'Memuat agregat';
      if (targets.value) targets.value.textContent = '—';
      if (targets.context) targets.context.textContent = 'Mengambil agregat publik...';
      if (targets.percent) targets.percent.textContent = 'Menunggu sinkronisasi';
      if (targets.progress) {
        targets.progress.className = (targets.progress.classList.contains('stat-progress-fill') ? 'stat-progress-fill ' : 'progress-bar ') + 'w-0';
        targets.progress.setAttribute('aria-valuenow', '0');
      }
      if (targets.footerLabel) targets.footerLabel.textContent = 'Data agregat';
      if (targets.footerValue) targets.footerValue.textContent = 'Memuat';
    });
  }
  function setError(message) {
    unmountRegionActions();
    state.loading = false;
    state.ready = false;
    CARD_KEYS.forEach(function (key) {
      var card = cardNode(key);
      var targets = ensureTargets(card);
      if (!card) return;
      card.setAttribute('data-public-aggregate-ready', 'false');
      card.setAttribute('aria-disabled', 'true');
      card.removeAttribute('data-public-aggregate-detail');
      card.classList.remove('is-ready', 'is-loading', 'public-aggregate-clickable');
      card.classList.add('is-limited');
      clearCardIcon(targets.icon);
      if (targets.badge) targets.badge.textContent = 'Terbatas';
      if (targets.context) targets.context.textContent = message || 'Agregat belum dapat dimuat.';
      if (targets.value) targets.value.textContent = '—';
      if (targets.percent) targets.percent.textContent = 'Menunggu data';
      if (targets.progress) {
        targets.progress.className = (targets.progress.classList.contains('stat-progress-fill') ? 'stat-progress-fill ' : 'progress-bar ') + 'w-0';
        targets.progress.setAttribute('aria-valuenow', '0');
      }
      if (targets.footerLabel) targets.footerLabel.textContent = 'Status';
      if (targets.footerValue) targets.footerValue.textContent = 'Terbatas';
    });
  }
  function applyCard(cardData) {
    if (!cardData || !cardData.key) return;
    var card = cardNode(cardData.key);
    var targets = ensureTargets(card);
    if (!card) return;
    card.setAttribute('data-public-aggregate-ready', 'true');
    card.setAttribute('aria-disabled', 'false');
    card.setAttribute('role', 'button');
    card.setAttribute('tabindex', '0');
    card.setAttribute('data-public-aggregate-detail', cardData.key);
    card.classList.remove('is-loading', 'is-limited');
    card.classList.add('is-ready', 'public-aggregate-clickable');
    var progress = clampPercent(cardData.progress_percent);
    renderCardIcon(targets.icon, cardData.key);
    if (targets.badge) targets.badge.textContent = String(cardData.badge || cardData.label || 'Agregat');
    if (targets.label) targets.label.textContent = String(cardData.label || '');
    if (targets.value) targets.value.textContent = String(cardData.value_text ?? cardData.value ?? '0');
    if (targets.context) targets.context.textContent = String(cardData.context || '');
    if (targets.percent) targets.percent.textContent = String(cardData.percent_text || '');
    if (targets.progress) {
      targets.progress.className = (targets.progress.classList.contains('stat-progress-fill') ? 'stat-progress-fill ' : 'progress-bar ') + widthClass(progress);
      targets.progress.setAttribute('aria-valuenow', String(progress));
    }
    if (targets.footerLabel) targets.footerLabel.textContent = String(cardData.footer_label || 'Data agregat');
    if (targets.footerValue) targets.footerValue.textContent = String(cardData.footer_value || 'Aman untuk publik');
  }
  function applyPayload(payload) {
    var safe = normalizePayload(payload);
    if (!safe || !safe.data || !safe.data.aggregate_cards) {
      setError('Agregat belum tersedia untuk konteks ini.');
      return;
    }
    state.payload = safe;
    state.loading = false;
    state.ready = true;
    window.PublicLandingAggregatePayload = safe;
    var cards = payloadCards(safe);
    CARD_KEYS.forEach(function (key) { applyCard(cards[key]); });
    window.requestAnimationFrame(function () { mountRegionActions(); });
  }
  function loadInitial(retry) {
    if (state.loading) return;
    if (!(window.UMKM && window.UMKM.ajax && typeof window.UMKM.ajax.get === 'function')) {
      if ((retry || 0) < 12) {
        window.setTimeout(function () { loadInitial((retry || 0) + 1); }, 250);
        return;
      }
      setError('AJAX internal belum siap.');
      return;
    }
    setLoading();
    requestJson(new URLSearchParams({ city_code: '16.73', scope: 'city' }))
      .then(applyPayload)
      .catch(function (error) { setError(error && error.message ? error.message : 'Agregat belum dapat dimuat.'); });
  }

function createModal(cardKey) {
    var modal = document.createElement('div');
    modal.className = 'modal fade public-aggregate-detail-modal';
    modal.tabIndex = -1;
    modal.setAttribute('aria-hidden', 'true');
    modal.setAttribute('data-public-aggregate-modal', cardKey);
    modal.innerHTML = [
      '<div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">',
      '  <div class="modal-content border-0 shadow-lg rounded-4">',
      '    <div class="modal-header border-0 pb-2">',
      '      <div><p class="text-muted small mb-1" data-public-aggregate-detail-subtitle>Rincian agregat wilayah</p><h5 class="modal-title mb-0 fw-bold" data-public-aggregate-detail-title>Rincian Agregat</h5></div>',
      '      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>',
      '    </div>',
      '    <div class="modal-body pt-2">',
      '      <div data-public-aggregate-detail-content></div>',
      '    </div>',
      '    <div class="modal-footer border-0 pt-0"><button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button></div>',
      '  </div>',
      '</div>'
    ].join('');
    document.body.appendChild(modal);
    modal.addEventListener('hidden.bs.modal', function () { modal.remove(); }, { once: true });
    return modal;
  }

function showModal(modal) {
    if (window.bootstrap && window.bootstrap.Modal) window.bootstrap.Modal.getOrCreateInstance(modal).show();
  }

function renderList(items, emptyText) {
    if (!Array.isArray(items) || items.length === 0) {
      return '<div class="aggregate-detail-empty">' + escapeHtml(emptyText || 'Data belum tersedia.') + '</div>';
    }

    return '<div class="aggregate-detail-list">' + items.map(function (item) {
      return '<div class="aggregate-detail-list-item"><div><strong>' + escapeHtml(item.label || '-') + '</strong><small>' + escapeHtml(item.meta || '') + '</small></div><span>' + escapeHtml(item.value || '0') + '</span></div>';
    }).join('') + '</div>';
  }

function renderDetail(modal, detail) {
    var content = modal.querySelector('[data-public-aggregate-detail-content]');
    var title = modal.querySelector('[data-public-aggregate-detail-title]');
    var subtitle = modal.querySelector('[data-public-aggregate-detail-subtitle]');
    var summary = Array.isArray(detail.summary) ? detail.summary : [];
    var sections = Array.isArray(detail.sections) ? detail.sections : [];
    var hero = detail.card || {};
    var primaryValue = hero.value_text || hero.value || (summary[0] ? summary[0].value : '—');
    var primaryLabel = hero.label || (summary[0] ? summary[0].label : 'Agregat');
    var primaryContext = hero.context || hero.percent_text || '';

    if (title) title.textContent = detail.title || 'Rincian Agregat';
    if (subtitle) subtitle.textContent = detail.subtitle || 'Agregat aman untuk publik';

    if (content) {
      var summaryHtml = summary.map(function (item) {
        return '<div class="aggregate-detail-chip"><small>' + escapeHtml(item.label || '-') + '</small><strong>' + escapeHtml(item.value || '-') + '</strong><span>' + escapeHtml(item.meta || '') + '</span></div>';
      }).join('');

      var sectionsHtml = sections.map(function (section) {
        return '<section class="aggregate-detail-section"><h6>' + escapeHtml(section.title || 'Rincian') + '</h6>' + renderList(section.items, section.empty) + '</section>';
      }).join('');

      content.innerHTML = [
        '<section class="aggregate-detail-hero">',
        '  <div><span>' + escapeHtml(primaryLabel) + '</span><strong>' + escapeHtml(primaryValue) + '</strong></div>',
        '  <p>' + escapeHtml(primaryContext || 'Data agregat pada wilayah aktif.') + '</p>',
        '</section>',
        '<section class="aggregate-detail-chip-grid">',
        summaryHtml || '<div class="aggregate-detail-empty">Ringkasan belum tersedia.</div>',
        '</section>',
        sectionsHtml,
        '<div class="aggregate-detail-note">' + escapeHtml(detail.public_safe_note || 'Rincian yang ditampilkan bersifat agregat aman untuk publik.') + '</div>'
      ].join('');
    }
  }

function renderDetailError(modal, message) {
    var content = modal.querySelector('[data-public-aggregate-detail-content]');
    if (content) {
      content.innerHTML = '<div class="alert alert-warning mb-0">' + escapeHtml(message || 'Rincian agregat belum dapat dimuat.') + '</div>';
    }
  }

function openDetail(cardKey) {
    var card = cardNode(cardKey);
    if (!card || card.getAttribute('data-public-aggregate-ready') !== 'true') return;
    if (card.getAttribute('data-public-aggregate-detail-loading') === 'true') return;

    card.setAttribute('data-public-aggregate-detail-loading', 'true');

    requestJson(query({ detail_card: cardKey }))
      .then(function (payload) {
        var safe = normalizePayload(payload);
        var detail = safe && safe.data ? safe.data.detail_card : null;
        if (!detail) throw new Error('Rincian agregat tidak tersedia.');
        state.payload = safe;
        window.PublicLandingAggregatePayload = safe;
        var modal = createModal(cardKey);
        renderDetail(modal, detail);
        showModal(modal);
      })
      .catch(function (error) {
        var modal = createModal(cardKey);
        renderDetailError(modal, error && error.message ? error.message : 'Rincian agregat gagal dimuat.');
        showModal(modal);
      })
      .then(function () {
        card.removeAttribute('data-public-aggregate-detail-loading');
      });
  }

function bindClicks() {
    document.addEventListener('click', function (event) {
      var trigger = event.target && event.target.closest ? event.target.closest('[data-public-aggregate-detail]') : null;
      if (!trigger) return;
      event.preventDefault();
      openDetail(trigger.getAttribute('data-public-aggregate-detail'));
    });
    document.addEventListener('keydown', function (event) {
      if (event.key !== 'Enter' && event.key !== ' ') return;
      var trigger = event.target && event.target.closest ? event.target.closest('[data-public-aggregate-detail]') : null;
      if (!trigger) return;
      event.preventDefault();
      openDetail(trigger.getAttribute('data-public-aggregate-detail'));
    });
  }
  function wrapAjaxWhenReady(retry) {
    if (!(window.UMKM && window.UMKM.ajax && typeof window.UMKM.ajax.get === 'function')) {
      if ((retry || 0) < 12) window.setTimeout(function () { wrapAjaxWhenReady((retry || 0) + 1); }, 250);
      return;
    }
    if (window.UMKM.ajax.__publicAggregateCardsWrapped) return;
    var originalGet = window.UMKM.ajax.get.bind(window.UMKM.ajax);
    window.UMKM.ajax.get = function (url, options) {
      var result = originalGet(url, options);
      if (String(url || '').indexOf(API_URL) !== -1) {
        Promise.resolve(result).then(applyPayload).catch(function () { return null; });
      }
      return result;
    };
    window.UMKM.ajax.__publicAggregateCardsWrapped = true;
  }
  function boot() {
    setInitialNoData();
    CARD_KEYS.forEach(function (key) { ensureTargets(cardNode(key)); });
    bindClicks();
    wrapAjaxWhenReady(0);
    loadInitial(0);
  }
  window.umkmApplyPublicLandingAggregateCards = applyPayload;
  document.addEventListener('umkm:landing-preview:loading', function () {
    setLoading();
  });
  document.addEventListener('umkm:landing-region:changed', function (event) {
    var response = event && event.detail ? event.detail.response : null;
    if (response) applyPayload(response);
  });
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot, { once: true });
  else boot();
}());
