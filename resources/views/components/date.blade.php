@props([
    'value' => null,
    'format' => 'date',
])

@php
    $dateFormat = match ($format) {
        'datetime' => 'd/m/Y H:i',
        'timestamp' => 'd/m/Y H:i:s',
        default => 'd/m/Y',
    };
@endphp

{{ $value?->format($dateFormat) ?? '—' }}