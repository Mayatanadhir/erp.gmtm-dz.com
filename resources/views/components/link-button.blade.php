@props([
    'variant' => 'primary', // primary | secondary
    'size'    => 'md',      // sm | md
    'arrow'   => false,     // يضيف سهماً ينعكس تلقائياً في RTL
])

@php
    $variants = [
        'primary'   => 'bg-brand-600 hover:bg-brand-700 active:bg-brand-800 dark:bg-brand-700 dark:hover:bg-brand-600 text-white shadow-sm',
        'secondary' => 'border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700',
    ];

    $sizes = [
        'sm' => 'px-4 py-1.5 rounded-lg font-medium',
        'md' => 'px-5 py-2.5 rounded-xl font-semibold',
    ];
@endphp

<a {{ $attributes->class([
    'inline-flex items-center justify-center gap-2 text-sm transition',
    'focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-800',
    $variants[$variant] ?? $variants['primary'],
    $sizes[$size] ?? $sizes['md'],
]) }}>
    <span>{{ $slot }}</span>
    @if($arrow)
        <svg class="w-4 h-4 rtl:rotate-180" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
        </svg>
    @endif
</a>
