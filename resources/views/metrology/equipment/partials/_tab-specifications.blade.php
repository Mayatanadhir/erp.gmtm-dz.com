{{-- Tab 2: Measurement & Source Capabilities --}}
<div x-show="activeTab === 'specifications'" x-transition class="space-y-6">
    @php
        $mesureSpecs = $equipment->specifications->filter(fn($s) => $s->grandeur->type === \App\Enums\GrandeurType::Measurement);
        $sourceSpecs = $equipment->specifications->filter(fn($s) => $s->grandeur->type === \App\Enums\GrandeurType::Source);
    @endphp

    @if($mesureSpecs->count() === 0 && $sourceSpecs->count() === 0)
        <div class="p-8 text-center rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
            <i class="fas fa-sliders-h text-gray-400 text-3xl mb-2"></i>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No physical specifications configured for this equipment.') }}</p>
        </div>
    @endif

    @if($mesureSpecs->count() > 0)
        <x-table>
            <x-slot:toolbar>
                <h4 class="text-sm font-bold text-emerald-700 dark:text-emerald-400 flex items-center gap-2">
                    <i class="fas fa-signal"></i>
                    <span>{{ __('Measurement Capabilities (Sensors / In)') }}</span>
                </h4>
            </x-slot:toolbar>

            <x-slot:header>
                <x-table.th>{{ __('Parameter') }}</x-table.th>
                <x-table.th>{{ __('Unit Symbol') }}</x-table.th>
                <x-table.th>{{ __('Measurement Range') }}</x-table.th>
                <x-table.th class="text-end">{{ __('Accuracy') }}</x-table.th>
            </x-slot:header>

            @foreach($mesureSpecs as $spec)
                <x-table.tr>
                    <x-table.td>
                        <div class="flex items-center gap-2.5">
                            <x-grandeur-icon :grandeur="$spec->grandeur" size="sm" :withBackground="true" />
                            <span class="font-bold text-gray-900 dark:text-white">{{ $spec->grandeur->name }}</span>
                        </div>
                    </x-table.td>
                    <x-table.td>
                        <span class="inline-flex items-center px-2 py-0.5 text-xs font-bold rounded-md border {{ $spec->discipline()->badgeClass() }}">
                            {{ $spec->grandeur->symbol }}
                        </span>
                    </x-table.td>
                    <x-table.td>
                        <span class="font-mono text-xs text-gray-700 dark:text-gray-300">
                            {{ $spec->range_min }} → {{ $spec->range_max }} {{ $spec->grandeur->symbol }}
                        </span>
                    </x-table.td>
                    <x-table.td class="text-end">
                        <span class="font-mono text-xs font-semibold text-gray-900 dark:text-white">
                            ±{{ $spec->accuracy_value }} {{ $spec->accuracy_type->value ?? '%' }}
                        </span>
                    </x-table.td>
                </x-table.tr>
            @endforeach
        </x-table>
    @endif

    @if($sourceSpecs->count() > 0)
        <x-table>
            <x-slot:toolbar>
                <h4 class="text-sm font-bold text-amber-700 dark:text-amber-400 flex items-center gap-2">
                    <i class="fas fa-bolt"></i>
                    <span>{{ __('Source Capabilities (Generators / Out)') }}</span>
                </h4>
            </x-slot:toolbar>

            <x-slot:header>
                <x-table.th>{{ __('Parameter') }}</x-table.th>
                <x-table.th>{{ __('Unit Symbol') }}</x-table.th>
                <x-table.th>{{ __('Generation Range') }}</x-table.th>
                <x-table.th class="text-end">{{ __('Accuracy') }}</x-table.th>
            </x-slot:header>

            @foreach($sourceSpecs as $spec)
                <x-table.tr>
                    <x-table.td>
                        <div class="flex items-center gap-2.5">
                            <x-grandeur-icon :grandeur="$spec->grandeur" size="sm" :withBackground="true" />
                            <span class="font-bold text-gray-900 dark:text-white">{{ $spec->grandeur->name }}</span>
                        </div>
                    </x-table.td>
                    <x-table.td>
                        <span class="inline-flex items-center px-2 py-0.5 text-xs font-bold rounded-md border {{ $spec->discipline()->badgeClass() }}">
                            {{ $spec->grandeur->symbol }}
                        </span>
                    </x-table.td>
                    <x-table.td>
                        <span class="font-mono text-xs text-gray-700 dark:text-gray-300">
                            {{ $spec->range_min }} → {{ $spec->range_max }} {{ $spec->grandeur->symbol }}
                        </span>
                    </x-table.td>
                    <x-table.td class="text-end">
                        <span class="font-mono text-xs font-semibold text-gray-900 dark:text-white">
                            ±{{ $spec->accuracy_value }} {{ $spec->accuracy_type->value ?? '%' }}
                        </span>
                    </x-table.td>
                </x-table.tr>
            @endforeach
        </x-table>
    @endif
</div>
