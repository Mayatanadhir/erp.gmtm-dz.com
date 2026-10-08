{{-- Tab 1: Overview & Technical Specs --}}
<div x-show="activeTab === 'overview'" x-transition class="space-y-6">
    <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm divide-y divide-gray-100 dark:divide-gray-700">
        <div class="p-4 sm:p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <div class="text-xs text-gray-500 dark:text-gray-400 font-medium">{{ __('Internal Inventory Code') }}</div>
                <div class="text-sm font-bold text-gray-900 dark:text-white mt-1 font-mono">
                    {{ $equipment->internal_code ?: '—' }}
                </div>
            </div>

            <div>
                <div class="text-xs text-gray-500 dark:text-gray-400 font-medium">{{ __('Serial Number (S/N)') }}</div>
                <div class="text-sm font-bold text-gray-900 dark:text-white mt-1 font-mono">
                    {{ $equipment->serial_number ?: '—' }}
                </div>
            </div>

            <div>
                <div class="text-xs text-gray-500 dark:text-gray-400 font-medium">{{ __('Category') }}</div>
                <div class="text-sm font-semibold text-gray-900 dark:text-white mt-1">
                    {{ $equipment->category->label() }}
                </div>
            </div>

            <div>
                <div class="text-xs text-gray-500 dark:text-gray-400 font-medium">{{ __('Logistics Package / Lot') }}</div>
                <div class="text-sm font-semibold text-gray-900 dark:text-white mt-1">
                    {{ $equipment->package->label() }}
                </div>
            </div>

            <div>
                <div class="text-xs text-gray-500 dark:text-gray-400 font-medium">{{ __('Requires Calibration') }}</div>
                <div class="text-sm font-semibold text-gray-900 dark:text-white mt-1">
                    {{ $equipment->requires_calibration ? __('Yes, periodic calibration required') : __('No') }}
                </div>
            </div>

            <div>
                <div class="text-xs text-gray-500 dark:text-gray-400 font-medium">{{ __('Registration Date') }}</div>
                <div class="text-sm text-gray-900 dark:text-white mt-1 font-mono">
                    <x-date :value="$equipment->created_at" format="datetime" />
                </div>
            </div>
        </div>

        @if($equipment->designation || $equipment->notes)
            <div class="p-4 sm:p-6 space-y-4">
                @if($equipment->designation)
                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 font-medium">{{ __('Designation') }}</div>
                        <p class="text-sm text-gray-800 dark:text-gray-200 mt-1 whitespace-pre-line">{{ $equipment->designation }}</p>
                    </div>
                @endif

                @if($equipment->notes)
                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 font-medium">{{ __('Notes') }}</div>
                        <p class="text-sm text-gray-800 dark:text-gray-200 mt-1 whitespace-pre-line">{{ $equipment->notes }}</p>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
