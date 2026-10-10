@props(['label', 'active' => false])

<div class="relative" x-data="{ dropOpen: false }" @click.outside="dropOpen = false" @keydown.escape.window="dropOpen = false">

    {{-- Trigger Button --}}
    <button
        type="button"
        @click="dropOpen = !dropOpen"
        :aria-expanded="dropOpen.toString()"
        class="inline-flex items-center gap-1 px-1 pt-1 border-b-2 text-sm font-medium leading-5 focus:outline-none transition duration-150 ease-in-out
               {{ $active
                    ? 'border-brand-500 dark:border-brand-400 text-gray-900 dark:text-gray-100'
                    : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600' }}"
    >
        {{ $label }}
        {{-- Chevron --}}
        <svg
            class="w-3.5 h-3.5 mt-0.5 transition-transform duration-200"
            :class="{ 'rotate-180': dropOpen }"
            fill="none" stroke="currentColor" viewBox="0 0 24 24"
        >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    {{-- Dropdown Panel --}}
    <div
        x-show="dropOpen"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
        class="absolute top-full start-0 z-50 mt-1 w-56 rounded-xl bg-white dark:bg-gray-800 shadow-lg ring-1 ring-gray-900/5 dark:ring-gray-700 py-1.5"
        style="display: none;"
    >
        {{ $slot }}
    </div>
</div>
