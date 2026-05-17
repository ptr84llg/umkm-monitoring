(function () {
    'use strict';

    window.UMKM = window.UMKM || {};

    const UMKM = window.UMKM;

    const FINAL_STATUSES = ['success', 'limited', 'failed', 'skipped'];

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

    function normalizeStatus(status) {
        return Object.prototype.hasOwnProperty.call(STATUS_META, status) ? status : 'limited';
    }

    function isFinal(status) {
        return FINAL_STATUSES.includes(status);
    }

    function parseLines(loader) {
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

    function updateLine(loader, line, status, message) {
        const safeStatus = normalizeStatus(status);
        const key = line.key;
        const element = getLineElement(loader, key);

        if (!element) {
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
    }

    function updateProgress(loader, lines) {
        const total = lines.length || 1;
        const done = lines.filter(function (line) {
            return isFinal(line.status);
        }).length;
        const percent = Math.round((done / total) * 100);
        const bar = qs('[data-umkm-readiness-bar]', loader);
        const label = qs('[data-umkm-readiness-percent]', loader);

        if (bar) {
            bar.style.width = percent + '%';
        }

        if (label) {
            label.textContent = percent + '%';
        }

        loader.dataset.umkmReadinessProgress = String(percent);

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

    async function run(loader, options) {
        if (!loader || loader.dataset.umkmReadinessRunning === 'true') {
            return;
        }

        const settings = Object.assign({
            autoHide: true,
            hideDelay: 260
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

        loader.dataset.umkmReadinessRunning = 'true';
        loader.hidden = false;
        document.documentElement.classList.add('umkm-readiness-lock');

        renderLines(loader, lines);
        updateProgress(loader, lines);

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
            await waitFrame();
        }

        loader.dataset.umkmReadinessRunning = 'false';
        loader.dataset.umkmReadinessComplete = 'true';

        document.dispatchEvent(new CustomEvent('umkm:readiness:complete', {
            detail: {
                lines: lines,
                progress: 100
            }
        }));

        if (settings.autoHide) {
            window.setTimeout(function () {
                loader.classList.add('is-hiding');

                window.setTimeout(function () {
                    loader.hidden = true;
                    loader.classList.remove('is-hiding');
                    document.documentElement.classList.remove('umkm-readiness-lock');
                }, 280);
            }, settings.hideDelay);
        }
    }

    function runAll() {
        qsa('[data-umkm-readiness-loader]').forEach(function (loader) {
            run(loader, {
                autoHide: loader.dataset.umkmReadinessAutoHide !== 'false',
                hideDelay: Number(loader.dataset.umkmReadinessHideDelay || 260)
            });
        });
    }

    const api = {
        run: run,
        runAll: runAll,
        statusMeta: STATUS_META
    };

    if (typeof UMKM.register === 'function') {
        UMKM.register('readiness', api);
    } else {
        UMKM.readiness = api;
    }

    ready(runAll);
})();
