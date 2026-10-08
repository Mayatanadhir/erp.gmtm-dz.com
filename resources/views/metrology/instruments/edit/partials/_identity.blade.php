{{-- Reusable Core Instrument Identity Card for Edit --}}
@props([
    'instrument',
    'type',
    'sites',
    'typeLabel' => null,
])

<div class="p-6 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm space-y-5">
    <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3.5">
        <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <i class="fas fa-id-card text-brand-600 dark:text-brand-400"></i>
            <span>{{ __('Basic Identification & Deployed Site') }}</span>
        </h3>
        <div class="flex items-center gap-2">
            @if($typeLabel)
                <x-badge :variant="$instrument->instrument_type->badgeVariant()" size="sm">
                    {{ $typeLabel }}
                </x-badge>
            @endif
            <span class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                #{{ $instrument->id }}
            </span>
        </div>
    </div>

    <input type="hidden" name="instrument_type" value="{{ $type }}">
    <input type="hidden" name="edit_id" value="{{ $instrument->id }}">

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <!-- Tag Number -->
        <div>
            <x-input-label for="tag_number" :value="__('Tag Number')" :required="true" />
            <x-text-input id="tag_number" name="tag_number" type="text"
                class="mt-1 block w-full font-mono text-sm"
                value="{{ old('tag_number', $instrument->tag_number) }}"
                placeholder="e.g. 05-PT-595 A" required />
            <x-input-error :messages="$errors->get('tag_number')" class="mt-1" />
        </div>

        <!-- Serial Number -->
        <div>
            <x-input-label for="serial_number" :value="__('Serial Number')" :required="true" />
            <x-text-input id="serial_number" name="serial_number" type="text"
                class="mt-1 block w-full font-mono text-sm"
                value="{{ old('serial_number', $instrument->serial_number) }}"
                placeholder="e.g. SN-984210" required />
            <x-input-error :messages="$errors->get('serial_number')" class="mt-1" />
        </div>

        <!-- Operating Status -->
        <div>
            <x-input-label for="status" :value="__('Operating Status')" :required="true" />
            <select id="status" name="status" required
                class="mt-1 block w-full text-sm rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-white focus:border-brand-600 focus:ring-brand-600">
                @foreach(\App\Enums\InstrumentStatus::cases() as $st)
                    <option value="{{ $st->value }}" @selected(old('status', $instrument->status->value) === $st->value)>
                        {{ $st->label() }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('status')" class="mt-1" />
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-gray-50 dark:border-gray-700/50">
        <!-- Deployed Site -->
        <div>
            <x-input-label for="site_id" :value="__('Industrial Site / Station')" />
            <select id="site_id" name="site_id" x-model="siteId"
                class="mt-1 block w-full text-sm rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-white focus:border-brand-600 focus:ring-brand-600">
                <option value="">-- {{ __('No Site (Warehouse / Mobile)') }} --</option>
                @foreach($sites as $site)
                    <option value="{{ $site->id }}" @selected(old('site_id', $instrument->site_id) == $site->id)>
                        {{ $site->short_name ?? $site->full_name }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('site_id')" class="mt-1" />
        </div>

        <!-- Photo Upload & Current Image -->
        <div>
            <x-input-label :value="__('Instrument Photo / Nameplate')" />
            <div class="mt-1 flex items-center gap-3">
                @if($instrument->image_url)
                    <div x-show="!removeImage" class="relative group shrink-0">
                        <img :src="imagePreview || '{{ $instrument->image_url }}'" class="w-12 h-12 object-contain rounded-lg border border-gray-200 dark:border-gray-700 p-0.5 bg-white dark:bg-gray-900" alt="Current Photo">
                    </div>
                @else
                    <template x-if="imagePreview">
                        <div class="shrink-0">
                            <img :src="imagePreview" class="w-12 h-12 object-contain rounded-lg border border-gray-200 dark:border-gray-700 p-0.5 bg-white dark:bg-gray-900" alt="Preview">
                        </div>
                    </template>
                @endif

                <div class="flex-1 min-w-0">
                    <input name="image" type="file" accept="image/*"
                        @change="const file = $event.target.files[0]; if (file) { imagePreview = URL.createObjectURL(file); removeImage = false; }"
                        class="block w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 dark:file:bg-gray-700 dark:file:text-gray-300"
                    />
                </div>

                @if($instrument->image_path)
                    <label class="flex items-center gap-1.5 text-xs text-rose-600 dark:text-rose-400 cursor-pointer shrink-0">
                        <input type="checkbox" name="remove_image" value="1" x-model="removeImage" class="rounded border-rose-300 text-rose-600">
                        <span>{{ __('Delete') }}</span>
                    </label>
                @endif
            </div>
            <x-input-error :messages="$errors->get('image')" class="mt-1" />
        </div>
    </div>
</div>
