<div class="flex items-center gap-2">
    <x-badge :variant="$equipment->status->badgeVariant()" size="md" :dot="true">
        {{ $equipment->status->label() }}
    </x-badge>

    @can('edit equipment')
        <x-secondary-button
            type="button"
            onclick="window.dispatchEvent(new CustomEvent('open-edit-equipment-modal', { bubbles: true }))"
            class="inline-flex items-center gap-1.5 text-xs font-semibold"
            title="{{ __('Edit Equipment') }}"
        >
            <svg class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            <span>{{ __('Edit') }}</span>
        </x-secondary-button>
    @endcan

    @can('create calibration certificates')
        @if($equipment->requires_calibration)
            <a href="{{ route('metrology.calibration-certificates.create', ['equipment_id' => $equipment->id]) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-brand-600 hover:bg-brand-700 text-white text-xs font-semibold rounded-lg shadow-sm transition">
                <i class="fas fa-certificate"></i>
                <span>{{ __('Register Certificate') }}</span>
            </a>
        @endif
    @endcan

    @can('delete equipment')
        <x-danger-button
            type="button"
            class="!px-3 !py-2 !text-xs gap-1.5"
            title="{{ __('Delete Equipment') }}"
            x-data
            @click="$dispatch('open-delete-modal', {
                action: '{{ route('metrology.equipment.destroy', $equipment) }}',
                name: '{{ addslashes($equipment->full_name) }}',
                title: '{{ __('Delete Equipment') }}'
            })"
        >
            <i class="fas fa-trash-alt"></i>
            <span>{{ __('Delete') }}</span>
        </x-danger-button>
    @endcan

    <a href="{{ route('metrology.equipment') }}">
        <x-secondary-button type="button">
            {{ __('Back to Inventory') }}
        </x-secondary-button>
    </a>
</div>
