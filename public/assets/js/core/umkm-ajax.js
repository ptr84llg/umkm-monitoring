(function () {
    'use strict';

    window.UMKM = window.UMKM || {};

    const UMKM = window.UMKM;

    function getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    function normalizeHeaders(headers) {
        const baseHeaders = {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-UMKM-Request': 'internal',
            'X-UMKM-Client': UMKM.config?.client || 'web',
            'X-UMKM-Security-Profile': UMKM.config?.profile || 'default'
        };

        const csrfToken = getCsrfToken();

        if (csrfToken) {
            baseHeaders['X-CSRF-TOKEN'] = csrfToken;
        }

        return Object.assign(baseHeaders, headers || {});
    }

    function buildOptions(options) {
        const method = (options.method || 'GET').toUpperCase();
        const headers = normalizeHeaders(options.headers);

        const requestOptions = {
            method: method,
            credentials: 'same-origin',
            headers: headers,
            cache: options.cache || 'no-store',
            redirect: options.redirect || 'follow'
        };

        if (options.signal) {
            requestOptions.signal = options.signal;
        }

        if (options.body !== undefined && options.body !== null) {
            if (options.body instanceof FormData) {
                requestOptions.body = options.body;
            } else if (typeof options.body === 'string') {
                requestOptions.body = options.body;
                headers['Content-Type'] = headers['Content-Type'] || 'application/json';
            } else {
                requestOptions.body = JSON.stringify(options.body);
                headers['Content-Type'] = headers['Content-Type'] || 'application/json';
            }
        }

        return requestOptions;
    }

    async function parseResponse(response) {
        const contentType = response.headers.get('content-type') || '';

        if (contentType.includes('application/json')) {
            return await response.json();
        }

        return await response.text();
    }

    async function request(url, options) {
        const requestOptions = buildOptions(options || {});
        const startedAt = performance.now();

        UMKM.log?.('info', 'ajax request', {
            url: url,
            method: requestOptions.method
        });

        try {
            const response = await fetch(url, requestOptions);
            const payload = await parseResponse(response);
            const duration = Math.round(performance.now() - startedAt);

            const result = {
                ok: response.ok,
                status: response.status,
                statusText: response.statusText,
                payload: payload,
                duration: duration,
                response: response
            };

            document.dispatchEvent(new CustomEvent('umkm:ajax:complete', {
                detail: result
            }));

            if (!response.ok) {
                document.dispatchEvent(new CustomEvent('umkm:ajax:error', {
                    detail: result
                }));
            }

            UMKM.log?.(response.ok ? 'info' : 'warn', 'ajax complete', {
                url: url,
                status: response.status,
                duration: duration
            });

            return result;
        } catch (error) {
            const result = {
                ok: false,
                status: 0,
                statusText: 'NETWORK_ERROR',
                error: error,
                duration: Math.round(performance.now() - startedAt)
            };

            document.dispatchEvent(new CustomEvent('umkm:ajax:error', {
                detail: result
            }));

            UMKM.log?.('error', 'ajax failed', result);

            return result;
        }
    }

    function get(url, options) {
        return request(url, Object.assign({}, options || {}, {
            method: 'GET'
        }));
    }

    function post(url, body, options) {
        return request(url, Object.assign({}, options || {}, {
            method: 'POST',
            body: body
        }));
    }

    function put(url, body, options) {
        return request(url, Object.assign({}, options || {}, {
            method: 'PUT',
            body: body
        }));
    }

    function patch(url, body, options) {
        return request(url, Object.assign({}, options || {}, {
            method: 'PATCH',
            body: body
        }));
    }

    function destroy(url, body, options) {
        return request(url, Object.assign({}, options || {}, {
            method: 'DELETE',
            body: body
        }));
    }

    UMKM.register?.('ajax', {
        request: request,
        get: get,
        post: post,
        put: put,
        patch: patch,
        delete: destroy,
        headers: normalizeHeaders,
        csrfToken: getCsrfToken
    });
})();
