<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-brand-orange-500 rounded-xl font-semibold text-sm text-white tracking-wide shadow-[0_6px_18px_-4px_rgba(249,118,0,0.45)] hover:bg-brand-orange-600 hover:-translate-y-0.5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-orange-500 focus-visible:ring-offset-2 active:translate-y-0 transition duration-200']) }}>
    {{ $slot }}
</button>
