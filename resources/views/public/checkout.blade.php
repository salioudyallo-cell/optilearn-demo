<x-layouts.public :title="__('Votre commande').' — '.config('brand.name')">
    <section class="mx-auto max-w-2xl px-4 sm:px-6 py-14">
        <div class="bg-white rounded-3xl ring-1 ring-ink-100 shadow-card p-8">
            <p class="text-sm font-semibold text-brand-orange-600 uppercase tracking-widest">{{ __('Commande') }} {{ $order->provider_ref }}</p>
            <h1 class="mt-2 text-2xl font-bold text-brand-charcoal">{{ $order->course->title }}</h1>

            <div class="mt-6 flex items-center justify-between border-t border-ink-100 pt-6">
                <span class="text-ink-500">{{ __('Montant') }}</span>
                <span class="text-2xl font-bold text-brand-charcoal">{{ \App\Support\Money::fcfa($order->amount_fcfa) }}</span>
            </div>

            @if ($order->isPaid())
                <div class="mt-6 rounded-2xl bg-green-50 text-green-800 p-5">
                    <p class="font-semibold">{{ __('Paiement confirmé — accès activé.') }}</p>
                </div>
                <x-ui.button :href="route('dashboard')" variant="primary" class="w-full mt-6">{{ __('Accéder à ma formation') }}</x-ui.button>
            @else
                <div class="mt-6 rounded-2xl bg-brand-blue-50 text-brand-blue-800 p-5 text-sm leading-relaxed">
                    <p class="font-semibold mb-1">{{ __('En attente de paiement') }}</p>
                    <p>{{ $instructions }}</p>
                </div>
                <x-ui.button :href="route('catalog.index')" variant="secondary" class="w-full mt-6">{{ __('Retour au catalogue') }}</x-ui.button>
            @endif
        </div>
    </section>
</x-layouts.public>
