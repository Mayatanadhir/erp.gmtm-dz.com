{{-- Left Column: Identity & Media Card --}}
<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5 space-y-4">
    <div class="flex items-center justify-between">
        <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <i class="fas fa-image text-brand-600"></i>
            <span>{{ __('Instrument Photo') }}</span>
        </h3>
        @can('edit measuring instruments')
            <x-secondary-button
                type="button"
                onclick="window.dispatchEvent(new CustomEvent('open-edit-instrument-modal', { bubbles: true }))"
                @click="$dispatch('open-edit-instrument-modal')"
                class="inline-flex items-center gap-1.5 text-xs font-semibold"
                title="{{ __('Edit Measuring Instrument') }}"
            >
                <svg class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                <span>{{ __('Edit') }}</span>
            </x-secondary-button>
        @endcan
    </div>

    <div class="w-full aspect-square rounded-xl bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 flex items-center justify-center overflow-hidden p-2">
        @if($instrument->image_url)
            <img src="{{ $instrument->image_url }}" alt="{{ $instrument->tag_number }}" class="max-h-full max-w-full object-contain rounded-lg">
        @else
            <div class="text-center p-6">
                <div class="w-16 h-16 mx-auto rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-400 mb-2">
                    <i class="fas {{ $instrument->instrument_type->icon() }} text-2xl"></i>
                </div>
                <p class="text-xs text-gray-400">{{ __('No photo available') }}</p>
            </div>
        @endif
    </div>

    <div class="border-t border-gray-100 dark:border-gray-700/60 pt-4 space-y-2.5 text-xs">
        <div class="flex justify-between py-1 border-b border-gray-50 dark:border-gray-800">
            <span class="text-gray-500 dark:text-gray-400">{{ __('Tag Number') }}</span>
            <span class="font-mono font-bold text-gray-900 dark:text-white">{{ $instrument->tag_number }}</span>
        </div>
        <div class="flex justify-between py-1 border-b border-gray-50 dark:border-gray-800">
            <span class="text-gray-500 dark:text-gray-400">{{ __('Serial Number') }}</span>
            <span class="font-mono text-gray-900 dark:text-white">{{ $instrument->serial_number }}</span>
        </div>
        <div class="flex justify-between py-1 border-b border-gray-50 dark:border-gray-800">
            <span class="text-gray-500 dark:text-gray-400">{{ __('Site') }}</span>
            <span class="text-gray-900 dark:text-white font-medium">{{ $instrument->site->short_name ?? $instrument->site->full_name ?? '---' }}</span>
        </div>
        <div class="flex justify-between py-1">
            <span class="text-gray-500 dark:text-gray-400">{{ __('Registration Date') }}</span>
            <span class="text-gray-900 dark:text-white"><x-date :value="$instrument->created_at" format="datetime" /></span>
        </div>
    </div>
</div>
