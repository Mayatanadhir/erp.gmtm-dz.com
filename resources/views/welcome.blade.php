@php
    $appName     = config('app.name', 'ERP-GMTM');
    $canLogin    = Route::has('login');
    $canRegister = Route::has('register') && is_registration_open();
    $modules     = config('workspace.modules', []);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="{{ __('Generale Maintenance & Travaux Montage') }}">
        <meta name="color-scheme" content="light dark">

        <title>{{ $appName }}</title>

        <link rel="icon" type="image/jpeg" href="{{ asset('images/LogoP.jpg') }}">

        <!-- Zero-FOUC Theme Script -->
        <script>
            (function () {
                const theme = localStorage.getItem('theme') || 'system';
                const isDark = theme === 'dark' ||
                    (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                if (isDark) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            })();
        </script>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body class="flex flex-col items-center lg:justify-center min-h-screen p-6 lg:p-8 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100">

        @if ($canLogin)
            <header class="w-full max-w-md lg:max-w-4xl mb-6">
                <nav class="flex items-center justify-end gap-3" aria-label="{{ __('Main navigation') }}">
                    <x-theme-switcher />
                    <x-language-switcher />

                    @auth
                        <x-link-button :href="route('dashboard')" size="sm">{{ __('Workspace') }}</x-link-button>
                    @else
                        <x-link-button :href="route('login')" size="sm">{{ __('Log in') }}</x-link-button>

                        @if ($canRegister)
                            <x-link-button :href="route('register')" variant="secondary" size="sm">{{ __('Register') }}</x-link-button>
                        @endif
                    @endauth
                </nav>
            </header>
        @endif

        <div class="flex items-center justify-center w-full lg:grow transition-opacity duration-700 motion-reduce:transition-none starting:opacity-0">
            <main class="flex flex-col-reverse lg:flex-row w-full max-w-md lg:max-w-4xl">

                {{-- لوحة النص --}}
                <section class="flex flex-col justify-between flex-1 p-6 lg:p-12 bg-white dark:bg-gray-800 ring-1 ring-inset ring-gray-900/10 dark:ring-white/10 rounded-b-lg lg:rounded-b-none lg:rounded-s-lg">
                    <div>
                        <div class="flex items-center gap-2 mb-3">
                            <span class="inline-block w-2 h-2 rounded-full bg-emerald-500" aria-hidden="true"></span>
                            <span class="text-xs font-semibold uppercase tracking-wider rtl:tracking-normal text-emerald-700 dark:text-emerald-400">
                                {{ __('GMTM System') }}
                            </span>
                        </div>

                        <h1 class="mb-1 text-xl font-bold text-gray-900 dark:text-white">
                            {{ __('Generale Maintenance & Travaux Montage') }}
                        </h1>
                        <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">
                            {{ __('Welcome to GMTM, your engineering work assistant') }}
                        </p>

                        <ul class="flex flex-col mb-6">
                            @foreach ($modules as $module)
                                <li class="relative flex items-start gap-3.5 py-2
                                           before:absolute before:start-[0.4rem] before:border-s before:border-gray-200 dark:before:border-gray-700
                                           before:top-0 before:bottom-0
                                           first:before:top-[1.0625rem]
                                           last:before:bottom-auto last:before:h-[1.0625rem]">
                                    <span class="relative py-0.5 bg-white dark:bg-gray-800" aria-hidden="true">
                                        <span class="flex items-center justify-center w-3.5 h-3.5 rounded-full border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400"></span>
                                        </span>
                                    </span>
                                    <div>
                                        <span class="block text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ __($module['title']) }}
                                        </span>
                                        <span class="text-sm leading-relaxed text-gray-500 dark:text-gray-400">
                                            {{ __($module['description']) }}
                                        </span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-4 pt-4 border-t border-gray-100 dark:border-gray-700/60">
                        @auth
                            <x-link-button :href="route('dashboard')" arrow>{{ __('Workspace') }}</x-link-button>
                        @elseif ($canLogin)
                            <div class="flex items-center gap-2.5">
                                <x-link-button :href="route('login')" arrow>{{ __('Log in') }}</x-link-button>

                                @if ($canRegister)
                                    <x-link-button :href="route('register')" variant="secondary">{{ __('Register') }}</x-link-button>
                                @endif
                            </div>
                        @endauth

                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $appName }} &copy; {{ date('Y') }}
                        </span>
                    </div>
                </section>

                {{-- لوحة الشعار: مصغّرة على الجوال حتى لا تدفع المحتوى للأسفل --}}
                <div class="relative flex items-center justify-center shrink-0 w-full lg:w-[438px] py-6 px-8 lg:p-12 -mb-px lg:mb-0 lg:-ms-px overflow-hidden bg-gray-100/70 dark:bg-gray-900/60 ring-1 ring-inset ring-gray-900/10 dark:ring-white/10 rounded-t-lg lg:rounded-none lg:rounded-e-lg">
                    <div class="w-full max-w-[140px] lg:max-w-[280px]">
                        {{-- الصورة المخفية افتراضياً تُحمَّل كسولاً فقط عند الحاجة --}}
                        <img src="{{ asset('images/Logo-black.png') }}" alt="{{ $appName }}" class="block dark:hidden w-full h-auto object-contain" decoding="async">
                        <img src="{{ asset('images/Logo-white.png') }}" alt="{{ $appName }}" class="hidden dark:block w-full h-auto object-contain" loading="lazy" decoding="async">
                    </div>
                </div>

            </main>
        </div>
    </body>
</html>
