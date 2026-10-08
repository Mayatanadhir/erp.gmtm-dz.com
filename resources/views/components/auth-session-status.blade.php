@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'mb-4 flex items-center gap-3.5 rounded-2xl border px-4 py-3.5 text-sm font-medium border-emerald-500/20 bg-emerald-50/80 text-emerald-950 dark:border-emerald-500/30 dark:bg-emerald-950/35 dark:text-emerald-100 shadow-lg shadow-emerald-950/5 dark:shadow-black/20 backdrop-blur-md']) }}>
        <div class="shrink-0 flex items-center justify-center w-8 h-8 rounded-xl bg-emerald-500/15 border border-emerald-500/25 text-emerald-600 dark:bg-emerald-500/20 dark:border-emerald-500/40 dark:text-emerald-400 shadow-xs backdrop-blur-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
            </svg>
        </div>
        <div class="flex-1 leading-relaxed">
            {{ $status }}
        </div>
    </div>
@endif
