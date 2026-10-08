@php
    $currentLocale = app()->getLocale();
    $locales = LaravelLocalization::getSupportedLocales();
@endphp

<div class="relative" x-data="{ open: false }" @click.outside="open = false" @close.stop="open = false">

    {{-- Trigger: ghost button showing globe icon + locale code --}}
    <button @click="open = ! open"
            type="button"
            class="flex items-center gap-1.5 h-9 px-2.5 rounded-full text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-600 dark:focus:ring-offset-gray-800 transition-all duration-150">
        <span class="text-base leading-none">🌐</span>
        <span class="text-xs font-semibold uppercase tracking-wide">{{ strtoupper($currentLocale) }}</span>
        <svg class="h-3 w-3 text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': open }" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
    </button>

    {{-- Dropdown --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 translate-y-1 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-1 scale-95"
         class="absolute end-0 z-50 mt-2 w-40 origin-top-right rounded-xl shadow-lg bg-white dark:bg-gray-800 ring-1 ring-gray-900/5 dark:ring-white/10 overflow-hidden"
         @click="open = false"
         x-cloak>

        <div class="p-1 space-y-0.5">
            @foreach($locales as $localeCode => $properties)
                @php $isActive = $localeCode === $currentLocale; @endphp
                <a href="{{ LaravelLocalization::getLocalizedURL($localeCode, null, request()->query(), true) }}"
                   rel="alternate"
                   hreflang="{{ $localeCode }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors duration-100 {{ $isActive ? 'bg-brand-50 dark:bg-gray-700/70 text-brand-700 dark:text-brand-400 font-semibold' : 'text-gray-700 dark:text-gray-200 hover:bg-brand-50 dark:hover:bg-gray-700' }}">
                    <span class="text-base leading-none shrink-0">🌐</span>
                    <span class="flex-1">{{ $properties['native'] }}</span>
                    @if($isActive)
                        <svg class="h-3.5 w-3.5 text-brand-600 dark:text-brand-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    @endif
                </a>
            @endforeach
        </div>
    </div>
</div>
