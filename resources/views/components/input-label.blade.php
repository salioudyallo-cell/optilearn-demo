@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-ink-700 mb-1.5']) }}>
    {{ $value ?? $slot }}
</label>
