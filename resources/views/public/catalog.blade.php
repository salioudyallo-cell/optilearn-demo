<x-layouts.public :title="__('Catalogue des formations').' — '.config('brand.name')"
    :metaDescription="__('Découvrez nos formations : marketing digital, SEO, publicité en ligne, IA, génération de leads.')">

    {{-- En-tête --}}
    <section class="bg-hero-mesh border-b border-ink-100">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 pt-16 pb-14">
            <p class="text-sm font-semibold text-brand-orange-600 uppercase tracking-widest">{{ __('Catalogue') }}</p>
            <h1 class="mt-3 text-4xl sm:text-5xl font-bold text-brand-charcoal max-w-3xl leading-[1.1]">
                {{ __('Toutes nos formations au digital') }}
            </h1>
            <p class="mt-5 text-lg text-ink-500 max-w-2xl">
                {{ __('Choisissez la formation adaptée à votre niveau. La transaction se fait avec :brand : demandez votre accès directement depuis la fiche.', ['brand' => config('brand.short_name')]) }}
            </p>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 sm:px-6 py-12">
        @if ($courses->isEmpty())
            <div class="bg-white rounded-3xl ring-1 ring-ink-100 shadow-soft p-14 text-center max-w-lg mx-auto">
                <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-blue-50">
                    <x-application-logo class="h-8 w-8" />
                </span>
                <h2 class="mt-5 text-xl font-semibold text-brand-charcoal">{{ __('Bientôt disponible') }}</h2>
                <p class="mt-2 text-ink-500">{{ __('Aucune formation n’est publiée pour le moment. Revenez très vite : le catalogue s’enrichit.') }}</p>
                <x-ui.button :href="route('register')" variant="secondary" class="mt-6">{{ __('Être informé — créer un compte') }}</x-ui.button>
            </div>
        @else
            {{-- Filtre par niveau en Alpine : les cours sont deja charges, aucun aller-retour serveur. --}}
            <div x-data="{ level: 'all' }">
                <div class="flex flex-wrap items-center gap-2 mb-10" role="group" aria-label="{{ __('Filtrer par niveau') }}">
                    <span class="text-sm font-medium text-ink-400 mr-1">{{ __('Niveau') }}</span>
                    <button type="button" x-on:click="level = 'all'"
                        :class="level === 'all' ? 'bg-brand-blue-600 text-white ring-brand-blue-600' : 'bg-white text-ink-600 ring-ink-200 hover:ring-ink-300'"
                        class="px-4 py-2 rounded-full text-sm font-medium ring-1 transition">
                        {{ __('Tous') }}
                    </button>
                    @foreach ($levels as $level)
                        <button type="button" x-on:click="level = '{{ $level->value }}'"
                            :class="level === '{{ $level->value }}' ? 'bg-brand-blue-600 text-white ring-brand-blue-600' : 'bg-white text-ink-600 ring-ink-200 hover:ring-ink-300'"
                            class="px-4 py-2 rounded-full text-sm font-medium ring-1 transition">
                            {{ $level->label() }}
                        </button>
                    @endforeach
                </div>

                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($courses as $course)
                        <div x-show="level === 'all' || level === '{{ $course->level->value }}'"
                             x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                            <x-public.course-card :course="$course" />
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </section>

</x-layouts.public>
