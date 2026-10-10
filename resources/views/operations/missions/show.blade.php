<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <x-secondary-button href="{{ route('operations.missions') }}" class="!p-2.5 !rounded-xl" title="{{ __('Back to Missions') }}">
                    <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </x-secondary-button>
                <div>
                    <div class="flex items-center gap-2.5">
                        <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight font-mono">
                            {{ $mission->reference }}
                        </h2>
                        <x-badge :variant="$mission->status->badgeVariant()" :dot="true">
                            {{ $mission->status->label() }}
                        </x-badge>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ $mission->site?->full_name ?? __('Field Operation') }} — {{ $mission->site?->location }}
                    </p>
                </div>
            </div>

            <!-- Lifecycle Actions -->
            <div class="flex flex-wrap items-center gap-2">
                <!-- Statistics Button -->
                <x-secondary-button href="{{ route('operations.missions.statistics', $mission->id) }}" class="gap-1.5">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    <span>{{ __('Unit Economics') }}</span>
                </x-secondary-button>

                <!-- Equipment Manifest Button -->
                <x-secondary-button href="{{ route('operations.missions.equipments', $mission->id) }}" target="_blank" class="gap-1.5">
                    <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                    <span>{{ __('Equipment Manifest') }}</span>
                </x-secondary-button>

                @can('edit missions')
                    @if ($mission->status->canActivate())
                        <form action="{{ route('operations.missions.activate', $mission->id) }}" method="POST" class="inline-block" x-data>
                            @csrf
                            <x-primary-button type="button" @click="if (confirm({{ json_encode(__('Activate this mission and verify equipment availability?')) }})) { $el.closest('form').submit(); }" class="gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>{{ __('Activate Mission') }}</span>
                            </x-primary-button>
                        </form>
                    @endif

                    @if ($mission->status->canComplete())
                        <form action="{{ route('operations.missions.complete', $mission->id) }}" method="POST" class="inline-block" x-data>
                            @csrf
                            <x-success-button type="button" @click="if (confirm({{ json_encode(__('Mark mission as completed and release deployed calibrators?')) }})) { $el.closest('form').submit(); }" class="gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>{{ __('Complete Mission') }}</span>
                            </x-success-button>
                        </form>
                    @endif

                    @if ($mission->status->canRevert())
                        <form action="{{ route('operations.missions.revert', $mission->id) }}" method="POST" class="inline-block" x-data>
                            @csrf
                            <x-secondary-button type="button" @click="if (confirm({{ json_encode(__('Revert mission back to Planned status?')) }})) { $el.closest('form').submit(); }" class="gap-1.5" title="{{ __('Revert to Planned') }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                <span>{{ __('Revert') }}</span>
                            </x-secondary-button>
                        </form>
                    @endif

                    @if ($mission->status->isModifiable())
                        <x-edit-button href="{{ route('operations.missions.edit', $mission->id) }}">
                            {{ __('Edit') }}
                        </x-edit-button>
                    @endif
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <x-alert variant="success">
                    {{ session('success') }}
                </x-alert>
            @endif

            @if (session('error'))
                <x-alert variant="danger">
                    {{ session('error') }}
                </x-alert>
            @endif

            <!-- 1. Mission Snapshot Grid -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Site Card -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
                    <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">{{ __('Location') }}</div>
                    <div class="text-base font-bold text-gray-900 dark:text-white truncate">{{ $mission->site?->full_name ?? '—' }}</div>
                    <div class="text-xs text-brand-600 dark:text-brand-400 font-medium mt-1 flex items-center gap-1">
                        <span>{{ $mission->site?->location }}</span>
                        @if ($mission->site?->map_link)
                            <a href="{{ $mission->site->map_link }}" target="_blank" class="hover:underline inline-flex items-center text-xs">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Calendar Timeline Card -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
                    <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">{{ __('Schedule Interval') }}</div>
                    <div class="text-sm font-bold text-gray-900 dark:text-white">
                        <x-date :value="$mission->start_date" /> → <x-date :value="$mission->end_date" />
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ $mission->total_days }} {{ __('calendar days') }}
                    </div>
                </div>

                <!-- Operational Duration Card -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
                    <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">{{ __('Operational Days') }}</div>
                    <div class="text-2xl font-black text-gray-900 dark:text-white">
                        {{ $mission->operational_days }} <span class="text-xs font-normal text-gray-500 dark:text-gray-400">{{ __('working days') }}</span>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ $mission->mob_dmob_days }} {{ __('mob/dmob transit days') }}
                    </div>
                </div>

                <!-- Assigned Team Card -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
                    <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">{{ __('Mission Chief') }}</div>
                    <div class="text-sm font-bold text-gray-900 dark:text-white truncate">
                        {{ $mission->teamLeader?->full_name ?? __('Unassigned') }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ $mission->employees->count() }} {{ __('certified team members') }}
                    </div>
                </div>
            </div>

            <!-- Mission Scope Description (if set) -->
            @if ($mission->description)
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
                    <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">{{ __('Technical Scope & Objectives') }}</div>
                    <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-line">{{ $mission->description }}</p>
                </div>
            @endif

            <!-- 2. Team Deployment & Travel Orders Table -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <span>{{ __('Field Personnel & Official Travel Orders (Ordres de Mission)') }}</span>
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-start text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900/60 text-gray-700 dark:text-gray-300 font-semibold text-xs uppercase tracking-wider border-b border-gray-100 dark:border-gray-700/60">
                            <tr>
                                <th class="px-5 py-3 text-start">{{ __('Employee') }}</th>
                                <th class="px-5 py-3 text-start">{{ __('Role') }}</th>
                                <th class="px-5 py-3 text-start">{{ __('Order Ref') }}</th>
                                <th class="px-5 py-3 text-start">{{ __('Travel Itinerary') }}</th>
                                <th class="px-5 py-3 text-start">{{ __('Designated Vehicle') }}</th>
                                <th class="px-5 py-3 text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach ($mission->missionOrders as $order)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/40 transition-colors">
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            @if ($order->employee?->profile_photo_url)
                                                <img src="{{ $order->employee->profile_photo_url }}" alt="{{ $order->employee->full_name }}" class="w-8 h-8 rounded-full object-cover border border-gray-200 dark:border-gray-700 shadow-xs">
                                            @else
                                                <div class="w-8 h-8 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 flex items-center justify-center font-bold text-xs border border-emerald-200 dark:border-emerald-800/70">
                                                    {{ $order->employee?->initials ?: mb_substr($order->employee?->full_name ?? '?', 0, 1) }}
                                                </div>
                                            @endif
                                            <div>
                                                <div class="font-medium text-gray-900 dark:text-white">{{ $order->employee?->full_name }}</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400 font-mono">{{ $order->employee?->registration_number }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        @if ($order->is_leader)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300">
                                                <svg class="w-3 h-3 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                                <span>{{ __('Team Leader') }}</span>
                                            </span>
                                        @else
                                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('Engineer / Technician') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 font-mono text-xs text-gray-700 dark:text-gray-300">
                                        {{ $order->order_reference ?? __('Pending Print') }}
                                    </td>
                                    <td class="px-5 py-4 text-xs text-gray-600 dark:text-gray-300 max-w-xs truncate">
                                        {{ $order->destination ?? '—' }}
                                    </td>
                                    <td class="px-5 py-4 text-xs">
                                        @if ($order->all_vehicles)
                                            <span class="text-emerald-600 dark:text-emerald-400 font-medium">{{ __('All Vehicles') }}</span>
                                        @elseif ($order->vehicle)
                                            <div class="flex items-center gap-2">
                                                @if ($order->vehicle->image_url)
                                                    <img src="{{ $order->vehicle->image_url }}" alt="{{ $order->vehicle->full_name }}" class="w-8 h-7 rounded-md object-contain p-0.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                                                @endif
                                                <span class="text-gray-900 dark:text-white">{{ $order->vehicle->full_name }}</span>
                                            </div>
                                        @else
                                            <span class="text-gray-400 dark:text-gray-500">{{ __('None') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-end">
                                        <div class="inline-flex items-center gap-1.5">
                                            <x-table.action-print href="{{ route('operations.missions.orders.print', [$mission->id, $order->id]) }}" target="_blank" title="{{ __('Print Travel Order') }}" />
                                            <x-table.action-edit href="{{ route('operations.missions.orders.edit', [$mission->id, $order->id]) }}" title="{{ __('Edit Itinerary') }}" />
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 3. Deployed Calibrators & Equipment Table -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
                        <span>{{ __('Mobilized Equipment, Calibrators & Standards') }}</span>
                    </h3>
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $mission->technical_deployments->count() }} {{ __('items deployed') }}</span>
                        <x-secondary-button href="{{ route('operations.missions.equipments', $mission->id) }}" target="_blank" class="!px-3 !py-1.5 !text-xs !rounded-lg gap-1.5" title="{{ __('Print Equipment Manifest') }}">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            <span>{{ __('Print Equipment Manifest') }}</span>
                        </x-secondary-button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-start text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900/60 text-gray-700 dark:text-gray-300 font-semibold text-xs uppercase tracking-wider border-b border-gray-100 dark:border-gray-700/60">
                            <tr>
                                <th class="px-5 py-3 text-start">{{ __('Code') }}</th>
                                <th class="px-5 py-3 text-start">{{ __('Equipment Designation') }}</th>
                                <th class="px-5 py-3 text-start">{{ __('Category') }}</th>
                                <th class="px-5 py-3 text-start">{{ __('Lot / Package') }}</th>
                                <th class="px-5 py-3 text-end">{{ __('Custody Status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($mission->technical_deployments as $deployment)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/40 transition-colors">
                                    <td class="px-5 py-3.5 font-mono text-xs font-semibold text-brand-600 dark:text-brand-400">
                                        {{ $deployment->equipment?->internal_code ?? '—' }}
                                    </td>
                                    <td class="px-5 py-3.5 font-medium text-gray-900 dark:text-white">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-9 h-9 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 flex items-center justify-center shrink-0 p-0.5 overflow-hidden shadow-2xs">
                                                @if ($deployment->equipment?->image_url)
                                                    <a href="{{ $deployment->equipment->image_url }}" target="_blank" title="{{ __('View Full Image') }}" class="w-full h-full flex items-center justify-center">
                                                        <img src="{{ $deployment->equipment->image_url }}" alt="{{ $deployment->equipment->full_name }}" class="w-full h-full object-contain hover:scale-110 transition-transform duration-150" loading="lazy">
                                                    </a>
                                                @else
                                                    <svg class="w-4 h-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
                                                @endif
                                            </div>
                                            <span>{{ $deployment->equipment?->full_name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3.5 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $deployment->equipment?->category?->label() ?? $deployment->equipment?->category?->value ?? $deployment->equipment?->category }}
                                    </td>
                                    <td class="px-5 py-3.5 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $deployment->equipment?->package?->label() ?? $deployment->equipment?->package?->value ?? $deployment->equipment?->package ?? '—' }}
                                    </td>
                                    <td class="px-5 py-3.5 text-end">
                                        <x-badge :variant="$deployment->status->badgeVariant()">
                                            {{ $deployment->status->label() }}
                                        </x-badge>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-8 text-center text-sm text-gray-400 dark:text-gray-500">
                                        {{ __('No equipment deployed for this mission.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
