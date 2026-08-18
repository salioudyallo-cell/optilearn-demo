@php
    use App\Enums\PlatformMode;
    use App\Facades\Platform;

    $current = Platform::mode();
    $capabilities = config('platform.capabilities', []);
    $activePreset = config('platform.presets.'.$current->value, []);
@endphp

<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Mode courant --}}
        <x-filament::section>
            <x-slot name="heading">Mode actuel</x-slot>
            <x-slot name="description">
                Le mode pilote automatiquement les fonctionnalités visibles dans toute l’application.
            </x-slot>

            <div class="flex items-start gap-4">
                <span @class([
                    'inline-flex items-center rounded-full px-3 py-1 text-sm font-semibold',
                    'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-300' => $current === PlatformMode::Commercial,
                    'bg-info-100 text-info-700 dark:bg-info-500/20 dark:text-info-300' => $current === PlatformMode::Enterprise,
                ])>
                    {{ $current->label() }}
                </span>
            </div>
            <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">{{ $current->description() }}</p>
        </x-filament::section>

        {{-- Capacités activées par le mode courant --}}
        <x-filament::section>
            <x-slot name="heading">Fonctionnalités actives dans ce mode</x-slot>

            <ul class="grid gap-2 sm:grid-cols-2">
                @foreach ($capabilities as $key => $label)
                    @php($on = in_array($key, $activePreset, true))
                    <li class="flex items-center gap-2 text-sm">
                        @if ($on)
                            <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-success-500" />
                            <span class="text-gray-700 dark:text-gray-200">{{ $label }}</span>
                        @else
                            <x-filament::icon icon="heroicon-m-minus-circle" class="h-5 w-5 text-gray-300 dark:text-gray-600" />
                            <span class="text-gray-400 dark:text-gray-500">{{ $label }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </x-filament::section>

        <p class="text-sm text-gray-500 dark:text-gray-400">
            Utilisez le bouton en haut de page pour changer de mode. Le changement prend effet immédiatement.
        </p>
    </div>
</x-filament-panels::page>
