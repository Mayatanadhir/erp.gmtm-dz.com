<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <x-tool-icon name="calibration-certificates" class="w-11 h-11 sm:w-12 sm:h-12 shrink-0" />
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                        {{ __('Calibration Certificates') }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('ISO/IEC 17025 conformity records, verification certificates, and validity tracking') }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                @can('create calibration certificates')
                    <a href="{{ route('metrology.calibration-certificates.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors duration-150">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>{{ __('Register Certificate') }}</span>
                    </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- Global Flash Alerts -->
            @if(session('success'))
                <x-alert variant="success">{{ session('success') }}</x-alert>
            @endif
            @if(session('error'))
                <x-alert variant="danger">{{ session('error') }}</x-alert>
            @endif
            @if(session('warning'))
                <x-alert variant="warning">{{ session('warning') }}</x-alert>
            @endif

            <!-- 5-Card KPI Counter Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200/80 dark:border-gray-700/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Total Certificates') }}</p>
                        <p class="text-2xl font-black text-gray-900 dark:text-white mt-1.5">{{ $statistics['total'] }}</p>
                        <p class="text-xs text-brand-600 dark:text-brand-400 mt-0.5 font-medium">{{ __('Metrology Archive') }}</p>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-brand-500/10 dark:bg-brand-500/20 text-brand-600 dark:text-brand-400 border border-brand-500/20 dark:border-brand-500/30 flex items-center justify-center shrink-0">
                        <i class="fas fa-certificate text-lg"></i>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200/80 dark:border-gray-700/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Valid & Active') }}</p>
                        <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1.5">{{ $statistics['valid'] }}</p>
                        <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-0.5 font-medium">{{ __('Operationally Compliant') }}</p>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 dark:border-emerald-500/30 flex items-center justify-center shrink-0">
                        <i class="fas fa-check-circle text-lg"></i>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200/80 dark:border-gray-700/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Expiring Soon') }}</p>
                        <p class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1.5">{{ $statistics['expiring_soon'] }}</p>
                        <p class="text-xs text-amber-600 dark:text-amber-400 mt-0.5 font-medium">{{ __('Within 30 Days') }}</p>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-amber-500/10 dark:bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-500/20 dark:border-amber-500/30 flex items-center justify-center shrink-0">
                        <i class="fas fa-clock text-lg"></i>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200/80 dark:border-gray-700/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Expired') }}</p>
                        <p class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1.5">{{ $statistics['expired'] }}</p>
                        <p class="text-xs text-rose-600 dark:text-rose-400 mt-0.5 font-medium">{{ __('Archived Certificate') }}</p>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-rose-500/10 dark:bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-500/20 dark:border-rose-500/30 flex items-center justify-center shrink-0">
                        <i class="fas fa-exclamation-triangle text-lg"></i>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200/80 dark:border-gray-700/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Drafts / In Review') }}</p>
                        <p class="text-2xl font-black text-gray-600 dark:text-gray-300 mt-1.5">{{ $statistics['draft'] }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 font-medium">{{ __('Pending Approval') }}</p>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-gray-500/10 dark:bg-gray-500/20 text-gray-600 dark:text-gray-300 border border-gray-500/20 dark:border-gray-600/30 flex items-center justify-center shrink-0">
                        <i class="fas fa-file-signature text-lg"></i>
                    </div>
                </div>
            </div>

            <div class="flex flex-col lg:flex-row gap-6 items-start">
                <!-- Metrology Module Sidebar -->
                <aside class="w-full lg:w-64 shrink-0">
                    <x-metrology-tabs active="calibration-certificates" />
                </aside>

                <!-- Main Content Area -->
                <main class="flex-1 w-full min-w-0 space-y-6">
                    <!-- Advanced Filter Toolbar -->
                    <x-global-filter
                        :action="route('metrology.calibration-certificates')"
                        :search-placeholder="__('Ref, Lab, Equipment...')"
                        :search-value="request('search')"
                    >
                        {{-- Equipment Filter --}}
                        <x-global-filter.select
                            name="equipment_id"
                            :placeholder="__('All Equipment')"
                            :value="request('equipment_id')"
                        >
                            @foreach($equipments as $eq)
                                <option value="{{ $eq->id }}" @selected(request('equipment_id') == $eq->id)>
                                    {{ $eq->short_name ?: $eq->full_name }}{{ $eq->internal_code ? ' ('.$eq->internal_code.')' : '' }}
                                </option>
                            @endforeach
                        </x-global-filter.select>

                        {{-- Status Filter --}}
                        <x-global-filter.select
                            name="status"
                            :placeholder="__('All Statuses')"
                            :value="request('status')"
                        >
                            @foreach(\App\Enums\CalibrationCertificateStatus::cases() as $st)
                                <option value="{{ $st->value }}" @selected(request('status') === $st->value)>{{ $st->label() }}</option>
                            @endforeach
                        </x-global-filter.select>

                        {{-- Calibration Date From --}}
                        <input type="date"
                               name="calibration_date_from"
                               value="{{ request('calibration_date_from') }}"
                               title="{{ __('Calibration From') }}"
                               class="rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/80 px-3 py-1.5 text-xs text-gray-900 dark:text-white focus:border-brand-600 focus:ring-1 focus:ring-brand-600 dark:focus:border-brand-600 transition-colors shadow-sm" />
                    </x-global-filter>

                    <!-- Certificates Table -->
                    <x-table>
                        <x-slot:toolbar>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2.5">
                                <x-tool-icon name="calibration-certificates" class="w-6 h-6 shrink-0" />
                                <span>{{ __('Calibration Certificates') }}</span>
                                <span class="text-xs px-2 py-0.5 rounded-full bg-brand-500/10 text-brand-700 dark:text-brand-300 dark:bg-brand-500/20 border border-brand-500/20 dark:border-brand-500/30 font-medium">
                                    {{ $certificates->total() }}
                                </span>
                            </h3>
                        </x-slot:toolbar>

                        <x-slot:header>
                            <x-table.th>{{ __('Certificate') }}</x-table.th>
                            <x-table.th>{{ __('Target Equipment') }}</x-table.th>
                            <x-table.th>{{ __('Calibration Date') }}</x-table.th>
                            <x-table.th>{{ __('Validity / Expiry') }}</x-table.th>
                            <x-table.th>{{ __('Status') }}</x-table.th>
                            <x-table.th class="text-end">{{ __('Actions') }}</x-table.th>
                        </x-slot:header>

                        @forelse($certificates as $cert)
                            <x-table.tr>
                                <x-table.td>
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-lg bg-brand-500/10 dark:bg-brand-500/20 text-brand-600 dark:text-brand-400 border border-brand-500/20 dark:border-brand-500/30 flex items-center justify-center shrink-0">
                                            <i class="fas fa-certificate text-sm"></i>
                                        </div>
                                        <div>
                                            <a href="{{ route('metrology.calibration-certificates.show', $cert) }}" class="font-semibold text-gray-900 dark:text-white hover:text-brand-600 dark:hover:text-brand-400 transition">
                                                {{ $cert->reference ?: ('CERT-#' . $cert->id) }}
                                            </a>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ ucfirst($cert->certificate_type) }}
                                                @if($cert->laboratory_name)
                                                    <span>• {{ $cert->laboratory_name }}</span>
                                                @endif
                                                @if($cert->is_locked)
                                                    <span class="inline-flex items-center text-amber-600 dark:text-amber-400 ml-1" title="{{ __('Locked & Approved') }}">
                                                        <i class="fas fa-lock text-xs"></i>
                                                    </span>
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                </x-table.td>

                                <x-table.td>
                                    @if($cert->equipment)
                                        <a href="{{ route('metrology.equipment.show', $cert->equipment) }}" class="group flex items-center gap-2.5">
                                            <div class="w-12 h-12 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/60 flex items-center justify-center shrink-0">
                                                @if($cert->equipment->image_url)
                                                    <img src="{{ $cert->equipment->image_url }}" alt="{{ $cert->equipment->short_name }}" class="w-full h-full object-contain p-0.5">
                                                @else
                                                    <i class="fas {{ $cert->equipment->category?->icon() ?? 'fa-tools' }} text-sm text-gray-400"></i>
                                                @endif
                                            </div>
                                            <div class="min-w-0">
                                                <span class="font-medium text-gray-900 dark:text-white group-hover:text-brand-600 transition block truncate">
                                                    {{ $cert->equipment->short_name }}
                                                </span>
                                                <span class="text-[11px] text-gray-500 font-mono">({{ $cert->equipment->internal_code }})</span>
                                            </div>
                                        </a>
                                    @else
                                        <span class="text-gray-400 italic">{{ __('None') }}</span>
                                    @endif
                                </x-table.td>


                                <x-table.td>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                                        <x-date :value="$cert->calibration_date" />
                                    </span>
                                </x-table.td>

                                <x-table.td>
                                    @if($cert->expiry_date)
                                        <div class="space-y-0.5">
                                            <span class="text-sm font-medium text-gray-900 dark:text-white block">
                                                <x-date :value="$cert->expiry_date" />
                                            </span>
                                            @php $rem = $cert->remaining_days; @endphp
                                            @if($rem !== null)
                                                @if($rem < 0)
                                                    <span class="inline-block text-[11px] font-semibold text-rose-600 dark:text-rose-400">
                                                        <i class="fas fa-times-circle mr-0.5"></i> {{ __('Expired :days d ago', ['days' => abs($rem)]) }}
                                                    </span>
                                                @elseif($rem <= 30)
                                                    <span class="inline-block text-[11px] font-semibold text-amber-600 dark:text-amber-400">
                                                        <i class="fas fa-exclamation-triangle mr-0.5"></i> {{ __('Expires in :days d', ['days' => $rem]) }}
                                                    </span>
                                                @else
                                                    <span class="inline-block text-[11px] font-medium text-emerald-600 dark:text-emerald-400">
                                                        <i class="fas fa-check mr-0.5"></i> {{ __(':days days remaining', ['days' => $rem]) }}
                                                    </span>
                                                @endif
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-gray-400 italic">—</span>
                                    @endif
                                </x-table.td>

                                <x-table.td>
                                    <x-badge :variant="$cert->status?->badgeVariant() ?? 'neutral'">
                                        {{ $cert->status?->label() ?? ucfirst((string) $cert->status) }}
                                    </x-badge>
                                </x-table.td>

                                <x-table.td class="text-end">
                                    <x-table.actions>
                                        <x-table.action-view
                                            href="{{ route('metrology.calibration-certificates.show', $cert) }}"
                                            :title="__('View Details')"
                                        />

                                        @if($cert->certificate_path)
                                            <x-table.action-download
                                                href="{{ route('metrology.calibration-certificates.download', $cert) }}"
                                                :title="__('Download PDF')"
                                            />
                                        @endif

                                        @can('edit calibration certificates')
                                            @if(! $cert->is_locked)
                                                <x-table.action-edit
                                                    href="{{ route('metrology.calibration-certificates.edit', $cert) }}"
                                                    :title="__('Edit')"
                                                />
                                            @endif
                                        @endcan

                                        @can('delete calibration certificates')
                                            @if(! $cert->is_locked)
                                                <x-table.action-delete
                                                    :action-url="route('metrology.calibration-certificates.destroy', $cert)"
                                                    :item-name="$cert->certificate_number"
                                                    :title="__('Delete Certificate')"
                                                />
                                            @endif
                                        @endcan
                                    </x-table.actions>
                                </x-table.td>
                            </x-table.tr>
                        @empty
                            <x-table.empty :colspan="7" :message="__('No calibration certificates matching criteria.')" />
                        @endforelse

                        <x-slot:pagination>
                            {{ $certificates->links() }}
                        </x-slot:pagination>
                    </x-table>
                </main>
            </div>
        </div>
    </div>
</x-app-layout>
