{{-- Edit Instrument Modal --}}
@can('edit measuring instruments')
    <div
        x-show="showEditModal"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        aria-labelledby="edit-modal-title"
        role="dialog"
        aria-modal="true"
    >
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div
                x-show="showEditModal"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="showEditModal = false"
                class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"
            ></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div
                x-show="showEditModal"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl text-start overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-gray-100 dark:border-gray-700"
            >
                <form action="{{ route('metrology.instruments.update', $instrument) }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_redirect" value="{{ route('metrology.instruments.show', $instrument) }}">

                    <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-4">
                        <div class="flex items-center gap-2.5">
                            <x-tool-icon name="instruments" class="w-8 h-8 shrink-0" />
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                {{ __('Edit Measuring Instrument') }}
                            </h3>
                        </div>
                        <button type="button" @click="showEditModal = false" class="text-gray-400 hover:text-gray-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    @if ($errors->any() && old('_method') === 'PUT')
                        <x-alert variant="danger">
                            <ul class="list-disc list-inside text-xs space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </x-alert>
                    @endif

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="edit_tag_number" :value="__('Tag Number')" :required="true" />
                            <x-text-input id="edit_tag_number" name="tag_number" type="text" class="mt-1 block w-full font-mono" x-model="editTagNumber" required />
                        </div>

                        <div>
                            <x-input-label for="edit_serial_number" :value="__('Serial Number')" :required="true" />
                            <x-text-input id="edit_serial_number" name="serial_number" type="text" class="mt-1 block w-full font-mono" x-model="editSerialNumber" required />
                        </div>

                        <div>
                            <x-input-label for="edit_instrument_type" :value="__('Instrument Type')" :required="true" />
                            <select id="edit_instrument_type" name="instrument_type" x-model="editType" required class="mt-1 block w-full text-sm rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-white">
                                @foreach(\App\Enums\InstrumentType::cases() as $type)
                                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label for="edit_site_id" :value="__('Site')" />
                            <select id="edit_site_id" name="site_id" x-model="editSiteId" class="mt-1 block w-full text-sm rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-white">
                                <option value="">-- {{ __('Select Site') }} --</option>
                                @foreach($sites as $site)
                                    <option value="{{ $site->id }}">{{ $site->short_name ?? $site->full_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label for="edit_status" :value="__('Status')" :required="true" />
                            <select id="edit_status" name="status" x-model="editStatus" required class="mt-1 block w-full text-sm rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-white">
                                @foreach(\App\Enums\InstrumentStatus::cases() as $st)
                                    <option value="{{ $st->value }}">{{ $st->label() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Transmitter / Probe specific fields -->
                        <div x-show="editType === 'transmitter' || editType === 'probe'" class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-3 gap-4 p-4 rounded-xl bg-gray-50 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-700">
                            <div>
                                <x-input-label for="edit_process_variable" :value="__('Process Variable')" />
                                <select id="edit_process_variable" name="process_variable" x-model="editProcessVariable" class="mt-1 block w-full text-sm rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-white">
                                    <option value="">-- {{ __('Select') }} --</option>
                                    @foreach(\App\Enums\ProcessVariable::cases() as $pv)
                                        <option value="{{ $pv->value }}">{{ $pv->label() }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-input-label for="edit_fluid_type" :value="__('Fluid Type')" />
                                <select id="edit_fluid_type" name="fluid_type" x-model="editFluidType" class="mt-1 block w-full text-sm rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-white">
                                    <option value="">-- {{ __('Select') }} --</option>
                                    @foreach(\App\Enums\FluidType::cases() as $ft)
                                        <option value="{{ $ft->value }}">{{ $ft->label() }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-input-label for="edit_technology" :value="__('Technology')" />
                                <x-text-input id="edit_technology" name="technology" type="text" class="mt-1 block w-full" x-model="editTechnology" />
                            </div>
                        </div>

                        <!-- Standard Gauge Specs -->
                        <div x-show="editType === 'standard_gauge'" class="sm:col-span-2 p-4 rounded-xl bg-gray-50 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-700 space-y-4">
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <i class="fas fa-flask text-brand-600"></i>
                                <span>{{ __('Standard Gauge (Jauge Étalon) Specifications') }}</span>
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label :value="__('Nominal Capacity (Liters)')" :required="true" />
                                    <x-text-input name="standard_gauge_spec[nominal_capacity_liters]" type="number" step="0.00001" class="mt-1 block w-full font-mono" x-model="editGaugeSpec.nominal_capacity_liters" />
                                </div>
                                <div>
                                    <x-input-label :value="__('Neck Scale Sensitivity (L/mm)')" />
                                    <x-text-input name="standard_gauge_spec[neck_scale_sensitivity]" type="number" step="0.00001" class="mt-1 block w-full font-mono" x-model="editGaugeSpec.neck_scale_sensitivity" />
                                </div>
                                <div>
                                    <x-input-label :value="__('Certificate Number')" />
                                    <x-text-input name="standard_gauge_spec[calibration_certificate_number]" type="text" class="mt-1 block w-full" x-model="editGaugeSpec.calibration_certificate_number" />
                                </div>
                                <div>
                                    <x-input-label :value="__('Certificate Expiry Date')" />
                                    <x-text-input name="standard_gauge_spec[calibration_expiry_date]" type="date" class="mt-1 block w-full" x-model="editGaugeSpec.calibration_expiry_date" />
                                </div>
                            </div>
                        </div>

                        <!-- Prover Specs -->
                        <div x-show="editType === 'prover'" class="sm:col-span-2 p-4 rounded-xl bg-gray-50 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-700 space-y-4">
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <i class="fas fa-tachometer-alt text-brand-600"></i>
                                <span>{{ __('Prover Engineering Specifications') }}</span>
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <x-input-label :value="__('Prover Type')" :required="true" />
                                    <select name="prover_spec[type]" x-model="editProverSpec.type" class="mt-1 block w-full text-sm rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-white">
                                        @foreach(\App\Enums\ProverType::cases() as $pt)
                                            <option value="{{ $pt->value }}">{{ $pt->label() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <x-input-label :value="__('Inner Diameter (mm)')" />
                                    <x-text-input name="prover_spec[inner_diameter]" type="number" step="0.001" class="mt-1 block w-full font-mono" x-model="editProverSpec.inner_diameter" />
                                </div>
                                <div>
                                    <x-input-label :value="__('Wall Thickness (mm)')" />
                                    <x-text-input name="prover_spec[wall_thickness]" type="number" step="0.001" class="mt-1 block w-full font-mono" x-model="editProverSpec.wall_thickness" />
                                </div>
                                <div class="sm:col-span-2">
                                    <x-input-label :value="__('Nominal Base Volume (Liters)')" />
                                    <x-text-input name="prover_spec[nominal_base_volume]" type="number" step="0.00001" class="mt-1 block w-full font-mono" x-model="editProverSpec.nominal_base_volume" />
                                </div>
                                <div>
                                    <x-input-label :value="__('Material')" />
                                    <x-text-input name="prover_spec[material]" type="text" class="mt-1 block w-full" x-model="editProverSpec.material" />
                                </div>
                            </div>
                        </div>

                        <!-- Image File Input and Existing Image -->
                        <div class="sm:col-span-2 space-y-2">
                            <x-input-label :value="__('Instrument Photo')" />
                            <template x-if="editImageUrl && !editRemoveImage">
                                <div class="flex items-center gap-3 p-2 rounded-lg bg-gray-50 dark:bg-gray-900/40 border border-gray-200 dark:border-gray-700">
                                    <img :src="editImageUrl" class="w-12 h-12 object-contain rounded border border-gray-200 dark:border-gray-700 p-0.5 bg-white dark:bg-gray-800" alt="Current Photo">
                                    <label class="flex items-center gap-1.5 text-xs text-rose-600 dark:text-rose-400 cursor-pointer">
                                        <input type="checkbox" name="remove_image" value="1" x-model="editRemoveImage" class="rounded border-rose-300 text-rose-600">
                                        <span>{{ __('Remove Current Photo') }}</span>
                                    </label>
                                </div>
                            </template>

                            <input name="image" type="file" accept="image/*"
                                @change="const file = $event.target.files[0]; if (file) { editImagePreview = URL.createObjectURL(file) }"
                                class="mt-1 block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 dark:file:bg-gray-700 dark:file:text-gray-300"
                            />
                            <template x-if="editImagePreview">
                                <div class="mt-2">
                                    <img :src="editImagePreview" class="w-16 h-16 object-contain rounded-lg border border-gray-200 dark:border-gray-700 p-1 bg-white dark:bg-gray-800" alt="Preview">
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Physical Quantities / Grandeurs Setup in Edit Modal -->
                    <div x-show="editType === 'transmitter' || editType === 'probe'" class="border-t border-gray-100 dark:border-gray-700 pt-4 space-y-3">
                        <h4 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-sliders-h text-brand-600"></i>
                            <span>{{ __('Configure Physical Quantities & Measurement Ranges') }}</span>
                        </h4>

                        <div class="space-y-2 max-h-56 overflow-y-auto pr-1">
                            @foreach($measurementGrandeurs as $grandeur)
                                <div class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                                    <label class="flex items-center gap-2 cursor-pointer font-medium text-gray-900 dark:text-white">
                                        <input type="checkbox" name="params[{{ $grandeur->id }}][selected]" value="1"
                                            :checked="Boolean(editSpecs[{{ $grandeur->id }}]?.selected)"
                                            @change="editSpecs[{{ $grandeur->id }}] = editSpecs[{{ $grandeur->id }}] || {}; editSpecs[{{ $grandeur->id }}].selected = $event.target.checked"
                                            class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                        <span>{{ $grandeur->name }} <span class="font-mono text-gray-400">({{ $grandeur->symbol }})</span></span>
                                    </label>

                                    <div class="flex items-center gap-2 font-mono">
                                        <input type="number" step="any" name="params[{{ $grandeur->id }}][min]" :value="editSpecs[{{ $grandeur->id }}]?.min ?? ''" placeholder="{{ __('Min') }}" class="w-20 text-xs rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-gray-900 dark:text-white py-1 px-2">
                                        <span>→</span>
                                        <input type="number" step="any" name="params[{{ $grandeur->id }}][max]" :value="editSpecs[{{ $grandeur->id }}]?.max ?? ''" placeholder="{{ __('Max') }}" class="w-20 text-xs rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-gray-900 dark:text-white py-1 px-2">
                                        <input type="number" step="any" name="params[{{ $grandeur->id }}][acc]" :value="editSpecs[{{ $grandeur->id }}]?.acc ?? ''" placeholder="{{ __('Accuracy') }}" class="w-16 text-xs rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-gray-900 dark:text-white py-1 px-2">
                                        <select name="params[{{ $grandeur->id }}][acc_type]" :value="editSpecs[{{ $grandeur->id }}]?.acc_type ?? '%'" class="text-xs rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-gray-900 dark:text-white py-1 px-1">
                                            <option value="%">%</option>
                                            <option value="abs">Abs</option>
                                        </select>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-gray-100 dark:border-gray-700 pt-4">
                        <x-secondary-button type="button" @click="showEditModal = false">
                            {{ __('Cancel') }}
                        </x-secondary-button>
                        <x-primary-button type="submit">
                            {{ __('Save Changes') }}
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endcan
