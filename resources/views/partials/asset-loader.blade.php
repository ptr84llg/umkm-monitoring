@php
    $assetProfile = $assetProfile ?? 'full';

    $coreCssBase = [
        'bootstrap-local.css',
        'umkm-theme.css',
        'umkm-ui.css',
        'umkm-buttons.css',
        'umkm-cards.css',
        'umkm-badges.css',
        'umkm-forms.css',
        'umkm-modals.css',
        'umkm-toast.css',
    ];

    $coreCssModules = [
        'tables' => 'umkm-tables.css',
        'datatables' => 'umkm-datatables.css',
        'map' => 'umkm-map.css',
        'charts' => 'umkm-charts.css',
    ];

    $coreJsBase = [
        'bootstrap-local.js',
        'umkm-ui.js',
        'umkm-ajax.js',
        'umkm-modal.js',
        'umkm-confirm.js',
        'umkm-toast.js',
        'umkm-forms.js',
    ];

    $coreJsModules = [
        'datatables' => 'umkm-datatables.js',
        'wizard' => 'umkm-wizard.js',
        'map' => 'umkm-map.js',
        'charts' => 'umkm-charts.js',
    ];

    $pageCss = $pageCss ?? [];
    $pageJs = $pageJs ?? [];
    $assetModules = $assetModules ?? [];

    if ($assetProfile === 'landing') {
        $coreCss = [
            'bootstrap-local.css',
            'umkm-theme.css',
            'umkm-ui.css',
            'umkm-buttons.css',
        ];

        $coreJs = [
            'bootstrap-local.js',
            'umkm-ui.js',
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

    $coreCss = array_values(array_unique($coreCss));
    $coreJs = array_values(array_unique($coreJs));
    $pageCss = array_values(array_unique($pageCss));
    $pageJs = array_values(array_unique($pageJs));
@endphp

@foreach($coreCss as $file)
    <link rel="stylesheet" href="{{ asset('assets/css/core/'.$file) }}">
@endforeach

@foreach($pageCss as $file)
    <link rel="stylesheet" href="{{ asset('assets/css/pages/'.$file) }}">
@endforeach

@foreach($coreJs as $file)
    <script src="{{ asset('assets/js/core/'.$file) }}" defer></script>
@endforeach

@foreach($pageJs as $file)
    <script src="{{ asset('assets/js/pages/'.$file) }}" defer></script>
@endforeach
