{{-- Flow Computer Channels (if applicable) --}}
@if($instrument->instrument_type === \App\Enums\InstrumentType::FlowComputer)
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5 space-y-4">
        <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <i class="fas fa-server text-brand-600"></i>
            <span>{{ __('Linked Flow Computer Channels (Transmitters)') }}</span>
        </h3>

        @if($instrument->linkedTransmitters->isNotEmpty())
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach($instrument->linkedTransmitters as $trans)
                    <div class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/30 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded text-xs font-mono font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                CH #{{ $trans->pivot->channel_number ?? 1 }}
                            </span>
                            <span class="text-xs font-bold text-gray-900 dark:text-white font-mono">{{ $trans->tag_number }}</span>
                        </div>
                        <span class="text-[11px] text-gray-500 dark:text-gray-400">{{ $trans->process_variable?->label() ?? '---' }}</span>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('No linked transmitters configured.') }}</p>
        @endif
    </div>
@endif

{{-- Standard Gauge Specs (if applicable) --}}
@if($instrument->standardGaugeSpecification)
    @php $gauge = $instrument->standardGaugeSpecification; @endphp
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5 space-y-4">
        <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <i class="fas fa-flask text-brand-600"></i>
            <span>{{ __('Standard Gauge Metrological Blueprint') }}</span>
        </h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
            <div class="p-2.5 rounded bg-gray-50 dark:bg-gray-700/30 border border-gray-100 dark:border-gray-700">
                <span class="text-gray-500 dark:text-gray-400 block">{{ __('Base Volume (Liters)') }}</span>
                <span class="font-mono font-bold text-gray-900 dark:text-white text-sm">{{ number_format($gauge->nominal_capacity_liters, 5) }} L</span>
            </div>
            <div class="p-2.5 rounded bg-gray-50 dark:bg-gray-700/30 border border-gray-100 dark:border-gray-700">
                <span class="text-gray-500 dark:text-gray-400 block">{{ __('Neck Scale Sensitivity') }}</span>
                <span class="font-mono font-bold text-gray-900 dark:text-white text-sm">{{ $gauge->neck_scale_sensitivity ?? '---' }} L/mm</span>
            </div>
            <div class="p-2.5 rounded bg-gray-50 dark:bg-gray-700/30 border border-gray-100 dark:border-gray-700">
                <span class="text-gray-500 dark:text-gray-400 block">{{ __('Vessel Material') }}</span>
                <span class="font-medium text-gray-900 dark:text-white">{{ $gauge->vessel_material }}</span>
            </div>
            <div class="p-2.5 rounded bg-gray-50 dark:bg-gray-700/30 border border-gray-100 dark:border-gray-700">
                <span class="text-gray-500 dark:text-gray-400 block">{{ __('Certificate Number') }}</span>
                <span class="font-mono text-gray-900 dark:text-white">{{ $gauge->calibration_certificate_number ?? '---' }}</span>
            </div>
        </div>
    </div>
@endif

{{-- Prover Specs (if applicable) --}}
@if($instrument->proverSpecification)
    @php $prover = $instrument->proverSpecification; @endphp
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5 space-y-4">
        <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <i class="fas fa-tachometer-alt text-brand-600"></i>
            <span>{{ __('Prover Engineering Specifications') }}</span>
        </h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
            <div class="p-2.5 rounded bg-gray-50 dark:bg-gray-700/30 border border-gray-100 dark:border-gray-700">
                <span class="text-gray-500 dark:text-gray-400 block">{{ __('Prover Type') }}</span>
                <span class="font-medium text-gray-900 dark:text-white">{{ $prover->type?->label() ?? $prover->type }}</span>
            </div>
            <div class="p-2.5 rounded bg-gray-50 dark:bg-gray-700/30 border border-gray-100 dark:border-gray-700">
                <span class="text-gray-500 dark:text-gray-400 block">{{ __('Nominal Base Volume') }}</span>
                <span class="font-mono font-bold text-gray-900 dark:text-white text-sm">{{ $prover->nominal_base_volume ? number_format($prover->nominal_base_volume, 5).' L' : '---' }}</span>
            </div>
            <div class="p-2.5 rounded bg-gray-50 dark:bg-gray-700/30 border border-gray-100 dark:border-gray-700">
                <span class="text-gray-500 dark:text-gray-400 block">{{ __('Inner Diameter / Wall Thickness') }}</span>
                <span class="font-mono text-gray-900 dark:text-white">{{ $prover->inner_diameter ?? '---' }} mm / {{ $prover->wall_thickness ?? '---' }} mm</span>
            </div>
            <div class="p-2.5 rounded bg-gray-50 dark:bg-gray-700/30 border border-gray-100 dark:border-gray-700">
                <span class="text-gray-500 dark:text-gray-400 block">{{ __('Material') }}</span>
                <span class="font-medium text-gray-900 dark:text-white">{{ $prover->material }}</span>
            </div>
        </div>
    </div>
@endif
