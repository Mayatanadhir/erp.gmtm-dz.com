@props(['module'])

@php
    $title = __($module['title']);
@endphp

<div class="flex flex-col justify-between rounded-2xl bg-white dark:bg-gray-800 p-6 shadow-sm border border-gray-100 dark:border-gray-700/60 hover:shadow-md transition-all duration-200 group">
    <div>
        <div class="flex items-center justify-between mb-4">
            <x-tool-icon :name="$module['icon']" class="w-14 h-14 shrink-0 transition-transform duration-200 group-hover:scale-105" />
            <x-badge variant="neutral" size="md">
                {{ trans_choice('{1} :count Explorer|[2,*] :count Explorers', $module['explorers']) }}
            </x-badge>
        </div>
        <h4 class="text-lg font-bold text-gray-900 dark:text-white group-hover:text-brand-600 transition-colors">
            {{ $title }}
        </h4>
        <p class="mt-2 text-xs sm:text-sm text-gray-500 dark:text-gray-400 leading-relaxed">
            {{ __($module['description']) }}
        </p>
    </div>

    <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700/60">
        <a href="{{ route($module['route']) }}"
           aria-label="{{ __('Open :name', ['name' => $title]) }}"
           class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-semibold bg-brand-600 hover:bg-brand-700 dark:bg-brand-700 text-white shadow-sm transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-800">
            <span>{{ __('Open Dashboard') }}</span>
            {{-- السهم ينعكس تلقائياً في الواجهات RTL --}}
            <svg class="w-4 h-4 rtl:rotate-180" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
            </svg>
        </a>
    </div>
</div>
