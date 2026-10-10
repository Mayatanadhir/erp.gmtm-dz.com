@props([
    'options' => [],
    'sortByName' => 'sort_by',
    'sortDirName' => 'sort_direction',
    'placeholder' => __('Sort By'),
    'autoSubmit' => false,
])

@php
$currentBy = (string) request($sortByName, '');
$currentDir = strtolower((string) request($sortDirName, 'asc'));
$nextDir = $currentDir === 'asc' ? 'desc' : 'asc';
@endphp

<div class="inline-flex items-center gap-1 rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/80 p-0.5 shadow-sm"
     x-data="{
         sortDir: '{{ $currentDir }}',
         toggleDir() {
             this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
             this.$refs.dirInput.value = this.sortDir;
             @if($autoSubmit)
                 this.$el.closest('form').submit();
             @endif
         }
     }">
    <select name="{{ $sortByName }}"
            @if($autoSubmit) onchange="this.form.submit()" @else data-no-auto-submit @endif
            class="bg-transparent border-0 py-1 ps-2 pe-6 text-xs text-gray-900 dark:text-white focus:ring-0 cursor-pointer">
        @if($placeholder !== false)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach($options as $key => $label)
            @php
                $optValue = is_numeric($key) ? (string) $label : (string) $key;
                $optLabel = is_array($label) ? ($label['name'] ?? $optValue) : $label;
            @endphp
            <option value="{{ $optValue }}" @selected($currentBy === $optValue)>
                {{ $optLabel }}
            </option>
        @endforeach
    </select>

    <input type="hidden" name="{{ $sortDirName }}" x-ref="dirInput" :value="sortDir" />

    <button type="button"
            @click="toggleDir()"
            :title="sortDir === 'asc' ? {{ Js::from(__('Ascending')) }} : {{ Js::from(__('Descending')) }}"
            class="p-1 rounded text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
        <svg x-show="sortDir === 'asc'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"/>
        </svg>
        <svg x-show="sortDir === 'desc'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4"/>
        </svg>
    </button>
</div>
