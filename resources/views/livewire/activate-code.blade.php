<div class="mx-auto max-w-xl px-4 sm:px-6 py-16">
    <div class="text-center mb-8">
        <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-orange-50 text-brand-orange-600">
            <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" /></svg>
        </span>
        <h1 class="mt-5 text-3xl font-bold text-brand-charcoal">{{ __('Activer une formation') }}</h1>
        <p class="mt-2 text-ink-500">{{ __('Saisissez le code d’accès que vous a transmis :brand.', ['brand' => config('brand.short_name')]) }}</p>
    </div>

    <div class="bg-white rounded-3xl ring-1 ring-ink-100 shadow-card p-6 sm:p-8">
        @if ($message)
            <div class="mb-6 flex items-start gap-3 rounded-xl px-4 py-3 text-sm {{ $success ? 'bg-green-50 text-green-800 ring-1 ring-green-200' : 'bg-red-50 text-red-800 ring-1 ring-red-200' }}"
                 role="status" aria-live="polite">
                <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    @if ($success)<path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />@else<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />@endif
                </svg>
                <span>{{ $message }}</span>
            </div>

            @if ($success && $courseSlug)
                <x-ui.button :href="route('learn.course', $courseSlug)" variant="primary" class="w-full mb-2">
                    {{ __('Commencer la formation') }}
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                </x-ui.button>
            @endif
        @endif

        <form wire:submit="redeem" class="space-y-5">
            <div>
                <label for="code" class="block font-medium text-sm text-ink-700 mb-1.5">{{ __('Code d’accès') }}</label>
                {{-- Saisie clavier : .blur, jamais .live nu (regle §6). --}}
                <input id="code" type="text" wire:model.blur="code"
                       autocomplete="off" autocapitalize="characters" spellcheck="false"
                       class="block w-full rounded-xl border-ink-200 focus:border-brand-blue-500 focus:ring-2 focus:ring-brand-blue-500/30 font-mono text-lg tracking-[0.3em] uppercase text-center shadow-soft transition"
                       placeholder="OPT7K4M9XQ" maxlength="20">
                @error('code')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                    class="w-full inline-flex items-center justify-center gap-2 px-5 py-3.5 rounded-xl bg-brand-orange-500 text-white font-semibold shadow-[0_6px_18px_-4px_rgba(249,118,0,0.45)] hover:bg-brand-orange-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-orange-500 focus-visible:ring-offset-2 transition disabled:opacity-60"
                    wire:loading.attr="disabled" wire:target="redeem">
                <span wire:loading.remove wire:target="redeem">{{ __('Activer mon accès') }}</span>
                <span wire:loading wire:target="redeem" class="inline-flex items-center gap-2">
                    <svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.4 0 0 5.4 0 12h4z"/></svg>
                    {{ __('Activation…') }}
                </span>
            </button>
        </form>
    </div>
</div>
