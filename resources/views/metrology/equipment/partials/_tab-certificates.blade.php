{{-- Tab 3: Calibration Certificates History --}}
<div x-show="activeTab === 'documents'" x-transition class="space-y-6">
    <x-table>
        <x-slot:toolbar>
            <div class="flex items-center justify-between w-full">
                <h4 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-certificate text-brand-600"></i>
                    <span>{{ __('Calibration Certificates History') }}</span>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 font-bold border border-emerald-200 dark:border-emerald-800/70">
                        {{ $equipment->calibrationCertificates->count() }}
                    </span>
                </h4>

                @can('create calibration certificates')
                    @if($equipment->requires_calibration)
                        <a href="{{ route('metrology.calibration-certificates.create', ['equipment_id' => $equipment->id]) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-brand-600 hover:bg-brand-700 text-white text-xs font-semibold rounded-lg shadow-sm transition">
                            <i class="fas fa-plus"></i>
                            <span>{{ __('Register Certificate') }}</span>
                        </a>
                    @endif
                @endcan
            </div>
        </x-slot:toolbar>

        <x-slot:header>
            <x-table.th>{{ __('Certificate') }}</x-table.th>
            <x-table.th>{{ __('Type') }}</x-table.th>
            <x-table.th>{{ __('Laboratory') }}</x-table.th>
            <x-table.th>{{ __('Calibration Date') }}</x-table.th>
            <x-table.th>{{ __('Expiry Date') }}</x-table.th>
            <x-table.th>{{ __('Status') }}</x-table.th>
            <x-table.th class="text-end">{{ __('Actions') }}</x-table.th>
        </x-slot:header>

        @forelse($equipment->calibrationCertificates as $cert)
            <x-table.tr>
                <x-table.td>
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/70 flex items-center justify-center shrink-0">
                            <i class="fas fa-certificate text-sm"></i>
                        </div>
                        <div>
                            <a href="{{ route('metrology.calibration-certificates.show', $cert) }}" class="font-semibold text-gray-900 dark:text-white hover:text-brand-600 dark:hover:text-brand-400 transition">
                                {{ $cert->reference ?: ('CERT-#' . $cert->id) }}
                            </a>
                            @if($cert->is_locked)
                                <span class="inline-flex items-center text-amber-600 dark:text-amber-400 ml-1 text-xs" title="{{ __('Locked & Approved') }}">
                                    <i class="fas fa-lock text-[10px]"></i>
                                </span>
                            @endif
                        </div>
                    </div>
                </x-table.td>

                <x-table.td>
                    <span class="text-xs text-gray-600 dark:text-gray-400 capitalize">{{ $cert->certificate_type }}</span>
                </x-table.td>

                <x-table.td>
                    <span class="text-xs text-gray-800 dark:text-gray-200">{{ $cert->laboratory_name ?: '—' }}</span>
                </x-table.td>

                <x-table.td>
                    <span class="text-xs font-medium text-gray-900 dark:text-white">
                        <x-date :value="$cert->calibration_date" />
                    </span>
                </x-table.td>

                <x-table.td>
                    @if($cert->expiry_date)
                        <div class="space-y-0.5">
                            <span class="text-xs font-medium text-gray-900 dark:text-white block">
                                <x-date :value="$cert->expiry_date" />
                            </span>
                            @php $rem = $cert->remaining_days; @endphp
                            @if($rem !== null)
                                @if($rem < 0)
                                    <span class="text-[10px] font-semibold text-rose-600 dark:text-rose-400 block">
                                        <i class="fas fa-times-circle mr-0.5"></i> {{ __('Expired :days d ago', ['days' => abs($rem)]) }}
                                    </span>
                                @elseif($rem <= 30)
                                    <span class="text-[10px] font-semibold text-amber-600 dark:text-amber-400 block">
                                        <i class="fas fa-exclamation-triangle mr-0.5"></i> {{ __('Expires in :days d', ['days' => $rem]) }}
                                    </span>
                                @else
                                    <span class="text-[10px] font-medium text-emerald-600 dark:text-emerald-400 block">
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
                    <x-badge :variant="$cert->status?->badgeVariant() ?? 'neutral'" size="sm">
                        {{ $cert->status?->label() ?? ucfirst((string) $cert->status) }}
                    </x-badge>
                </x-table.td>

                <x-table.td class="text-end">
                    <x-table.actions>
                        <x-table.action-view href="{{ route('metrology.calibration-certificates.show', $cert) }}" :title="__('View Details')" />

                        @if($cert->certificate_path)
                            <x-table.action-download href="{{ route('metrology.calibration-certificates.download', $cert) }}" :title="__('Download PDF')" />
                        @endif

                        @can('edit calibration certificates')
                            @if(! $cert->is_locked)
                                <x-table.action-edit href="{{ route('metrology.calibration-certificates.edit', $cert) }}" :title="__('Edit')" />
                                <form method="POST" action="{{ route('metrology.calibration-certificates.approve', $cert) }}" class="inline" x-data>
                                    @csrf
                                    <x-table.action type="success" buttonType="button" @click="if (confirm({{ json_encode(__('Are you sure you want to approve and officially lock this certificate?')) }})) { $el.closest('form').submit(); }" :title="__('Approve & Lock')" />
                                </form>
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
            <x-table.empty :colspan="7" :message="__('No calibration certificates recorded for this equipment yet.')" />
        @endforelse
    </x-table>
</div>
