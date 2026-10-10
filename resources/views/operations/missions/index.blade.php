<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <x-tool-icon name="missions" class="w-11 h-11 sm:w-12 sm:h-12 shrink-0" />
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                        {{ __('Mission Management') }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('Plan and supervise field missions, team assignments, and operational execution') }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <x-badge variant="info" size="md">
                    <span class="font-mono"><bdi>{{ $missions->total() }}</bdi></span> {{ __('Missions') }}
                </x-badge>
                @can('create missions')
                    <x-primary-button href="{{ route('operations.missions.create') }}" class="gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>{{ __('Create Mission') }}</span>
                    </x-primary-button>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row gap-6 items-start">
                <!-- Sidebar Navigation -->
                <aside class="w-full lg:w-64 shrink-0">
                    <x-operations-tabs active="missions" />
                </aside>

                <!-- Main Content -->
                <main class="flex-1 w-full min-w-0 space-y-6">
                    @if (session('success'))
                        <x-alert variant="success" class="mb-6">
                            {{ session('success') }}
                        </x-alert>
                    @endif

                    @if (session('error'))
                        <x-alert variant="danger" class="mb-6">
                            {{ session('error') }}
                        </x-alert>
                    @endif

                    @if (session('warning'))
                        <x-alert variant="warning" class="mb-6">
                            {{ session('warning') }}
                        </x-alert>
                    @endif

                    <!-- Filter Bar -->
                    <x-global-filter
                        :action="route('operations.missions')"
                        :search-placeholder="__('Ref, site, engineer...')"
                        :search-value="request('search')"
                    >
                        {{-- Site Filter --}}
                        <x-global-filter.select
                            name="site_id"
                            :placeholder="__('All Sites')"
                            :value="request('site_id')"
                        >
                            @foreach ($sites as $site)
                                <option value="{{ $site->id }}" @selected(request('site_id') == $site->id)>
                                    {{ $site->short_name ?? $site->full_name }}
                                </option>
                            @endforeach
                        </x-global-filter.select>

                        {{-- Status Filter --}}
                        <x-global-filter.select
                            name="status"
                            :placeholder="__('All Statuses')"
                            :value="request('status')"
                        >
                            @foreach (\App\Enums\MissionStatus::cases() as $case)
                                <option value="{{ $case->value }}" @selected(request('status') === $case->value)>
                                    {{ $case->label() }}
                                </option>
                            @endforeach
                        </x-global-filter.select>
                    </x-global-filter>

                    <!-- Missions Table -->
                    <x-table>
                        <x-slot:toolbar>
                            <div class="flex items-center justify-between w-full">
                                <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2.5">
                                    <x-tool-icon name="missions" class="w-5 h-5 shrink-0" />
                                    <span>{{ __('Missions Catalog') }}</span>
                                    <span class="ms-1.5 px-2 py-0.5 text-xs font-semibold rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/70 font-mono">
                                        <bdi>{{ $missions->total() }}</bdi>
                                    </span>
                                </h3>
                            </div>
                        </x-slot:toolbar>

                        <x-slot:header>
                            <x-table.th>{{ __('Reference') }}</x-table.th>
                            <x-table.th>{{ __('Site') }}</x-table.th>
                            <x-table.th>{{ __('Timeline') }}</x-table.th>
                            <x-table.th>{{ __('Team Leader & Staff') }}</x-table.th>
                            <x-table.th>{{ __('Status') }}</x-table.th>
                            <x-table.th class="text-end">{{ __('Actions') }}</x-table.th>
                        </x-slot:header>

                        @forelse ($missions as $mission)
                            <x-table.tr>
                                <!-- Reference -->
                                <x-table.td class="font-semibold">
                                    <a href="{{ route('operations.missions.show', $mission->id) }}" class="text-brand-600 dark:text-brand-400 hover:underline flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-gray-400 dark:text-gray-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <span class="font-mono"><bdi>{{ $mission->reference }}</bdi></span>
                                    </a>
                                </x-table.td>

                                <!-- Site -->
                                <x-table.td>
                                    <div class="flex flex-col">
                                        <span class="font-medium text-gray-900 dark:text-white">
                                            {{ $mission->site?->short_name ?? $mission->site?->full_name ?? '—' }}
                                        </span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $mission->site?->location ?? '' }}
                                        </span>
                                    </div>
                                </x-table.td>

                                <!-- Timeline -->
                                <x-table.td>
                                    <div class="flex flex-col text-xs text-gray-600 dark:text-gray-300">
                                        <span class="font-mono"><bdi>{{ $mission->start_date?->format('d/m/Y') ?? '—' }}</bdi> &rarr; <bdi>{{ $mission->end_date?->format('d/m/Y') ?? '—' }}</bdi></span>
                                        <span class="text-gray-500 dark:text-gray-400 mt-0.5">
                                            <span class="font-mono"><bdi>{{ $mission->total_days }}</bdi></span> {{ __('days') }} (<span class="font-mono"><bdi>{{ $mission->operational_days }}</bdi></span> {{ __('op.') }})
                                        </span>
                                    </div>
                                </x-table.td>

                                <!-- Team -->
                                <x-table.td>
                                    <div class="flex items-center gap-2">
                                        @if ($mission->teamLeader?->profile_photo_url)
                                            <img src="{{ $mission->teamLeader->profile_photo_url }}" alt="{{ $mission->teamLeader->full_name }}" class="w-7 h-7 rounded-full object-cover border border-gray-200 dark:border-gray-700">
                                        @else
                                            <div class="w-7 h-7 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/70 flex items-center justify-center font-bold text-xs">
                                                {{ $mission->teamLeader?->initials ?: mb_substr($mission->teamLeader?->full_name ?? '?', 0, 1) }}
                                            </div>
                                        @endif
                                        <div class="flex flex-col">
                                            <span class="font-medium text-xs text-gray-900 dark:text-white">
                                                {{ $mission->teamLeader?->full_name ?? __('No Leader Assigned') }}
                                            </span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                <span class="font-mono"><bdi>{{ $mission->employees->count() }}</bdi></span> {{ __('members') }}
                                            </span>
                                        </div>
                                    </div>
                                </x-table.td>

                                <!-- Status -->
                                <x-table.td>
                                    <x-badge :variant="$mission->status->badgeVariant()" :dot="true">
                                        {{ $mission->status->label() }}
                                    </x-badge>
                                </x-table.td>

                                <!-- Actions -->
                                <x-table.td class="text-end">
                                    <x-table.actions>
                                        <x-table.action-view
                                            href="{{ route('operations.missions.show', $mission->id) }}"
                                            :title="__('View Details')"
                                        />

                                        @can('edit missions')
                                            @if ($mission->status->isModifiable())
                                                <x-table.action-edit
                                                    href="{{ route('operations.missions.edit', $mission->id) }}"
                                                    :title="__('Edit')"
                                                />
                                            @endif
                                        @endcan

                                        <x-table.action-stats
                                            href="{{ route('operations.missions.statistics', $mission->id) }}"
                                            :title="__('Unit Economics')"
                                        />

                                        @can('delete missions')
                                            @if ($mission->status->isModifiable())
                                                <x-table.action-delete
                                                    :action-url="route('operations.missions.destroy', $mission->id)"
                                                    :item-name="$mission->reference"
                                                    :title="__('Delete Mission')"
                                                />
                                            @endif
                                        @endcan
                                    </x-table.actions>
                                </x-table.td>
                            </x-table.tr>
                        @empty
                            <x-table.empty :colspan="6" :message="__('No missions found matching the selected criteria.')" />
                        @endforelse
                    </x-table>

                    <!-- Pagination -->
                    @if ($missions->hasPages())
                        <div class="mt-4">
                            {{ $missions->links() }}
                        </div>
                    @endif
                </main>
            </div>
        </div>
    </div>
</x-app-layout>
