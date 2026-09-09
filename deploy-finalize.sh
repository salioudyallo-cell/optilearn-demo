#!/usr/bin/env bash
#
# Finalisation d'un déploiement sur le serveur LWS.
# À lancer en SSH APRÈS que le workflow GitHub a terminé le transfert FTP :
#
#     bash ~/public_html/elearning/deploy-finalize.sh
#
# Rejoue les dépendances, les migrations et reconstruit les caches.
set -euo pipefail

cd "$(dirname "$0")"

php artisan down --render="errors::503" || true

# Dépendances PHP (ne réinstalle que si composer.lock a changé).
composer install --no-dev --optimize-autoloader --no-interaction

# Migrations de base (sans interaction, sûres en production).
php artisan migrate --force

# Lien symbolique de stockage (idempotent) : sert les medias deposes via le disque
# « public » (couvertures, certificats publics) sur /storage. Sans lui -> 404.
php artisan storage:link || true

# Reconstruction des caches (config, routes, vues).
php artisan optimize

php artisan up

echo "Finalisation terminée."
