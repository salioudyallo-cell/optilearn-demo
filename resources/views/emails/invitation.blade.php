Bonjour {{ $user->name }},

Un accès à la plateforme de formation {{ config('brand.name') }} vient d'être créé pour vous par votre organisation.

Pour activer votre compte et définir votre mot de passe, cliquez sur le lien ci-dessous :

{{ $setPasswordUrl }}

Ce lien est valable un temps limité. Passé ce délai, demandez un nouveau lien depuis la page de connexion (« Mot de passe oublié »).

Une fois votre mot de passe défini, vous pourrez vous connecter et suivre les formations qui vous ont été attribuées.

À bientôt,
L'équipe {{ config('brand.name') }}
