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

    $formatted = '—';
    if ($value instanceof \DateTimeInterface) {
        $formatted = $value->format($dateFormat);
    } elseif (is_string($value) && trim($value) !== '') {
        try {
            $formatted = \Carbon\Carbon::parse($value)->format($dateFormat);
        } catch (\Throwable) {
            $formatted = $value;
        }
    }
@endphp

{{ $formatted }}