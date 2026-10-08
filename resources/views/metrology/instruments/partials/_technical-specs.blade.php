{{-- Technical Characteristics Card --}}
<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5 space-y-4">
    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
        <i class="fas fa-microchip text-brand-600"></i>
        <span>{{ __('Technical Characteristics') }}</span>
    </h3>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/40 border border-gray-100 dark:border-gray-700">
            <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('Instrument Type') }}</div>
            <div class="text-sm font-semibold text-gray-900 dark:text-white mt-1 flex items-center gap-1.5">
                <i class="fas {{ $instrument->instrument_type->icon() }} text-brand-600"></i>
                <span>{{ $instrument->instrument_type->label() }}</span>
            </div>
        </div>

        <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/40 border border-gray-100 dark:border-gray-700">
            <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('Process Variable') }}</div>
            <div class="text-sm font-semibold text-gray-900 dark:text-white mt-1">
                @if($instrument->process_variable)
                    <x-badge :variant="$instrument->process_variable->badgeVariant()" size="sm">
                        {{ $instrument->process_variable->label() }}
                    </x-badge>
                @else
                    <span class="text-gray-400">---</span>
                @endif
            </div>
        </div>

        <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/40 border border-gray-100 dark:border-gray-700">
            <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('Fluid Type') }}</div>
            <div class="text-sm font-semibold text-gray-900 dark:text-white mt-1">
                @if($instrument->fluid_type)
                    <x-badge :variant="$instrument->fluid_type->badgeVariant()" size="sm">
                        {{ $instrument->fluid_type->label() }}
                    </x-badge>
                @else
                    <span class="text-gray-400">---</span>
                @endif
            </div>
        </div>

        <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/40 border border-gray-100 dark:border-gray-700">
            <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('Measurement Technology') }}</div>
            <div class="text-sm font-semibold text-gray-900 dark:text-white mt-1">
                {{ $instrument->technology ?? 'Conventional' }}
                @if($instrument->measurement_type)
                    <span class="text-xs text-gray-500 dark:text-gray-400">({{ $instrument->measurement_type }})</span>
                @endif
            </div>
        </div>
    </div>
</div>
