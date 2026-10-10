@props(['autocomplete' => 'current-password'])

{{--
    حقل كلمة مرور مع زر إظهار/إخفاء.
    يتطلب Alpine (يأتي مع Breeze في resources/js/app.js).
    أي خصائص إضافية (id, name, required, aria-describedby ...) تُمرَّر إلى الحقل.
--}}
<div class="relative mt-1.5" x-data="{ show: false }">
    <x-text-input
        {{ $attributes->merge(['class' => 'block w-full pe-10']) }}
        type="password"
        x-bind:type="show ? 'text' : 'password'"
        :autocomplete="$autocomplete" />

    <button type="button"
            x-on:click="show = !show"
            x-bind:aria-pressed="show.toString()"
            x-bind:aria-label="show ? @js(__('Hide password')) : @js(__('Show password'))"
            aria-label="{{ __('Show password') }}"
            @if($attributes->has('id')) aria-controls="{{ $attributes->get('id') }}" @endif
            class="absolute inset-y-0 end-0 flex items-center px-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 focus:outline-none focus-visible:text-brand-600 transition-colors">
        {{-- eye --}}
        <svg x-bind:class="{ 'hidden': show }" class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
        </svg>
        {{-- eye slash --}}
        <svg x-bind:class="{ 'hidden': !show }" class="h-5 w-5 hidden" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.956 9.956 0 012.223-3.592M6.53 6.533A9.956 9.956 0 0112 5c4.477 0 8.268 2.943 9.542 7a9.97 9.97 0 01-4.293 5.292M15 12a3 3 0 00-3-3m0 0a3 3 0 00-3 3M3 3l18 18" />
        </svg>
    </button>
</div>
