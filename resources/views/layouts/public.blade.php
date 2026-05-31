@php
    $activeTheme = $activeTheme ?? 'green';
@endphp
<!doctype html>
<html lang="id" data-umkm-theme="{{ $activeTheme }}">
<head>
    <x-umkm.seo-meta area="public" robots="public" :render-title="false" :render-description="false" />
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="umkm-client" content="public">
    <meta name="umkm-security-profile" content="{{ $assetProfile ?? 'public' }}">
    <meta name="umkm-active-theme" content="{{ $activeTheme }}">
    <title>@yield('title', 'Monitoring UMKM | Visual Analitik Interaktif')</title>
    @include('partials.asset-loader')
</head>
<body class="layout-public">
    <main>
        @yield('content')
    </main>

    @includeWhen(($showPublicFooter ?? true), 'partials.public-footer')
</body>
</html>
