<div class="relative"
     x-data="{
         open: false,
         theme: localStorage.getItem('theme') || 'system',
         init() {
             this.applyTheme(this.theme);
             window.addEventListener('theme-changed', (e) => {
                 this.theme = e.detail;
             });
             const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
             mediaQuery.addEventListener('change', (e) => {
                 if (this.theme === 'system') {
                     document.documentElement.classList.toggle('dark', e.matches);
                 }
             });
         },
         setTheme(newTheme) {
             if (newTheme === 'system') {
                 localStorage.removeItem('theme');
             } else {
                 localStorage.setItem('theme', newTheme);
             }
             this.theme = newTheme;
             this.applyTheme(newTheme);
             window.dispatchEvent(new CustomEvent('theme-changed', { detail: newTheme }));
             this.open = false;
         },
         applyTheme(t) {
             const isDark = t === 'dark' || (t === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
             document.documentElement.classList.toggle('dark', isDark);
         }
     }"
     @click.outside="open = false"
     @close.stop="open = false">

    {{-- Trigger: ghost icon button --}}
    <button @click="open = ! open"
            type="button"
            :title="theme === 'light' ? {{ Js::from(__('Light')) }} : (theme === 'dark' ? {{ Js::from(__('Dark')) }} : {{ Js::from(__('System')) }})"
            class="relative flex items-center justify-center w-9 h-9 rounded-full text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-700 dark:hover:text-gray-200 focus:outline-none focus:ring-2 focus:ring-brand-600 dark:focus:ring-offset-gray-800 transition-all duration-150">

        {{-- Sun — Light --}}
        <svg x-show="theme === 'light'" x-cloak class="h-[18px] w-[18px] text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
        </svg>

        {{-- Moon — Dark --}}
        <svg x-show="theme === 'dark'" x-cloak class="h-[18px] w-[18px] text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
        </svg>

        {{-- Monitor — System --}}
        <svg x-show="theme === 'system'" class="h-[18px] w-[18px]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0H3" />
        </svg>
    </button>

    {{-- Dropdown --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 translate-y-1 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-1 scale-95"
         class="absolute end-0 z-50 mt-2 w-40 ltr:origin-top-right rtl:origin-top-left rounded-xl shadow-lg bg-white dark:bg-gray-800 ring-1 ring-gray-900/5 dark:ring-white/10 overflow-hidden"
         @click="open = false"
         x-cloak>

        <div class="p-1 space-y-0.5">
            {{-- Light --}}
            <button @click="setTheme('light')"
                    type="button"
                    class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-700 dark:text-gray-200 hover:bg-brand-50 dark:hover:bg-gray-700 transition-colors duration-100"
                    :class="{ 'bg-brand-50 dark:bg-gray-700/70 text-brand-700 dark:text-brand-400 font-semibold': theme === 'light' }">
                <svg class="h-4 w-4 text-amber-500 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                </svg>
                <span class="flex-1 text-start">{{ __('Light') }}</span>
                <template x-if="theme === 'light'">
                    <svg class="h-3.5 w-3.5 text-brand-600 dark:text-brand-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                </template>
            </button>

            {{-- Dark --}}
            <button @click="setTheme('dark')"
                    type="button"
                    class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-700 dark:text-gray-200 hover:bg-brand-50 dark:hover:bg-gray-700 transition-colors duration-100"
                    :class="{ 'bg-brand-50 dark:bg-gray-700/70 text-brand-700 dark:text-brand-400 font-semibold': theme === 'dark' }">
                <svg class="h-4 w-4 text-indigo-400 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
                </svg>
                <span class="flex-1 text-start">{{ __('Dark') }}</span>
                <template x-if="theme === 'dark'">
                    <svg class="h-3.5 w-3.5 text-brand-600 dark:text-brand-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                </template>
            </button>

            {{-- System --}}
            <button @click="setTheme('system')"
                    type="button"
                    class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-700 dark:text-gray-200 hover:bg-brand-50 dark:hover:bg-gray-700 transition-colors duration-100"
                    :class="{ 'bg-brand-50 dark:bg-gray-700/70 text-brand-700 dark:text-brand-400 font-semibold': theme === 'system' }">
                <svg class="h-4 w-4 text-gray-400 dark:text-gray-400 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0H3" />
                </svg>
                <span class="flex-1 text-start">{{ __('System') }}</span>
                <template x-if="theme === 'system'">
                    <svg class="h-3.5 w-3.5 text-brand-600 dark:text-brand-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                </template>
            </button>
        </div>
    </div>
</div>
