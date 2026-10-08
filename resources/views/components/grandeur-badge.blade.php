@props([
    'grandeur' => null,
    'discipline' => null,
    'name' => null,
    'symbol' => null,
    'size' => 'sm',
    'showSymbol' => true,
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

    $displayName = $name ?: ($grandeur?->name ?? $disc->label());
    $displaySymbol = $symbol ?: ($grandeur?->symbol ?? '');

    $badgeClasses = $disc->badgeClass();
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold border {$badgeClasses} {$class} shadow-2xs"]) }}>
    <x-grandeur-icon :discipline="$disc" size="xs" :colored="false" />
    <span>{{ $displayName }}</span>
    @if($showSymbol && $displaySymbol !== '')
        <span class="font-mono text-[10px] font-bold px-1.5 py-0.5 rounded bg-black/5 dark:bg-white/10 opacity-90">
            {{ $displaySymbol }}
        </span>
    @endif
</span>
