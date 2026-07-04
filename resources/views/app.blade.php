<!DOCTYPE html>
@php
    $localeConfig = config('locale.available.'.app()->getLocale(), []);
@endphp
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    dir="{{ $localeConfig['dir'] ?? 'ltr' }}"
    @class(['dark' => ($appearance ?? 'system') == 'dark'])
>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Apply saved theme colors before paint --}}
        <script>
            (function() {
                try {
                    var raw = localStorage.getItem('theme-colors');
                    if (!raw) return;
                    var c = JSON.parse(raw);
                    var root = document.documentElement;
                    var dark = root.classList.contains('dark');
                    var accent = dark ? (c.accentDark || c.accent) : c.accent;
                    var bg = dark ? (c.backgroundDark || c.brand) : (c.backgroundLight || '#f5f5f5');
                    if (c.brand) {
                        root.style.setProperty('--school-navy', c.brand);
                        root.style.setProperty('--sidebar-background', c.brand);
                        root.style.setProperty('--sidebar', c.brand);
                    }
                    if (accent) {
                        root.style.setProperty('--primary', accent);
                        root.style.setProperty('--school-gold', accent);
                        root.style.setProperty('--school-accent', accent);
                    }
                    if (bg) {
                        root.style.setProperty('--background', bg);
                        root.style.setProperty('--school-panel', bg);
                    }
                } catch (e) {}
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: var(--background, #f5f5f5);
            }

            html.dark {
                background-color: var(--background, #0a0a0a);
            }
        </style>

        <link rel="icon" href="/logo.png" type="image/png">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
