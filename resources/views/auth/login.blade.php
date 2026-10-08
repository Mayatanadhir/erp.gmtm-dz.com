<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if (session('error'))
        <div class="mb-4">
            <x-alert variant="danger" :dismissible="true">
                {{ session('error') }}
            </x-alert>
        </div>
    @endif

    @if ($errors->has('registration_closed'))
        <div class="mb-4">
            <x-alert variant="danger" :dismissible="true">
                {{ $errors->first('registration_closed') }}
            </x-alert>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-4"
          x-data="{ submitting: false }"
          x-on:submit="submitting = true"
          x-on:pageshow.window="submitting = false">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1.5 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-password-input id="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <label for="remember_me" class="inline-flex items-center cursor-pointer">
            <input id="remember_me" type="checkbox" class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-brand-600 shadow-sm focus:ring-brand-600 dark:focus:ring-offset-gray-800" name="remember">
            <span class="ms-2 text-sm text-gray-600 dark:text-gray-400 select-none">{{ __('Remember me') }}</span>
        </label>

        <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
            @if (Route::has('password.request'))
                <a class="text-sm text-gray-600 dark:text-gray-400 hover:text-brand-600 dark:hover:text-brand-400 underline underline-offset-4 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-600 dark:focus:ring-offset-gray-800 transition" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @else
                <span></span>
            @endif

            <x-primary-button class="px-6 py-2.5 disabled:opacity-60 disabled:cursor-not-allowed" x-bind:disabled="submitting">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>

    @if (Route::has('register') && is_registration_open())
        <div class="text-center text-sm text-gray-600 dark:text-gray-400 border-t border-gray-100 dark:border-gray-700/60 pt-4 mt-6">
            <span>{{ __("Don't have an account?") }}</span>
            <a href="{{ route('register') }}" class="underline font-semibold text-brand-700 dark:text-brand-400 hover:text-brand-600 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-600 dark:focus:ring-offset-gray-800 ms-1">
                {{ __('Create an account') }}
            </a>
        </div>
    @endif
</x-guest-layout>
