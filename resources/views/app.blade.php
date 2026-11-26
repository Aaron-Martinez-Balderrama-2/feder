@php
    $basePath = rtrim(request()->getBasePath(), '/');
    $manifestPath = public_path('build/manifest.json');
    $manifest = file_exists($manifestPath) ? json_decode(file_get_contents($manifestPath), true) : null;
    $isHot = file_exists(public_path('hot'));
@endphp
<!DOCTYPE html>
<html lang="es" data-theme="light" data-segment="adultos">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'FEDER') }} · Fundación de Educación y Desarrollo Empresarial</title>

    <link rel="icon" href="{{ $basePath }}/favicon.png" type="image/png">
    <link rel="shortcut icon" href="{{ $basePath }}/favicon.png" type="image/png">
    <link rel="apple-touch-icon" href="{{ $basePath }}/favicon.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">

    <script>
        window.__APP_BASE__ = @json($basePath);
        window.__QUERY_API__ = @json((bool) env('APP_QUERY_ROUTING', false));
        (function () {
            try {
                var prefs = JSON.parse(localStorage.getItem('feder_prefs') || '{}');
                var seg = prefs.preferred_theme || 'adultos';
                if (seg === 'ninos') seg = 'adultos';
                var mode = prefs.preferred_color_mode || 'auto';
                var scale = parseFloat(prefs.font_scale || '1');
                var contrast = !!prefs.high_contrast;

                var resolvedTheme = mode;
                if (mode === 'auto') {
                    var hr = new Date().getHours();
                    resolvedTheme = (hr >= 19 || hr < 6) ? 'dark' : 'light';
                }

                document.documentElement.setAttribute('data-segment', seg);
                document.documentElement.setAttribute('data-theme', resolvedTheme);
                if (contrast) {
                    document.documentElement.setAttribute('data-contrast', 'high');
                }
                if (scale && scale >= 0.85 && scale <= 1.35) {
                    document.documentElement.style.fontSize = Math.round(scale * 100) + '%';
                }
            } catch (e) {}
        })();
    </script>

    @if ($isHot)
        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    @elseif ($manifest)
        @if (!empty($manifest['resources/css/app.css']['file']))
            <link rel="stylesheet" href="{{ $basePath }}/build/{{ $manifest['resources/css/app.css']['file'] }}">
        @endif
        @if (!empty($manifest['resources/js/app.jsx']['css']))
            @foreach ($manifest['resources/js/app.jsx']['css'] as $cssFile)
                <link rel="stylesheet" href="{{ $basePath }}/build/{{ $cssFile }}">
            @endforeach
        @endif
        @if (!empty($manifest['resources/js/app.jsx']['file']))
            <script type="module" src="{{ $basePath }}/build/{{ $manifest['resources/js/app.jsx']['file'] }}"></script>
        @endif
    @endif
</head>
<body>
    <div id="feder-root"></div>
</body>
</html>
