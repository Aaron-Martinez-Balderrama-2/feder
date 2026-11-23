<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <link rel="icon" href="/favicon.png" type="image/png">
    <link rel="shortcut icon" href="/favicon.png" type="image/png">
    <link rel="apple-touch-icon" href="/favicon.png">
    
    <!-- Tipografías de Alta Legibilidad -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible:ital,wght@0,400;0,700;1,400&family=Fraunces:ital,opsz,wght@0,9..144,500..700;1,9..144,400..600&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    
    <!-- Script Anti-parpadeo de tema (Claro/Oscuro) -->
    <script>
        try {
            var t = localStorage.getItem('feder_theme') || localStorage.getItem('feder-theme');
            if (t) {
                document.documentElement.setAttribute('data-theme', t);
            } else if (matchMedia('(prefers-color-scheme: dark)').matches) {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        } catch (e) {}
    </script>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>
<body class="antialiased font-body">
    @inertia
</body>
</html>
