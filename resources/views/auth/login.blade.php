<x-guest-layout>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-brand-charcoal">{{ __('Content de vous revoir') }}</h1>
        <p class="mt-1 text-sm text-ink-500">{{ __('Connectez-vous pour accéder à vos formations.') }}</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="vous@exemple.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" placeholder="••••••••" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-ink-300 text-brand-blue-600 shadow-sm focus:ring-brand-blue-500" name="remember">
                <span class="ms-2 text-sm text-ink-500">{{ __('Remember me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm font-medium text-brand-blue-700 hover:text-brand-blue-800 hover:underline" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif
        </div>

        <x-primary-button class="w-full justify-center py-3">
            {{ __('Log in') }}
        </x-primary-button>

        <p class="text-center text-sm text-ink-500 pt-2">
            {{ __('Pas encore de compte ?') }}
            <a href="{{ route('register') }}" class="font-semibold text-brand-blue-700 hover:underline">{{ __('Créer un compte') }}</a>
        </p>
    </form>
</x-guest-layout>
