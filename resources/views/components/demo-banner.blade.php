@if (config('lms.demo.enabled'))
    <div class="bg-brand-navy text-white text-center text-xs sm:text-sm py-2 px-4">
        <span class="font-semibold">{{ __('Environnement de démonstration') }}</span>
        <span class="text-white/70 hidden sm:inline">— {{ __('données fictives, réinitialisées régulièrement. Contactez OptiLeads pour votre propre plateforme.') }}</span>
    </div>
@endif
