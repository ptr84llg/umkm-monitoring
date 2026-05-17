@php
    $assetProfile = $assetProfile ?? 'full';

    $vendorCss = $vendorCss ?? [];
    $vendorJs = $vendorJs ?? [];
    $pageCss = $pageCss ?? [];
    $pageJs = $pageJs ?? [];
    $assetModules = $assetModules ?? [];

    $vendorLocalCss = [
        'bootstrap/5.3.8/css/bootstrap.min.css',
    ];

    $vendorLocalJs = [
        'bootstrap/5.3.8/js/bootstrap.bundle.min.js',
    ];

    $vendorLocalCssModules = [
        'tabulator' => [
            'tabulator/css/tabulator_bootstrap5.min.css',
        ],
    ];

    $vendorLocalJsModules = [
        'vue' => [
            'vue/vue.global.prod.js',
        ],
        'echarts' => [
            'echarts/echarts.min.js',
        ],
        'tabulator' => [
            'tabulator/js/tabulator.min.js',
        ],
    ];

    $coreThemeCss = [
        'themes/umkm-theme-blue.css',
        'themes/umkm-theme-green.css',
        'themes/umkm-theme-maroon.css',
        'themes/umkm-theme-gold.css',
        'themes/umkm-theme-gradient-1.css',
        'themes/umkm-theme-gradient-2.css',
        'themes/umkm-theme-gradient-3.css',
    ];

    $coreCssBase = array_merge([
        'umkm-theme.css',
    ], $coreThemeCss, [
        'umkm-bootstrap-bridge.css',
        'umkm-scrollbar.css',
        'umkm-ui.css',
        'umkm-buttons.css',
        'umkm-cards.css',
        'umkm-badges.css',
        'umkm-forms.css',
        'umkm-modals.css',
        'umkm-toast.css',
        'umkm-footer.css',
        'umkm-security.css',
    ]);

    $coreCssModules = [
        'loader' => 'umkm-loader.css',
        'readiness' => 'umkm-loader.css',
        'tables' => 'umkm-tables.css',
        'datatables' => 'umkm-datatables.css',
        'tabulator' => 'umkm-tabulator.css',
        'map' => 'umkm-map.css',
        'charts' => 'umkm-charts.css',
        'echarts' => 'umkm-charts.css',
        'security' => 'umkm-security.css',
    ];

    $coreJsBase = [
        'umkm-ui.js',
        'umkm-ajax.js',
        'umkm-security.js',
        'umkm-modal.js',
        'umkm-confirm.js',
        'umkm-toast.js',
        'umkm-forms.js',
    ];

    $coreJsModules = [
        'ajax' => 'umkm-ajax.js',
        'security' => 'umkm-security.js',
        'loader' => 'umkm-loader.js',
        'readiness' => 'umkm-readiness.js',
        'location' => 'umkm-location.js',
        'modal' => 'umkm-modal.js',
        'locationGate' => 'umkm-location-gate.js',
        'session' => 'umkm-session.js',
        'datatables' => 'umkm-datatables.js',
        'tabulator' => 'umkm-tabulator.js',
        'wizard' => 'umkm-wizard.js',
        'map' => 'umkm-map.js',
        'charts' => 'umkm-charts.js',
        'echarts' => 'umkm-echarts.js',
        'vue' => 'umkm-vue.js',
    ];

    $moduleDependencies = [
        'locationGate' => ['location', 'modal'],
        'readiness' => ['loader'],
        'datatables' => ['loader'],
        'tabulator' => ['loader', 'tables'],
        'wizard' => ['loader'],
        'map' => ['loader'],
        'charts' => ['loader'],
        'echarts' => ['loader', 'charts'],
        'vue' => ['loader'],
    ];

    $requestedModules = [];

    foreach ($assetModules as $module) {
        if (! is_string($module) || trim($module) === '') {
            continue;
        }

        $module = trim($module);

        foreach (($moduleDependencies[$module] ?? []) as $dependency) {
            $requestedModules[] = $dependency;
        }

        $requestedModules[] = $module;
    }

    $assetModules = array_values(array_unique($requestedModules));

    foreach ($assetModules as $module) {
        if (isset($vendorLocalCssModules[$module])) {
            foreach ($vendorLocalCssModules[$module] as $file) {
                $vendorLocalCss[] = $file;
            }
        }

        if (isset($vendorLocalJsModules[$module])) {
            foreach ($vendorLocalJsModules[$module] as $file) {
                $vendorLocalJs[] = $file;
            }
        }
    }

    if ($assetProfile === 'landing') {
        $coreCss = array_merge([
            'umkm-theme.css',
        ], $coreThemeCss, [
            'umkm-bootstrap-bridge.css',
            'umkm-scrollbar.css',
            'umkm-ui.css',
            'umkm-buttons.css',
            'umkm-cards.css',
            'umkm-badges.css',
            'umkm-footer.css',
            'umkm-security.css',
        ]);

        $coreJs = [
            'umkm-ui.js',
            'umkm-ajax.js',
            'umkm-security.js',
        ];
    } elseif ($assetProfile === 'base') {
        $coreCss = $coreCssBase;
        $coreJs = $coreJsBase;
    } else {
        $coreCss = array_merge($coreCssBase, array_values($coreCssModules));
        $coreJs = array_merge($coreJsBase, array_values($coreJsModules));
    }

    foreach ($assetModules as $module) {
        if (isset($coreCssModules[$module])) {
            $coreCss[] = $coreCssModules[$module];
        }

        if (isset($coreJsModules[$module])) {
            $coreJs[] = $coreJsModules[$module];
        }
    }

    $vendorLocalCss = array_values(array_unique($vendorLocalCss));
    $vendorLocalJs = array_values(array_unique($vendorLocalJs));
    $coreCss = array_values(array_unique($coreCss));
    $coreJs = array_values(array_unique($coreJs));
    $pageCss = array_values(array_unique($pageCss));
    $pageJs = array_values(array_unique($pageJs));
    $vendorCss = array_values(array_unique($vendorCss));
    $vendorJs = array_values(array_unique($vendorJs));
@endphp

@foreach($vendorCss as $file)
    <link rel="stylesheet" href="{{ $file }}">
@endforeach

@foreach($vendorLocalCss as $file)
    <link rel="stylesheet" href="{{ asset('assets/vendor/'.$file) }}">
@endforeach

@foreach($coreCss as $file)
    <link rel="stylesheet" href="{{ asset('assets/css/core/'.$file) }}">
@endforeach

@foreach($pageCss as $file)
    <link rel="stylesheet" href="{{ asset('assets/css/pages/'.$file) }}">
@endforeach

@foreach($vendorJs as $file)
    <script src="{{ $file }}" defer></script>
@endforeach

@foreach($vendorLocalJs as $file)
    <script src="{{ asset('assets/vendor/'.$file) }}" defer></script>
@endforeach

@foreach($coreJs as $file)
    <script src="{{ asset('assets/js/core/'.$file) }}" defer></script>
@endforeach

@foreach($pageJs as $file)
    <script src="{{ asset('assets/js/pages/'.$file) }}" defer></script>
@endforeach
