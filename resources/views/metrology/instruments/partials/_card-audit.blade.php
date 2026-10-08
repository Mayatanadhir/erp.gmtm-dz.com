{{-- Forensic Activity Log / Audit Trail Card --}}
<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5 space-y-4">
    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
        <i class="fas fa-history text-brand-600"></i>
        <span>{{ __('Forensic Activity Log') }}</span>
    </h3>

    <x-table>
        <x-slot:header>
            <x-table.th>{{ __('Timestamp') }}</x-table.th>
            <x-table.th>{{ __('Event') }}</x-table.th>
            <x-table.th>{{ __('Causer / Actor') }}</x-table.th>
            <x-table.th>{{ __('Changes / Description') }}</x-table.th>
        </x-slot:header>

        @forelse($instrument->activities as $act)
            <x-table.tr>
                <x-table.td>
                    <span class="font-mono text-xs text-gray-500 dark:text-gray-400">
                        <x-date :value="$act->created_at" format="timestamp" />
                    </span>
                </x-table.td>

                <x-table.td>
                    <x-badge :variant="$act->description === 'created' ? 'success' : 'info'" size="sm">
                        {{ ucfirst($act->description) }}
                    </x-badge>
                </x-table.td>

                <x-table.td>
                    @if($causer = $act->causer)
                        <span class="text-xs font-semibold text-gray-900 dark:text-white">{{ $causer->name }}</span>
                    @else
                        <span class="text-xs text-gray-400">System / CLI</span>
                    @endif
                </x-table.td>

                <x-table.td>
                    @if($changes = $act->properties->get('attributes'))
                        <div class="text-xs font-mono text-gray-600 dark:text-gray-300">
                            {{ json_encode(array_keys($changes)) }}
                        </div>
                    @else
                        <span class="text-xs text-gray-400">—</span>
                    @endif
                </x-table.td>
            </x-table.tr>
        @empty
            <x-table.empty :colspan="4" :message="__('No audit logs recorded for this measuring instrument.')" />
        @endforelse
    </x-table>
</div>
