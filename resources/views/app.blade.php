<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#16a34a">
    @if (request()->is('wali*'))
        {{-- crossorigin="use-credentials" WAJIB ada: tanpa ini browser fetch manifest
             tanpa cookie sesi, middleware auth.wali menganggap belum login, redirect
             ke /wali/login (HTML, bukan JSON) — Chrome gagal parse itu sebagai manifest
             dan kriteria "bisa di-install" gagal diam-diam tanpa error yang jelas. --}}
        <link rel="manifest" href="{{ route('wali.manifest') }}" crossorigin="use-credentials">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="Portal Wali">
    @else
        <link rel="manifest" href="/manifest.json">
    @endif
    <link rel="icon" href="/icons/icon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/icons/icon.svg">
    <title inertia>{{ config('app.name', 'SiMasjid') }}</title>

    <script>
        (function () {
            var stored = localStorage.getItem('simasjid-theme');
            var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (stored ? stored === 'dark' : prefersDark) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    @routes
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>
<body class="antialiased">
    @inertia
</body>
</html>
