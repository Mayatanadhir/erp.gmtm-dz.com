{{-- Tab 4: Audit Trail / Forensic Activity Log --}}
<div x-show="activeTab === 'audit'" x-transition class="space-y-6">
    <x-table>
        <x-slot:toolbar>
            <h4 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fas fa-history"></i>
                <span>{{ __('Forensic Activity Log') }}</span>
            </h4>
        </x-slot:toolbar>

        <x-slot:header>
            <x-table.th>{{ __('Timestamp') }}</x-table.th>
            <x-table.th>{{ __('Event') }}</x-table.th>
            <x-table.th>{{ __('Causer / Actor') }}</x-table.th>
            <x-table.th>{{ __('Changes / Description') }}</x-table.th>
        </x-slot:header>

        @forelse($equipment->activities as $act)
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
            <x-table.empty :colspan="4" :message="__('No audit logs recorded for this equipment.')" />
        @endforelse
    </x-table>
</div>
