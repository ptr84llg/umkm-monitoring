(function () {
    'use strict';

    window.UMKM = window.UMKM || {};

    const UMKM = window.UMKM;
    const FINAL_STATUSES = ['success', 'limited', 'failed', 'skipped'];
    const RING_CIRCUMFERENCE = 314.159;

    const STATUS_META = {
        pending: { icon: '•', label: 'menunggu' },
        running: { icon: '…', label: 'memeriksa' },
        success: { icon: '✓', label: 'siap' },
        limited: { icon: '!', label: 'terbatas' },
        failed: { icon: '×', label: 'gagal' },
        skipped: { icon: '–', label: 'dilewati' }
    };

    function ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
            return;
        }

        callback();
    }

    function qs(selector, root) {
        return (root || document).querySelector(selector);
    }

    function qsa(selector, root) {
        return Array.from((root || document).querySelectorAll(selector));
    }

    function waitFrame() {
        return new Promise(function (resolve) {
            window.requestAnimationFrame(function () {
                resolve();
            });
        });
    }

    function wait(ms) {
        const duration = Math.max(0, Number(ms || 0));

        if (duration <= 0) {
            return Promise.resolve();
        }

        return new Promise(function (resolve) {
            window.setTimeout(resolve, duration);
        });
    }

    function normalizeStatus(status) {
        return Object.prototype.hasOwnProperty.call(STATUS_META, status) ? status : 'limited';
    }

    function isFinal(status) {
        return FINAL_STATUSES.includes(status);
    }

    function numberFromDataset(element, key, fallback) {
        const value = Number(element.dataset[key]);

        return Number.isFinite(value) && value >= 0 ? value : fallback;
    }

    function decodeBase64Utf8(value) {
        if (!value) {
            return '';
        }

        const binary = window.atob(value);

        if (window.TextDecoder) {
            const bytes = Uint8Array.from(binary, function (char) {
                return char.charCodeAt(0);
            });

            return new TextDecoder('utf-8').decode(bytes);
        }

        return decodeURIComponent(escape(binary));
    }

    function parseLines(loader) {
        const attributeBase64 = loader.getAttribute('data-umkm-readiness-lines-base64');

        if (attributeBase64) {
            try {
                const decoded = decodeBase64Utf8(attributeBase64);
                const parsed = JSON.parse(decoded || '[]');

                return Array.isArray(parsed) ? parsed : [];
            } catch (error) {
                return [
                    {
                        key: 'readiness-config',
                        label: 'Konfigurasi readiness',
                        description: 'Konfigurasi readiness tidak dapat dibaca dari payload halaman.',
                        check: 'manual',
                        status: 'failed'
                    }
                ];
            }
        }

        const attributeJson = loader.getAttribute('data-umkm-readiness-lines-json');

        if (attributeJson) {
            try {
                const parsed = JSON.parse(attributeJson);

                return Array.isArray(parsed) ? parsed : [];
            } catch (error) {
                return [
                    {
                        key: 'readiness-config',
                        label: 'Konfigurasi readiness',
                        description: 'Konfigurasi readiness tidak dapat dibaca dari atribut halaman.',
                        check: 'manual',
                        status: 'failed'
                    }
                ];
            }
        }

        const scriptId = loader.getAttribute('data-umkm-readiness-lines');
        const script = scriptId ? document.getElementById(scriptId) : null;

        if (!script) {
            return [];
        }

        try {
            const parsed = JSON.parse(script.textContent || '[]');

            return Array.isArray(parsed) ? parsed : [];
        } catch (error) {
            return [
                {
                    key: 'readiness-config',
                    label: 'Konfigurasi readiness',
                    description: 'Konfigurasi readiness tidak dapat dibaca.',
                    check: 'manual',
                    status: 'failed'
                }
            ];
        }
    }

    function buildLineElement(line, index) {
        const item = document.createElement('li');
        const icon = document.createElement('span');
        const label = document.createElement('span');
        const title = document.createElement('strong');
        const description = document.createElement('span');
        const status = document.createElement('span');

        item.className = 'umkm-readiness-line is-pending';
        item.dataset.umkmReadinessKey = line.key || ('line-' + index);
        item.dataset.umkmReadinessStatus = 'pending';

        icon.className = 'umkm-readiness-line-icon';
        icon.textContent = STATUS_META.pending.icon;

        label.className = 'umkm-readiness-line-label';
        title.textContent = line.label || ('Tahapan ' + (index + 1));
        description.textContent = line.description || 'Menunggu pemeriksaan.';

        status.className = 'umkm-readiness-line-status';
        status.textContent = STATUS_META.pending.label;

        label.appendChild(title);
        label.appendChild(description);

        item.appendChild(icon);
        item.appendChild(label);
        item.appendChild(status);

        return item;
    }

    function renderLines(loader, lines) {
        const container = qs('[data-umkm-readiness-list]', loader);

        if (!container) {
            return;
        }

        container.innerHTML = '';

        lines.forEach(function (line, index) {
            container.appendChild(buildLineElement(line, index));
        });
    }

    function getLineElement(loader, key) {
        return qs('[data-umkm-readiness-key="' + key + '"]', loader);
    }

    function updateActive(loader, line, status, message) {
        const safeStatus = normalizeStatus(status);
        const meta = STATUS_META[safeStatus] || STATUS_META.limited;
        const activeBadge = qs('[data-umkm-readiness-active-badge]', loader);
        const activeTitle = qs('[data-umkm-readiness-active-title]', loader);
        const activeMessage = qs('[data-umkm-readiness-active-message]', loader);
        const activeIcon = qs('[data-umkm-readiness-active-icon]', loader);

        if (activeBadge) {
            activeBadge.textContent = meta.label;
        }

        if (activeTitle) {
            activeTitle.textContent = line?.label || 'Kesiapan halaman';
        }

        if (activeMessage) {
            activeMessage.textContent = message || line?.description || 'Tahapan kesiapan sedang diproses.';
        }

        if (activeIcon) {
            activeIcon.textContent = meta.icon;
        }

        loader.dataset.umkmReadinessActiveStatus = safeStatus;
    }

    function updateLine(loader, line, status, message) {
        const safeStatus = normalizeStatus(status);
        const key = line.key;
        const element = getLineElement(loader, key);

        if (!element) {
            updateActive(loader, line, safeStatus, message);
            return;
        }

        const icon = qs('.umkm-readiness-line-icon', element);
        const description = qs('.umkm-readiness-line-label span', element);
        const statusLabel = qs('.umkm-readiness-line-status', element);

        element.classList.remove(
            'is-pending',
            'is-running',
            'is-success',
            'is-limited',
            'is-failed',
            'is-skipped'
        );

        element.classList.add('is-' + safeStatus);
        element.dataset.umkmReadinessStatus = safeStatus;

        if (icon) {
            icon.textContent = STATUS_META[safeStatus].icon;
        }

        if (description) {
            description.textContent = message || line.description || STATUS_META[safeStatus].label;
        }

        if (statusLabel) {
            statusLabel.textContent = STATUS_META[safeStatus].label;
        }

        updateActive(loader, line, safeStatus, message);
    }

    function updateSummary(loader, lines) {
        const counts = {
            success: 0,
            limited: 0,
            failed: 0,
            skipped: 0
        };

        lines.forEach(function (line) {
            const status = normalizeStatus(line.status);

            if (Object.prototype.hasOwnProperty.call(counts, status)) {
                counts[status] += 1;
            }
        });

        Object.keys(counts).forEach(function (status) {
            const target = qs('[data-umkm-readiness-count="' + status + '"]', loader);

            if (target) {
                target.textContent = String(counts[status]);
            }
        });

        const processed = lines.filter(function (line) {
            return isFinal(line.status);
        }).length;

        const detailSummary = qs('[data-umkm-readiness-detail-summary]', loader);

        if (detailSummary) {
            detailSummary.textContent = processed + '/' + lines.length + ' diproses';
        }
    }

    function updateProgress(loader, lines) {
        const total = lines.length || 1;
        const done = lines.filter(function (line) {
            return isFinal(line.status);
        }).length;
        const percent = Math.round((done / total) * 100);
        const bar = qs('[data-umkm-readiness-bar]', loader);
        const label = qs('[data-umkm-readiness-percent]', loader);
        const ring = qs('[data-umkm-readiness-ring]', loader);
        const dashOffset = RING_CIRCUMFERENCE - ((percent / 100) * RING_CIRCUMFERENCE);

        if (bar) {
            bar.style.width = percent + '%';
        }

        if (label) {
            label.textContent = percent + '%';
        }

        if (ring) {
            ring.style.strokeDasharray = String(RING_CIRCUMFERENCE);
            ring.style.strokeDashoffset = String(dashOffset);
        }

        loader.dataset.umkmReadinessProgress = String(percent);
        updateSummary(loader, lines);

        return percent;
    }

    function moduleReady(name) {
        if (!name) {
            return false;
        }

        return Boolean(
            UMKM.modules?.[name]?.ready ||
            UMKM[name] ||
            window[name]
        );
    }

    function evaluateLine(line) {
        const check = line.check || 'manual';

        if (line.skip === true) {
            return {
                status: 'skipped',
                message: line.skippedMessage || 'Tahapan ini tidak digunakan pada halaman ini.'
            };
        }

        if (line.status && isFinal(line.status)) {
            return {
                status: line.status,
                message: line.message || line.description
            };
        }

        if (check === 'dom') {
            return {
                status: document.body ? 'success' : 'failed',
                message: document.body ? 'Struktur halaman tersedia.' : 'Struktur halaman belum tersedia.'
            };
        }

        if (check === 'core') {
            const ok = Boolean(UMKM.state?.core?.ready || UMKM.modules?.ui?.ready);

            return {
                status: ok ? 'success' : (line.required === false ? 'limited' : 'failed'),
                message: ok ? 'Core sistem aktif.' : 'Core sistem belum terdeteksi.'
            };
        }

        if (check === 'module') {
            const ok = moduleReady(line.module);

            return {
                status: ok ? 'success' : (line.required === false ? 'limited' : 'failed'),
                message: ok
                    ? 'Modul ' + line.module + ' aktif.'
                    : 'Modul ' + line.module + ' belum tersedia.'
            };
        }

        if (check === 'selector') {
            const ok = Boolean(qs(line.selector || ''));

            return {
                status: ok ? 'success' : (line.required === false ? 'limited' : 'failed'),
                message: ok
                    ? 'Elemen halaman ditemukan.'
                    : 'Elemen halaman belum ditemukan.'
            };
        }

        if (check === 'global') {
            const ok = Boolean(window[line.global]);

            return {
                status: ok ? 'success' : (line.required === false ? 'limited' : 'failed'),
                message: ok
                    ? 'Dependensi ' + line.global + ' tersedia.'
                    : 'Dependensi ' + line.global + ' belum tersedia.'
            };
        }

        if (check === 'permission') {
            return {
                status: 'limited',
                message: line.message || 'Status izin akan diperiksa oleh modul terkait.'
            };
        }

        return {
            status: 'success',
            message: line.message || line.description || 'Tahapan diproses.'
        };
    }

    function makeSmokeResult(key, ok, severity, message) {
        return {
            key: key,
            ok: Boolean(ok),
            severity: severity || 'warning',
            message: message || ''
        };
    }

    function runSmokeGuard(loader, lines) {
        const enabled = loader.dataset.umkmReadinessSmokeGuard !== 'false';

        if (!enabled) {
            return null;
        }

        const results = [];
        const hasLanding = Boolean(qs('.umkm-landing'));
        const hasLocationModule = moduleReady('location');
        const locationGatedLinks = qsa('[data-location-gated]');

        results.push(makeSmokeResult(
            'loader-hidden',
            loader.hidden === true,
            'warning',
            'Overlay readiness harus sudah tersembunyi setelah selesai.'
        ));

        results.push(makeSmokeResult(
            'document-unlocked',
            !document.documentElement.classList.contains('umkm-readiness-lock'),
            'warning',
            'Class pengunci scroll readiness harus dilepas.'
        ));

        results.push(makeSmokeResult(
            'content-available',
            hasLanding || Boolean(document.body && document.body.children.length > 0),
            'warning',
            'Konten halaman harus tetap tersedia setelah readiness selesai.'
        ));

        results.push(makeSmokeResult(
            'csrf-meta-present',
            Boolean(qs('meta[name="csrf-token"]')),
            'warning',
            'CSRF meta harus tetap tersedia untuk request internal.'
        ));

        results.push(makeSmokeResult(
            'security-profile-present',
            Boolean(qs('meta[name="umkm-security-profile"]')),
            'warning',
            'Security profile meta harus tetap tersedia.'
        ));

        if (hasLocationModule || locationGatedLinks.length > 0) {
            results.push(makeSmokeResult(
                'location-gate-preserved',
                locationGatedLinks.length > 0,
                'warning',
                'Elemen data-location-gated harus tetap ada agar tombol masuk tidak melewati location gate.'
            ));
        }

        const failed = results.filter(function (item) {
            return !item.ok;
        });

        const status = failed.length ? 'warning' : 'clean';

        UMKM.state = UMKM.state || {};
        UMKM.state.readinessSmoke = {
            status: status,
            checkedAt: new Date().toISOString(),
            results: results,
            lines: lines
        };

        document.documentElement.setAttribute('data-umkm-readiness-smoke', status);

        document.dispatchEvent(new CustomEvent('umkm:readiness:smoke', {
            detail: UMKM.state.readinessSmoke
        }));

        if (typeof UMKM.log === 'function') {
            UMKM.log(status === 'clean' ? 'info' : 'warn', 'readiness smoke guard ' + status, UMKM.state.readinessSmoke);
        }

        return UMKM.state.readinessSmoke;
    }

    async function finishAndHide(loader, settings, startedAt, lines) {
        const elapsed = performance.now() - startedAt;
        const remainingMinVisible = Math.max(0, settings.minVisible - elapsed);

        if (remainingMinVisible > 0) {
            await wait(remainingMinVisible);
        }

        loader.classList.add('is-complete');
        loader.dataset.umkmReadinessState = 'complete';

        updateActive(loader, {
            label: 'Halaman siap ditampilkan',
            description: 'Seluruh tahapan kesiapan telah diproses.'
        }, 'success', 'Seluruh tahapan kesiapan telah diproses.');

        document.dispatchEvent(new CustomEvent('umkm:readiness:complete', {
            detail: {
                lines: lines,
                progress: 100,
                duration: Math.round(performance.now() - startedAt)
            }
        }));

        if (!settings.autoHide) {
            document.documentElement.classList.remove('umkm-readiness-lock');
            runSmokeGuard(loader, lines);
            return;
        }

        await wait(settings.completeDelay);
        await wait(settings.hideDelay);

        loader.classList.add('is-hiding');
        loader.dataset.umkmReadinessState = 'hiding';

        await wait(340);

        loader.hidden = true;
        loader.classList.remove('is-hiding');
        document.documentElement.classList.remove('umkm-readiness-lock');
        loader.dataset.umkmReadinessState = 'hidden';

        const smoke = runSmokeGuard(loader, lines);

        document.dispatchEvent(new CustomEvent('umkm:readiness:hidden', {
            detail: {
                lines: lines,
                progress: 100,
                smoke: smoke
            }
        }));
    }

    async function run(loader, options) {
        if (!loader || loader.dataset.umkmReadinessRunning === 'true') {
            return;
        }

        const settings = Object.assign({
            autoHide: true,
            hideDelay: numberFromDataset(loader, 'umkmReadinessHideDelay', 420),
            minVisible: numberFromDataset(loader, 'umkmReadinessMinVisible', 1200),
            lineDelay: numberFromDataset(loader, 'umkmReadinessLineDelay', 80),
            completeDelay: numberFromDataset(loader, 'umkmReadinessCompleteDelay', 240)
        }, options || {});

        let lines = parseLines(loader).map(function (line, index) {
            return Object.assign({
                key: line.key || ('line-' + index),
                status: 'pending'
            }, line);
        });

        if (!lines.length) {
            lines = [
                {
                    key: 'readiness-default',
                    label: 'Kesiapan halaman',
                    description: 'Halaman siap ditampilkan.',
                    check: 'dom',
                    status: 'pending'
                }
            ];
        }

        const startedAt = performance.now();

        loader.dataset.umkmReadinessRunning = 'true';
        loader.dataset.umkmReadinessState = 'running';
        loader.hidden = false;
        loader.classList.remove('is-hiding', 'is-complete');
        document.documentElement.classList.add('umkm-readiness-lock');

        renderLines(loader, lines);
        updateSummary(loader, lines);
        updateProgress(loader, lines);
        updateActive(loader, {
            label: 'Menyiapkan kesiapan halaman',
            description: 'Mohon tunggu, sistem sedang menyiapkan tampilan awal.'
        }, 'pending', 'Mohon tunggu, sistem sedang menyiapkan tampilan awal.');

        document.dispatchEvent(new CustomEvent('umkm:readiness:start', {
            detail: {
                lines: lines
            }
        }));

        for (let index = 0; index < lines.length; index += 1) {
            const line = lines[index];

            updateLine(loader, line, 'running', line.runningMessage || 'Sedang memeriksa...');
            await waitFrame();

            try {
                const result = await Promise.resolve(evaluateLine(line));
                line.status = normalizeStatus(result.status);
                line.message = result.message || line.description;

                updateLine(loader, line, line.status, line.message);
            } catch (error) {
                line.status = line.required === false ? 'limited' : 'failed';
                line.message = error && error.message ? error.message : 'Tahapan gagal diproses.';

                updateLine(loader, line, line.status, line.message);
            }

            updateProgress(loader, lines);

            if (settings.lineDelay > 0 && index < lines.length - 1) {
                await wait(settings.lineDelay);
            } else {
                await waitFrame();
            }
        }

        loader.dataset.umkmReadinessRunning = 'false';
        loader.dataset.umkmReadinessComplete = 'true';

        await finishAndHide(loader, settings, startedAt, lines);
    }

    function runAll() {
        qsa('[data-umkm-readiness-loader]').forEach(function (loader) {
            run(loader, {
                autoHide: loader.dataset.umkmReadinessAutoHide !== 'false',
                hideDelay: numberFromDataset(loader, 'umkmReadinessHideDelay', 420),
                minVisible: numberFromDataset(loader, 'umkmReadinessMinVisible', 1200),
                lineDelay: numberFromDataset(loader, 'umkmReadinessLineDelay', 80),
                completeDelay: numberFromDataset(loader, 'umkmReadinessCompleteDelay', 240)
            });
        });
    }

    const api = {
        run: run,
        runAll: runAll,
        smoke: runSmokeGuard,
        statusMeta: STATUS_META
    };

    if (typeof UMKM.register === 'function') {
        UMKM.register('readiness', api);
    } else {
        UMKM.readiness = api;
    }

    ready(runAll);
})();
