<x-mail::message>
# Bonjour {{ $userName }},

Votre accès à la formation **« {{ $courseTitle }} »** est désormais actif.

Vous pouvez commencer dès maintenant, à votre rythme, depuis votre espace.

<x-mail::button :url="$courseUrl" color="primary">
Accéder à ma formation
</x-mail::button>

Si ce bouton ne fonctionne pas, copiez ce lien dans votre navigateur :
{{ $courseUrl }}

Bonne formation,<br>
L’équipe {{ config('brand.short_name') }}
</x-mail::message>
