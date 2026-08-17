(() => {
    'use strict';

    const payloadNode = document.getElementById('adminDinasSpatialPayload');
    const svg = document.getElementById('adminDinasSpatialMap');

    if (!payloadNode || !svg) {
        return;
    }

    let payload = {};

    try {
        payload = JSON.parse(payloadNode.textContent || '{}');
    } catch (error) {
        svg.innerHTML = '<text x="24" y="48">Payload peta tidak dapat dibaca.</text>';
        return;
    }

    const features = Array.isArray(payload?.geometry?.features)
        ? payload.geometry.features
        : [];

    const points = Array.isArray(payload?.points)
        ? payload.points
        : [];

    const metricSelect = document.getElementById('spatialMetric');
    const pointToggle = document.getElementById('spatialPointToggle');

    const detail = {
        name: document.getElementById('spatialRegionName'),
        umkm: document.getElementById('spatialRegionUmkm'),
        workers: document.getElementById('spatialRegionWorkers'),
        quality: document.getElementById('spatialRegionQuality'),
        financial: document.getElementById('spatialRegionFinancial'),
        category: document.getElementById('spatialRegionCategory'),
        link: document.getElementById('spatialRegionDataLink'),
    };

    const WIDTH = 1000;
    const HEIGHT = 620;
    const PAD = 28;

    const allCoordinates = [];

    const collectCoordinates = (value) => {
        if (!Array.isArray(value)) {
            return;
        }

        if (
            value.length >= 2
            && typeof value[0] === 'number'
            && typeof value[1] === 'number'
        ) {
            allCoordinates.push([value[0], value[1]]);
            return;
        }

        value.forEach(collectCoordinates);
    };

    features.forEach((feature) => {
        collectCoordinates(feature?.geometry?.coordinates);
    });

    if (allCoordinates.length < 1) {
        svg.innerHTML = [
            '<rect x="0" y="0" width="1000" height="620" fill="#f5f8f7"></rect>',
            '<text x="500" y="300" text-anchor="middle" fill="#52625c" font-size="22">',
            'Geometry wilayah belum tersedia pada konteks ini.',
            '</text>',
        ].join('');
        return;
    }

    const longitudes = allCoordinates.map((coordinate) => coordinate[0]);
    const latitudes = allCoordinates.map((coordinate) => coordinate[1]);

    let minX = Math.min(...longitudes);
    let maxX = Math.max(...longitudes);
    let minY = Math.min(...latitudes);
    let maxY = Math.max(...latitudes);

    if (minX === maxX) {
        minX -= 0.01;
        maxX += 0.01;
    }

    if (minY === maxY) {
        minY -= 0.01;
        maxY += 0.01;
    }

    const project = (longitude, latitude) => {
        const x = PAD + ((longitude - minX) / (maxX - minX)) * (WIDTH - (PAD * 2));
        const y = HEIGHT - PAD - ((latitude - minY) / (maxY - minY)) * (HEIGHT - (PAD * 2));

        return [x, y];
    };

    const ringPath = (ring) => {
        if (!Array.isArray(ring) || ring.length < 1) {
            return '';
        }

        return ring.map((coordinate, index) => {
            const [x, y] = project(Number(coordinate[0]), Number(coordinate[1]));

            return `${index === 0 ? 'M' : 'L'} ${x.toFixed(2)} ${y.toFixed(2)}`;
        }).join(' ') + ' Z';
    };

    const geometryPath = (geometry) => {
        if (!geometry || !Array.isArray(geometry.coordinates)) {
            return '';
        }

        if (geometry.type === 'Polygon') {
            return geometry.coordinates.map(ringPath).join(' ');
        }

        if (geometry.type === 'MultiPolygon') {
            return geometry.coordinates
                .flatMap((polygon) => polygon.map(ringPath))
                .join(' ');
        }

        return '';
    };

    const numberFormat = new Intl.NumberFormat('id-ID');

    const metricValue = (feature, metric) => {
        const value = Number(feature?.properties?.[metric] ?? 0);

        return Number.isFinite(value) ? value : 0;
    };

    const metricMax = (metric) => {
        return Math.max(
            1,
            ...features.map((feature) => metricValue(feature, metric))
        );
    };

    const fillFor = (value, max) => {
        if (value <= 0) {
            return '#edf4f1';
        }

        const ratio = Math.min(1, value / Math.max(1, max));

        if (ratio >= 0.8) return '#0b6b57';
        if (ratio >= 0.6) return '#15866d';
        if (ratio >= 0.4) return '#36a68b';
        if (ratio >= 0.2) return '#7bc8b5';

        return '#bfe5da';
    };

    const createSvgElement = (name, attributes = {}) => {
        const element = document.createElementNS('http://www.w3.org/2000/svg', name);

        Object.entries(attributes).forEach(([key, value]) => {
            element.setAttribute(key, String(value));
        });

        return element;
    };

    const clearDetail = () => {
        if (detail.name) detail.name.textContent = 'Pilih wilayah pada peta';
        if (detail.umkm) detail.umkm.textContent = '—';
        if (detail.workers) detail.workers.textContent = '—';
        if (detail.quality) detail.quality.textContent = '—';
        if (detail.financial) detail.financial.textContent = '—';
        if (detail.category) detail.category.textContent = '—';

        if (detail.link) {
            detail.link.href = '#';
            detail.link.classList.add('disabled');
            detail.link.setAttribute('aria-disabled', 'true');
        }
    };

    const showDetail = (feature) => {
        const properties = feature?.properties ?? {};

        if (detail.name) {
            detail.name.textContent = properties.region_label
                || properties.region_name
                || 'Wilayah';
        }

        if (detail.umkm) {
            detail.umkm.textContent = numberFormat.format(Number(properties.umkm_total || 0));
        }

        if (detail.workers) {
            detail.workers.textContent = numberFormat.format(Number(properties.workers_total || 0));
        }

        if (detail.quality) {
            detail.quality.textContent = numberFormat.format(Number(properties.quality_affected || 0));
        }

        if (detail.financial) {
            detail.financial.textContent = numberFormat.format(Number(properties.financial_filled || 0));
        }

        if (detail.category) {
            detail.category.textContent = properties.dominant_category || 'Belum tersedia';
        }

        if (detail.link && properties.data_url) {
            detail.link.href = properties.data_url;
            detail.link.classList.remove('disabled');
            detail.link.removeAttribute('aria-disabled');
        }
    };

    const render = () => {
        const metric = metricSelect?.value || 'umkm_total';
        const max = metricMax(metric);

        svg.replaceChildren();

        const background = createSvgElement('rect', {
            x: 0,
            y: 0,
            width: WIDTH,
            height: HEIGHT,
            fill: '#f7faf9',
        });

        svg.appendChild(background);

        const mapGroup = createSvgElement('g', {
            'aria-label': 'Wilayah administratif',
        });

        features.forEach((feature) => {
            const pathData = geometryPath(feature.geometry);

            if (!pathData) {
                return;
            }

            const value = metricValue(feature, metric);
            const properties = feature.properties ?? {};

            const path = createSvgElement('path', {
                d: pathData,
                fill: fillFor(value, max),
                stroke: '#ffffff',
                'stroke-width': 2,
                'fill-rule': 'evenodd',
                tabindex: 0,
                role: 'button',
                'aria-label': `${properties.region_label || properties.region_name || 'Wilayah'}: ${numberFormat.format(value)}`,
                style: 'cursor:pointer; transition: opacity .15s ease, stroke-width .15s ease;',
            });

            const title = createSvgElement('title');
            title.textContent = [
                properties.region_label || properties.region_name || 'Wilayah',
                numberFormat.format(value),
            ].join(' — ');

            path.appendChild(title);

            const activate = () => {
                mapGroup.querySelectorAll('path').forEach((item) => {
                    item.setAttribute('stroke-width', '2');
                    item.setAttribute('opacity', '0.9');
                });

                path.setAttribute('stroke-width', '5');
                path.setAttribute('opacity', '1');
                showDetail(feature);
            };

            path.addEventListener('click', activate);
            path.addEventListener('focus', activate);
            path.addEventListener('mouseenter', () => path.setAttribute('opacity', '1'));
            path.addEventListener('mouseleave', () => {
                if (path.getAttribute('stroke-width') !== '5') {
                    path.setAttribute('opacity', '0.9');
                }
            });

            mapGroup.appendChild(path);
        });

        svg.appendChild(mapGroup);

        if (
            payload.coordinateAccess === true
            && pointToggle?.checked === true
            && points.length > 0
        ) {
            const pointGroup = createSvgElement('g', {
                'aria-label': 'Titik UMKM coordinate-mapped',
            });

            points.forEach((point) => {
                const [x, y] = project(
                    Number(point.longitude),
                    Number(point.latitude)
                );

                const circle = createSvgElement('circle', {
                    cx: x.toFixed(2),
                    cy: y.toFixed(2),
                    r: 4.2,
                    fill: '#0d6efd',
                    stroke: '#ffffff',
                    'stroke-width': 1.5,
                    tabindex: 0,
                    role: 'link',
                    'aria-label': point.business_name || point.umkm_code || 'UMKM',
                    style: 'cursor:pointer;',
                });

                const title = createSvgElement('title');
                title.textContent = [
                    point.business_name || point.umkm_code || 'UMKM',
                    point.village_name || point.district_name || '',
                ].filter(Boolean).join(' — ');

                circle.appendChild(title);

                const openDetail = () => {
                    if (point.detail_url) {
                        window.location.href = point.detail_url;
                    }
                };

                circle.addEventListener('click', openDetail);
                circle.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        openDetail();
                    }
                });

                pointGroup.appendChild(circle);
            });

            svg.appendChild(pointGroup);
        }
    };

    metricSelect?.addEventListener('change', () => {
        clearDetail();
        render();
    });

    pointToggle?.addEventListener('change', render);

    clearDetail();
    render();
})();
