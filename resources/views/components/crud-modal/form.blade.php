@props([
    'show'          => 'showFormModal',
    'actionUrl'     => '',
    'alpineAction'  => null,  // Alpine.js variable name for dynamic action URLs (edit forms)
    'method'        => 'POST',
    'title'         => null,
    'description'   => null,
    'iconColor'     => 'orange',
    'submitText'    => null,
    'submitIcon'    => 'check',
    'maxWidth'      => 'lg',
    'enctype'       => null,
])

@php
    $resolvedTitle      = $title      ?? __('Form');
    $resolvedSubmitText = $submitText ?? ($method === 'POST' ? __('Create') : __('Save Changes'));
    $resolvedDescription = $description;

    // Icon background + text color tokens by semantic color
    $iconTheme = match($iconColor) {
        'amber', 'yellow'  => 'bg-amber-500/10  dark:bg-amber-500/20  text-amber-600  dark:text-amber-400',
        'indigo', 'blue'   => 'bg-indigo-500/10 dark:bg-indigo-500/20 text-brand-700 dark:text-indigo-400',
        'emerald', 'green' => 'bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400',
        'rose', 'red'      => 'bg-rose-500/10   dark:bg-rose-500/20   text-rose-600   dark:text-rose-400',
        default            => 'bg-brand-600/10 dark:bg-brand-600/20 text-brand-700 dark:text-brand-400',
    };

    $headingId = 'form-modal-heading-' . Str::random(6);

    $maxWidthClass = match($maxWidth) {
        'md'  => 'sm:max-w-md',
        'xl'  => 'sm:max-w-xl',
        '2xl' => 'sm:max-w-2xl',
        '3xl' => 'sm:max-w-3xl',
        default => 'sm:max-w-lg',
    };

    $isPost = strtoupper($method) === 'POST';
@endphp

{{--
    Unified Create / Edit Form Modal (x-crud-modal.form)
    ------------------------------------------------------
    Usage (Create):
        <x-crud-modal.form
            show="showCreateUserModal"
            :action-url="route('system-tables.users.store')"
            method="POST"
            :title="__('Create New User')"
            :description="__('Add a new user account.')"
            icon-color="green"
            :submit-text="__('Create User')"
        >
            <x-text-input name="name" ... />
        </x-crud-modal.form>

    Usage (Edit):
        <x-crud-modal.form
            show="showEditUserModal"
            :action-url="editUserActionUrl"
            method="PUT"
            :title="__('Edit User')"
            icon-color="amber"
            :submit-text="__('Save Changes')"
        >
            <x-text-input name="name" x-model="editUserName" ... />
        </x-crud-modal.form>
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
            class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 text-start shadow-xl transition-all sm:my-8 sm:w-full {{ $maxWidthClass }} border border-gray-100 dark:border-gray-700"
            @click.stop
        >
            @if($alpineAction)
            <form method="POST" :action="{{ $alpineAction }}" @if($enctype) enctype="{{ $enctype }}" @endif>
            @else
            <form method="POST" action="{{ $actionUrl }}" @if($enctype) enctype="{{ $enctype }}" @endif>
            @endif
                @csrf
                @if(! $isPost)
                    @method($method)
                @endif

                {{-- Extra hidden inputs injected via the $hidden slot --}}
                @isset($hidden)
                    {{ $hidden }}
                @endisset

                {{-- Header --}}
                <div class="px-6 pt-6 pb-4">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl {{ $iconTheme }} flex items-center justify-center shrink-0">
                                @if(! $isPost)
                                    {{-- Edit icon --}}
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                @else
                                    {{-- Create icon --}}
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                @endif
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900 dark:text-white" id="{{ $headingId }}">
                                    {{ $resolvedTitle }}
                                </h3>
                                @if($resolvedDescription)
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $resolvedDescription }}</p>
                                @endif
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="{{ $show }} = false"
                            class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 transition-colors"
                        >
                            <span class="sr-only">{{ __('Close') }}</span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{-- Form fields --}}
                    <div class="mt-4 space-y-4">
                        {{ $slot }}
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-end gap-3 rounded-b-2xl">
                    <x-secondary-button type="button" @click="{{ $show }} = false">
                        {{ __('Cancel') }}
                    </x-secondary-button>

                    <x-primary-button type="submit">
                        @if(! $isPost)
                            <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        @else
                            <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        @endif
                        {{ $resolvedSubmitText }}
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</div>
