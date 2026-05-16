@php
$coreCss=['bootstrap-local.css','umkm-theme.css','umkm-ui.css','umkm-cards.css','umkm-buttons.css','umkm-badges.css','umkm-forms.css','umkm-tables.css','umkm-modals.css','umkm-toast.css','umkm-datatables.css','umkm-map.css','umkm-charts.css'];
$coreJs=['bootstrap-local.js','umkm-ui.js','umkm-ajax.js','umkm-modal.js','umkm-confirm.js','umkm-toast.js','umkm-forms.js','umkm-datatables.js','umkm-wizard.js','umkm-map.js','umkm-charts.js'];
$pageCss=$pageCss ?? [];$pageJs=$pageJs ?? [];
@endphp
@foreach($coreCss as $file)<link rel="stylesheet" href="{{ asset('assets/css/core/'.$file) }}">@endforeach
@foreach($pageCss as $file)<link rel="stylesheet" href="{{ asset('assets/css/pages/'.$file) }}">@endforeach
@foreach($coreJs as $file)<script src="{{ asset('assets/js/core/'.$file) }}" defer></script>@endforeach
@foreach($pageJs as $file)<script src="{{ asset('assets/js/pages/'.$file) }}" defer></script>@endforeach
