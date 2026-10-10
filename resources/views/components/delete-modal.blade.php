<div
    x-data="{
        open: false,
        actionUrl: '',
        itemName: '',
        title: @js(__('Confirm Delete')),
        message: '',
        method: 'DELETE',
        submitText: @js(__('Delete')),

        init() {
            window.addEventListener('open-delete-modal', (e) => {
                const data = e.detail || {};
                this.actionUrl  = data.action || data.actionUrl || '';
                this.itemName   = data.name   || data.itemName  || '';
                this.title      = data.title  || @js(__('Confirm Delete'));
                this.message    = data.message || '';
                this.method     = data.method || 'DELETE';
                this.submitText = data.submitText || @js(__('Delete'));
                this.open       = true;
            });
        }
    }"
    @keydown.escape.window="open = false"
    x-effect="document.body.classList.toggle('overflow-hidden', open)"
    x-cloak
    x-show="open"
    class="fixed inset-0 z-50 overflow-y-auto"
    role="dialog"
    aria-modal="true"
    aria-labelledby="global-delete-modal-title"
>
    {{-- Backdrop --}}
    <div
        x-show="open"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"
        @click="open = false"
    ></div>

    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <div
            x-show="open"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 text-start shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md border border-gray-100 dark:border-gray-700"
            @click.stop
        >
            <form method="POST" :action="actionUrl">
                @csrf
                <input type="hidden" name="_method" :value="method">

                <div class="p-6">
                    <div class="flex items-start gap-4">
                        {{-- Danger Icon --}}
                        <div class="w-11 h-11 rounded-2xl bg-rose-50 dark:bg-rose-500/10 border border-rose-100 dark:border-rose-500/20 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0 shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </div>

                        <div class="flex-1 min-w-0">
                            <h3 class="text-base font-bold text-gray-900 dark:text-white" id="global-delete-modal-title" x-text="title || @js(__('Confirm Delete'))">
                                {{ __('Confirm Delete') }}
                            </h3>

                            {{-- Message logic: Custom -> Named Item -> Generic --}}
                            <template x-if="message">
                                <p class="mt-1.5 text-sm text-gray-600 dark:text-gray-300" x-text="message"></p>
                            </template>

                            <template x-if="!message && itemName">
                                <div class="mt-1.5 space-y-1">
                                    <p class="text-sm text-gray-600 dark:text-gray-300">
                                        {{ __('Are you sure you want to delete') }}
                                        <strong class="font-bold text-gray-900 dark:text-white font-mono break-all" x-text="itemName"></strong>{{ app()->getLocale() === 'ar' ? '؟' : '?' }}
                                    </p>
                                    <p class="text-xs text-rose-600 dark:text-rose-400 font-medium flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                        </svg>
                                        <span>{{ __('This action cannot be undone.') }}</span>
                                    </p>
                                </div>
                            </template>

                            <template x-if="!message && !itemName">
                                <p class="mt-1.5 text-sm text-gray-600 dark:text-gray-300">
                                    {{ __('Are you sure you want to delete this record? This action cannot be undone.') }}
                                </p>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Footer Buttons --}}
                <div class="px-6 py-4 bg-gray-50/80 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-end gap-3 rounded-b-2xl">
                    <x-secondary-button type="button" @click="open = false">
                        {{ __('Cancel') }}
                    </x-secondary-button>

                    <x-danger-button type="submit" x-bind:disabled="!actionUrl" class="flex items-center gap-1.5 shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        <span x-text="submitText || @js(__('Delete'))"></span>
                    </x-danger-button>
                </div>
            </form>
        </div>
    </div>
</div>
