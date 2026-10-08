@props([
    'name',
    'placeholder' => __('All'),
    'options' => [],
    'value' => null,
    'autoSubmit' => true,
])

@php
$currentValue = (string) ($value ?? request($name, ''));
@endphp

<div class="relative inline-block">
    <select name="{{ $name }}"
            @if($autoSubmit) onchange="this.form.submit()" @endif
            {{ $attributes->merge(['class' => 'rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/80 px-3 py-1.5 pe-8 text-xs text-gray-900 dark:text-white focus:border-brand-600 focus:ring-1 focus:ring-brand-600 dark:focus:border-brand-600 transition-colors shadow-sm cursor-pointer']) }}>
        @if($placeholder !== false)
            <option value="">{{ $placeholder }}</option>
        @endif

        @if(!empty($options))
            @foreach($options as $key => $label)
                @php
                    $optValue = is_numeric($key) && !is_array($label) ? (string) $label : (string) $key;
                    $optLabel = is_array($label) ? ($label['name'] ?? $label['title'] ?? $optValue) : $label;
                    $isSelected = $currentValue !== '' && $currentValue === $optValue;
                @endphp
                <option value="{{ $optValue }}" @selected($isSelected)>
                    {{ $optLabel }}
                </option>
            @endforeach
        @else
            {{ $slot }}
        @endif
    </select>
</div>
