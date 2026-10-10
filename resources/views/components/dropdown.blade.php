@props(['align' => 'right', 'width' => '48', 'contentClasses' => 'p-1 bg-white dark:bg-gray-800'])


@php
$alignmentClasses = match ($align) {
    'start', 'left' => 'ltr:origin-top-left rtl:origin-top-right start-0',
    'top' => 'origin-top',
    'end', 'right' => 'ltr:origin-top-right rtl:origin-top-left end-0',
    default => 'ltr:origin-top-right rtl:origin-top-left end-0',
};

$width = match ($width) {
    '48' => 'w-48',
    '80' => 'w-80',
    '84' => 'w-84',
    '96' => 'w-96',
    default => (str_starts_with($width, 'w-') ? $width : "w-{$width}"),
};
@endphp

<div class="relative" x-data="{ open: false }" @click.outside="open = false" @close.stop="open = false" @keydown.escape.window="open = false">
    <div @click="open = ! open">
        {{ $trigger }}
    </div>

    <div x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="absolute z-50 mt-2 {{ $width }} max-w-[calc(100vw-2rem)] rounded-2xl shadow-xl {{ $alignmentClasses }}"
            x-cloak
            @click="open = false">
        <div class="rounded-2xl ring-1 ring-black/5 dark:ring-white/10 {{ $contentClasses }}">
            {{ $content }}
        </div>
    </div>
</div>
