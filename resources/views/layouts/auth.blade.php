<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="umkm-client" content="auth">
    <meta name="umkm-security-profile" content="{{ $assetProfile ?? 'auth' }}">
    <title>@yield('title', 'Login Internal | Monitoring UMKM')</title>
    @include('partials.asset-loader')
</head>
<body class="layout-auth">
    <main class="auth-main" id="mainContent">
        @yield('content')
    </main>
</body>
</html>
