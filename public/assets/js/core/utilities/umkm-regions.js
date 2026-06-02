(function () {
    'use strict';

    window.UMKM = window.UMKM || {};

    const UMKM = window.UMKM;

    const BASE_URL = '/api/internal/regions';

    const LEVELS = Object.freeze({
        PROVINCE: 'province',
        CITY: 'city',
        DISTRICT: 'district',
        VILLAGE: 'village',
    });

    const NEXT_LEVEL = Object.freeze({
        province: 'city',
        city: 'district',
        district: 'village',
        village: null,
    });

    const DEFAULT_LIMITS = Object.freeze({
        provinces: 100,
        children: 200,
        search: 50,
    });

    const cache = {
        provinces: null,
        children: new Map(),
        search: new Map(),
        regions: new Map(),
        breadcrumbs: new Map(),
    };

    function now() {
        return new Date().toISOString();
    }

    function hasAjax() {
        return Boolean(UMKM.ajax && typeof UMKM.ajax.get === 'function');
    }

    function emit(name, detail) {
        const payload = Object.assign({
            module: 'regions',
            emitted_at: now(),
        }, detail || {});

        if (UMKM.events && typeof UMKM.events.emit === 'function') {
            UMKM.events.emit(`regions:${name}`, payload);
            return;
        }

        document.dispatchEvent(new CustomEvent(`umkm:regions:${name}`, {
            detail: payload,
        }));
    }

    function log(level, message, data) {
        if (typeof UMKM.log === 'function') {
            UMKM.log(level || 'info', message, data);
        }
    }

    function fail(message, status, errors, meta) {
        const payload = {
            ok: false,
            status: status || 0,
            statusText: status ? 'REQUEST_ERROR' : 'CLIENT_ERROR',
            message: message || 'Permintaan wilayah tidak dapat diproses.',
            data: null,
            meta: meta || {},
            errors: errors || null,
            raw: null,
        };

        emit('error', payload);

        return Promise.resolve(payload);
    }

    function normalizeString(value) {
        return String(value === undefined || value === null ? '' : value).trim();
    }

    function normalizeCode(code) {
        return normalizeString(code);
    }

    function isValidCode(code) {
        const normalized = normalizeCode(code);

        return Boolean(
            normalized &&
            normalized.length <= 13 &&
            /^[0-9.]+$/.test(normalized)
        );
    }

    function normalizeLevel(level) {
        const normalized = normalizeString(level).toLowerCase();

        if (!normalized) {
            return '';
        }

        return Object.values(LEVELS).includes(normalized) ? normalized : '';
    }

    function normalizeLimit(value, fallback, maximum) {
        const parsed = Number.parseInt(value, 10);

        if (Number.isNaN(parsed)) {
            return fallback;
        }

        return Math.min(Math.max(parsed, 1), maximum || 500);
    }

    function normalizeKeyword(keyword) {
        return normalizeString(keyword).replace(/\s+/g, ' ');
    }

    function queryString(params) {
        const searchParams = new URLSearchParams();

        Object.keys(params || {}).forEach(function (key) {
            const value = params[key];

            if (value === undefined || value === null || value === '') {
                return;
            }

            searchParams.append(key, value);
        });

        const result = searchParams.toString();

        return result ? `?${result}` : '';
    }

    function url(path, params) {
        const normalizedPath = path ? `/${String(path).replace(/^\/+/, '')}` : '';

        return `${BASE_URL}${normalizedPath}${queryString(params || {})}`;
    }

    function cacheKey(parts) {
        return parts
            .map(function (part) {
                return normalizeString(part);
            })
            .join('|');
    }

    function normalizeResponse(result) {
        const payload = result && result.payload !== undefined ? result.payload : null;
        const isJsonObject = payload && typeof payload === 'object' && !Array.isArray(payload);

        return {
            ok: Boolean(result && result.ok),
            status: result && result.status ? result.status : 0,
            statusText: result && result.statusText ? result.statusText : '',
            message: isJsonObject && payload.message ? payload.message : null,
            data: isJsonObject && payload.data !== undefined ? payload.data : payload,
            meta: isJsonObject && payload.meta ? payload.meta : {},
            errors: isJsonObject && payload.errors ? payload.errors : null,
            raw: result || null,
        };
    }

    async function request(path, params, options) {
        const requestOptions = Object.assign({
            cache: true,
            throwOnError: false,
            signal: null,
        }, options || {});

        if (!hasAjax()) {
            return fail(
                'Core UMKM.ajax belum aktif. Permintaan wilayah wajib melalui one-door request.',
                0,
                null,
                { dependency: 'UMKM.ajax' }
            );
        }

        const targetUrl = url(path, params);

        emit('loading', {
            url: targetUrl,
            params: params || {},
        });

        const result = await UMKM.ajax.get(targetUrl, {
            signal: requestOptions.signal || undefined,
        });

        const normalized = normalizeResponse(result);

        if (!normalized.ok) {
            emit('error', normalized);

            if (requestOptions.throwOnError) {
                throw normalized;
            }

            return normalized;
        }

        emit('loaded', {
            url: targetUrl,
            status: normalized.status,
            meta: normalized.meta,
        });

        return normalized;
    }

    function cloneCached(value) {
        if (value === null || value === undefined) {
            return value;
        }

        try {
            return JSON.parse(JSON.stringify(value));
        } catch (error) {
            return value;
        }
    }

    function getFromCache(store, key, force) {
        if (force) {
            return null;
        }

        if (!store || !store.has(key)) {
            return null;
        }

        return cloneCached(store.get(key));
    }

    function setCache(store, key, value) {
        if (!store) {
            return;
        }

        store.set(key, cloneCached(value));
    }

    async function getProvinces(options) {
        const requestOptions = Object.assign({
            force: false,
            limit: DEFAULT_LIMITS.provinces,
        }, options || {});

        if (!requestOptions.force && cache.provinces) {
            return cloneCached(cache.provinces);
        }

        const response = await request('/provinces', {
            limit: normalizeLimit(requestOptions.limit, DEFAULT_LIMITS.provinces, 500),
        }, requestOptions);

        if (response.ok) {
            cache.provinces = cloneCached(response);
        }

        return response;
    }

    async function getChildren(parentCode, level, options) {
        const requestOptions = Object.assign({
            force: false,
            limit: DEFAULT_LIMITS.children,
            q: '',
        }, options || {});

        const normalizedParentCode = normalizeCode(parentCode);
        const normalizedLevel = normalizeLevel(level);
        const keyword = normalizeKeyword(requestOptions.q);

        if (!normalizedParentCode && !normalizedLevel) {
            return fail(
                'Parameter parent_code atau level wajib diisi untuk membaca anak wilayah.',
                422,
                {
                    parent_code: [
                        'Isi parent_code untuk membaca anak wilayah, atau isi level untuk membaca wilayah level tertentu.',
                    ],
                }
            );
        }

        if (normalizedParentCode && !isValidCode(normalizedParentCode)) {
            return fail(
                'Kode parent wilayah tidak valid.',
                422,
                {
                    parent_code: [
                        'Kode wilayah hanya boleh berisi angka dan titik.',
                    ],
                }
            );
        }

        const key = cacheKey([
            normalizedParentCode,
            normalizedLevel,
            keyword,
            requestOptions.limit,
        ]);

        const cached = getFromCache(cache.children, key, requestOptions.force);

        if (cached) {
            return cached;
        }

        const response = await request('/children', {
            parent_code: normalizedParentCode || null,
            level: normalizedLevel || null,
            q: keyword || null,
            limit: normalizeLimit(requestOptions.limit, DEFAULT_LIMITS.children, 500),
        }, requestOptions);

        if (response.ok) {
            setCache(cache.children, key, response);
        }

        return response;
    }

    async function searchRegions(keyword, options) {
        const requestOptions = Object.assign({
            force: false,
            level: '',
            parent_code: '',
            limit: DEFAULT_LIMITS.search,
        }, options || {});

        const normalizedKeyword = normalizeKeyword(keyword);
        const normalizedLevel = normalizeLevel(requestOptions.level);
        const normalizedParentCode = normalizeCode(requestOptions.parent_code);

        if (normalizedKeyword.length < 2) {
            return fail(
                'Kata kunci pencarian wilayah minimal 2 karakter.',
                422,
                {
                    q: [
                        'Kata kunci pencarian wilayah minimal 2 karakter.',
                    ],
                }
            );
        }

        if (requestOptions.level && !normalizedLevel) {
            return fail(
                'Level wilayah tidak valid.',
                422,
                {
                    level: [
                        'Level wilayah harus province, city, district, atau village.',
                    ],
                }
            );
        }

        if (normalizedParentCode && !isValidCode(normalizedParentCode)) {
            return fail(
                'Kode parent wilayah tidak valid.',
                422,
                {
                    parent_code: [
                        'Kode wilayah hanya boleh berisi angka dan titik.',
                    ],
                }
            );
        }

        const key = cacheKey([
            normalizedKeyword,
            normalizedLevel,
            normalizedParentCode,
            requestOptions.limit,
        ]);

        const cached = getFromCache(cache.search, key, requestOptions.force);

        if (cached) {
            return cached;
        }

        const response = await request('/search', {
            q: normalizedKeyword,
            level: normalizedLevel || null,
            parent_code: normalizedParentCode || null,
            limit: normalizeLimit(requestOptions.limit, DEFAULT_LIMITS.search, 100),
        }, requestOptions);

        if (response.ok) {
            setCache(cache.search, key, response);
        }

        return response;
    }

    async function getRegion(code, options) {
        const requestOptions = Object.assign({
            force: false,
        }, options || {});

        const normalizedCode = normalizeCode(code);

        if (!isValidCode(normalizedCode)) {
            return fail(
                'Kode wilayah tidak valid.',
                422,
                {
                    code: [
                        'Kode wilayah hanya boleh berisi angka dan titik.',
                    ],
                }
            );
        }

        const cached = getFromCache(cache.regions, normalizedCode, requestOptions.force);

        if (cached) {
            return cached;
        }

        const response = await request(`/${encodeURIComponent(normalizedCode)}`, {}, requestOptions);

        if (response.ok) {
            setCache(cache.regions, normalizedCode, response);
        }

        return response;
    }

    async function getBreadcrumb(code, options) {
        const requestOptions = Object.assign({
            force: false,
        }, options || {});

        const normalizedCode = normalizeCode(code);

        if (!isValidCode(normalizedCode)) {
            return fail(
                'Kode wilayah tidak valid.',
                422,
                {
                    code: [
                        'Kode wilayah hanya boleh berisi angka dan titik.',
                    ],
                }
            );
        }

        const cached = getFromCache(cache.breadcrumbs, normalizedCode, requestOptions.force);

        if (cached) {
            return cached;
        }

        const response = await request(`/${encodeURIComponent(normalizedCode)}/breadcrumb`, {}, requestOptions);

        if (response.ok) {
            setCache(cache.breadcrumbs, normalizedCode, response);
        }

        return response;
    }

    function inferNextLevel(parentLevel) {
        const normalizedLevel = normalizeLevel(parentLevel);

        return normalizedLevel ? NEXT_LEVEL[normalizedLevel] : null;
    }

    function inferLevelByCode(code) {
        const normalizedCode = normalizeCode(code);

        if (!isValidCode(normalizedCode)) {
            return null;
        }

        const segmentCount = normalizedCode.split('.').length;

        if (segmentCount === 1) {
            return LEVELS.PROVINCE;
        }

        if (segmentCount === 2) {
            return LEVELS.CITY;
        }

        if (segmentCount === 3) {
            return LEVELS.DISTRICT;
        }

        if (segmentCount === 4) {
            return LEVELS.VILLAGE;
        }

        return null;
    }

    function clearCache(scope) {
        const normalizedScope = normalizeString(scope);

        if (!normalizedScope || normalizedScope === 'all') {
            cache.provinces = null;
            cache.children.clear();
            cache.search.clear();
            cache.regions.clear();
            cache.breadcrumbs.clear();

            emit('cache-cleared', { scope: 'all' });

            return;
        }

        if (normalizedScope === 'provinces') {
            cache.provinces = null;
        }

        if (cache[normalizedScope] && typeof cache[normalizedScope].clear === 'function') {
            cache[normalizedScope].clear();
        }

        emit('cache-cleared', { scope: normalizedScope });
    }

    function status() {
        return {
            ready: hasAjax(),
            base_url: BASE_URL,
            levels: Object.assign({}, LEVELS),
            cache: {
                provinces: Boolean(cache.provinces),
                children: cache.children.size,
                search: cache.search.size,
                regions: cache.regions.size,
                breadcrumbs: cache.breadcrumbs.size,
            },
            checked_at: now(),
        };
    }

    UMKM.register?.('regions', {
        levels: LEVELS,
        nextLevel: inferNextLevel,
        inferLevelByCode: inferLevelByCode,
        getProvinces: getProvinces,
        getChildren: getChildren,
        searchRegions: searchRegions,
        getRegion: getRegion,
        getBreadcrumb: getBreadcrumb,
        clearCache: clearCache,
        status: status,
    });

    log('info', 'regions service initialized', status());
})();
