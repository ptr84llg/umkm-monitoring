<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'UMKM Monitoring')</title>
    @include('partials.asset-loader')
</head>
<body class="layout-public">
    <main>
        @yield('content')
    </main>

    @includeWhen(($showPublicFooter ?? true), 'partials.public-footer')
</body>
</html>
