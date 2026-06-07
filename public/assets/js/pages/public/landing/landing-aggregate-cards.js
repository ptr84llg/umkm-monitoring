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
  function ensureTargets(card) {
    if (!card) return {};
    var targets = {
      badge: card.querySelector('[data-public-aggregate-badge]'),
      label: card.querySelector('[data-public-aggregate-label]'),
      value: card.querySelector('[data-public-aggregate-value]'),
      context: card.querySelector('[data-public-aggregate-context]'),
      percent: card.querySelector('[data-public-aggregate-percent]'),
      progress: card.querySelector('[data-public-aggregate-progress]')
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
      targets.progress = card.querySelector('.progress-bar');
      if (targets.progress) targets.progress.setAttribute('data-public-aggregate-progress', '');
    }
    return targets;
  }
  function setLoading() {
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
      if (targets.badge) targets.badge.textContent = 'Memuat';
      if (targets.label) targets.label.textContent = 'Memuat agregat';
      if (targets.value) targets.value.textContent = '—';
      if (targets.context) targets.context.textContent = 'Mengambil agregat publik...';
      if (targets.percent) targets.percent.textContent = 'Menunggu sinkronisasi';
      if (targets.progress) {
        targets.progress.className = 'progress-bar w-0';
        targets.progress.setAttribute('aria-valuenow', '0');
      }
    });
  }
  function setError(message) {
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
      if (targets.badge) targets.badge.textContent = 'Terbatas';
      if (targets.context) targets.context.textContent = message || 'Agregat belum dapat dimuat.';
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
    if (targets.badge) targets.badge.textContent = String(cardData.badge || cardData.label || 'Agregat');
    if (targets.label) targets.label.textContent = String(cardData.label || '');
    if (targets.value) targets.value.textContent = String(cardData.value_text ?? cardData.value ?? '0');
    if (targets.context) targets.context.textContent = String(cardData.context || '');
    if (targets.percent) targets.percent.textContent = String(cardData.percent_text || '');
    if (targets.progress) {
      targets.progress.className = 'progress-bar ' + widthClass(progress);
      targets.progress.setAttribute('aria-valuenow', String(progress));
    }
  }
  function applyPayload(payload) {
    var safe = normalizePayload(payload);
    if (!safe || !safe.data || !safe.data.aggregate_cards) return;
    state.payload = safe;
    state.loading = false;
    state.ready = true;
    window.PublicLandingAggregatePayload = safe;
    var cards = payloadCards(safe);
    CARD_KEYS.forEach(function (key) { applyCard(cards[key]); });
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
    modal.className = 'modal fade';
    modal.tabIndex = -1;
    modal.setAttribute('aria-hidden', 'true');
    modal.setAttribute('data-public-aggregate-modal', cardKey);
    modal.innerHTML = [
      '<div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">',
      '  <div class="modal-content border-0 shadow-lg">',
      '    <div class="modal-header">',
      '      <div><p class="text-muted small mb-1" data-public-aggregate-detail-subtitle>Memuat detail agregat</p><h5 class="modal-title mb-0" data-public-aggregate-detail-title>Detail Agregat</h5></div>',
      '      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>',
      '    </div>',
      '    <div class="modal-body">',
      '      <div class="d-flex align-items-center gap-3 py-3" data-public-aggregate-detail-loader><div class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></div><div class="flex-grow-1"><strong class="d-block">Mengambil data agregat...</strong><div class="progress mt-2"><div class="progress-bar progress-bar-striped progress-bar-animated w-75" role="progressbar" aria-label="Memuat"></div></div></div></div>',
      '      <div data-public-aggregate-detail-content hidden></div>',
      '    </div>',
      '    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button></div>',
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
    if (!Array.isArray(items) || items.length === 0) return '<div class="alert alert-light border mb-0">' + escapeHtml(emptyText || 'Data belum tersedia.') + '</div>';
    return '<div class="list-group list-group-flush border rounded-3 overflow-hidden">' + items.map(function (item) {
      return '<div class="list-group-item"><div class="d-flex justify-content-between gap-3"><strong>' + escapeHtml(item.label || '-') + '</strong><span class="fw-bold">' + escapeHtml(item.value || '0') + '</span></div><small class="text-muted">' + escapeHtml(item.meta || '') + '</small></div>';
    }).join('') + '</div>';
  }
  function renderDetail(modal, detail) {
    var loader = modal.querySelector('[data-public-aggregate-detail-loader]');
    var content = modal.querySelector('[data-public-aggregate-detail-content]');
    var title = modal.querySelector('[data-public-aggregate-detail-title]');
    var subtitle = modal.querySelector('[data-public-aggregate-detail-subtitle]');
    var summary = Array.isArray(detail.summary) ? detail.summary : [];
    var sections = Array.isArray(detail.sections) ? detail.sections : [];
    if (title) title.textContent = detail.title || 'Detail Agregat';
    if (subtitle) subtitle.textContent = detail.subtitle || 'Public-safe';
    if (loader) loader.hidden = true;
    if (content) {
      content.innerHTML = '<div class="row g-3 mb-4">' + summary.map(function (item) {
        return '<div class="col-12 col-md-6"><div class="border rounded-3 p-3 h-100"><small class="text-muted d-block mb-1">' + escapeHtml(item.label || '-') + '</small><strong class="fs-5">' + escapeHtml(item.value || '-') + '</strong></div></div>';
      }).join('') + '</div>' + sections.map(function (section) {
        return '<section class="mb-4"><h6 class="fw-bold mb-3">' + escapeHtml(section.title || 'Detail') + '</h6>' + renderList(section.items, section.empty) + '</section>';
      }).join('') + '<div class="alert alert-info small mb-0">' + escapeHtml(detail.public_safe_note || 'Detail yang ditampilkan bersifat agregat public-safe.') + '</div>';
      content.hidden = false;
    }
  }
  function renderDetailError(modal, message) {
    var loader = modal.querySelector('[data-public-aggregate-detail-loader]');
    var content = modal.querySelector('[data-public-aggregate-detail-content]');
    if (loader) loader.hidden = true;
    if (content) {
      content.innerHTML = '<div class="alert alert-warning mb-0">' + escapeHtml(message || 'Detail agregat belum dapat dimuat.') + '</div>';
      content.hidden = false;
    }
  }
  function openDetail(cardKey) {
    var card = cardNode(cardKey);
    if (!card || card.getAttribute('data-public-aggregate-ready') !== 'true') return;
    var modal = createModal(cardKey);
    showModal(modal);
    requestJson(query({ detail_card: cardKey }))
      .then(function (payload) {
        var safe = normalizePayload(payload);
        var detail = safe && safe.data ? safe.data.detail_card : null;
        if (!detail) throw new Error('Detail agregat tidak tersedia.');
        state.payload = safe;
        window.PublicLandingAggregatePayload = safe;
        renderDetail(modal, detail);
      })
      .catch(function (error) { renderDetailError(modal, error && error.message ? error.message : 'Detail agregat gagal dimuat.'); });
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
    CARD_KEYS.forEach(function (key) { ensureTargets(cardNode(key)); });
    bindClicks();
    wrapAjaxWhenReady(0);
    loadInitial(0);
  }
  window.umkmApplyPublicLandingAggregateCards = applyPayload;
  document.addEventListener('umkm:public-landing-context-label-updated', function (event) {
    var payload = event && event.detail ? event.detail.payload : null;
    if (payload) applyPayload(payload);
  });
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot, { once: true });
  else boot();
}());
