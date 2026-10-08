{{-- Edit Equipment Modal --}}
@can('edit equipment')
    <div
        x-show="showEditModal"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        aria-labelledby="modal-title"
        role="dialog"
        aria-modal="true"
    >
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            {{-- Backdrop --}}
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

            {{-- Modal Panel --}}
            <div
                x-show="showEditModal"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl text-start overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full border border-gray-100 dark:border-gray-700"
            >
                <form action="{{ route('metrology.equipment.update', $equipment) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_redirect" value="{{ route('metrology.equipment.show', $equipment) }}">

                    {{-- Header --}}
                    <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2 rounded-lg bg-indigo-50 dark:bg-indigo-500/20 text-indigo-600 dark:text-indigo-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Edit Equipment') }}</h3>
                        </div>
                        <button type="button" @click="showEditModal = false" class="text-gray-400 hover:text-gray-500 text-xl font-bold">&times;</button>
                    </div>

                    {{-- Body --}}
                    <div class="p-6 space-y-6 max-h-[75vh] overflow-y-auto">

                        {{-- Validation Errors --}}
                        @if($errors->any() && old('_method') === 'PUT')
                            <x-alert variant="danger">
                                <ul class="list-disc list-inside text-xs space-y-1">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </x-alert>
                        @endif

                        {{-- Basic Information --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <x-input-label for="edit_full_name" :value="__('Full Name')" required />
                                <x-text-input id="edit_full_name" name="full_name" type="text" class="mt-1 block w-full" x-model="editFullName" required />
                                <x-input-error :messages="$errors->get('full_name')" class="mt-1" />
                            </div>

                            <div>
                                <x-input-label for="edit_short_name" :value="__('Short / Acronym Name')" />
                                <x-text-input id="edit_short_name" name="short_name" type="text" class="mt-1 block w-full" x-model="editShortName" />
                            </div>

                            <div>
                                <x-input-label for="edit_internal_code" :value="__('Storage / Internal Code')" />
                                <x-text-input id="edit_internal_code" name="internal_code" type="text" class="mt-1 block w-full font-mono" x-model="editInternalCode" />
                            </div>

                            <div>
                                <x-input-label for="edit_serial_number" :value="__('Serial Number (S/N)')" />
                                <x-text-input id="edit_serial_number" name="serial_number" type="text" class="mt-1 block w-full font-mono" x-model="editSerialNumber" />
                            </div>

                            <div>
                                <x-input-label for="edit_category" :value="__('Equipment Category')" required />
                                <select id="edit_category" name="category" x-model="editCategory" required
                                    class="mt-1 block w-full py-2 px-3 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                    @foreach(\App\Enums\EquipmentCategory::cases() as $cat)
                                        <option value="{{ $cat->value }}">{{ $cat->label() }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-input-label for="edit_package" :value="__('Logistics Package / Lot')" />
                                <select id="edit_package" name="package" x-model="editPackage"
                                    class="mt-1 block w-full py-2 px-3 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                    @foreach(\App\Enums\EquipmentPackage::cases() as $pkg)
                                        <option value="{{ $pkg->value }}">{{ $pkg->label() }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-input-label for="edit_status" :value="__('Operational Status')" />
                                <select id="edit_status" name="status" x-model="editStatus"
                                    class="mt-1 block w-full py-2 px-3 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                    @foreach(\App\Enums\EquipmentStatus::cases() as $st)
                                        <option value="{{ $st->value }}">{{ $st->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Calibration Switch --}}
                        <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/40">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" name="requires_calibration" value="1" x-model="editRequiresCalibration"
                                    class="rounded border-gray-300 text-orange-500 shadow-sm focus:ring-orange-500 w-4 h-4">
                                <div>
                                    <div class="font-semibold text-sm text-gray-900 dark:text-white flex items-center gap-2">
                                        <i class="fas fa-certificate text-orange-500"></i>
                                        <span>{{ __('Requires Periodic Metrological Calibration') }}</span>
                                    </div>
                                </div>
                            </label>
                        </div>

                        {{-- Physical Specifications --}}
                        <div x-show="editRequiresCalibration" x-transition class="space-y-4">
                            <h4 class="font-bold text-sm text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2 flex items-center gap-2">
                                <i class="fas fa-wave-square text-indigo-500"></i>
                                <span>{{ __('Measurement & Generation Capabilities') }}</span>
                            </h4>

                            {{-- Measurement Parameters --}}
                            @if(isset($measurementGrandeurs) && $measurementGrandeurs->count() > 0)
                                <div>
                                    <div class="text-xs font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider mb-2">
                                        <i class="fas fa-signal me-1"></i> {{ __('Measurement Capabilities') }}
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        @foreach($measurementGrandeurs as $g)
                                            <div class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm space-y-2">
                                                <label class="flex items-center justify-between cursor-pointer">
                                                    <span class="font-semibold text-xs text-gray-900 dark:text-white flex items-center gap-1.5">
                                                        <span>{{ $g->name }}</span>
                                                        <span class="text-[11px] px-1.5 py-0.5 rounded bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border border-emerald-500/20 font-bold">({{ $g->symbol }})</span>
                                                    </span>
                                                    <input type="checkbox" name="params[{{ $g->id }}][selected]" value="1" :checked="editSpecs[{{ $g->id }}]?.selected"
                                                        class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-brand-600 focus:ring-brand-500 dark:focus:ring-offset-gray-800">
                                                </label>
                                                <div class="grid grid-cols-2 gap-2">
                                                    <input type="number" step="any" name="params[{{ $g->id }}][min]" placeholder="{{ __('Min') }}" :value="editSpecs[{{ $g->id }}]?.min ?? ''" class="py-1 px-2 text-xs rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                                    <input type="number" step="any" name="params[{{ $g->id }}][max]" placeholder="{{ __('Max') }}" :value="editSpecs[{{ $g->id }}]?.max ?? ''" class="py-1 px-2 text-xs rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                                </div>
                                                <div class="grid grid-cols-2 gap-2">
                                                    <input type="number" step="any" name="params[{{ $g->id }}][acc]" placeholder="{{ __('Accuracy') }}" :value="editSpecs[{{ $g->id }}]?.acc ?? ''" class="py-1 px-2 text-xs rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                                    <select name="params[{{ $g->id }}][acc_type]" class="py-1 px-2 text-xs rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                                        <option value="%" :selected="editSpecs[{{ $g->id }}]?.acc_type === '%'">%</option>
                                                        <option value="abs" :selected="editSpecs[{{ $g->id }}]?.acc_type === 'abs'">Abs</option>
                                                    </select>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            {{-- Source Parameters --}}
                            @if(isset($sourceGrandeurs) && $sourceGrandeurs->count() > 0)
                                <div>
                                    <div class="text-xs font-bold text-amber-700 dark:text-amber-400 uppercase tracking-wider mb-2 mt-4">
                                        <i class="fas fa-bolt me-1"></i> {{ __('Source Capabilities') }}
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        @foreach($sourceGrandeurs as $g)
                                            <div class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm space-y-2">
                                                <label class="flex items-center justify-between cursor-pointer">
                                                    <span class="font-semibold text-xs text-gray-900 dark:text-white flex items-center gap-1.5">
                                                        <span>{{ $g->name }}</span>
                                                        <span class="text-[11px] px-1.5 py-0.5 rounded bg-amber-500/10 text-amber-700 dark:text-amber-300 border border-amber-500/20 font-bold">({{ $g->symbol }})</span>
                                                    </span>
                                                    <input type="checkbox" name="params[{{ $g->id }}][selected]" value="1" :checked="editSpecs[{{ $g->id }}]?.selected"
                                                        class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-brand-600 focus:ring-brand-500 dark:focus:ring-offset-gray-800">
                                                </label>
                                                <div class="grid grid-cols-2 gap-2">
                                                    <input type="number" step="any" name="params[{{ $g->id }}][min]" placeholder="{{ __('Min') }}" :value="editSpecs[{{ $g->id }}]?.min ?? ''" class="py-1 px-2 text-xs rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                                    <input type="number" step="any" name="params[{{ $g->id }}][max]" placeholder="{{ __('Max') }}" :value="editSpecs[{{ $g->id }}]?.max ?? ''" class="py-1 px-2 text-xs rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                                </div>
                                                <div class="grid grid-cols-2 gap-2">
                                                    <input type="number" step="any" name="params[{{ $g->id }}][acc]" placeholder="{{ __('Accuracy') }}" :value="editSpecs[{{ $g->id }}]?.acc ?? ''" class="py-1 px-2 text-xs rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                                    <select name="params[{{ $g->id }}][acc_type]" class="py-1 px-2 text-xs rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                                        <option value="%" :selected="editSpecs[{{ $g->id }}]?.acc_type === '%'">%</option>
                                                        <option value="abs" :selected="editSpecs[{{ $g->id }}]?.acc_type === 'abs'">Abs</option>
                                                    </select>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Media Uploads --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-gray-100 dark:border-gray-700">
                            <div>
                                <x-input-label for="edit_image" :value="__('Change Equipment Photo')" />
                                <input id="edit_image" name="image" type="file" accept="image/*"
                                    @change="handleImageChange($event)"
                                    class="mt-1 block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100 dark:file:bg-gray-700 dark:file:text-gray-300">
                                <template x-if="editImagePreview || editImageUrl">
                                    <div class="mt-2 flex items-center gap-3">
                                        <img :src="editImagePreview || editImageUrl" class="w-16 h-16 object-contain p-0.5 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                                        <label class="text-xs text-rose-600 dark:text-rose-400 flex items-center gap-1 cursor-pointer">
                                            <input type="checkbox" name="remove_image" value="1" x-model="editRemoveImage" class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-rose-600 focus:ring-rose-500 dark:focus:ring-offset-gray-800">
                                            <span>{{ __('Remove image') }}</span>
                                        </label>
                                    </div>
                                </template>
                            </div>

                            <div>
                                <x-input-label for="edit_certificate" :value="__('Change Certificate (PDF)')" />
                                <input id="edit_certificate" name="certificate" type="file" accept=".pdf"
                                    class="mt-1 block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-gray-700 dark:file:text-gray-300">
                                <template x-if="editCertificateUrl">
                                    <div class="mt-2 flex items-center gap-3">
                                        <a :href="editCertificateUrl" target="_blank" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1">
                                            <i class="fas fa-file-pdf"></i>
                                            <span>{{ __('View Current Certificate') }}</span>
                                        </a>
                                        <label class="text-xs text-rose-600 dark:text-rose-400 flex items-center gap-1 cursor-pointer">
                                            <input type="checkbox" name="remove_certificate" value="1" x-model="editRemoveCertificate" class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-rose-600 focus:ring-rose-500 dark:focus:ring-offset-gray-800">
                                            <span>{{ __('Remove') }}</span>
                                        </label>
                                    </div>
                                </template>
                            </div>

                            <div class="sm:col-span-2">
                                <x-input-label for="edit_designation" :value="__('Designation / Technical Notes')" />
                                <textarea id="edit_designation" name="designation" rows="2" x-model="editDesignation"
                                    class="mt-1 block w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm"></textarea>
                            </div>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-100 dark:border-gray-700 flex justify-end gap-3">
                        <x-secondary-button type="button" @click="showEditModal = false">
                            {{ __('Cancel') }}
                        </x-secondary-button>
                        <x-primary-button type="submit">
                            {{ __('Update Equipment') }}
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endcan
