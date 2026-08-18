@props(['class' => ''])

{{-- Conserve pour compatibilite (utilise comme embleme/placeholder). Delegue a l'embleme
     de marque, recolore par instance. --}}
<x-brand-emblem :class="$class" {{ $attributes }} />
