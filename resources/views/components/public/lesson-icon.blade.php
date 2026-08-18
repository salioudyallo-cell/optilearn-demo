@props(['type'])

@php
    $paths = [
        'video' => 'M15.75 10.5 21 7.5v9l-5.25-3M4.5 6.75h9A1.5 1.5 0 0 1 15 8.25v7.5A1.5 1.5 0 0 1 13.5 17.25h-9A1.5 1.5 0 0 1 3 15.75v-7.5A1.5 1.5 0 0 1 4.5 6.75Z',
        'text' => 'M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h10.5',
        'pdf' => 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z',
        'quiz' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
    ];
    $path = $paths[$type] ?? $paths['text'];
@endphp

<svg class="w-4 h-4 text-ink-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true">
    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}" />
</svg>
