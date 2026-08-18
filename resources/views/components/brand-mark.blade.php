@props(['class' => ''])

@php $logo = config('brand.logo_image'); @endphp

{{-- Marque de l'instance. Deux modes (cf. config/brand.php) :
     - une image de logo est fournie -> on l'affiche ;
     - sinon -> embleme colore + nom en toutes lettres (aucun fichier a produire). --}}
<span {{ $attributes->merge(['class' => 'inline-flex items-center '.$class]) }}>
    @if ($logo)
        <img src="{{ asset($logo) }}" alt="{{ config('brand.name') }}" class="h-8 w-auto">
    @else
        <x-brand-emblem class="h-9 w-9 shrink-0" />
        <span class="ml-2.5 text-lg font-bold tracking-tight text-brand-navy">{{ config('brand.name') }}</span>
    @endif
</span>
