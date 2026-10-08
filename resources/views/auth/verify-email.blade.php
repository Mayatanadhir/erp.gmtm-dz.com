<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        {{ __("Before getting started, please verify your email address by clicking on the link we just emailed to you. If you didn't receive the email, we will gladly send you another.") }}
    </div>

    @if (session('status') === 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-emerald-600 dark:text-emerald-400" role="status">
            {{ __('A new verification link has been sent to your email address.') }}
        </div>
    @endif

    <div class="mt-6 flex flex-wrap items-center justify-between gap-4">
        <form method="POST" action="{{ route('verification.send') }}"
              x-data="{ submitting: false }"
              x-on:submit="submitting = true"
              x-on:pageshow.window="submitting = false">
            @csrf

            <x-primary-button class="disabled:opacity-60 disabled:cursor-not-allowed" x-bind:disabled="submitting">
                {{ __('Resend Verification Email') }}
            </x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <x-secondary-button type="submit">
                {{ __('Log Out') }}
            </x-secondary-button>
        </form>
    </div>
</x-guest-layout>
