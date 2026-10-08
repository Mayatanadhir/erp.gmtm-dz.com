@php
    $totalYears = (int) data_get($stats, 'total_years', 0);
    $latest     = data_get($stats, 'latest_forecast');
    $avgDaily   = (float) data_get($stats, 'avg_planned_daily', 0);

    $canAct = \Illuminate\Support\Facades\Gate::any(['edit annual forecasts', 'delete annual forecasts']);

    /*
     * الحالة الأولية للنافذة. بعد فشل التحقق تُفتح النافذة بنفس النمط (إنشاء/تعديل)
     * وبالقيم القديمة، ويُحسب رابط الإرسال من edit_forecast_id حتى لا يُرسل إلى مسار خاطئ.
     */
    $oldIsEdit = old('_method') === 'PUT';
    $oldEditId = $oldIsEdit ? (int) old('edit_forecast_id', 0) : 0;
    $storeUrl  = route('analytics.forecasts.store', request()->query());

    $formState = [
        'showForm'   => $errors->any(),
        'mode'       => $oldEditId ? 'edit' : 'create',
        'id'         => $oldEditId ?: null,
        'year'       => (string) old('year', date('Y')),
        'days'       => (string) old('expected_work_days', 250),
        'income'     => (string) old('annual_income', ''),
        'action'     => $oldEditId
            ? route('analytics.forecasts.update', ['forecast' => $oldEditId] + request()->query())
            : $storeUrl,
    ];

    $defaults = ['year' => date('Y'), 'days' => '250', 'storeUrl' => $storeUrl];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <x-tool-icon name="forecasts" class="w-14 h-14 shrink-0 transition-transform duration-200 hover:scale-105" />
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                        {{ __('Annual Forecasts') }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('Financial planning, revenue projections, and annual business forecasts') }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                @can('create annual forecasts')
                    <x-primary-button type="button"
                                      @click="$dispatch('open-create-forecast-modal')"
                                      class="flex items-center gap-2 shadow-sm text-xs">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>{{ __('New Forecast') }}</span>
                    </x-primary-button>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row gap-6 items-start">
                <!-- Sidebar Navigation -->
                <aside class="w-full lg:w-64 shrink-0" aria-label="{{ __('Analytics navigation') }}">
                    <x-analytics-tabs active="forecasts" />
                </aside>

                <!-- Main Content -->
                <main class="flex-1 w-full min-w-0 space-y-6"
                     x-data="{
                        ...@js($formState),
                        defaults: @js($defaults),
                        submitting: false,

                        showDeleteModal: false,
                        deleteForecastId: null,
                        deleteForecastYear: '',
                        deleteFormAction: '',

                        get dailyRate() {
                            const income = parseFloat(this.income) || 0;
                            const days   = parseInt(this.days, 10) || 0;
                            return days > 0 ? (income / days).toFixed(2) : '0.00';
                        },

                        openCreate() {
                            this.mode   = 'create';
                            this.id     = null;
                            this.year   = this.defaults.year;
                            this.days   = this.defaults.days;
                            this.income = '';
                            this.action = this.defaults.storeUrl;
                            this.showForm = true;
                            this.$nextTick(() => this.$refs.year?.focus());
                        },

                        openEdit(id, year, days, income, updateUrl) {
                            this.mode   = 'edit';
                            this.id     = id;
                            this.year   = String(year);
                            this.days   = String(days);
                            this.income = String(income);
                            this.action = updateUrl;
                            this.showForm = true;
                            this.$nextTick(() => this.$refs.year?.focus());
                        },

                        openDeleteModal(id, year, destroyUrl) {
                            this.deleteForecastId   = id;
                            this.deleteForecastYear = String(year);
                            this.deleteFormAction   = destroyUrl;
                            this.showDeleteModal    = true;
                        }
                     }"
                     @open-create-forecast-modal.window="openCreate()"
                     @keydown.escape.window="if (showForm) showForm = false; if (showDeleteModal) showDeleteModal = false;">

                    {{-- تنبيهات الجلسة --}}
                    @if (session('success'))
                        <x-alert variant="success">{{ session('success') }}</x-alert>
                    @endif
                    @if (session('error'))
                        <x-alert variant="danger">{{ session('error') }}</x-alert>
                    @endif

                    {{-- ===== بطاقات المؤشرات القياسية ===== --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="group relative overflow-hidden rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700/60 transition-all duration-200 hover:shadow-md">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Total Configured Years') }}</p>
                                    <h3 class="mt-1 text-2xl font-bold font-mono text-gray-900 dark:text-white">
                                        {{ $totalYears }} <span class="text-xs font-normal text-gray-500">{{ __('years') }}</span>
                                    </h3>
                                    <p class="mt-1 text-xs text-brand-700 dark:text-brand-400 font-medium">{{ __('Recorded financial years') }}</p>
                                </div>
                                <div class="w-12 h-12 rounded-xl bg-brand-50 dark:bg-brand-500/10 text-brand-600 dark:text-brand-400 flex items-center justify-center shrink-0">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                            </div>
                        </div>

                        <div class="group relative overflow-hidden rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700/60 transition-all duration-200 hover:shadow-md">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Latest Target') }}</p>
                                    <h3 class="mt-1 text-2xl font-bold font-mono text-gray-900 dark:text-white" dir="ltr">
                                        {{ $latest ? number_format((float) $latest->annual_income, 2, '.', ' ') : '0.00' }} <span class="text-xs font-normal text-gray-500">{{ __('DA') }}</span>
                                    </h3>
                                    <p class="mt-1 text-xs text-brand-700 dark:text-brand-400 font-medium">{{ __('Target Year') }}: {{ $latest?->year ?? '—' }}</p>
                                </div>
                                <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                            </div>
                        </div>

                        <div class="group relative overflow-hidden rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700/60 transition-all duration-200 hover:shadow-md">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Average Planned Daily Rate') }}</p>
                                    <h3 class="mt-1 text-2xl font-bold font-mono text-gray-900 dark:text-white" dir="ltr">
                                        {{ number_format($avgDaily, 2, '.', ' ') }} <span class="text-xs font-normal text-gray-500">{{ __('DA/j') }}</span>
                                    </h3>
                                    <p class="mt-1 text-xs text-brand-700 dark:text-brand-400 font-medium">{{ __('Across all configured years') }}</p>
                                </div>
                                <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ===== جدول التوقعات السنوية ===== --}}
                    <x-table>
                        <x-slot:toolbar>
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 w-full">
                                <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2.5">
                                    <x-tool-icon name="forecasts" class="w-6 h-6 shrink-0" />
                                    <span>{{ __('Annual Forecasts Directory') }}</span>
                                </h3>

                                <x-global-filter :action="route('analytics.forecasts')" :search="false">
                                    <div class="flex items-center gap-2">
                                        <select name="year" aria-label="{{ __('Year') }}"
                                                class="py-1.5 ps-2.5 pe-8 text-xs rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/80 text-gray-900 dark:text-white focus:border-brand-600 focus:ring-1 focus:ring-brand-600 shadow-sm cursor-pointer">
                                            <option value="">{{ __('All Years') }}</option>
                                            @foreach ($availableYears as $yr)
                                                <option value="{{ $yr }}" @selected((string) request('year') === (string) $yr)>{{ $yr }}</option>
                                            @endforeach
                                        </select>
                                        <x-secondary-button type="submit" class="text-xs">{{ __('Apply') }}</x-secondary-button>
                                    </div>
                                </x-global-filter>
                            </div>
                        </x-slot:toolbar>

                        <x-slot:header>
                            <x-table.th class="w-16">#</x-table.th>
                            <x-table.th>{{ __('Year') }}</x-table.th>
                            <x-table.th>{{ __('Expected Work Days') }}</x-table.th>
                            <x-table.th>{{ __('Annual Income Target') }}</x-table.th>
                            <x-table.th>{{ __('Planned Daily Rate') }}</x-table.th>
                            @if ($canAct)
                                <x-table.th class="text-end">{{ __('Actions') }}</x-table.th>
                            @endif
                        </x-slot:header>

                        @forelse ($forecasts as $index => $forecast)
                            @php
                                $editArgs = \Illuminate\Support\Js::from([
                                    $forecast->id,
                                    (string) $forecast->year,
                                    (string) $forecast->expected_work_days,
                                    (string) $forecast->annual_income,
                                    route('analytics.forecasts.update', ['forecast' => $forecast->id] + request()->query()),
                                ]);
                                $deleteArgs = \Illuminate\Support\Js::from([
                                    $forecast->id,
                                    (string) $forecast->year,
                                    route('analytics.forecasts.destroy', ['forecast' => $forecast->id] + request()->query()),
                                ]);
                            @endphp

                            <x-table.tr>
                                <x-table.td class="font-mono text-xs font-semibold text-gray-500 dark:text-gray-400">
                                    <bdi>{{ $forecasts->firstItem() + $index }}</bdi>
                                </x-table.td>

                                <x-table.td>
                                    <x-badge variant="primary" size="md">
                                        <bdi>{{ $forecast->year }}</bdi>
                                    </x-badge>
                                </x-table.td>

                                <x-table.td>
                                    <x-badge variant="neutral" size="sm">
                                        <svg class="w-3.5 h-3.5 text-gray-500 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        <span>{{ $forecast->expected_work_days }} {{ __('days') }}</span>
                                    </x-badge>
                                </x-table.td>

                                <x-table.td class="font-mono text-sm font-bold text-gray-900 dark:text-white" dir="ltr">
                                    {{ number_format((float) $forecast->annual_income, 2, '.', ' ') }} <span class="text-xs font-normal text-gray-400">{{ __('DA') }}</span>
                                </x-table.td>

                                <x-table.td class="font-mono text-sm font-bold text-emerald-600 dark:text-emerald-400" dir="ltr">
                                    {{ number_format((float) $forecast->planned_daily_rate, 2, '.', ' ') }} <span class="text-xs font-normal text-gray-400">{{ __('DA/j') }}</span>
                                </x-table.td>

                                @if ($canAct)
                                    <x-table.td class="text-end">
                                        <x-table.actions>
                                            @can('edit annual forecasts')
                                                <x-table.action-edit type="button"
                                                                     :title="__('Edit Forecast')"
                                                                     @click="openEdit(...{{ $editArgs }})" />
                                            @endcan

                                            @can('delete annual forecasts')
                                                <x-table.action-delete type="button"
                                                                       :title="__('Delete Forecast')"
                                                                       @click="openDeleteModal(...{{ $deleteArgs }})" />
                                            @endcan
                                        </x-table.actions>
                                    </x-table.td>
                                @endif
                            </x-table.tr>
                        @empty
                            <x-table.empty :colspan="$canAct ? 6 : 5" :message="__('No forecasts found.')" />
                        @endforelse

                        <x-slot:pagination>
                            {{ $forecasts->links() }}
                        </x-slot:pagination>
                    </x-table>

                    {{-- ===== نافذة الإنشاء / التعديل (واحدة للاثنين) ===== --}}
                    @canany(['create annual forecasts', 'edit annual forecasts'])
                        <div x-show="showForm" x-cloak
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0"
                             class="fixed inset-0 z-50 overflow-y-auto"
                             role="dialog" aria-modal="true" aria-labelledby="forecast-modal-title">
                            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showForm = false" aria-hidden="true"></div>

                                <div class="relative inline-block w-full max-w-lg bg-white dark:bg-gray-800 rounded-2xl text-start shadow-xl border border-gray-100 dark:border-gray-700/60 overflow-hidden z-10">

                                    {{-- رأس النافذة --}}
                                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-700/60 bg-gray-50/50 dark:bg-gray-800/80">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-xl flex items-center justify-center"
                                                 :class="mode === 'edit' ? 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400' : 'bg-brand-500/10 text-brand-600 dark:text-brand-400'">
                                                <svg x-show="mode === 'create'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                                <svg x-show="mode === 'edit'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                            </div>
                                            <div>
                                                <h3 id="forecast-modal-title" class="text-base font-bold text-gray-900 dark:text-white">
                                                    <span x-show="mode === 'create'">{{ __('New Forecast') }}</span>
                                                    <span x-show="mode === 'edit'">
                                                        {{ __('Edit Forecast') }} : <span class="text-brand-600 dark:text-brand-400 font-mono" x-text="year"></span>
                                                    </span>
                                                </h3>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Planning Details') }}</p>
                                            </div>
                                        </div>
                                        <button type="button" @click="showForm = false"
                                                aria-label="{{ __('Close') }}"
                                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors rounded-md focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>

                                    <form method="POST" :action="action" @submit="submitting = true">
                                        @csrf
                                        <input type="hidden" name="_method" value="PUT" :disabled="mode !== 'edit'">
                                        <input type="hidden" name="edit_forecast_id" :value="id" :disabled="mode !== 'edit'">

                                        <div class="px-6 py-5 space-y-4">
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                <div>
                                                    <x-input-label for="forecast_year" :value="__('Year') . ' *'" />
                                                    <x-text-input id="forecast_year" name="year" type="number" min="2000" max="2100"
                                                                  class="mt-1 block w-full font-mono font-semibold"
                                                                  x-model="year" x-ref="year" required />
                                                    <x-input-error :messages="$errors->get('year')" class="mt-1" />
                                                </div>

                                                <div>
                                                    <x-input-label for="forecast_work_days" :value="__('Expected Work Days') . ' *'" />
                                                    <x-text-input id="forecast_work_days" name="expected_work_days" type="number" min="1" max="366"
                                                                  class="mt-1 block w-full font-mono"
                                                                  x-model="days" required />
                                                    <x-input-error :messages="$errors->get('expected_work_days')" class="mt-1" />
                                                </div>
                                            </div>

                                            <div>
                                                <x-input-label for="forecast_income" :value="__('Annual Income Target') . ' (DA) *'" />
                                                <x-text-input id="forecast_income" name="annual_income" type="number" step="0.01" min="0"
                                                              class="mt-1 block w-full font-mono text-base"
                                                              x-model="income" required placeholder="12000000.00" />
                                                <x-input-error :messages="$errors->get('annual_income')" class="mt-1" />
                                            </div>

                                            {{-- معاينة معدل اليوم --}}
                                            <div class="rounded-xl p-3.5 bg-brand-50/70 dark:bg-gray-700/60 border border-brand-100 dark:border-gray-600/60">
                                                <div class="flex items-center justify-between">
                                                    <div class="flex items-center gap-2">
                                                        <svg class="w-4 h-4 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                        <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ __('Calculated Daily Rate') }}:</span>
                                                    </div>
                                                    <div class="text-sm font-black font-mono text-brand-700 dark:text-brand-300" dir="ltr" aria-live="polite">
                                                        <span x-text="dailyRate">0.00</span>
                                                        <span class="text-xs font-semibold">{{ __('DA/j') }}</span>
                                                    </div>
                                                </div>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                    {{ __('Formula: Annual Target ÷ Expected Work Days') }}
                                                </p>
                                            </div>
                                        </div>

                                        <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 dark:bg-gray-800/80 border-t border-gray-100 dark:border-gray-700/60">
                                            <x-secondary-button type="button" @click="showForm = false">
                                                {{ __('Cancel') }}
                                            </x-secondary-button>
                                            <x-primary-button type="submit" class="flex items-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed" x-bind:disabled="submitting">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                <span x-show="mode === 'create'">{{ __('Save') }}</span>
                                                <span x-show="mode === 'edit'">{{ __('Save Changes') }}</span>
                                            </x-primary-button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endcanany

                    {{-- ===== نافذة الحذف القياسية ===== --}}
                    @can('delete annual forecasts')
                        <x-crud-modal.delete
                            show="showDeleteModal"
                            action-url="deleteFormAction"
                            item-name="deleteForecastYear"
                            :title="__('Delete Forecast')"
                        />
                    @endcan
                </main>
            </div>
        </div>
    </div>
</x-app-layout>
