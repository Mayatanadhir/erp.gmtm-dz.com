<div class="flex items-center gap-2">
    @can('edit measuring instruments')
        <a href="{{ route('metrology.instruments.edit', $instrument) }}">
            <x-secondary-button
                type="button"
                class="inline-flex items-center gap-1.5 text-xs font-semibold"
                title="{{ __('Edit Measuring Instrument') }}"
            >
                <svg class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                <span>{{ __('Edit') }}</span>
            </x-secondary-button>
        </a>
    @endcan

    @can('delete measuring instruments')
        <x-danger-button
            type="button"
            class="!px-3 !py-2 !text-xs gap-1.5"
            title="{{ __('Delete Measuring Instrument') }}"
            x-data
            @click="$dispatch('open-delete-modal', {
                action: '{{ route('metrology.instruments.destroy', $instrument) }}',
                name: '{{ addslashes($instrument->tag_number) }}',
                title: '{{ __('Delete Measuring Instrument') }}'
            })"
        >
            <i class="fas fa-trash-alt"></i>
            <span>{{ __('Delete') }}</span>
        </x-danger-button>
    @endcan

    <x-secondary-button href="{{ route('metrology.instruments') }}" class="flex items-center gap-2">
        <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        <span>{{ __('Back to List') }}</span>
    </x-secondary-button>
</div>
