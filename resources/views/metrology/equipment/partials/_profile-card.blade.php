{{-- Profile Card: Image + Badges + Name + Edit Button --}}
<div class="p-6 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm flex flex-col md:flex-row gap-6 items-start">
    <div class="w-32 h-32 shrink-0 rounded-2xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900/40 flex items-center justify-center shadow-sm p-1.5">
        @if($equipment->image_url)
            <a href="{{ $equipment->image_url }}" target="_blank" title="{{ __('View Full Image') }}" class="w-full h-full flex items-center justify-center group">
                <img src="{{ $equipment->image_url }}" alt="{{ $equipment->full_name }}" class="w-full h-full object-contain transition-transform duration-200 group-hover:scale-105">
            </a>
        @else
            <div class="text-4xl text-gray-400">
                <i class="fas {{ $equipment->category->icon() }}"></i>
            </div>
        @endif
    </div>

    <div class="flex-1 min-w-0 space-y-3">
        <div class="flex flex-wrap items-center gap-2">
            <x-badge :variant="$equipment->category->badgeVariant()" size="sm">
                <i class="fas {{ $equipment->category->icon() }} me-1"></i>
                {{ $equipment->category->label() }}
            </x-badge>
            <x-badge variant="neutral" size="sm">
                {{ $equipment->package->label() }}
            </x-badge>
            @if($equipment->requires_calibration)
                <x-badge variant="info" size="sm" :dot="true">
                    {{ __('Calibration Required') }}
                </x-badge>
            @endif
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h1 class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white">
                {{ $equipment->full_name }}
            </h1>

            @can('edit equipment')
                <x-secondary-button
                    type="button"
                    onclick="window.dispatchEvent(new CustomEvent('open-edit-equipment-modal', { bubbles: true }))"
                    class="inline-flex items-center gap-1.5 text-xs font-semibold self-start sm:self-auto"
                    title="{{ __('Edit Equipment') }}"
                >
                    <svg class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    <span>{{ __('Edit Equipment') }}</span>
                </x-secondary-button>
            @endcan
        </div>

        @if($equipment->designation)
            <p class="text-sm text-gray-600 dark:text-gray-300">
                {{ $equipment->designation }}
            </p>
        @endif
    </div>
</div>

{{-- Tabs Navigation --}}
<div class="border-b border-gray-200 dark:border-gray-700">
    <nav class="-mb-px flex space-x-6 rtl:space-x-reverse" aria-label="Tabs">
        <button type="button" @click="activeTab = 'overview'"
            :class="activeTab === 'overview' ? 'border-brand-600 text-brand-600 dark:text-brand-400 dark:border-brand-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
            class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm flex items-center gap-2">
            <i class="fas fa-info-circle"></i>
            <span>{{ __('Overview & Technical Specs') }}</span>
        </button>

        <button type="button" @click="activeTab = 'specifications'"
            :class="activeTab === 'specifications' ? 'border-brand-600 text-brand-600 dark:text-brand-400 dark:border-brand-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
            class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm flex items-center gap-2">
            <i class="fas fa-wave-square"></i>
            <span>{{ __('Measurement & Generation Capabilities') }}</span>
            <span class="ms-1 px-1.5 py-0.5 rounded-full text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                {{ $equipment->specifications->count() }}
            </span>
        </button>

        <button type="button" @click="activeTab = 'documents'"
            :class="activeTab === 'documents' ? 'border-brand-600 text-brand-600 dark:text-brand-400 dark:border-brand-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
            class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm flex items-center gap-2">
            <i class="fas fa-certificate"></i>
            <span>{{ __('Calibration Certificates') }}</span>
            <span class="ms-1 px-1.5 py-0.5 rounded-full text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                {{ $equipment->calibrationCertificates->count() }}
            </span>
        </button>

        <button type="button" @click="activeTab = 'audit'"
            :class="activeTab === 'audit' ? 'border-brand-600 text-brand-600 dark:text-brand-400 dark:border-brand-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
            class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm flex items-center gap-2">
            <i class="fas fa-history"></i>
            <span>{{ __('Audit Trail') }}</span>
        </button>
    </nav>
</div>
