<div class="flex items-center gap-2">
    <x-badge :variant="$equipment->status->badgeVariant()" size="md" :dot="true">
        {{ $equipment->status->label() }}
    </x-badge>

    @can('edit equipment')
        <x-edit-button
            type="button"
            onclick="window.dispatchEvent(new CustomEvent('open-edit-equipment-modal', { bubbles: true }))"
            class="gap-1.5 text-xs font-semibold"
            title="{{ __('Edit Equipment') }}"
        />
    @endcan

    @can('create calibration certificates')
        @if($equipment->requires_calibration)
            <x-primary-button href="{{ route('metrology.calibration-certificates.create', ['equipment_id' => $equipment->id]) }}" class="gap-1.5 text-xs font-semibold">
                <i class="fas fa-certificate"></i>
                <span>{{ __('Register Certificate') }}</span>
            </x-primary-button>
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

    <x-secondary-button href="{{ route('metrology.equipment') }}" class="gap-1.5 text-xs">
        {{ __('Back to Inventory') }}
    </x-secondary-button>
</div>
