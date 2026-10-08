<x-guest-layout>
    <form method="POST" action="{{ route('password.store') }}" class="space-y-4"
          x-data="{ submitting: false }"
          x-on:submit="submitting = true"
          x-on:pageshow.window="submitting = false">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1.5 w-full" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-password-input id="password" name="password" required autocomplete="new-password" aria-describedby="password-hint" />
            <p id="password-hint" class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                {{ __('Use at least :min characters.', ['min' => 8]) }}
            </p>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-password-input id="password_confirmation" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end pt-2">
            <x-primary-button class="px-6 py-2.5 disabled:opacity-60 disabled:cursor-not-allowed" x-bind:disabled="submitting">
                {{ __('Reset Password') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
