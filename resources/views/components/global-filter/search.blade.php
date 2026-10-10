@props([
    'name' => 'search',
    'placeholder' => __('Search...'),
    'value' => null,
    'width' => 'w-56 sm:w-64',
])

@php
$currentValue = $value ?? request($name, '');
@endphp

<div class="relative {{ $width }}" x-data="{ query: '{{ addslashes((string) $currentValue) }}' }">
    <div class="absolute inset-y-0 start-0 flex items-center ps-2.5 pointer-events-none text-gray-400 dark:text-gray-500">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
    </div>

    <input type="text"
           name="{{ $name }}"
           x-model="query"
           placeholder="{{ $placeholder }}"
           {{ $attributes->merge(['class' => 'w-full ps-8 pe-8 py-1.5 text-xs rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/80 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-400 focus:border-brand-600 focus:ring-1 focus:ring-brand-600 dark:focus:border-brand-600 transition-colors shadow-sm']) }} />

    <button type="button"
            x-show="query && query.length > 0"
            @click="query = ''; $el.closest('form').submit()"
            title="{{ __('Clear') }}"
            class="absolute inset-y-0 end-0 flex items-center pe-2.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors"
            x-cloak>
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>
</div>
