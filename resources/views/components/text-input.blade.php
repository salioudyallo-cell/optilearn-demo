@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'w-full border-ink-200 text-ink-800 placeholder-ink-400 focus:border-brand-blue-500 focus:ring-2 focus:ring-brand-blue-500/30 rounded-xl shadow-soft transition']) }}>
