<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    @if (session('status') === 'profile-updated')
        <div class="mt-4">
            <x-alert variant="success" :dismissible="true">
                {{ __('Profile Information updated successfully.') }}
            </x-alert>
        </div>        
    @endif

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <!-- Profile Photo Suite -->
        <div x-data="{
            photoPreview: @js($user->profile_photo_url),
            hasPhoto: @js(!blank($user->profile_photo_path)),
            removePhoto: false,
            fileName: '',
            previewImage(e) {
                const file = e.target.files?.[0];
                if (!file) return;
                this.fileName = file.name;
                this.removePhoto = false;
                this.photoPreview = URL.createObjectURL(file);
                this.hasPhoto = true;
            },
            clearPhoto() {
                this.photoPreview = null;
                this.hasPhoto = false;
                this.removePhoto = true;
                this.fileName = '';
                if (this.$refs.photoInput) {
                    this.$refs.photoInput.value = '';
                }
            }
        }">
            <x-input-label :value="__('Profile Photo (Optional)')" />

            <div class="mt-2 flex items-center gap-5">
                <!-- Circular Avatar Preview -->
                <div class="relative shrink-0 w-20 h-20 rounded-full overflow-hidden ring-4 ring-brand-100 dark:ring-brand-500/30 shadow-md bg-gradient-to-tr from-brand-600 to-brand-800 flex items-center justify-center">
                    <img x-show="photoPreview" :src="photoPreview" alt="{{ $user->name }}" class="w-full h-full object-cover" x-on:error="photoPreview = null">
                    <span x-show="!photoPreview" class="text-white font-bold text-2xl select-none">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </span>
                </div>

                <!-- Actions & Info -->
                <div class="space-y-2">
                    <input type="file" x-ref="photoInput" name="photo" @change="previewImage($event)" accept="image/png,image/jpeg,image/jpg,image/webp,image/gif" class="hidden">
                    <input type="hidden" name="remove_photo" :value="removePhoto ? '1' : '0'">

                    <div class="flex items-center gap-3 flex-wrap">
                        <x-secondary-button type="button" @click="$refs.photoInput.click()" class="text-xs !py-1.5 !px-3">
                            <svg class="w-3.5 h-3.5 me-1.5 text-gray-500 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" />
                            </svg>
                            {{ __('Choose Photo') }}
                        </x-secondary-button>

                        <x-danger-button type="button" x-show="hasPhoto" @click="clearPhoto" class="text-xs !py-1.5 !px-3">
                            <svg class="w-3.5 h-3.5 me-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                            {{ __('Remove Photo') }}
                        </x-danger-button>
                    </div>

                    <p class="text-[11px] text-gray-500 dark:text-gray-400" x-show="!fileName">
                        {{ __('PNG, JPG, WEBP up to 5MB') }}
                    </p>
                    <p class="text-[11px] text-emerald-600 dark:text-emerald-400 font-medium" x-show="fileName" x-text="fileName"></p>
                </div>
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('photo')" />
        </div>

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800 dark:text-gray-200">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-600 dark:focus:ring-offset-gray-800">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-emerald-600 dark:text-emerald-400">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>
        </div>
    </form>
</section>
