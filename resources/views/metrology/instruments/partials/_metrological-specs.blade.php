{{-- Metrological Specifications (Grandeurs & Ranges) --}}
<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5 space-y-4">
    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
        <i class="fas fa-sliders-h text-brand-600"></i>
        <span>{{ __('Metrological Ranges & Precision') }}</span>
    </h3>

    @if($instrument->specifications->isNotEmpty())
        <div class="divide-y divide-gray-100 dark:divide-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
            @foreach($instrument->specifications as $spec)
                <div class="p-3.5 bg-gray-50/50 dark:bg-gray-700/20 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center gap-2.5">
                        <div class="p-2 rounded-lg bg-brand-50 dark:bg-brand-500/20 text-brand-600 dark:text-brand-400">
                            <i class="fas fa-tachometer-alt"></i>
                        </div>
                        <div>
                            <div class="text-sm font-bold text-gray-900 dark:text-white">
                                {{ $spec->grandeur?->name ?? __('Physical Quantity') }}
                                <span class="text-xs text-gray-500 dark:text-gray-400 font-mono">({{ $spec->grandeur?->symbol ?? '---' }})</span>
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ __('Type') }}: {{ $spec->grandeur?->type?->value ?? 'measurement' }}
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 text-xs font-mono">
                        <div class="p-2 rounded bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600">
                            <span class="text-gray-400 text-[10px] block uppercase">{{ __('Range') }}</span>
                            <span class="font-bold text-gray-900 dark:text-white">{{ $spec->range_min ?? 0 }} → {{ $spec->range_max ?? 0 }} {{ $spec->grandeur?->symbol }}</span>
                        </div>
                        @if($spec->accuracy_value)
                            <div class="p-2 rounded bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600">
                                <span class="text-gray-400 text-[10px] block uppercase">{{ __('Accuracy') }}</span>
                                <span class="font-bold text-emerald-600 dark:text-emerald-400">±{{ $spec->accuracy_value }}{{ $spec->accuracy_type?->value ?? '%' }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-700/20 text-center text-xs text-gray-500 dark:text-gray-400">
            {{ __('No physical quantity specifications registered.') }}
        </div>
    @endif
</div>
