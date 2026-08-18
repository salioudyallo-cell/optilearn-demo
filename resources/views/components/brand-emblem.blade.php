@props(['class' => ''])

{{-- Embleme de marque : tuile + double vague. Couleurs tirees des variables de marque
     (recolorees automatiquement par instance). Pas de gradient : evite les id dupliques
     quand l'embleme est repete sur une page. --}}
<svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg" {{ $attributes->merge(['class' => $class]) }} aria-hidden="true">
    <rect width="64" height="64" rx="16" fill="var(--color-brand-blue-600)" />
    <path d="M13 39C21 25 43 25 51 39" stroke="var(--color-brand-yellow)" stroke-width="5" stroke-linecap="round" />
    <path d="M13 45C23 33 41 33 51 45" stroke="var(--color-brand-orange-500)" stroke-width="5" stroke-linecap="round" />
    <circle cx="32" cy="21" r="3.6" fill="#ffffff" />
</svg>
