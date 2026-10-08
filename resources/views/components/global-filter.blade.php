@props([
    'action' => request()->url(),
    'method' => 'GET',
    'search' => true,
    'searchName' => 'search',
    'searchPlaceholder' => __('Search...'),
    'searchValue' => null,
    'searchWidth' => 'w-56 sm:w-64',
    'resetUrl' => null,
    'submitText' => __('Filter'),
    'autoSubmit' => true,
    'showSubmit' => false,
])

@php
$currentSearch = $searchValue ?? request($searchName, '');
$resolvedResetUrl = $resetUrl ?? $action;
$formId = 'gf-' . uniqid();

// Detect active query filters (ignoring pagination 'page')
$activeQueryParams = collect(request()->query())->filter(function ($val, $key) {
    return $key !== 'page' && $val !== null && $val !== '' && $val !== [];
});
$hasActiveFilters = $activeQueryParams->isNotEmpty();
@endphp

<div class="w-full sm:w-auto"
     x-data="{
         searchQuery: '{{ addslashes((string) $currentSearch) }}',
         hasActiveFilters: {{ $hasActiveFilters ? 'true' : 'false' }},
         clearSearch() {
             this.searchQuery = '';
             this.$refs.filterForm.submit();
         }
     }">
    <form id="{{ $formId }}"
          x-ref="filterForm"
          method="{{ strtoupper($method) === 'GET' ? 'GET' : 'POST' }}"
          action="{{ $action }}"
          class="flex flex-wrap items-center gap-2">
        @if(strtoupper($method) !== 'GET')
            @csrf
            @if(in_array(strtoupper($method), ['PUT', 'PATCH', 'DELETE']))
                @method($method)
            @endif
        @endif

        {{-- Built-in Search Input --}}
        @if($search)
            <div class="relative {{ $searchWidth }}">
                <div class="absolute inset-y-0 start-0 flex items-center ps-2.5 pointer-events-none text-gray-400 dark:text-gray-500">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>

                <input type="text"
                       name="{{ $searchName }}"
                       x-model="searchQuery"
                       placeholder="{{ $searchPlaceholder }}"
                       class="w-full ps-8 pe-8 py-1.5 text-xs rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/80 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-400 focus:border-brand-600 focus:ring-1 focus:ring-brand-600 dark:focus:border-brand-600 transition-colors shadow-sm" />

                {{-- Clear Search Input Button --}}
                <button type="button"
                        x-show="searchQuery && searchQuery.length > 0"
                        @click="clearSearch()"
                        title="{{ __('Clear') }}"
                        class="absolute inset-y-0 end-0 flex items-center pe-2.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors"
                        x-cloak>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        @endif

        {{-- Custom Filter Controls (Selects, Date pickers, etc.) --}}
        @if($slot->isNotEmpty())
            <div class="flex flex-wrap items-center gap-2">
                {{ $slot }}
            </div>
        @endif

        {{-- Optional Sorting Slot --}}
        @isset($sorting)
            <div class="flex items-center">
                {{ $sorting }}
            </div>
        @endisset

        {{-- Submit Button (only when explicitly enabled) --}}
        @if($showSubmit && !$autoSubmit)
            <x-primary-button type="submit" class="py-1.5 px-3 text-xs rounded-lg flex items-center gap-1.5 shrink-0">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                <span>{{ $submitText }}</span>
            </x-primary-button>
        @endif

        {{-- Reset Filters Button --}}
        @if($hasActiveFilters)
            <a href="{{ $resolvedResetUrl }}"
               title="{{ __('Reset Filters') }}"
               class="inline-flex items-center gap-1 py-1.5 px-2.5 rounded-lg text-xs font-medium text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700/60 hover:bg-gray-200 dark:hover:bg-gray-600 border border-gray-200 dark:border-gray-600 transition-colors shrink-0 shadow-sm focus:outline-none focus:ring-1 focus:ring-brand-600">
                <svg class="w-3.5 h-3.5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <span>{{ __('Reset') }}</span>
            </a>
        @endif
    </form>
</div>

@if($autoSubmit)
<script>
(function () {
    var form = document.getElementById('{{ $formId }}');
    if (!form) return;
    form.addEventListener('change', function (e) {
        if (e.target.tagName === 'SELECT') {
            form.submit();
        }
    });
}());
</script>
@endif
