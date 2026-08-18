@props(['certificate'])

@php
    $verifyUrl = route('certificate.verify', $certificate->serial);
    $title = $certificate->course->title;

    // LinkedIn « Ajouter au profil » : pré-remplit la section Certifications.
    $linkedin = 'https://www.linkedin.com/profile/add?'.http_build_query([
        'startTask' => 'CERTIFICATION_NAME',
        'name' => $title,
        'organizationName' => config('brand.short_name'),
        'certUrl' => $verifyUrl,
        'certId' => $certificate->serial,
    ]);

    $whatsapp = 'https://wa.me/?'.http_build_query([
        'text' => __('J’ai obtenu mon certificat « :title » sur :brand. Vérifiez-le ici : :url', ['title' => $title, 'brand' => config('brand.name'), 'url' => $verifyUrl]),
    ]);
@endphp

<div {{ $attributes->merge(['class' => 'inline-flex items-center gap-2']) }}>
    <a href="{{ $linkedin }}" target="_blank" rel="noopener"
       class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold text-white bg-[#0a66c2] hover:opacity-90 transition"
       aria-label="{{ __('Ajouter à mon profil LinkedIn') }}">
        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.45 20.45h-3.56v-5.57c0-1.33-.02-3.04-1.85-3.04-1.85 0-2.13 1.45-2.13 2.94v5.67H9.35V9h3.42v1.56h.05c.48-.9 1.64-1.85 3.37-1.85 3.6 0 4.27 2.37 4.27 5.46v6.28zM5.34 7.43a2.07 2.07 0 1 1 0-4.14 2.07 2.07 0 0 1 0 4.14zM7.12 20.45H3.55V9h3.57v11.45zM22.22 0H1.77C.79 0 0 .77 0 1.73v20.54C0 23.23.79 24 1.77 24h20.45c.98 0 1.78-.77 1.78-1.73V1.73C24 .77 23.2 0 22.22 0z"/></svg>
        {{ __('LinkedIn') }}
    </a>

    <a href="{{ $whatsapp }}" target="_blank" rel="noopener"
       class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold text-white bg-[#25d366] hover:opacity-90 transition"
       aria-label="{{ __('Partager sur WhatsApp') }}">
        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.65.07-.3-.15-1.26-.46-2.4-1.48-.89-.79-1.49-1.77-1.66-2.07-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.07-.15-.67-1.62-.92-2.22-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.8.37-.27.3-1.04 1.02-1.04 2.48 0 1.46 1.07 2.87 1.22 3.07.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.69.63.71.22 1.36.19 1.87.12.57-.09 1.76-.72 2-1.41.25-.7.25-1.29.17-1.41-.07-.13-.27-.2-.57-.35zM12.04 21.5h-.01a9.5 9.5 0 0 1-4.83-1.32l-.35-.2-3.59.94.96-3.5-.23-.36a9.46 9.46 0 0 1-1.45-5.05c0-5.24 4.27-9.5 9.52-9.5 2.54 0 4.93.99 6.73 2.79a9.44 9.44 0 0 1 2.79 6.72c-.01 5.24-4.28 9.5-9.52 9.5zM20.5 3.49A11.4 11.4 0 0 0 12.04.01C5.75.01.62 5.14.62 11.44c0 2.01.52 3.98 1.53 5.71L.5 23.5l6.5-1.7a11.4 11.4 0 0 0 5.04 1.18h.01c6.29 0 11.42-5.13 11.42-11.43 0-3.05-1.19-5.92-3.36-8.06z"/></svg>
        {{ __('WhatsApp') }}
    </a>
</div>
