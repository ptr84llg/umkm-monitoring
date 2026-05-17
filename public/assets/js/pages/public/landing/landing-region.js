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
                loading.textContent = 'Memuat data wilayah...';

                const form = modal.querySelector('.landing-region-form');

                if (form) {
                    form.insertAdjacentElement('afterend', loading);
                }
            }

            loading.textContent = message || 'Memuat data wilayah...';
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

    Landing.openRegionModal = function () {
        const shell = Landing.qs(S.regionModalShell);

        if (!shell) {
            return;
        }

        setRegionAlert('');
        Landing.ensureRegionContext();
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
            setRegionAlert(error.message || 'Desa/kelurahan tidak dapat dimuat.');
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

    Landing.buildPreview = function (selection) {
        const key = selection.village?.code || selection.district?.code || selection.city?.code || '16.73';
        const seed = Landing.hash(key + selection.scope);

        if (selection.hasPublicUmkmData === false) {
            return {
                empty: true,
                total: 0,
                active: 0,
                validation: 0,
                watched: 'Belum tersedia',
                dominant: 'Belum tersedia',
                fields: [],
                message: 'Belum ada data agregat UMKM untuk wilayah ini.'
            };
        }

        if (selection.scope === 'city') {
            return {
                total: 1248,
                active: 1086,
                validation: 36,
                watched: (Landing.regionState.districts.length || 8) + ' Kecamatan',
                dominant: 'Perdagangan',
                fields: [
                    { name: 'Perdagangan', percent: 82 },
                    { name: 'Kuliner', percent: 74 },
                    { name: 'Jasa', percent: 64 }
                ]
            };
        }

        if (selection.scope === 'district') {
            const total = 110 + (seed % 115);
            const active = Math.round(total * (0.81 + ((seed % 8) / 100)));
            const validation = 3 + (seed % 7);
            const dominant = ['Perdagangan', 'Kuliner', 'Jasa', 'Industri Rumah Tangga'][seed % 4];

            return {
                total: total,
                active: active,
                validation: validation,
                watched: (Landing.regionState.villages.length || 'Semua') + ' Kelurahan',
                dominant: dominant,
                fields: [
                    { name: dominant, percent: 76 + (seed % 12) },
                    { name: dominant === 'Kuliner' ? 'Perdagangan' : 'Kuliner', percent: 66 + (seed % 10) },
                    { name: 'Jasa', percent: 56 + (seed % 9) }
                ]
            };
        }

        const total = 18 + (seed % 54);
        const active = Math.round(total * (0.78 + ((seed % 10) / 100)));
        const validation = 1 + (seed % 4);
        const dominant = ['Perdagangan', 'Kuliner', 'Jasa'][seed % 3];

        return {
            total: total,
            active: active,
            validation: validation,
            watched: '1 Kelurahan',
            dominant: dominant,
            fields: [
                { name: dominant, percent: 70 + (seed % 16) },
                { name: dominant === 'Perdagangan' ? 'Kuliner' : 'Perdagangan', percent: 58 + (seed % 14) },
                { name: 'Jasa', percent: 48 + (seed % 12) }
            ]
        };
    };

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
            const row = document.createElement('div');
            const label = document.createElement('span');
            const indicatorName = document.createElement('span');
            const indicatorMeta = document.createElement('span');
            const bar = document.createElement('b');

            indicatorName.className = 'indicator-name';
            indicatorName.textContent = field.name || 'Indikator';
            indicatorMeta.className = 'indicator-meta';
            indicatorMeta.textContent = Landing.formatNumber(count) + ' UMKM • ' + percent + '%';
            bar.style.width = Math.max(24, Math.min(95, percent)) + '%';

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

        if (!container || !preview || !Array.isArray(preview.fields)) {
            return;
        }

        container.innerHTML = '';

        if (preview.empty || !preview.fields.length) {
            const empty = document.createElement('div');
            empty.className = 'preview-empty-inline';
            empty.innerHTML = '<strong>Data wilayah belum tersedia</strong><small>Belum ada ringkasan UMKM publik untuk wilayah yang dipilih.</small>';
            container.appendChild(empty);
            return;
        }

        const areaNames = pickAreaNames(selection);
        const distributions = areaNames.length === 1 ? [1] : [0.42, 0.34, 0.24];

        areaNames.slice(0, 3).forEach(function (area, index) {
            const field = preview.fields[index % preview.fields.length] || preview.fields[0];
            const percent = Math.max(1, Math.min(100, Math.round(Number(field.percent || 0))));
            const count = Math.max(1, Math.round(Number(preview.total || 0) * (distributions[index] || 0.2)));
            const row = document.createElement('div');
            const name = document.createElement('span');
            const value = document.createElement('strong');
            const sector = document.createElement('small');

            name.textContent = area.name;
            value.textContent = Landing.formatNumber(count) + ' UMKM';
            sector.textContent = field.name + ' ' + percent + '%';

            row.appendChild(name);
            row.appendChild(value);
            row.appendChild(sector);
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

    Landing.applyRegionSelection = function (selection) {
        const safeSelection = Object.assign({}, Landing.DEFAULT_SELECTION, selection || {});
        const preview = Landing.buildPreview(safeSelection);
        const label = Landing.cleanRegionLabel(safeSelection.label || 'Kota Lubuklinggau');

        safeSelection.label = label;
        Landing.regionState.applied = safeSelection;

        Landing.setText(S.publicRegionSource, label);
        Landing.setText(S.publicChartRegion, label);
        Landing.setText(S.regionModalCurrent, label);
        Landing.setText(S.publicWatchedLabel, preview.watched);
        Landing.setText(S.publicDominantLabel, preview.dominant);
        togglePreviewEmptyState(preview, label);

        updateMetric('[data-public-metric="total"]', preview.total);
        updateMetric('[data-public-metric="active"]', preview.active);
        updateMetric('[data-public-metric="validation"]', preview.validation);

        renderIndicatorDetails(preview);
        renderAreaStats(safeSelection, preview);

        if (Landing.renderChart) {
            Landing.renderChart(safeSelection, preview);
        }

        Landing.emit('umkm:landing-region:changed', {
            selection: safeSelection,
            preview: preview
        });

        Landing.log('info', 'landing region preview applied', {
            label: label,
            scope: safeSelection.scope
        });
    };

    Landing.initRegionModal = function () {
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
})();
