<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#16a34a">
    <link rel="manifest" href="/manifest.json">
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
