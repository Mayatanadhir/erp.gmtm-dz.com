<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <a href="{{ route('metrology.instruments.create') }}" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition" title="{{ __('Back to Type Selection') }}">
                    <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <div class="w-10 h-10 rounded-xl bg-purple-100 dark:bg-purple-950/60 text-purple-700 dark:text-purple-400 border border-purple-200 dark:border-purple-800/70 flex items-center justify-center shrink-0">
                    <i class="fas fa-server text-lg"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                            {{ __('Register New Flow Computer') }}
                        </h2>
                        <x-badge variant="primary" size="sm">AGA 8 / ISO 6976</x-badge>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('Supervisory flow measurement computer with dynamic multichannel transmitter loop wiring') }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('metrology.instruments.create') }}">
                    <x-secondary-button type="button" class="flex items-center gap-1.5 text-xs">
                        <i class="fas fa-th-large"></i>
                        <span>{{ __('Change Type') }}</span>
                    </x-secondary-button>
                </a>
                <a href="{{ route('metrology.instruments') }}">
                    <x-secondary-button type="button" class="flex items-center gap-1.5 text-xs">
                        <i class="fas fa-times"></i>
                        <span>{{ __('Cancel') }}</span>
                    </x-secondary-button>
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $transmittersData = $transmitters->map(fn($t) => [
            'id' => $t->id,
            'tag' => $t->tag_number,
            'site_id' => $t->site_id,
            'serial_number' => $t->serial_number,
        ])->values();

        $initialWiring = [];
        if (old('transmitters')) {
            foreach (old('transmitters') as $row) {
                if (!empty($row['id']) || !empty($row['transmitter_id'])) {
                    $initialWiring[] = [
                        'id' => (int) ($row['id'] ?? $row['transmitter_id']),
                        'channel' => (string) ($row['channel'] ?? $row['channel_number'] ?? 'Ch1'),
                    ];
                }
            }
        }
    @endphp

    <div class="py-8"
        x-data="{
            siteId: '{{ old('site_id', '') }}',
            imagePreview: null,
            transmitters: {{ Js::from($transmittersData) }},
            wiringRows: {{ Js::from($initialWiring) }},

            get availableTransmittersForSite() {
                if (!this.siteId) return [];
                return this.transmitters.filter(t => String(t.site_id) === String(this.siteId));
            },

            addWiringRow() {
                const nextChannel = 'Ch' + (this.wiringRows.length + 1);
                this.wiringRows.push({
                    id: '',
                    channel: nextChannel
                });
            },

            removeWiringRow(index) {
                this.wiringRows.splice(index, 1);
            }
        }"
    >
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row gap-6 items-start">
                <aside class="w-full lg:w-64 shrink-0">
                    <x-metrology-tabs active="instruments" />
                </aside>

                <main class="flex-1 w-full min-w-0 space-y-6">
                    @if ($errors->any())
                        <x-alert variant="danger">
                            <div class="font-bold mb-1">{{ __('Please correct the errors below:') }}</div>
                            <ul class="list-disc list-inside text-xs space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </x-alert>
                    @endif

                    <form action="{{ route('metrology.instruments.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                        @csrf

                        <!-- Section 1: Basic Identity -->
                        @include('metrology.instruments.create.partials._identity', [
                            'type' => 'flow_computer',
                            'typeLabel' => __('Flow Computer'),
                            'sites' => $sites
                        ])

                        <input type="hidden" name="technology" value="SMART">

                        <!-- Section 2: Flow Computer Specifications & Standards -->
                        <div class="p-6 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm space-y-4">
                            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3.5">
                                <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <i class="fas fa-microchip text-brand-600 dark:text-brand-400"></i>
                                    <span>{{ __('Calculation Standards & Measurement Compliance') }}</span>
                                </h3>
                                <span class="text-xs font-mono text-gray-500">Supervisory Metrology</span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                                <div class="p-3.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30 space-y-1">
                                    <span class="font-bold text-gray-900 dark:text-white block">Gas Volume & Energy</span>
                                    <span class="text-gray-500 dark:text-gray-400">AGA 8, ISO 12213, ISO 6976, AGA 3 / AGA 7</span>
                                </div>
                                <div class="p-3.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30 space-y-1">
                                    <span class="font-bold text-gray-900 dark:text-white block">Liquid Hydrocarbons</span>
                                    <span class="text-gray-500 dark:text-gray-400">API MPMS Ch. 11.1 (ASTM D 1250), API 12.2</span>
                                </div>
                                <div class="p-3.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30 space-y-1">
                                    <span class="font-bold text-gray-900 dark:text-white block">Communication Protocols</span>
                                    <span class="text-gray-500 dark:text-gray-400">Modbus RTU / TCP, HART Multidrop, OPC</span>
                                </div>
                            </div>
                        </div>

                        <!-- Section 3: Connected Transmitters & Channels Wiring -->
                        <div class="p-6 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm space-y-5">
                            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3.5">
                                <div>
                                    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                        <i class="fas fa-network-wired text-brand-600 dark:text-brand-400"></i>
                                        <span>{{ __('Connected Transmitters & Channels Wiring') }}</span>
                                    </h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                        {{ __('Link physical pressure, temperature, and differential transmitters to flow computer calculation channels') }}
                                    </p>
                                </div>

                                <x-secondary-button type="button" @click="addWiringRow()" class="flex items-center gap-1.5 text-xs">
                                    <i class="fas fa-plus text-brand-600"></i>
                                    <span>{{ __('Add Channel') }}</span>
                                </x-secondary-button>
                            </div>

                            <!-- Site Hint Banner -->
                            <div class="p-3 rounded-xl border text-xs flex items-center gap-2.5 transition"
                                 :class="availableTransmittersForSite.length > 0 ? 'bg-emerald-50 dark:bg-emerald-950/30 border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-300' : 'bg-amber-50 dark:bg-amber-950/30 border-amber-200 dark:border-amber-800/60 text-amber-800 dark:text-amber-300'">
                                <i :class="availableTransmittersForSite.length > 0 ? 'fas fa-check-circle text-emerald-500' : 'fas fa-exclamation-triangle text-amber-500'"></i>
                                <span x-show="!siteId">{{ __('Please select an industrial site above to filter available transmitters.') }}</span>
                                <span x-show="siteId && availableTransmittersForSite.length > 0">
                                    <strong x-text="availableTransmittersForSite.length"></strong> {{ __('active transmitters deployed at this station ready for channel wiring.') }}
                                </span>
                                <span x-show="siteId && availableTransmittersForSite.length === 0">
                                    {{ __('No active transmitters found for the selected site.') }}
                                </span>
                            </div>

                            <!-- Dynamic Wiring Rows -->
                            <div class="space-y-3">
                                <template x-for="(row, index) in wiringRows" :key="index">
                                    <div class="p-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30 flex items-center gap-3">
                                        <div class="flex-1">
                                            <label class="text-[10px] text-gray-500 dark:text-gray-400 font-medium">{{ __('Transmitter') }}</label>
                                            <select :name="'transmitters[' + index + '][id]'" x-model="row.id"
                                                class="mt-0.5 block w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-white focus:border-brand-600 focus:ring-brand-600">
                                                <option value="">-- {{ __('Select Transmitter') }} --</option>
                                                <template x-for="t in (availableTransmittersForSite.length > 0 ? availableTransmittersForSite : transmitters)" :key="t.id">
                                                    <option :value="t.id" x-text="t.tag + (t.serial_number ? ' (' + t.serial_number + ')' : '')"></option>
                                                </template>
                                            </select>
                                        </div>

                                        <div class="w-32">
                                            <label class="text-[10px] text-gray-500 dark:text-gray-400 font-medium">{{ __('Channel') }}</label>
                                            <input type="text" :name="'transmitters[' + index + '][channel]'" x-model="row.channel"
                                                placeholder="Ch1"
                                                class="mt-0.5 block w-full text-xs font-mono rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-white py-1 px-2.5">
                                        </div>

                                        <div class="pt-4">
                                            <button type="button" @click="removeWiringRow(index)" class="p-2 text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg transition" title="{{ __('Remove Channel') }}">
                                                <i class="fas fa-trash-alt text-sm"></i>
                                            </button>
                                        </div>
                                    </div>
                                </template>

                                <div x-show="wiringRows.length === 0" class="p-6 text-center text-xs text-gray-400 dark:text-gray-500 border border-dashed border-gray-200 dark:border-gray-700 rounded-xl">
                                    {{ __('No transmitter channels wired yet. Click "Add Channel" to connect transmitters.') }}
                                </div>
                            </div>
                        </div>

                        <!-- Submission & Actions -->
                        <div class="flex items-center justify-between border-t border-gray-200 dark:border-gray-700 pt-5">
                            <a href="{{ route('metrology.instruments.create') }}">
                                <x-secondary-button type="button" class="flex items-center gap-1.5 text-xs">
                                    <i class="fas fa-arrow-right rtl:rotate-180"></i>
                                    <span>{{ __('Back to Type Selection') }}</span>
                                </x-secondary-button>
                            </a>

                            <div class="flex items-center gap-3">
                                <x-primary-button type="submit" class="flex items-center gap-2">
                                    <i class="fas fa-plus"></i>
                                    <span>{{ __('Create Measuring Instrument') }}</span>
                                </x-primary-button>
                            </div>
                        </div>
                    </form>
                </main>
            </div>
        </div>
    </div>
</x-app-layout>
