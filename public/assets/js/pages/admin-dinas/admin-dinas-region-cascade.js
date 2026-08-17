(function () {
    'use strict';

    function optionText(option) {
        return option ? String(option.textContent || '').trim() : '';
    }

    function districtCode(option) {
        return option ? String(option.dataset.regionCode || '').trim() : '';
    }

    function villageParentCode(option) {
        return option ? String(option.dataset.parentCode || '').trim() : '';
    }

    function findDistrictByCode(select, code) {
        if (!select || !code) {
            return null;
        }

        return Array.from(select.options).find(function (option) {
            return districtCode(option) === code;
        }) || null;
    }

    function selectedVillage(select) {
        if (!select || !select.value) {
            return null;
        }

        return select.options[select.selectedIndex] || null;
    }

    function setVillageAvailability(districtSelect, villageSelect, preserveSelection) {
        if (!districtSelect || !villageSelect) {
            return;
        }

        let selectedVillageOption = selectedVillage(villageSelect);

        if (!districtSelect.value && selectedVillageOption) {
            const parentCode = villageParentCode(selectedVillageOption);
            const parentDistrict = findDistrictByCode(districtSelect, parentCode);

            if (parentDistrict) {
                districtSelect.value = parentDistrict.value;
            }
        }

        const selectedDistrictOption = districtSelect.value
            ? districtSelect.options[districtSelect.selectedIndex]
            : null;
        const selectedDistrictCode = districtCode(selectedDistrictOption);
        const currentVillageValue = preserveSelection ? String(villageSelect.value || '') : '';

        Array.from(villageSelect.options).forEach(function (option) {
            if (option.value === '') {
                option.hidden = false;
                option.disabled = false;
                return;
            }

            const belongsToDistrict = selectedDistrictCode !== ''
                && villageParentCode(option) === selectedDistrictCode;

            option.hidden = !belongsToDistrict;
            option.disabled = !belongsToDistrict;
        });

        if (!selectedDistrictCode) {
            villageSelect.value = '';
            villageSelect.disabled = true;
            villageSelect.setAttribute('aria-disabled', 'true');
            villageSelect.title = 'Pilih kecamatan terlebih dahulu untuk memuat kelurahan.';
            return;
        }

        villageSelect.disabled = false;
        villageSelect.removeAttribute('aria-disabled');
        villageSelect.title = '';

        if (currentVillageValue !== '') {
            const candidate = Array.from(villageSelect.options).find(function (option) {
                return option.value === currentVillageValue && !option.disabled;
            });

            villageSelect.value = candidate ? currentVillageValue : '';
        } else {
            villageSelect.value = '';
        }
    }

    function initPair(districtSelect, villageSelect) {
        if (!districtSelect || !villageSelect || districtSelect.dataset.regionCascadeReady === '1') {
            return;
        }

        districtSelect.dataset.regionCascadeReady = '1';
        villageSelect.dataset.regionCascadeReady = '1';

        setVillageAvailability(districtSelect, villageSelect, true);

        districtSelect.addEventListener('change', function () {
            villageSelect.value = '';
            setVillageAvailability(districtSelect, villageSelect, false);
        });
    }

    function init() {
        document.querySelectorAll('select[name="district_id"]').forEach(function (districtSelect) {
            const form = districtSelect.closest('form');
            const villageSelect = form
                ? form.querySelector('select[name="village_id"]')
                : document.querySelector('select[name="village_id"]');

            initPair(districtSelect, villageSelect);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
        init();
    }
})();