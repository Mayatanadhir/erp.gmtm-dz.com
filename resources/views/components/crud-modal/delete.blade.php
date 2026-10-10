@props([
    'show'               => 'showDeleteModal',
    'actionUrl'          => '',
    'alpineAction'       => null,
    'title'              => null,
    'message'            => null,
    'itemName'           => null,
    'targetNameVariable' => null,
    'submitText'         => null,
    'method'             => 'DELETE',
])

@php
    $resolvedAction     = $alpineAction ?? $actionUrl;
    $resolvedItemName   = $targetNameVariable ?? $itemName;
    $resolvedTitle      = $title ?? __('Confirm Delete');
    $resolvedSubmitText = $submitText ?? __('Delete');

    // Determine if $resolvedAction is a raw Alpine JS expression/variable or needs string quotes
    $isJsVar = ! empty($alpineAction) || preg_match('/^[a-zA-Z_$][a-zA-Z0-9_$.]*$/', trim((string) $resolvedAction));
    $actionBinding = $isJsVar ? $resolvedAction : json_encode($resolvedAction);
    $headingId = 'delete-modal-heading-' . Str::random(6);
@endphp

{{--
    Unified Delete Confirmation Modal (x-crud-modal.delete)
    --------------------------------------------------------
    Usage:
        <x-crud-modal.delete
            show="showDeleteModal"
            action-url="deleteActionUrl"
            item-name="deleteName"
            :title="__('Delete Record')"
        />
--}}
<div
    x-cloak
    x-show="{{ $show }}"
    @keydown.escape.window="{{ $show }} = false"
    class="fixed inset-0 z-50 overflow-y-auto"
    role="dialog"
    aria-modal="true"
    aria-labelledby="{{ $headingId }}"
>
    {{-- Backdrop --}}
    <div
        x-show="{{ $show }}"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"
        @click="{{ $show }} = false"
    ></div>

    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <div
            x-show="{{ $show }}"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 text-start shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md border border-gray-100 dark:border-gray-700"
            @click.stop
        >
            <form method="POST" :action="{{ $actionBinding }}">
                @csrf
                <input type="hidden" name="_method" value="{{ $method }}">

                {{-- Hidden fields or custom warnings slot --}}
                {{ $slot }}

                <div class="p-6">
                    <div class="flex items-start gap-4">
                        {{-- Danger Icon --}}
                        <div class="w-11 h-11 rounded-2xl bg-rose-50 dark:bg-rose-500/10 border border-rose-100 dark:border-rose-500/20 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0 shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </div>

                        <div class="flex-1 min-w-0">
                            <h3 class="text-base font-bold text-gray-900 dark:text-white" id="{{ $headingId }}">
                                {{ $resolvedTitle }}
                            </h3>

                            @if($message !== null)
                                <p class="mt-1.5 text-sm text-gray-600 dark:text-gray-300">
                                    {{ $message }}
                                    @if($resolvedItemName !== null)
                                        <strong class="font-bold text-gray-900 dark:text-white font-mono break-all" x-text="{{ $resolvedItemName }}"></strong>
                                    @endif
                                </p>
                            @elseif($resolvedItemName !== null)
                                <div class="mt-1.5 space-y-1">
                                    <p class="text-sm text-gray-600 dark:text-gray-300">
                                        {{ __('Are you sure you want to delete') }}
                                        <strong class="font-bold text-gray-900 dark:text-white font-mono break-all" x-text="{{ $resolvedItemName }}"></strong>{{ app()->getLocale() === 'ar' ? '؟' : '?' }}
                                    </p>
                                    <p class="text-xs text-rose-600 dark:text-rose-400 font-medium flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                        </svg>
                                        <span>{{ __('This action cannot be undone.') }}</span>
                                    </p>
                                </div>
                            @else
                                <p class="mt-1.5 text-sm text-gray-600 dark:text-gray-300">
                                    {{ __('Are you sure you want to delete this record? This action cannot be undone.') }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Footer Buttons --}}
                <div class="px-6 py-4 bg-gray-50/80 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-end gap-3 rounded-b-2xl">
                    <x-secondary-button type="button" @click="{{ $show }} = false">
                        {{ __('Cancel') }}
                    </x-secondary-button>

                    <x-danger-button type="submit" class="flex items-center gap-1.5 shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        <span>{{ $resolvedSubmitText }}</span>
                    </x-danger-button>
                </div>
            </form>
        </div>
    </div>
</div>
