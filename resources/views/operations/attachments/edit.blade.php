<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <a href="{{ route('operations.attachments.show', $attachment->id) }}" class="p-2 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                            {{ __('Edit Work Attachment') }}: <span class="text-brand-600 dark:text-brand-400">{{ $attachment->code_ref ?? $attachment->ods }}</span>
                        </h2>
                        <x-badge :variant="$attachment->status === 'approved' ? 'success' : 'warning'" size="sm">
                            {{ $attachment->status === 'approved' ? __('Approved') : ($attachment->status === 'draft' ? __('Draft') : ucfirst((string) $attachment->status)) }}
                        </x-badge>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('Update work attachment details and line-item consumption quantities') }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('operations.attachments.show', $attachment->id) }}" class="px-4 py-2 text-sm font-semibold rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                    {{ __('View Details') }}
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $contractsPayload = $contracts->map(function ($c) {
            return [
                'id'        => $c->id,
                'reference' => $c->reference,
                'object'    => $c->object,
                'duree'     => (int) ($c->duree ?? 12),
                'customer'  => $c->customer ? [
                    'id'           => $c->customer->id,
                    'company_name' => $c->customer->company_name,
                    'short_name'   => $c->customer->short_name,
                ] : null,
                'missions' => $c->missions->map(function ($m) {
                    return [
                        'id'           => $m->id,
                        'reference'    => $m->reference,
                        'status'       => $m->status instanceof \App\Enums\MissionStatus ? $m->status->value : (string) $m->status,
                        'status_label' => $m->status instanceof \App\Enums\MissionStatus ? $m->status->label() : (string) $m->status,
                        'site'         => $m->site ? [
                            'id'         => $m->site->id,
                            'short_name' => $m->site->short_name,
                            'full_name'  => $m->site->full_name,
                            'location'   => $m->site->location,
                        ] : null,
                    ];
                }),
                'items' => $c->items->map(function ($it) {
                    return [
                        'id'          => $it->id,
                        'designation' => $it->designation,
                        'item_type'   => $it->itemType?->designation ?? '—',
                        'type'        => $it->type ?? 'service',
                        'frequency'   => $it->frequency ? ($it->frequency instanceof \App\Enums\BillingCycle ? $it->frequency->value : (string) $it->frequency) : '',
                        'quantity'    => (float) ($it->quantity ?? 0),
                        'unit_price'  => (float) ($it->unit_price ?? 0),
                    ];
                }),
            ];
        });

        // Map existing attachment items for pre-population
        $existingItemsPayload = $attachment->items->map(function ($item) {
            $ci = $item->contractItem;
            return [
                'contract_item_id' => $item->contract_item_id,
                'designation'      => $ci?->designation ?? '—',
                'item_type'        => $ci?->itemType?->designation ?? '—',
                'type'             => $ci?->type ?? 'service',
                'frequency'        => $ci?->frequency ? ($ci->frequency instanceof \App\Enums\BillingCycle ? $ci->frequency->value : (string) $ci->frequency) : '',
                'unit_price'       => (float) ($ci?->unit_price ?? 0),
                'contract_qty'     => (float) ($ci?->quantity ?? 0),
                'planned_quantity' => (float) $item->planned_quantity,
                'actual_quantity'  => (float) $item->actual_quantity,
            ];
        });
    @endphp

    <div class="py-8" x-data="attachmentEdit">

        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (isset($errors) && $errors->any())
                <x-alert variant="danger">
                    <div class="font-semibold mb-1">{{ __('Please correct the following errors:') }}</div>
                    <ul class="list-disc list-inside text-xs space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-alert>
            @endif

            <form action="{{ route('operations.attachments.update', $attachment->id) }}" method="POST" id="attachmentEditForm" class="space-y-6">
                @csrf
                @method('PUT')

                <!-- Section 1: Attachment Details Card -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2 mb-5 pb-3 border-b border-gray-100 dark:border-gray-700">
                        <svg class="w-5 h-5 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span>{{ __('Attachment Details') }}</span>
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                        <!-- Code Ref -->
                        <div>
                            <label for="code_ref" class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">
                                {{ __('Reference Code') }}
                            </label>
                            <input type="text" id="code_ref" name="code_ref" value="{{ old('code_ref', $attachment->code_ref) }}" class="w-full py-2.5 px-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 text-sm focus:ring-brand-500 focus:border-brand-500 shadow-sm">
                        </div>

                        <!-- ODS Reference -->
                        <div>
                            <label for="ods" class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">
                                {{ __('ODS Number') }}
                            </label>
                            <input type="text" id="ods" name="ods" value="{{ old('ods', $attachment->ods) }}" class="w-full py-2.5 px-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 text-sm focus:ring-brand-500 focus:border-brand-500 shadow-sm" placeholder="{{ __('e.g. 1844-7 / ODS-2026') }}">
                        </div>

                        <!-- Date -->
                        <div>
                            <label for="date" class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">
                                {{ __('Date') }} <span class="text-rose-500">*</span>
                            </label>
                            <input type="date" id="date" name="date" value="{{ old('date', $attachment->date ? $attachment->date->format('Y-m-d') : date('Y-m-d')) }}" required class="w-full py-2.5 px-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 text-sm focus:ring-brand-500 focus:border-brand-500 shadow-sm">
                        </div>

                        <!-- Type (Service / Supply) -->
                        <div>
                            <label for="type" class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">
                                {{ __('Nature of Work') }} <span class="text-rose-500">*</span>
                            </label>
                            <select id="type" name="type" x-model="selectedType" required class="w-full py-2.5 px-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 text-sm focus:ring-brand-500 focus:border-brand-500 shadow-sm">
                                <option value="service">{{ __('Service (Prestation)') }}</option>
                                <option value="supply">{{ __('Supply (Fourniture)') }}</option>
                            </select>
                        </div>

                        <!-- Frequency / Billing Cycle -->
                        <div>
                            <label for="frequency" class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">
                                {{ __('Billing Cycle') }}
                            </label>
                            <select id="frequency" name="frequency" x-model="selectedFrequency" class="w-full py-2.5 px-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 text-sm focus:ring-brand-500 focus:border-brand-500 shadow-sm">
                                <option value="">— {{ __('All Cycles') }} —</option>
                                <option value="semestrielle">{{ __('Semi-annual') }}</option>
                                <option value="annuelle">{{ __('Annual') }}</option>
                            </select>
                        </div>

                        <!-- Status -->
                        <div>
                            <label for="status" class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">
                                {{ __('Status') }} <span class="text-rose-500">*</span>
                            </label>
                            <select id="status" name="status" required class="w-full py-2.5 px-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 text-sm focus:ring-brand-500 focus:border-brand-500 shadow-sm">
                                <option value="draft" @selected(old('status', $attachment->status) === 'draft')>{{ __('Draft') }}</option>
                                <option value="approved" @selected(old('status', $attachment->status) === 'approved')>{{ __('Approved') }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Contract & Mission Card -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2 mb-5 pb-3 border-b border-gray-100 dark:border-gray-700">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        <span>{{ __('Contract & Mission Selection') }}</span>
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <!-- Contract Selection -->
                        <div>
                            <label for="contract_id" class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">
                                {{ __('Select Contract') }} <span class="text-rose-500">*</span>
                            </label>
                            <select id="contract_id" name="contract_id" x-model="selectedContractId" @change="onContractChange()" required class="w-full py-2.5 px-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 text-sm focus:ring-brand-500 focus:border-brand-500 shadow-sm">
                                <option value="">— {{ __('Select Contract') }} —</option>
                                <template x-for="c in contracts" :key="c.id">
                                    <option :value="c.id" x-text="c.reference + (c.customer ? ' (' + (c.customer.short_name || c.customer.company_name) + ')' : '')"></option>
                                </template>
                            </select>
                        </div>

                        <!-- Mission Selection -->
                        <div>
                            <label for="mission_id" class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">
                                {{ __('Select Mission') }} <span class="text-rose-500">*</span>
                            </label>
                            <select id="mission_id" name="mission_id" x-model="selectedMissionId" @change="onMissionChange()" required class="w-full py-2.5 px-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 text-sm focus:ring-brand-500 focus:border-brand-500 shadow-sm">
                                <option value="">— {{ __('Select Mission') }} —</option>
                                <template x-for="m in availableMissions" :key="m.id">
                                    <option :value="m.id" x-text="m.reference + (m.site ? ' [' + (m.site.short_name || m.site.full_name) + ']' : '') + (m.status_label ? ' (' + m.status_label + ')' : '')"></option>
                                </template>
                            </select>
                        </div>

                        <!-- Industrial Site Display -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">
                                {{ __('Industrial Site') }}
                            </label>
                            <input type="text" :value="selectedSite || '—'" readonly class="w-full py-2.5 px-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-sm shadow-sm cursor-not-allowed">
                        </div>
                    </div>
                </div>

                <!-- Section 3: Consumed Items Table Card -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5 pb-4 border-b border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">
                                {{ __('Contract Items Consumptions') }}
                            </h3>
                            <span class="ms-1.5 px-2.5 py-0.5 text-xs font-semibold rounded-full bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300" x-text="items.length + ' {{ __('lines') }}'"></span>
                        </div>

                        <div class="flex items-center gap-2 flex-wrap" x-show="selectedContractId">
                            <button type="button" @click="autoFillItems()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                <span>{{ __('Reset & Auto-fill from Contract') }}</span>
                            </button>

                            <button type="button" @click="addItem()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <span>{{ __('Add Line Item') }}</span>
                            </button>

                            <button type="button" @click="clearItems()" x-show="items.length > 0" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-lg text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                <span>{{ __('Clear') }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <template x-if="items.length > 0">
                        <div class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-xl shadow-xs">
                            <table class="w-full text-start text-xs">
                                <thead class="bg-gray-50 dark:bg-gray-900/60 font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700">
                                    <tr>
                                        <th class="py-3 px-3 text-start w-10">#</th>
                                        <th class="py-3 px-3 text-start">{{ __('Contract Line Item') }}</th>
                                        <th class="py-3 px-3 text-center w-28">{{ __('Cycle') }}</th>
                                        <th class="py-3 px-3 text-end w-32">{{ __('Unit Price (DA)') }}</th>
                                        <th class="py-3 px-3 text-center w-32">{{ __('Planned Qty') }}</th>
                                        <th class="py-3 px-3 text-center w-36 font-bold text-emerald-600 dark:text-emerald-400">{{ __('Actual Qty') }} <span class="text-rose-500">*</span></th>
                                        <th class="py-3 px-3 text-end w-36">{{ __('Subtotal (DA)') }}</th>
                                        <th class="py-3 px-2 text-center w-12">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    <template x-for="(item, idx) in items" :key="idx">
                                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                                            <td class="py-2.5 px-3 text-gray-400 font-medium" x-text="idx + 1"></td>

                                            <!-- Contract Item Select -->
                                            <td class="py-2.5 px-3">
                                                <select :name="'items[' + idx + '][contract_item_id]'"
                                                        x-model="item.contract_item_id"
                                                        @change="onItemSelectChange(idx, $event.target.value)"
                                                        required
                                                        class="w-full py-1.5 px-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 text-xs focus:ring-brand-500 focus:border-brand-500">
                                                    <template x-for="p in contractItemsPool" :key="p.id">
                                                        <option :value="p.id"
                                                                :selected="p.id == item.contract_item_id"
                                                                x-text="(p.type ? '[' + p.type.toUpperCase() + '] ' : '') + p.designation">
                                                        </option>
                                                    </template>
                                                </select>
                                                <div class="flex items-center gap-2 mt-1 text-[11px] text-gray-400">
                                                    <span x-text="'{{ __('Category') }}: ' + item.item_type"></span>
                                                    <span>•</span>
                                                    <span x-text="'{{ __('Contract Total') }}: ' + item.contract_qty"></span>
                                                </div>
                                            </td>

                                            <!-- Frequency Badge -->
                                            <td class="py-2.5 px-3 text-center">
                                                <span class="inline-block px-2 py-0.5 rounded text-[11px] font-medium"
                                                      :class="item.frequency === 'semestrielle' ? 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' : (item.frequency === 'annuelle' ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400')"
                                                      x-text="item.frequency === 'semestrielle' ? '{{ __('Semi-annual') }}' : (item.frequency === 'annuelle' ? '{{ __('Annual') }}' : (item.frequency || '{{ __('Standard') }}'))">
                                                </span>
                                            </td>

                                            <!-- Unit Price -->
                                            <td class="py-2.5 px-3 text-end font-medium text-gray-700 dark:text-gray-300">
                                                <span dir="ltr" x-text="(parseFloat(item.unit_price) || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                                            </td>

                                            <!-- Planned Qty -->
                                            <td class="py-2.5 px-3 text-center">
                                                <input type="number" step="any" min="0"
                                                       :name="'items[' + idx + '][planned_quantity]'"
                                                       x-model.number="item.planned_quantity"
                                                       class="w-24 text-center py-1.5 px-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 text-xs focus:ring-brand-500 focus:border-brand-500">
                                            </td>

                                            <!-- Actual Qty -->
                                            <td class="py-2.5 px-3 text-center">
                                                <input type="number" step="any" min="0"
                                                       :name="'items[' + idx + '][actual_quantity]'"
                                                       x-model.number="item.actual_quantity"
                                                       required
                                                       class="w-28 text-center font-bold text-emerald-600 dark:text-emerald-400 py-1.5 px-2 rounded-lg border border-emerald-300 dark:border-emerald-700 bg-white dark:bg-gray-900 text-xs focus:ring-emerald-500 focus:border-emerald-500">
                                            </td>

                                            <!-- Subtotal -->
                                            <td class="py-2.5 px-3 text-end font-bold text-gray-900 dark:text-white">
                                                <span dir="ltr" x-text="(((parseFloat(item.actual_quantity) || 0) * (parseFloat(item.unit_price) || 0))).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' DA'"></span>
                                            </td>

                                            <!-- Delete Row Button -->
                                            <td class="py-2.5 px-2 text-center">
                                                <button type="button" @click="removeItem(idx)" class="p-1 rounded-md text-gray-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30 transition-colors" title="{{ __('Remove item') }}">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </template>

                    <!-- Empty State -->
                    <template x-if="items.length === 0">
                        <div class="py-12 text-center text-gray-500 dark:text-gray-400 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-xl">
                            <svg class="w-10 h-10 text-gray-300 dark:text-gray-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            <p class="font-medium text-sm text-gray-700 dark:text-gray-300">
                                {{ __('No line items on this attachment.') }}
                            </p>
                            <div class="mt-3 flex items-center justify-center gap-2">
                                <button type="button" @click="autoFillItems()" class="px-3.5 py-1.5 text-xs font-semibold rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 shadow-sm transition-colors">
                                    {{ __('Auto-populate from Contract') }}
                                </button>
                                <button type="button" @click="addItem()" class="px-3.5 py-1.5 text-xs font-semibold rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm transition-colors">
                                    {{ __('Add Single Item') }}
                                </button>
                            </div>
                        </div>
                    </template>

                    <!-- Total Live Valorization Footer -->
                    <div class="mt-5 flex flex-col sm:flex-row items-center justify-between gap-4 p-4 rounded-xl bg-emerald-50/80 dark:bg-gray-800/90 border border-emerald-200/80 dark:border-gray-700">
                        <div>
                            <span class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-semibold">{{ __('Planned Valorization (DA)') }}</span>
                            <div class="text-base font-bold text-gray-900 dark:text-white"><span dir="ltr" x-text="getTotalPlannedAmount().toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' DA'"></span></div>
                        </div>
                        <div class="sm:text-end">
                            <span class="text-xs font-bold text-brand-700 dark:text-brand-300 uppercase tracking-wider">{{ __('Total Actual Invoiced (DA)') }}</span>
                            <div class="text-2xl font-black text-brand-700 dark:text-brand-300"><span dir="ltr" x-text="getTotalActualAmount().toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' DA'"></span></div>
                        </div>
                    </div>
                </div>

                <!-- Section 4: Submit Actions -->
                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('operations.attachments.show', $attachment->id) }}" class="px-5 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 text-sm font-semibold transition-colors">
                        {{ __('Cancel') }}
                    </a>
                    <button type="submit" class="btn-primary flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>{{ __('Update Attachment') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('attachmentEdit', () => ({
                contracts:          @json($contractsPayload),
                existingItems:      @json($existingItemsPayload),
                selectedContractId: '{{ old('contract_id', $selectedContractId) }}',
                selectedMissionId:  '{{ old('mission_id', $attachment->mission_id) }}',
                selectedType:       '{{ old('type', $attachment->type) }}',
                selectedFrequency:  '{{ old('frequency', $attachment->frequency?->value ?? (string) $attachment->frequency) }}',
                availableMissions:  [],
                contractItemsPool:  [],
                items:              [],
                selectedSite:       '',

                init() {
                    if (this.selectedContractId) {
                        this.onContractChange(true);
                    }
                    // Pre-populate with existing saved items
                    if (this.existingItems && this.existingItems.length > 0) {
                        this.items = JSON.parse(JSON.stringify(this.existingItems));
                    } else {
                        this.autoFillItems();
                    }
                    this.onMissionChange();
                },

                onContractChange(isInit = false) {
                    const contract = this.contracts.find(c => String(c.id) === String(this.selectedContractId));
                    if (contract) {
                        this.availableMissions  = contract.missions || [];
                        this.contractItemsPool  = contract.items    || [];
                        if (!isInit) {
                            this.autoFillItems();
                        }
                    } else {
                        this.availableMissions  = [];
                        this.contractItemsPool  = [];
                        this.items             = [];
                        this.selectedSite      = '';
                    }
                    if (!this.availableMissions.some(m => String(m.id) === String(this.selectedMissionId))) {
                        this.selectedMissionId = '';
                        this.selectedSite      = '';
                    } else {
                        this.onMissionChange();
                    }
                },

                onMissionChange() {
                    const mission = this.availableMissions.find(m => String(m.id) === String(this.selectedMissionId));
                    if (mission && mission.site) {
                        this.selectedSite = (mission.site.short_name || mission.site.full_name)
                            + (mission.site.location ? ' (' + mission.site.location + ')' : '');
                    } else {
                        this.selectedSite = '';
                    }
                },

                calcPlanned(item, duration) {
                    const totalQty = item.quantity || 0;
                    let planned = totalQty;
                    if (item.frequency === 'annuelle') {
                        planned = totalQty / (duration / 12);
                    } else if (item.frequency === 'semestrielle') {
                        planned = totalQty / (duration / 6);
                    }
                    planned = Math.floor(planned);
                    return (planned < 0 || isNaN(planned)) ? 0 : planned;
                },

                autoFillItems() {
                    const contract = this.contracts.find(c => String(c.id) === String(this.selectedContractId));
                    if (!contract || !this.contractItemsPool.length) {
                        this.items = [];
                        return;
                    }

                    const duration   = contract.duree || 12;
                    const typeFilter = this.selectedType;
                    const freqFilter = this.selectedFrequency;

                    const filtered = this.contractItemsPool.filter(item => {
                        if (typeFilter && item.type && item.type !== typeFilter) return false;
                        if (freqFilter) {
                            if (freqFilter === 'semestrielle' && item.frequency && item.frequency !== 'semestrielle') return false;
                            if (freqFilter === 'annuelle' && item.frequency && item.frequency !== 'annuelle' && item.frequency !== 'semestrielle') return false;
                        }
                        return true;
                    });

                    this.items = filtered.map(it => {
                        const planned = this.calcPlanned(it, duration);
                        return {
                            contract_item_id: it.id,
                            designation:      it.designation,
                            item_type:        it.item_type,
                            type:             it.type,
                            frequency:        it.frequency,
                            unit_price:       it.unit_price,
                            contract_qty:     it.quantity,
                            planned_quantity: planned,
                            actual_quantity:  0,
                        };
                    });
                },

                addItem(specificItemId = null) {
                    if (!this.contractItemsPool.length) return;
                    let target = this.contractItemsPool[0];
                    if (specificItemId) {
                        const found = this.contractItemsPool.find(it => String(it.id) === String(specificItemId));
                        if (found) target = found;
                    }
                    const contract  = this.contracts.find(c => String(c.id) === String(this.selectedContractId));
                    const duration  = contract ? (contract.duree || 12) : 12;
                    const planned   = this.calcPlanned(target, duration);
                    this.items.push({
                        contract_item_id: target.id,
                        designation:      target.designation,
                        item_type:        target.item_type,
                        type:             target.type,
                        frequency:        target.frequency,
                        unit_price:       target.unit_price,
                        contract_qty:     target.quantity,
                        planned_quantity: planned,
                        actual_quantity:  0,
                    });
                },

                removeItem(index) {
                    this.items.splice(index, 1);
                },

                clearItems() {
                    this.items = [];
                },

                onItemSelectChange(index, newId) {
                    const target = this.contractItemsPool.find(it => String(it.id) === String(newId));
                    if (target && this.items[index]) {
                        const contract  = this.contracts.find(c => String(c.id) === String(this.selectedContractId));
                        const duration  = contract ? (contract.duree || 12) : 12;
                        const planned   = this.calcPlanned(target, duration);
                        this.items[index].contract_item_id = target.id;
                        this.items[index].designation      = target.designation;
                        this.items[index].item_type        = target.item_type;
                        this.items[index].type             = target.type;
                        this.items[index].frequency        = target.frequency;
                        this.items[index].unit_price       = target.unit_price;
                        this.items[index].contract_qty     = target.quantity;
                        this.items[index].planned_quantity = planned;
                        this.items[index].actual_quantity  = 0;
                    }
                },

                getTotalActualAmount() {
                    return this.items.reduce((acc, it) =>
                        acc + ((parseFloat(it.actual_quantity) || 0) * (parseFloat(it.unit_price) || 0)), 0);
                },

                getTotalPlannedAmount() {
                    return this.items.reduce((acc, it) =>
                        acc + ((parseFloat(it.planned_quantity) || 0) * (parseFloat(it.unit_price) || 0)), 0);
                },
            }));
        });
    </script>
    @endpush

</x-app-layout>
