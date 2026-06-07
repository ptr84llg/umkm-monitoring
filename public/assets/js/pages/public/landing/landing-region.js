(function () {
    'use strict';

    const Landing = window.UMKMLanding;
    const S = Landing.SELECTORS;

    function createOption(region) {
        const option = document.createElement('option');

        option.value = region.code || '';
        option.textContent = region.name || region.code || '';
        option.dataset.regionName = region.name || '';
        option.dataset.regionLevel = region.level || '';
        option.dataset.virtual = region.is_virtual || region.isVirtual ? '1' : '0';
        option.dataset.hasPublicUmkmData = region.has_public_umkm_data === false
            ? '0'
            : (region.has_public_umkm_data === true ? '1' : 'unknown');

        return option;
    }

    function fillLockedSelect(select, region) {
        if (!select || !region) {
            return;
        }

        select.innerHTML = '';
        select.appendChild(createOption(region));
        select.value = region.code;
        select.disabled = true;
    }

    function fillDistricts(select, allOption, districts) {
        if (!select) {
            return;
        }

        select.innerHTML = '';
        select.appendChild(createOption(allOption || Landing.DEFAULT_CONTEXT.options.district_all));

        (districts || []).forEach(function (region) {
            select.appendChild(createOption(region));
        });

        select.value = (allOption || Landing.DEFAULT_CONTEXT.options.district_all).code;
        select.disabled = false;
    }

    function fillVillages(select, allOption, villages, disabled) {
        if (!select) {
            return;
        }

        select.innerHTML = '';
        select.appendChild(createOption(allOption || Landing.DEFAULT_CONTEXT.options.village_all));

        (villages || []).forEach(function (region) {
            select.appendChild(createOption(region));
        });

        select.value = (allOption || Landing.DEFAULT_CONTEXT.options.village_all).code;
        select.disabled = Boolean(disabled);
    }

    function selectedOption(select) {
        if (!select || !select.value) {
            return null;
        }

        const option = select.options[select.selectedIndex];

        return {
            code: option.value,
            name: option.dataset.regionName || option.textContent || option.value,
            level: option.dataset.regionLevel || '',
            isVirtual: option.dataset.virtual === '1',
            hasPublicUmkmData: option.dataset.hasPublicUmkmData === '0'
                ? false
                : (option.dataset.hasPublicUmkmData === '1' ? true : null)
        };
    }

    function setRegionLoading(isLoading, message) {
        Landing.regionState.loading = Boolean(isLoading);

        const modal = Landing.qs(S.regionModalPanel);
        const applyButton = Landing.qs(S.regionModalApply);

        if (modal) {
            modal.classList.toggle('is-loading', Landing.regionState.loading);

            let loading = modal.querySelector('[data-region-loading]');

            if (!loading) {
                loading = document.createElement('div');
                loading.className = 'landing-region-loading';
                loading.dataset.regionLoading = 'true';
                loading.setAttribute('role', 'status');
                loading.setAttribute('aria-live', 'polite');
                loading.hidden = true;
                loading.textContent = 'Memuat data wilayah...';

                const form = modal.querySelector('.landing-region-form');

                if (form) {
                    form.insertAdjacentElement('afterend', loading);
                }
            }

            loading.textContent = message || 'Memuat data wilayah...';
            loading.hidden = !Landing.regionState.loading;
        }

        if (applyButton) {
            applyButton.disabled = Landing.regionState.loading;
            applyButton.classList.toggle('is-disabled', Landing.regionState.loading);
        }
    }
    function setRegionAlert(message) {
        const alert = Landing.qs(S.regionModalAlert);

        if (!alert) {
            return;
        }

        alert.hidden = !message;
        alert.textContent = message || '';
    }

    function setModalCurrent(text) {
        Landing.setText(S.regionModalCurrent, Landing.cleanRegionLabel(text || 'Kota Lubuklinggau'));
    }


    // PUBLICLANDING-REDESIGN-1B: region modal resilience
    function fallbackDistrictRegions() {
        return [
            { code: '16.73.01', name: 'Lubuk Linggau Timur I', level: 'district', is_virtual: false, has_public_umkm_data: true },
            { code: '16.73.02', name: 'Lubuk Linggau Barat I', level: 'district', is_virtual: false, has_public_umkm_data: true },
            { code: '16.73.03', name: 'Lubuk Linggau Selatan I', level: 'district', is_virtual: false, has_public_umkm_data: true },
            { code: '16.73.04', name: 'Lubuk Linggau Utara I', level: 'district', is_virtual: false, has_public_umkm_data: true },
            { code: '16.73.05', name: 'Lubuk Linggau Timur II', level: 'district', is_virtual: false, has_public_umkm_data: true },
            { code: '16.73.06', name: 'Lubuk Linggau Barat II', level: 'district', is_virtual: false, has_public_umkm_data: true },
            { code: '16.73.07', name: 'Lubuk Linggau Selatan II', level: 'district', is_virtual: false, has_public_umkm_data: true },
            { code: '16.73.08', name: 'Lubuk Linggau Utara II', level: 'district', is_virtual: false, has_public_umkm_data: true }
        ];
    }

    function applyRegionContextFallback(error) {
        Landing.regionState.context = Landing.DEFAULT_CONTEXT;
        Landing.regionState.districts = fallbackDistrictRegions();
        Landing.regionState.villages = [];
        Landing.regionState.contextLoaded = true;

        fillLockedSelect(Landing.qs(S.provinceSelect), Landing.regionState.context.province);
        fillLockedSelect(Landing.qs(S.citySelect), Landing.regionState.context.city);
        fillDistricts(
            Landing.qs(S.districtSelect),
            Landing.regionState.context.options.district_all,
            Landing.regionState.districts
        );
        fillVillages(
            Landing.qs(S.villageSelect),
            Landing.regionState.context.options.village_all,
            [],
            true
        );

        setModalCurrent(Landing.regionState.applied.label || 'Kota Lubuklinggau');
        setRegionAlert('Mode preview wilayah aktif. Daftar kecamatan memakai fallback public-safe karena konteks wilayah belum dapat dimuat penuh.');

        Landing.log('warn', 'landing region context fallback applied', {
            message: error && error.message ? error.message : 'region context fallback'
        });
    }
    function releaseRegionModalFocus(shell) {
        if (window.UMKM.modal && typeof window.UMKM.modal.releaseFocus === 'function') {
            window.UMKM.modal.releaseFocus(shell, S.regionModalOpen);
            return;
        }

        const activeElement = document.activeElement;

        if (shell && activeElement && shell.contains(activeElement) && typeof activeElement.blur === 'function') {
            activeElement.blur();
        }

        if (!document.body.hasAttribute('tabindex')) {
            document.body.setAttribute('tabindex', '-1');
        }

        document.body.focus({ preventScroll: true });
    }

    function bindRegionModalFocusGuard(shell) {
        if (!shell || !window.UMKM.modal || typeof window.UMKM.modal.bindFocusGuard !== 'function') {
            return;
        }

        window.UMKM.modal.bindFocusGuard(shell, {
            fallbackTriggerSelector: S.regionModalOpen,
            returnFocus: true,
            setInertWhenHidden: true
        });
    }

    Landing.openRegionModal = async function () {
        const shell = Landing.qs(S.regionModalShell);

        if (!shell) {
            return;
        }

        setRegionAlert('');
        await Landing.ensureRegionContext();
        bindRegionModalFocusGuard(shell);

        if (window.UMKM.modal && typeof window.UMKM.modal.rememberTrigger === 'function') {
            window.UMKM.modal.rememberTrigger(shell, document.activeElement);
        }

        if (window.bootstrap && window.bootstrap.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(shell, {
                backdrop: true,
                keyboard: true,
                focus: true
            }).show();
            return;
        }

        shell.hidden = false;
        shell.classList.add('show');
        shell.style.display = 'block';
        shell.setAttribute('aria-modal', 'true');
        shell.removeAttribute('aria-hidden');
        document.body.classList.add('is-region-modal-open');
    };

    Landing.closeRegionModal = function () {
        if (Landing.regionState.loading) {
            return;
        }

        const shell = Landing.qs(S.regionModalShell);

        if (!shell) {
            return;
        }

        bindRegionModalFocusGuard(shell);
        releaseRegionModalFocus(shell);

        if (window.UMKM.modal && typeof window.UMKM.modal.hide === 'function') {
            window.UMKM.modal.hide(shell, S.regionModalOpen);
            return;
        }

        if (window.bootstrap && window.bootstrap.Modal) {
            const modal = window.bootstrap.Modal.getInstance(shell);

            if (modal) {
                modal.hide();
                return;
            }
        }

        shell.hidden = true;
        shell.classList.remove('show');
        shell.style.display = '';
        shell.setAttribute('aria-hidden', 'true');
        shell.removeAttribute('aria-modal');
        document.body.classList.remove('is-region-modal-open');
    };

    async function loadChildren(parentCode, level) {
        const url = Landing.API.regionChildren + '?parent_code=' + encodeURIComponent(parentCode) + '&level=' + encodeURIComponent(level);
        const result = await window.UMKM.ajax.get(url);
        const payload = Landing.unwrap(result);

        if (!Landing.resultOk(result) || !payload || !payload.data) {
            throw new Error(payload && payload.message ? payload.message : 'Data wilayah tidak dapat dimuat.');
        }

        return payload.data;
    }

    Landing.ensureRegionContext = async function () {
        if (!Landing.ajaxReady()) {
            setRegionAlert('Modul AJAX sistem belum siap. Muat ulang halaman lalu coba kembali.');
            return;
        }

        if (Landing.regionState.contextLoaded) {
            return;
        }

        try {
            setRegionLoading(true, 'Memuat kecamatan Kota Lubuklinggau...');
            setRegionAlert('');

            const result = await window.UMKM.ajax.get(Landing.API.regionContext);
            const payload = Landing.unwrap(result);

            if (!Landing.resultOk(result) || !payload || !payload.data) {
                throw new Error(payload && payload.message ? payload.message : 'Konteks wilayah tidak dapat dimuat.');
            }

            Landing.regionState.context = {
                province: payload.data.province || Landing.DEFAULT_CONTEXT.province,
                city: payload.data.city || Landing.DEFAULT_CONTEXT.city,
                options: Object.assign({}, Landing.DEFAULT_CONTEXT.options, payload.data.options || {})
            };

            fillLockedSelect(Landing.qs(S.provinceSelect), Landing.regionState.context.province);
            fillLockedSelect(Landing.qs(S.citySelect), Landing.regionState.context.city);

            const districtData = await loadChildren(Landing.regionState.context.city.code, 'district');

            Landing.regionState.districts = districtData.regions || [];
            Landing.regionState.villages = [];

            fillDistricts(
                Landing.qs(S.districtSelect),
                districtData.all_option || Landing.regionState.context.options.district_all,
                Landing.regionState.districts
            );

            fillVillages(
                Landing.qs(S.villageSelect),
                Landing.regionState.context.options.village_all,
                [],
                true
            );

            Landing.regionState.contextLoaded = true;
            setModalCurrent(Landing.regionState.applied.label || 'Kota Lubuklinggau');
        } catch (error) {
            setRegionAlert(error.message || 'Wilayah tidak dapat dimuat.');
        } finally {
            setRegionLoading(false);
        }
    };

    async function onDistrictChanged() {
        const districtSelect = Landing.qs(S.districtSelect);
        const villageSelect = Landing.qs(S.villageSelect);
        const district = selectedOption(districtSelect);

        Landing.regionState.villages = [];

        if (!district || district.isVirtual) {
            fillVillages(villageSelect, Landing.regionState.context.options?.village_all, [], true);
            setModalCurrent('Kota Lubuklinggau');
            setRegionAlert('');
            return;
        }

        try {
            setRegionLoading(true, 'Memuat desa/kelurahan...');
            setRegionAlert('');

            const villageData = await loadChildren(district.code, 'village');

            Landing.regionState.villages = villageData.regions || [];

            fillVillages(
                villageSelect,
                villageData.all_option || Landing.regionState.context.options?.village_all,
                Landing.regionState.villages,
                false
            );

            setModalCurrent(district.name);
        } catch (error) {
            fillVillages(villageSelect, Landing.regionState.context.options?.village_all, [], true);
            setModalCurrent(district.name);
            setRegionAlert('Kelurahan rinci belum tersedia. Preview kecamatan tetap dapat diterapkan.');
            Landing.log('warn', 'landing village fallback applied', {
                message: error.message || 'village children failed'
            });
        } finally {
            setRegionLoading(false);
        }
    }

    Landing.getAppliedSelection = function () {
        const district = selectedOption(Landing.qs(S.districtSelect));
        const village = selectedOption(Landing.qs(S.villageSelect));

        const districtAll = !district || district.isVirtual || district.code === '__ALL_DISTRICTS__';
        const villageAll = !village || village.isVirtual || village.code === '__ALL_VILLAGES__';

        let label = Landing.regionState.context.city.name || 'Kota Lubuklinggau';
        let scope = 'city';

        if (!districtAll && villageAll) {
            label = district.name;
            scope = 'district';
        }

        if (!districtAll && !villageAll) {
            label = village.name;
            scope = 'village';
        }

        return {
            province: Landing.regionState.context.province,
            city: Landing.regionState.context.city,
            district: districtAll ? null : district,
            village: villageAll ? null : village,
            districtAll: districtAll,
            villageAll: villageAll,
            hasPublicUmkmData: !districtAll && !villageAll
                ? village.hasPublicUmkmData
                : (!districtAll ? district.hasPublicUmkmData : null),
            label: Landing.cleanRegionLabel(label),
            scope: scope
        };
    };

    function previewQuery(selection) {
        const query = new URLSearchParams();
        const cityCode = selection && selection.city && selection.city.code ? selection.city.code : '16.73';
        const districtCode = selection && selection.district && selection.district.code && !selection.district.isVirtual && selection.district.code !== '__ALL_DISTRICTS__'
            ? selection.district.code
            : '';
        const villageCode = selection && selection.scope === 'village' && selection.village && selection.village.code && !selection.village.isVirtual && selection.village.code !== '__ALL_VILLAGES__'
            ? selection.village.code
            : '';

        query.set('mode', Landing.activeMode || 'kinerja');
        query.set('city_code', cityCode);

        if (districtCode) {
            query.set('district_code', districtCode);
            query.set('scope', 'district');
        } else {
            query.set('scope', 'city');
        }

        if (villageCode) {
            query.set('village_code', villageCode);
            query.set('scope', 'village');
        }

        return query;
    }

    function fallbackPreviewResponse(selection) {
        const aggregateCards = [
            {
                key: 'total_umkm',
                label: 'UMKM OPERASIONAL',
                value: 0,
                value_text: '0',
                context: 'Unit usaha operasional',
                badge: 'Terbatas',
                percent_text: '0,00% dari cakupan',
                progress_percent: 0
            },
            {
                key: 'mapped_umkm',
                label: 'TERPETAKAN',
                value: 0,
                value_text: '0',
                context: 'Unit terpetakan',
                badge: 'Terbatas',
                percent_text: '0,00% dari cakupan',
                progress_percent: 0
            },
            {
                key: 'dominant_category',
                label: 'KATEGORI DOMINAN',
                value: 'Belum tersedia',
                value_text: 'Belum tersedia',
                context: 'Kategori terbanyak',
                badge: 'Terbatas',
                percent_text: '0,00% dari cakupan',
                progress_percent: 0
            },
            {
                key: 'active_regions',
                label: 'WILAYAH AKTIF',
                value: 0,
                value_text: '0',
                context: 'Kelurahan memiliki data',
                badge: 'Terbatas',
                percent_text: '0,00% wilayah tercakup',
                progress_percent: 0
            }
        ];

        return {
            scope: selection.scope || 'city',
            selection: {
                scope: selection.scope || 'city',
                label: Landing.cleanRegionLabel(selection.label || 'Kota Lubuklinggau'),
                region_code: selection.village?.code || selection.district?.code || selection.city?.code || '16.73',
                has_public_umkm_data: selection.hasPublicUmkmData ?? null
            },
            context_label: Landing.cleanRegionLabel(selection.label || 'Kota Lubuklinggau'),
            aggregate_cards: aggregateCards,
            aggregate_card_map: aggregateCards.reduce(function (carry, card) {
                carry[card.key] = card;
                return carry;
            }, {}),
            preview: {
                empty: true,
                total: 0,
                active: 0,
                validation: 0,
                mapped: 0,
                mapped_percent: 0,
                mapped_percent_text: '0,00%',
                coverage_percent: '0,00%',
                coverage_percent_value: 0,
                active_regions: 0,
                aggregate_cards: aggregateCards,
                watched: 'Belum tersedia',
                dominant: 'Belum tersedia',
                fields: [],
                areas: [],
                message: 'Preview publik belum dapat dimuat dari server.'
            },
            chart: {
                title: 'Preview publik belum tersedia',
                subtitle: 'Data agregat belum dapat dimuat dari server.',
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul'],
                unit_label: 'Jumlah UMKM',
                percent_label: 'Persentase (%)',
                unit_data: [0, 0, 0, 0, 0, 0, 0],
                percent_data: [0, 0, 0, 0, 0, 0, 0],
                summary_one: 'Server preview belum tersedia',
                summary_two: 'Data belum dapat dimuat',
                summary_three: 'Coba muat ulang halaman'
            }
        };
    }

    Landing.loadPreviewData = async function (selection) {
        const safeSelection = Object.assign({}, Landing.DEFAULT_SELECTION, selection || {});
        const label = Landing.cleanRegionLabel(safeSelection.label || 'Kota Lubuklinggau');

        safeSelection.label = label;
        Landing.regionState.applied = safeSelection;
        Landing.regionState.previewLoading = true;
        setPreviewBusyState(safeSelection);
        Landing.emit('umkm:landing-preview:loading', {
            selection: safeSelection
        });

        if (!Landing.ajaxReady()) {
            const fallback = fallbackPreviewResponse(safeSelection);
            Landing.applyPreviewResponse(safeSelection, fallback);
            Landing.regionState.previewLoading = false;
            return fallback;
        }

        try {
            const result = await window.UMKM.ajax.get(Landing.API.previewData + '?' + previewQuery(safeSelection), {
                headers: {
                    'X-UMKM-Preview': 'landing-public-safe'
                }
            });

            const payload = Landing.unwrap(result);

            if (!Landing.resultOk(result) || !payload || payload.ok !== true || !payload.data) {
                throw new Error(payload && payload.message ? payload.message : 'Preview publik tidak dapat dimuat.');
            }

            Landing.applyPreviewResponse(safeSelection, payload.data);
            return payload.data;
        } catch (error) {
            Landing.log('warn', 'landing preview data failed', {
                message: error.message || 'preview failed'
            });

            const fallback = fallbackPreviewResponse(safeSelection);
            Landing.applyPreviewResponse(safeSelection, fallback);
            return fallback;
        } finally {
            Landing.regionState.previewLoading = false;
        }
    };

    function replaceHtml(selector, html) {
        const element = Landing.qs(selector);

        if (!element || typeof html !== 'string') {
            return;
        }

        element.innerHTML = html;
    }

    function replaceMetricsFromFragment(html) {
        if (typeof html !== 'string' || html.trim() === '') {
            return false;
        }

        const template = document.createElement('template');
        template.innerHTML = html.trim();

        ['total', 'active', 'validation'].forEach(function (key) {
            const source = template.content.querySelector('[data-public-metric="' + key + '"]');
            const target = Landing.qs('[data-public-metric="' + key + '"]');

            if (source && target) {
                target.dataset.count = source.dataset.count || '0';
                target.textContent = source.textContent || '0';
            }
        });

        return true;
    }
    function updateMetric(selector, value) {
        const element = Landing.qs(selector);

        if (!element) {
            return;
        }

        element.dataset.count = String(value);
        element.textContent = Landing.formatNumber(value);
    }

    function renderIndicatorDetails(preview) {
        const container = Landing.qs(S.publicFieldList);

        if (!container || !preview || !Array.isArray(preview.fields)) {
            return;
        }

        container.innerHTML = '';
        container.classList.remove('is-loading');

        if (preview.empty || !preview.fields.length) {
            const empty = document.createElement('div');
            empty.className = 'preview-empty-inline';
            empty.innerHTML = '<strong>Indikator belum tersedia</strong><small>Data bidang usaha akan tampil setelah terdapat data UMKM pada wilayah ini.</small>';
            container.appendChild(empty);
            return;
        }

        preview.fields.slice(0, 3).forEach(function (field, index) {
            const percent = Math.max(1, Math.min(100, Math.round(Number(field.percent || 0))));
            const count = Math.max(index === 0 ? 1 : 0, Math.round(Number(preview.total || 0) * (percent / 100)));

            if (container.classList.contains('analytics-legend')) {
                const row = document.createElement('span');
                const marker = document.createElement('i');
                const value = document.createElement('strong');

                marker.className = ['is-mikro', 'is-kecil', 'is-menengah'][index] || 'is-mikro';
                row.appendChild(marker);
                row.appendChild(document.createTextNode((field.name || 'Kategori') + ' '));
                value.textContent = percent + '%';
                row.appendChild(value);
                container.appendChild(row);
                return;
            }

            const row = document.createElement('div');
            const label = document.createElement('span');
            const indicatorName = document.createElement('span');
            const indicatorMeta = document.createElement('span');
            const bar = document.createElement('b');

            indicatorName.className = 'indicator-name';
            indicatorName.textContent = field.name || 'Indikator';
            indicatorMeta.className = 'indicator-meta';
            indicatorMeta.textContent = Landing.formatNumber(count) + ' UMKM • ' + percent + '%';
            bar.className = 'progress-fill-' + Math.max(24, Math.min(95, percent));

            label.appendChild(indicatorName);
            label.appendChild(indicatorMeta);
            row.appendChild(label);
            row.appendChild(bar);
            container.appendChild(row);
        });
    }

    function getRegionOptions(selector) {
        const select = Landing.qs(selector);

        if (!select) {
            return [];
        }

        return Array.from(select.options)
            .filter(function (option) {
                return option.value && option.dataset.virtual !== '1' && !option.value.startsWith('__');
            })
            .map(function (option) {
                return {
                    code: option.value,
                    name: Landing.cleanName(option.dataset.regionName || option.textContent || option.value)
                };
            })
            .filter(function (item) { return item.name; });
    }

    function pickAreaNames(selection) {
        if (selection && selection.scope === 'village') {
            return [{ name: Landing.cleanName(selection.village?.name || selection.label || 'Wilayah terpilih') }];
        }

        if (selection && selection.scope === 'district') {
            const villages = getRegionOptions(S.villageSelect);

            if (villages.length) {
                return villages.slice(0, 3);
            }

            return [{ name: Landing.cleanName(selection.district?.name || selection.label || 'Kecamatan terpilih') }];
        }

        const districts = getRegionOptions(S.districtSelect);

        if (districts.length) {
            return districts.slice(0, 3);
        }

        return [
            { name: 'Lubuk Linggau Timur II' },
            { name: 'Lubuk Linggau Utara II' },
            { name: 'Lubuk Linggau Barat II' }
        ];
    }

    function renderAreaStats(selection, preview) {
        const container = Landing.qs(S.publicAreaList);
        const areas = Array.isArray(preview?.areas) ? preview.areas : [];

        function barWidthClass(percent) {
            if (percent >= 88) {
                return 'bar-w-92';
            }

            if (percent >= 76) {
                return 'bar-w-80';
            }

            if (percent >= 68) {
                return 'bar-w-72';
            }

            if (percent >= 60) {
                return 'bar-w-64';
            }

            return 'bar-w-56';
        }

        if (!container) {
            return;
        }

        container.innerHTML = '';
        container.classList.remove('is-loading');

        if (preview?.empty || !areas.length) {
            const empty = document.createElement('div');
            empty.className = 'preview-empty-inline';
            empty.innerHTML = '<strong>Data wilayah belum tersedia</strong><small>Belum ada ringkasan UMKM publik untuk wilayah yang dipilih.</small>';
            container.appendChild(empty);
            return;
        }

        areas.slice(0, 3).forEach(function (area) {
            const row = document.createElement('div');
            const name = document.createElement('span');
            const value = document.createElement('strong');
            const sector = document.createElement('small');
            const bar = document.createElement('i');
            const percent = Math.max(0, Math.min(100, Number(area.percent || 0)));

            row.className = container.classList.contains('bar-list') ? 'bar-item' : '';
            name.textContent = area.name || 'Wilayah';
            value.textContent = Landing.formatNumber(area.count || 0) + ' UMKM';
            sector.textContent = (area.sector || 'Indikator') + ' ' + percent + '%';
            bar.className = 'bar-fill ' + barWidthClass(percent);

            row.appendChild(name);
            row.appendChild(value);
            row.appendChild(sector);
            row.appendChild(bar);
            container.appendChild(row);
        });
    }

    function togglePreviewEmptyState(preview, label) {
        const emptyState = Landing.qs(S.publicEmptyState);
        const emptyTitle = Landing.qs(S.publicEmptyTitle);
        const emptyMessage = Landing.qs(S.publicEmptyMessage);
        const isEmpty = Boolean(preview && preview.empty);

        if (emptyState) {
            emptyState.hidden = !isEmpty;
        }

        if (emptyTitle) {
            emptyTitle.textContent = 'Data UMKM ' + Landing.cleanRegionLabel(label || 'wilayah ini') + ' belum tersedia';
        }

        if (emptyMessage) {
            emptyMessage.textContent = preview && preview.message
                ? preview.message + ' Pilih wilayah lain atau kembali ke Kota Lubuklinggau untuk melihat preview agregat.'
                : 'Belum ada data agregat UMKM untuk wilayah yang dipilih. Pilih wilayah lain atau kembali ke Kota Lubuklinggau untuk melihat preview agregat.';
        }
    }

    // PUBLICLANDING-UI-2D: automatically sync public filter fields from applied region selection.
    function ensurePublicFilterOption(select, value, label) {
        if (!select) {
            return;
        }

        const safeValue = value || '';
        const safeLabel = label || 'Semua';
        let option = Array.from(select.options).find(function (item) {
            return item.value === safeValue;
        });

        if (!option) {
            option = document.createElement('option');
            option.value = safeValue;
            select.appendChild(option);
        }

        option.textContent = safeLabel;
        select.value = safeValue;
    }

    function syncPublicFiltersFromSelection(selection) {
        const districtFilter = document.getElementById('filter-kecamatan');
        const villageFilter = document.getElementById('filter-kelurahan');

        if (!selection) {
            ensurePublicFilterOption(districtFilter, '', 'Semua');
            ensurePublicFilterOption(villageFilter, '', 'Semua');
            return;
        }

        const districtValue = selection.district?.code || '';
        const districtLabel = selection.district?.name || 'Semua';
        const villageValue = selection.village?.code || '';
        const villageLabel = selection.village?.name || 'Semua';

        ensurePublicFilterOption(districtFilter, districtValue, districtLabel);
        ensurePublicFilterOption(villageFilter, villageValue, villageLabel);

        if (villageFilter) {
            villageFilter.disabled = !districtValue;
        }
    }

    function bindPublicFilterRefresh() {
        if (Landing.publicFilterRefreshBound === true) {
            return;
        }

        Landing.publicFilterRefreshBound = true;

        document.addEventListener('click', function (event) {
            const button = event.target && event.target.closest
                ? event.target.closest('[data-public-filter-refresh], .public-filter-btn')
                : null;

            if (!button) {
                return;
            }

            event.preventDefault();
            Landing.loadPreviewData(Landing.regionState.applied || Object.assign({}, Landing.DEFAULT_SELECTION));
        });
    }

    function updateContextTargets(label) {
        const safeLabel = Landing.cleanRegionLabel(label || 'Kota Lubuklinggau');

        Landing.qsa('[data-public-context-label], [data-public-coverage-label], [data-public-active-context-label]').forEach(function (element) {
            element.textContent = safeLabel;
        });
    }

    function setPreviewBusyState(selection) {
        const label = Landing.cleanRegionLabel(selection?.label || 'Kota Lubuklinggau');

        updateContextTargets(label);
        Landing.setText(S.publicRegionSource, label);
        Landing.setText(S.publicChartRegion, label);
        Landing.setText(S.regionModalCurrent, label);
        syncPublicFiltersFromSelection(selection);

        [
            Landing.qs(S.publicFieldList),
            Landing.qs(S.publicAreaList)
        ].forEach(function (container) {
            if (!container) {
                return;
            }

            container.classList.add('is-loading');
            container.innerHTML = '<div class="preview-empty-inline is-loading"><strong>Memuat agregat wilayah</strong><small>Menyiapkan ringkasan public-safe untuk konteks terpilih.</small></div>';
        });

        Landing.qsa('[data-public-map-cluster-value]').forEach(function (element) {
            element.textContent = '—';
        });
    }

    function updateAnalyticsSummary(preview, response) {
        const totalTarget = Landing.qs('[data-public-analytics-total]');
        const trendTarget = Landing.qs('[data-public-trend-list]');
        const updatedTarget = Landing.qs('[data-public-updated-label]');
        const trendPoints = Array.isArray(response?.trend_points) ? response.trend_points : [];

        if (totalTarget) {
            totalTarget.textContent = Landing.formatNumber(preview?.total || 0);
        }

        if (updatedTarget) {
            updatedTarget.textContent = response?.updated_at || 'Belum tersedia';
        }

        if (trendTarget && trendPoints.length) {
            trendTarget.innerHTML = '';

            trendPoints.slice(0, 5).forEach(function (point) {
                const item = document.createElement('span');

                item.className = 'mini-point ' + (point.class || 'point-01');
                item.textContent = point.value || '0';
                trendTarget.appendChild(item);
            });
        }
    }

    function updateMapPreview(preview) {
        const values = Array.isArray(preview?.areas) && preview.areas.length
            ? preview.areas.slice(0, 3).map(function (area) { return area.count || 0; })
            : [
                preview?.mapped || 0,
                preview?.active_regions || 0,
                Math.max(0, Number(preview?.total || 0) - Number(preview?.mapped || 0))
            ];

        Landing.qsa('[data-public-map-cluster-value]').forEach(function (element, index) {
            element.textContent = Landing.formatNumber(values[index] || 0);
        });
    }

    Landing.applyPreviewResponse = function (selection, response) {
        const safeSelection = Object.assign({}, Landing.DEFAULT_SELECTION, selection || {});
        const preview = response?.preview || {};
        const label = Landing.cleanRegionLabel(response?.selection?.label || safeSelection.label || 'Kota Lubuklinggau');

        safeSelection.label = label;
        Landing.regionState.applied = safeSelection;
        Landing.regionState.preview = response;

        Landing.setText(S.publicRegionSource, label);
        Landing.setText(S.publicChartRegion, label);
        Landing.setText(S.regionModalCurrent, label);
        updateContextTargets(label);
        syncPublicFiltersFromSelection(safeSelection);
        Landing.setText(S.publicWatchedLabel, preview.watched || 'Belum tersedia');
        Landing.setText(S.publicDominantLabel, preview.dominant || 'Belum tersedia');
        updateAnalyticsSummary(preview, response);
        updateMapPreview(preview);

        const fragments = response?.fragments || {};

        if (!replaceMetricsFromFragment(fragments.metrics || '')) {
            updateMetric('[data-public-metric="total"]', preview.total || 0);
            updateMetric('[data-public-metric="active"]', preview.active || 0);
            updateMetric('[data-public-metric="validation"]', preview.validation || 0);
        }

        if (typeof fragments.indicators === 'string') {
            replaceHtml(S.publicFieldList, fragments.indicators);
        } else {
            renderIndicatorDetails(preview);
        }

        if (typeof fragments.areas === 'string') {
            replaceHtml(S.publicAreaList, fragments.areas);
        } else {
            renderAreaStats(safeSelection, preview);
        }

        if (typeof fragments.empty_state === 'string') {
            const emptyState = Landing.qs(S.publicEmptyState);

            if (emptyState) {
                emptyState.hidden = !Boolean(preview && preview.empty);
                emptyState.innerHTML = fragments.empty_state;
            }
        } else {
            togglePreviewEmptyState(preview, label);
        }

        if (Landing.renderChart) {
            Landing.renderChart(safeSelection, response);
        }

        Landing.emit('umkm:landing-region:changed', {
            selection: safeSelection,
            preview: preview,
            response: response
        });

        Landing.log('info', 'landing region preview applied from backend', {
            label: label,
            scope: safeSelection.scope
        });
    };

    Landing.applyRegionSelection = function (selection) {
        return Landing.loadPreviewData(selection);
    };

    Landing.initRegionModal = function () {
        bindPublicFilterRefresh();

        const shell = Landing.qs(S.regionModalShell);

        if (shell && shell.dataset.regionShellBound !== 'true') {
            shell.dataset.regionShellBound = 'true';
            bindRegionModalFocusGuard(shell);

            shell.addEventListener('hide.bs.modal', function (event) {
                if (Landing.regionState.loading) {
                    event.preventDefault();
                    return false;
                }

                releaseRegionModalFocus(shell);
                return true;
            });

            shell.addEventListener('shown.bs.modal', function () {
                document.body.classList.add('is-region-modal-open');
            });

            shell.addEventListener('hidden.bs.modal', function () {
                document.body.classList.remove('is-region-modal-open');
            });
        }

        Landing.qsa(S.regionModalOpen).forEach(function (button) {
            if (button.dataset.regionOpenBound === 'true') {
                return;
            }

            button.dataset.regionOpenBound = 'true';
            button.addEventListener('click', Landing.openRegionModal);
        });

        Landing.qsa(S.regionModalClose).forEach(function (button) {
            if (button.dataset.regionCloseBound === 'true') {
                return;
            }

            button.dataset.regionCloseBound = 'true';
            button.addEventListener('click', function (event) {
                if (Landing.regionState.loading) {
                    event.preventDefault();
                    event.stopPropagation();
                    return;
                }

                Landing.closeRegionModal();
            });
        });

        const districtSelect = Landing.qs(S.districtSelect);

        if (districtSelect && districtSelect.dataset.regionChangeBound !== 'true') {
            districtSelect.dataset.regionChangeBound = 'true';
            districtSelect.addEventListener('change', onDistrictChanged);
        }

        const villageSelect = Landing.qs(S.villageSelect);

        if (villageSelect && villageSelect.dataset.regionChangeBound !== 'true') {
            villageSelect.dataset.regionChangeBound = 'true';
            villageSelect.addEventListener('change', function () {
                const selection = Landing.getAppliedSelection();
                setModalCurrent(selection.label);
            });
        }

        const applyButton = Landing.qs(S.regionModalApply);

        if (applyButton && applyButton.dataset.regionApplyBound !== 'true') {
            applyButton.dataset.regionApplyBound = 'true';
            applyButton.addEventListener('click', function () {
                if (Landing.regionState.loading) {
                    return;
                }

                Landing.applyRegionSelection(Landing.getAppliedSelection());
                Landing.closeRegionModal();
            });
        }
    };

    // PUBLICLANDING-REDESIGN-1C: final clickable modal override
    function forceRegionModalInteractive(shell) {
        if (!shell) {
            return;
        }

        shell.removeAttribute('inert');
        shell.removeAttribute('aria-hidden');
        shell.style.pointerEvents = 'auto';

        const dialog = shell.querySelector('.modal-dialog');
        const panel = Landing.qs(S.regionModalPanel);

        if (dialog) {
            dialog.style.pointerEvents = 'auto';
        }

        if (panel) {
            panel.style.pointerEvents = 'auto';
        }
    }

    Landing.openRegionModal = function (event) {
        if (event && typeof event.preventDefault === 'function') {
            event.preventDefault();
        }

        const shell = Landing.qs(S.regionModalShell);

        if (!shell) {
            return;
        }

        setRegionAlert('');
        forceRegionModalInteractive(shell);

        if (window.UMKM.modal && typeof window.UMKM.modal.rememberTrigger === 'function') {
            window.UMKM.modal.rememberTrigger(shell, document.activeElement);
        }

        if (window.bootstrap && window.bootstrap.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(shell, {
                backdrop: true,
                keyboard: true,
                focus: false
            }).show();
        } else {
            shell.hidden = false;
            shell.classList.add('show');
            shell.style.display = 'block';
            shell.setAttribute('aria-modal', 'true');
            shell.removeAttribute('aria-hidden');
            document.body.classList.add('is-region-modal-open');
        }

        window.setTimeout(function () {
            forceRegionModalInteractive(shell);
        }, 0);

        Promise.resolve(Landing.ensureRegionContext())
            .catch(function (error) {
                if (typeof applyRegionContextFallback === 'function') {
                    applyRegionContextFallback(error);
                    return;
                }

                setRegionAlert(error && error.message ? error.message : 'Konteks wilayah tidak dapat dimuat.');
            })
            .finally(function () {
                setRegionLoading(false);
                forceRegionModalInteractive(shell);
            });
    };

    // PUBLICLANDING-REDESIGN-1E: core modal restore override
    function restoreCoreRegionModalState(shell) {
        if (!shell) {
            return;
        }

        shell.removeAttribute('inert');
        shell.style.pointerEvents = '';

        const dialog = shell.querySelector('.modal-dialog');
        const panel = Landing.qs(S.regionModalPanel);

        if (dialog) {
            dialog.style.pointerEvents = '';
        }

        if (panel) {
            panel.style.pointerEvents = '';
        }
    }

    Landing.openRegionModal = function (event) {
        if (event && typeof event.preventDefault === 'function') {
            event.preventDefault();
        }

        const shell = Landing.qs(S.regionModalShell);

        if (!shell) {
            return;
        }

        setRegionAlert('');
        restoreCoreRegionModalState(shell);

        if (window.bootstrap && window.bootstrap.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(shell, {
                backdrop: true,
                keyboard: true,
                focus: true
            }).show();
        } else {
            shell.hidden = false;
            shell.classList.add('show');
            shell.style.display = 'block';
            shell.setAttribute('aria-modal', 'true');
            shell.removeAttribute('aria-hidden');
            document.body.classList.add('is-region-modal-open');
        }

        Promise.resolve(Landing.ensureRegionContext())
            .catch(function (error) {
                if (typeof applyRegionContextFallback === 'function') {
                    applyRegionContextFallback(error);
                    return;
                }

                setRegionAlert(error && error.message ? error.message : 'Konteks wilayah tidak dapat dimuat.');
            })
            .finally(function () {
                setRegionLoading(false);
                restoreCoreRegionModalState(shell);
            });
    };

    Landing.closeRegionModal = function () {
        if (Landing.regionState.loading) {
            setRegionLoading(false);
        }

        const shell = Landing.qs(S.regionModalShell);

        if (!shell) {
            return;
        }

        restoreCoreRegionModalState(shell);

        if (window.bootstrap && window.bootstrap.Modal) {
            const modal = window.bootstrap.Modal.getInstance(shell);

            if (modal) {
                modal.hide();
                return;
            }
        }

        shell.hidden = true;
        shell.classList.remove('show');
        shell.style.display = '';
        shell.setAttribute('aria-hidden', 'true');
        shell.removeAttribute('aria-modal');
        document.body.classList.remove('is-region-modal-open');
    };
})();
