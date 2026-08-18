<x-guest-layout>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-brand-charcoal">{{ __('Créez votre compte') }}</h1>
        <p class="mt-1 text-sm text-ink-500">{{ __('Gratuit. Explorez le catalogue et demandez votre accès.') }}</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" placeholder="Awa Diop" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autocomplete="username" placeholder="vous@exemple.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="phone" :value="__('Phone')" />
                <x-text-input id="phone" type="tel" name="phone" :value="old('phone')" required autocomplete="tel" placeholder="+221 77 000 00 00" />
                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="country" :value="__('Country')" />
                <select id="country" name="country" required
                    class="w-full border-ink-200 text-ink-800 focus:border-brand-blue-500 focus:ring-2 focus:ring-brand-blue-500/30 rounded-xl shadow-soft transition">
                    <option value="" disabled {{ old('country') ? '' : 'selected' }}>{{ __('Select your country') }}</option>
                    <option value="SN" @selected(old('country') === 'SN')>{{ __('Senegal') }}</option>
                    <option value="CI" @selected(old('country') === 'CI')>{{ __('Ivory Coast') }}</option>
                    <option value="ML" @selected(old('country') === 'ML')>{{ __('Mali') }}</option>
                </select>
                <x-input-error :messages="$errors->get('country')" class="mt-2" />
            </div>
        </div>

        <div>
            <x-input-label for="company" :value="__('Company (optional)')" />
            <x-text-input id="company" type="text" name="company" :value="old('company')" autocomplete="organization" placeholder="Sonatel" />
            <x-input-error :messages="$errors->get('company')" class="mt-2" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="password" :value="__('Password')" />
                <x-text-input id="password" type="password" name="password" required autocomplete="new-password" placeholder="••••••••" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                <x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="••••••••" />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>
        </div>

        <x-primary-button class="w-full justify-center py-3 mt-2">
            {{ __('Register') }}
        </x-primary-button>

        <p class="text-center text-sm text-ink-500 pt-2">
            {{ __('Already registered?') }}
            <a href="{{ route('login') }}" class="font-semibold text-brand-blue-700 hover:underline">{{ __('Se connecter') }}</a>
        </p>
    </form>
</x-guest-layout>
