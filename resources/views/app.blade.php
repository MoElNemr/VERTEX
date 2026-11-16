<!DOCTYPE html>
<html lang="ar" dir="rtl" class="dark h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#0f172a">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <link rel="manifest" href="/manifest.webmanifest">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased h-full bg-slate-950 text-slate-100">
        @inertia

        <script>
            if ('serviceWorker' in navigator) {
                @if(app()->isLocal())
                    navigator.serviceWorker.getRegistrations().then(function(registrations) {
                        for(let registration of registrations) {
                            registration.unregister();
                        }
                    });
                    if ('caches' in window) {
                        caches.keys().then(function(names) {
                            for (let name of names) caches.delete(name);
                        });
                    }
                @else
                    window.addEventListener('load', () => {
                        navigator.serviceWorker.register('/sw.js').catch(() => {});
                    });
                @endif
            }
        </script>
    </body>
</html>
