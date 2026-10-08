<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Favicon -->
        <link rel="icon" type="image/x-icon" href="{{ asset('images/LogoP.jpg') }}">

        <!-- Arabic Fonts (Noto Kufi Arabic) -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=noto-kufi-arabic:400,500,600&display=swap" rel="stylesheet" />

        <!-- Zero-FOUC Theme Script -->
        <script>
            (function() {
                const theme = localStorage.getItem('theme') || 'system';
                const isDark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                if (isDark) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            })();
        </script>

        <!-- Font Awesome Icons -->
        <link rel="stylesheet" href="{{ asset('assets/css/all.min.css') }}">

        <!-- RTL Isolated Bundle -->
        @vite(['resources/css/app-rtl.css', 'resources/js/app-rtl.js'])

        @stack('styles')
    </head>
    <body class="font-sans antialiased text-gray-900 dark:text-gray-100 bg-gray-100 dark:bg-gray-900 transition-colors duration-200">
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            @include('layouts.navigation-rtl')

            <!-- Page Heading -->
            @isset($header)
                <header x-data class="bg-white dark:bg-gray-800 shadow dark:shadow-gray-950/50">
                    <div class="w-full py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>

        <!-- Global Unified Delete Modal -->
        <x-delete-modal />

        @stack('scripts')
    </body>
</html>
