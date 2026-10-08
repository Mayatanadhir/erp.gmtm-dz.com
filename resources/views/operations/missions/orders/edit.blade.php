<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3.5">
            <a href="{{ route('operations.missions.show', $mission->id) }}" class="p-2 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                    {{ __('Edit Travel Order (Ordre de Mission)') }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ $order->employee?->full_name }} — {{ $mission->reference }}
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm">
                <form action="{{ route('operations.missions.orders.update', [$mission->id, $order->id]) }}" method="POST" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <!-- Employee Details Display -->
                    <div class="p-4 rounded-lg bg-gray-50/70 dark:bg-gray-900/60 border border-gray-100 dark:border-gray-700/60 flex items-center justify-between">
                        <div>
                            <div class="font-bold text-base text-gray-900 dark:text-white">{{ $order->employee?->full_name }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $order->employee?->position }} — <span class="font-mono">{{ $order->employee?->registration_number }}</span></div>
                        </div>
                        <div class="text-end">
                            <span class="text-xs text-gray-500 dark:text-gray-400 block">{{ __('Order Reference') }}</span>
                            <span class="font-mono text-sm font-semibold text-brand-600 dark:text-brand-400">{{ $order->order_reference ?? __('Unassigned') }}</span>
                        </div>
                    </div>

                    <!-- Destination / Itinerary -->
                    <div>
                        <label for="destination" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            {{ __('Travel Itinerary (Destination)') }}
                        </label>
                        <input type="text" id="destination" name="destination" value="{{ old('destination', $order->destination) }}" class="w-full py-2 px-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-brand-500 focus:border-brand-500 shadow-sm" placeholder="{{ __('Alger - Site - Alger') }}">
                    </div>

                    <!-- Dates -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="started_at" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Effective Start Date') }}</label>
                            <input type="date" id="started_at" name="started_at" value="{{ old('started_at', $order->started_at?->format('Y-m-d')) }}" class="w-full py-2 px-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-brand-500 focus:border-brand-500 shadow-sm">
                        </div>
                        <div>
                            <label for="ended_at" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Effective End Date') }}</label>
                            <input type="date" id="ended_at" name="ended_at" value="{{ old('ended_at', $order->ended_at?->format('Y-m-d')) }}" class="w-full py-2 px-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-brand-500 focus:border-brand-500 shadow-sm">
                        </div>
                    </div>

                    <!-- Per-Diem Rate -->
                    <div>
                        <label for="daily_rate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Per-Diem Daily Rate (DZD)') }}</label>
                        <input type="number" step="0.01" id="daily_rate" name="daily_rate" value="{{ old('daily_rate', $order->daily_rate) }}" class="w-full py-2 px-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-brand-500 focus:border-brand-500 shadow-sm">
                    </div>

                    <!-- Vehicle Assignment -->
                    <div>
                        <label for="vehicle_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Designated Vehicle') }}</label>
                        <select id="vehicle_id" name="vehicle_id" class="w-full py-2 px-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-brand-500 focus:border-brand-500 shadow-sm">
                            <option value="">{{ __('No Vehicle Assigned') }}</option>
                            @foreach ($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}" @selected(old('vehicle_id', $order->vehicle_id) == $vehicle->id)>
                                    {{ $vehicle->full_name }} ({{ $vehicle->serial_number }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- All Vehicles Authorization -->
                    <div class="flex items-center gap-2 pt-2">
                        <input type="checkbox" id="all_vehicles" name="all_vehicles" value="1" @checked(old('all_vehicles', $order->all_vehicles)) class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-brand-600 focus:ring-brand-500">
                        <label for="all_vehicles" class="text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer">
                            {{ __('Authorize operation of all vehicles (Tous les véhicules)') }}
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <a href="{{ route('operations.missions.show', $mission->id) }}" class="btn-secondary">
                            {{ __('Cancel') }}
                        </a>
                        <x-primary-button type="submit" class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>{{ __('Save Travel Order') }}</span>
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
