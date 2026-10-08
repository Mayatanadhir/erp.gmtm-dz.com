@props([
    'variant' => 'success',
    'dismissible' => true,
    'title' => null,
    'message' => null,
])

@php
    $normalizedVariant = match($variant) {
        'brand', 'orange', 'primary' => 'primary',
        'success', 'emerald', 'green' => 'success',
        'danger', 'rose', 'red' => 'danger',
        'warning', 'amber', 'yellow' => 'warning',
        'info', 'indigo', 'blue' => 'info',
        default => 'neutral',
    };

    $containerClasses = match($normalizedVariant) {
        'primary' => 'bg-brand-50/80 border-brand-200/80 text-brand-950 dark:bg-brand-950/35 dark:border-brand-500/30 dark:text-brand-100 dark:shadow-brand-950/20',
        'success' => 'bg-emerald-50/80 border-emerald-200/80 text-emerald-950 dark:bg-emerald-950/35 dark:border-emerald-500/30 dark:text-emerald-100 dark:shadow-emerald-950/20',
        'danger' => 'bg-rose-50/80 border-rose-200/80 text-rose-950 dark:bg-rose-950/35 dark:border-rose-500/30 dark:text-rose-100 dark:shadow-rose-950/20',
        'warning' => 'bg-amber-50/80 border-amber-200/80 text-amber-950 dark:bg-amber-950/35 dark:border-amber-500/30 dark:text-amber-100 dark:shadow-amber-950/20',
        'info' => 'bg-indigo-50/80 border-indigo-200/80 text-indigo-950 dark:bg-indigo-950/35 dark:border-indigo-500/30 dark:text-indigo-100 dark:shadow-indigo-950/20',
        'neutral' => 'bg-gray-50/80 border-gray-200/80 text-gray-900 dark:bg-gray-900/60 dark:border-gray-700/80 dark:text-gray-100 dark:shadow-black/20',
    };

    $iconContainerClasses = match($normalizedVariant) {
        'primary' => 'bg-brand-500/15 dark:bg-brand-500/20 border-brand-500/25 dark:border-brand-500/40 text-brand-600 dark:text-brand-400 backdrop-blur-sm shadow-xs',
        'success' => 'bg-emerald-500/15 dark:bg-emerald-500/20 border-emerald-500/25 dark:border-emerald-500/40 text-emerald-600 dark:text-emerald-400 backdrop-blur-sm shadow-xs',
        'danger' => 'bg-rose-500/15 dark:bg-rose-500/20 border-rose-500/25 dark:border-rose-500/40 text-rose-600 dark:text-rose-400 backdrop-blur-sm shadow-xs',
        'warning' => 'bg-amber-500/15 dark:bg-amber-500/20 border-amber-500/25 dark:border-amber-500/40 text-amber-600 dark:text-amber-400 backdrop-blur-sm shadow-xs',
        'info' => 'bg-indigo-500/15 dark:bg-indigo-500/20 border-indigo-500/25 dark:border-indigo-500/40 text-indigo-600 dark:text-indigo-400 backdrop-blur-sm shadow-xs',
        'neutral' => 'bg-gray-500/15 dark:bg-gray-700/60 border-gray-500/25 dark:border-gray-600 text-gray-600 dark:text-gray-300 backdrop-blur-sm shadow-xs',
    };

    $closeHoverClasses = match($normalizedVariant) {
        'primary' => 'text-brand-600 dark:text-brand-400 hover:bg-brand-500/15 dark:hover:bg-brand-500/25',
        'success' => 'text-emerald-600 dark:text-emerald-400 hover:bg-emerald-500/15 dark:hover:bg-emerald-500/25',
        'danger' => 'text-rose-600 dark:text-rose-400 hover:bg-rose-500/15 dark:hover:bg-rose-500/25',
        'warning' => 'text-amber-600 dark:text-amber-400 hover:bg-amber-500/15 dark:hover:bg-amber-500/25',
        'info' => 'text-indigo-600 dark:text-indigo-400 hover:bg-indigo-500/15 dark:hover:bg-indigo-500/25',
        'neutral' => 'text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700/60',
    };
@endphp

<div x-data="{ show: true }"
     x-show="show"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 transform scale-100"
     x-transition:leave-end="opacity-0 transform scale-95"
     {{ $attributes->merge(['class' => "p-4 rounded-2xl border shadow-lg backdrop-blur-md text-sm flex items-start sm:items-center justify-between gap-3.5 $containerClasses"]) }}
     role="alert">
    <div class="flex items-start sm:items-center gap-3.5 min-w-0">
        <div class="shrink-0 w-8 h-8 rounded-xl border {{ $iconContainerClasses }} flex items-center justify-center">
            @if($normalizedVariant === 'success')
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
            @elseif($normalizedVariant === 'danger')
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                </svg>
            @elseif($normalizedVariant === 'warning')
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
            @elseif($normalizedVariant === 'info')
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                </svg>
            @elseif($normalizedVariant === 'primary')
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z" />
                </svg>
            @else
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                </svg>
            @endif
        </div>

        <div class="min-w-0">
            @if($title)
                <div class="font-bold text-sm text-gray-900 dark:text-white mb-0.5 leading-snug">{{ $title }}</div>
            @endif
            <div class="leading-relaxed font-medium">{{ $message ?? $slot }}</div>
        </div>
    </div>

    @if($dismissible)
        <button type="button"
                @click="show = false"
                class="p-1.5 rounded-lg transition-colors shrink-0 {{ $closeHoverClasses }}"
                aria-label="{{ __('Close') }}">
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>
    @endif
</div>
