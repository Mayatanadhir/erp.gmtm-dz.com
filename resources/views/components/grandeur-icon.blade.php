@props([
    'grandeur' => null,
    'discipline' => null,
    'name' => null,
    'symbol' => null,
    'size' => 'md',
    'withBackground' => false,
    'colored' => true,
    'class' => '',
])

@php
    use App\Enums\GrandeurDiscipline;

    if ($discipline instanceof GrandeurDiscipline) {
        $disc = $discipline;
    } elseif (is_string($discipline) && GrandeurDiscipline::tryFrom($discipline)) {
        $disc = GrandeurDiscipline::from($discipline);
    } elseif ($grandeur) {
        $disc = method_exists($grandeur, 'discipline')
            ? $grandeur->discipline()
            : GrandeurDiscipline::detect($grandeur->name ?? '', $grandeur->symbol ?? '');
    } else {
        $disc = GrandeurDiscipline::detect($name, $symbol);
    }

    $sizeClasses = match($size) {
        'xs' => 'w-3.5 h-3.5',
        'sm' => 'w-4 h-4',
        'md' => 'w-5 h-5',
        'lg' => 'w-6 h-6',
        'xl' => 'w-8 h-8',
        default => 'w-5 h-5',
    };

    $containerSizes = match($size) {
        'xs' => 'w-6 h-6 rounded-md',
        'sm' => 'w-7 h-7 rounded-lg',
        'md' => 'w-8 h-8 rounded-lg',
        'lg' => 'w-10 h-10 rounded-xl',
        'xl' => 'w-12 h-12 rounded-xl',
        default => 'w-8 h-8 rounded-lg',
    };

    $textClass = $colored ? $disc->textClass() : '';
    $containerClass = $disc->iconContainerClass();
@endphp

@if($withBackground)
    <div {{ $attributes->merge(['class' => "{$containerSizes} {$containerClass} flex items-center justify-center shrink-0 shadow-xs"]) }}>
@endif

@switch($disc)
    @case(GrandeurDiscipline::Temperature)
        {{-- Temperature: Precision Thermometer --}}
        <svg class="{{ $sizeClasses }} {{ $textClass }} {{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M14 14.76V3.5a2.5 2.5 0 0 0-5 0v11.26a4.5 4.5 0 1 0 5 0z" />
            <path d="M11.5 8h2" />
            <circle cx="11.5" cy="17.5" r="1.5" fill="currentColor" />
        </svg>
        @break

    @case(GrandeurDiscipline::Pressure)
        {{-- Pressure: Precision Dial Gauge / Manometer --}}
        <svg class="{{ $sizeClasses }} {{ $textClass }} {{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2z" />
            <path d="M12 6v2" />
            <path d="M18 12h-2" />
            <path d="M6 12H8" />
            <path d="m14 10-3 3" />
            <circle cx="12" cy="13" r="1.5" fill="currentColor" />
        </svg>
        @break

    @case(GrandeurDiscipline::Current)
        {{-- Electrical Current: High-Energy Spark / Flow --}}
        <svg class="{{ $sizeClasses }} {{ $textClass }} {{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2" fill="currentColor" fill-opacity="0.25" />
        </svg>
        @break

    @case(GrandeurDiscipline::Voltage)
        {{-- Voltage: Electric Potential / Lightning --}}
        <svg class="{{ $sizeClasses }} {{ $textClass }} {{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M4 14h4v7l12-11h-7l3-8z" />
        </svg>
        @break

    @case(GrandeurDiscipline::Resistance)
        {{-- Electrical Resistance: Omega Ω / Resistor --}}
        <svg class="{{ $sizeClasses }} {{ $textClass }} {{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M3 19h4.5c1.5-3 2.5-6.5 4.5-6.5s3 3.5 4.5 6.5H21" />
            <path d="M8 19a5.5 5.5 0 1 1 8 0" />
        </svg>
        @break

    @case(GrandeurDiscipline::Frequency)
        {{-- Frequency: Continuous Sine Wave --}}
        <svg class="{{ $sizeClasses }} {{ $textClass }} {{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M2 12h3c2 0 2-8 4-8s2 16 4 16 2-8 4-8h5" />
        </svg>
        @break

    @case(GrandeurDiscipline::Pulse)
        {{-- Pulses: Digital Square Wave / Counter --}}
        <svg class="{{ $sizeClasses }} {{ $textClass }} {{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M2 14h4V6h5v12h5V6h6" />
        </svg>
        @break

    @case(GrandeurDiscipline::Dimensional)
        {{-- Dimensional: Vernier Caliper / Ruler --}}
        <svg class="{{ $sizeClasses }} {{ $textClass }} {{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M21.3 8.7 8.7 21.3c-.4.4-1 .4-1.4 0l-5.6-5.6c-.4-.4-.4-1 0-1.4L14.3 1.7c.4-.4 1-.4 1.4 0l5.6 5.6c.4.4.4 1 0 1.4z" />
            <path d="m14.5 4.5-2 2" />
            <path d="m11.5 7.5-3 3" />
            <path d="m8.5 10.5-2 2" />
            <path d="m5.5 13.5-3 3" />
        </svg>
        @break

    @case(GrandeurDiscipline::Mass)
        {{-- Mass & Weight: Balance Scale --}}
        <svg class="{{ $sizeClasses }} {{ $textClass }} {{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M12 3v18" />
            <path d="M6 7h12" />
            <path d="m3 14 3-7 3 7a3 3 0 0 1-6 0z" fill="currentColor" fill-opacity="0.15" />
            <path d="m15 14 3-7 3 7a3 3 0 0 1-6 0z" fill="currentColor" fill-opacity="0.15" />
            <path d="M8 21h8" />
        </svg>
        @break

    @case(GrandeurDiscipline::Flow)
        {{-- Flow: Fluid Streamlines --}}
        <svg class="{{ $sizeClasses }} {{ $textClass }} {{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M17.7 7.7a2.5 2.5 0 1 1 1.8 4.3H2" />
            <path d="M9.6 4.6A2 2 0 1 1 11 8H2" />
            <path d="M12.6 19.4A2 2 0 1 0 14 16H2" />
        </svg>
        @break

    @default
        {{-- Generic: Multi-parameter Controls --}}
        <svg class="{{ $sizeClasses }} {{ $textClass }} {{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <line x1="4" y1="21" x2="4" y2="14" />
            <line x1="4" y1="10" x2="4" y2="3" />
            <line x1="12" y1="21" x2="12" y2="12" />
            <line x1="12" y1="8" x2="12" y2="3" />
            <line x1="20" y1="21" x2="20" y2="16" />
            <line x1="20" y1="12" x2="20" y2="3" />
            <circle cx="4" cy="12" r="2" />
            <circle cx="12" cy="10" r="2" />
            <circle cx="20" cy="14" r="2" />
        </svg>
@endswitch

@if($withBackground)
    </div>
@endif
