<x-mail::message>
# Félicitations {{ $userName }} !

Vous avez validé la formation **« {{ $courseTitle }} »**. Votre certificat de réussite est prêt.

- **Numéro de série :** {{ $serial }}

<x-mail::button :url="$certificatesUrl" color="primary">
Télécharger mon certificat
</x-mail::button>

Toute personne peut vérifier l’authenticité de ce certificat à cette adresse :
{{ $verifyUrl }}

Bravo pour votre parcours,<br>
L’équipe {{ config('brand.short_name') }}
</x-mail::message>
