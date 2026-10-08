<x-guest-layout>
    <div class="mb-5 text-center">
        <div class="flex justify-center mb-2">
            <x-badge variant="danger" :dot="true" :dot-ping="true">
                {{ __('Database Unavailable') }}
            </x-badge>
        </div>
        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">
            {{ __('Database Connection Required') }}
        </h2>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            {{ __('The application could not automatically connect to or initialize the specified database.') }}
        </p>
    </div>

    <!-- Diagnostic Details Table -->
    <div class="mb-5 overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700/80 bg-gray-50/60 dark:bg-gray-900/40">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700/80 text-xs">
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700/80">
                <tr>
                    <td class="px-3.5 py-2 font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">
                        {{ __('Database Name') }}
                    </td>
                    <td class="px-3.5 py-2 text-end font-mono text-gray-800 dark:text-gray-200">
                        {{ $database ?? config('database.connections.'.config('database.default').'.database') }}
                    </td>
                </tr>
                <tr>
                    <td class="px-3.5 py-2 font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">
                        {{ __('Host / Server') }}
                    </td>
                    <td class="px-3.5 py-2 text-end font-mono text-gray-800 dark:text-gray-200">
                        {{ $host ?? config('database.connections.'.config('database.default').'.host', '127.0.0.1') }}:{{ $port ?? config('database.connections.'.config('database.default').'.port', '3306') }}
                    </td>
                </tr>
                <tr>
                    <td class="px-3.5 py-2 font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">
                        {{ __('Database Driver') }}
                    </td>
                    <td class="px-3.5 py-2 text-end font-mono uppercase text-gray-800 dark:text-gray-200">
                        {{ $connection ?? config('database.default') }}
                    </td>
                </tr>
                <tr>
                    <td class="px-3.5 py-2 font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">
                        {{ __('Connection Status') }}
                    </td>
                    <td class="px-3.5 py-2 text-end">
                        <x-badge variant="danger">
                            <svg class="w-3 h-3 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            {{ __('Not Connected') }}
                        </x-badge>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Troubleshooting Guidance -->
    <div class="mb-5 p-3.5 rounded-lg bg-amber-500/10 border border-amber-500/20 text-xs text-amber-800 dark:text-amber-300">
        <div class="font-semibold mb-1 flex items-center gap-1.5">
            <svg class="w-4 h-4 shrink-0 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ __('Troubleshooting Suggestions') }}</span>
        </div>
        <ul class="list-disc list-inside space-y-1 text-gray-600 dark:text-gray-300 ps-1">
            <li>{{ __('Ensure your MySQL server or Herd database service is started and running.') }}</li>
            <li>{{ __('Verify your database credentials in the .env configuration file.') }}</li>
            <li>{{ __('Grant CREATE DATABASE privileges to your database user or create the database manually.') }}</li>
        </ul>
    </div>

    <!-- Actions -->
    <div class="flex flex-col gap-3">
        <x-primary-button type="button" onclick="window.location.reload()" class="w-full justify-center py-2.5 text-sm gap-2 shadow-md">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <span>{{ __('Retry Connection') }}</span>
        </x-primary-button>
    </div>
</x-guest-layout>
