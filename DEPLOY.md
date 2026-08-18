# DEPLOY.md — Mise en production LWS (cPanel mutualisé)

Procédure de déploiement de la plateforme e-learning OptiLeads sur l'hébergement
mutualisé **LWS**. Méthode validée avec le client : **transfert des fichiers par FTP,
finalisation au terminal SSH**. Git n'est pas le transport (pas de `git pull` sur le
serveur).

> Contexte serveur confirmé (24/07/2026) : cPanel `cpsrvd`, **MariaDB 11.4.10**, socket
> UNIX, jeu de caractères serveur `latin1`, SSH activé. Voir `CLAUDE.md` §4.

---

## 0. Prérequis côté LWS (une seule fois)

| Élément | Réglage |
|---|---|
| Version PHP du domaine | **8.3** via cPanel « Select PHP Version » (ne PAS utiliser 8.4/8.5) |
| Extensions PHP à activer | `bcmath`, `mbstring`, `intl`, `gd`, `zip`, `fileinfo`, `pdo_mysql` |
| Base de données | créer une base + un utilisateur dédiés (cPanel « Bases de données MySQL ») |
| Jeu de caractères | forcer `utf8mb4` — voir §3, point critique |
| SSL | certificat Let's Encrypt actif sur le domaine |
| Document root | faire pointer le domaine vers `~/optileads-lms/public` |

> ⚠️ La version PHP affichée par phpMyAdmin (8.4.x) est le PHP interne de cPanel, pas
> celle du site. Régler la version du domaine séparément dans « Select PHP Version ».

---

## 1. Préparer le paquet en local (à chaque release)

Node.js est **absent du serveur** : les assets Vite se compilent en local et
`public/build` est versionné puis transféré.

```bash
npm run build
```

Vérifier que `public/build/manifest.json` et les assets compilés sont présents et à jour.

**Ne jamais transférer par FTP :**

```
.git/          tests/         node_modules/    vendor/
phpunit.xml    .env           .env.example     storage/logs/*
storage/framework/cache/*     storage/framework/sessions/*
```

- `vendor/` est installé sur le serveur par Composer (SSH), jamais uploadé : plusieurs
  milliers d'inodes, transfert interminable, inodes comptés sur le mutualisé.
- `public/build` **est** transféré (compilé en local).
- `public/fonts/sora/` **est** transféré (police auto-hébergée, aucun CDN).

---

## 2. Installation initiale (une seule fois)

### 2.1 Transfert

Par FTP, déposer le projet dans `~/optileads-lms` (hors `public_html`), en respectant les
exclusions du §1. Faire pointer le document root du domaine vers
`~/optileads-lms/public`.

### 2.2 Dépendances (SSH)

```bash
cd ~/optileads-lms
composer install --no-dev --optimize-autoloader
# En cas d'erreur mémoire :
# COMPOSER_MEMORY_LIMIT=-1 composer install --no-dev --optimize-autoloader
```

### 2.3 Environnement (SSH)

```bash
cp .env.example .env
php artisan key:generate
```

Éditer `.env` et renseigner **au minimum** :

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://formation.opti-leads.com   # domaine réel, en https

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=<base LWS>
DB_USERNAME=<utilisateur LWS>
DB_PASSWORD=<mot de passe LWS>
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=database
CACHE_STORE=database
LOG_CHANNEL=stack
LOG_STACK=daily
LOG_DAILY_DAYS=14

MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=<login Brevo>
MAIL_PASSWORD=<clé SMTP Brevo>
MAIL_FROM_ADDRESS=no-reply@opti-leads.com

BUNNY_LIBRARY_ID=<...>
BUNNY_API_KEY=<...>
BUNNY_CDN_HOSTNAME=<...>
BUNNY_TOKEN_SECURITY_KEY=<...>

CONTACT_WHATSAPP_NUMBER=<numéro international sans +>
```

> Le `.env` de production n'est **jamais** transféré par FTP : il est créé ici et laissé
> en place à chaque mise à jour.

### 2.4 Base et stockage (SSH)

```bash
php artisan migrate --force
php artisan storage:link
```

### 2.5 Vérifier le jeu de caractères — POINT CRITIQUE

Le serveur MariaDB LWS est en `latin1`. `utf8mb4` **ne s'hérite pas** du serveur, il est
imposé par `.env` + `config/database.php` + la création de la base. Vérifier après
migration :

```bash
php artisan tinker --execute="echo DB::selectOne('SELECT @@character_set_database AS c')->c;"
# doit afficher : utf8mb4
```

Puis insérer une chaîne accentuée de test et la relire : les accents doivent ressortir
intacts. Sinon, recréer la base en `CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci`
avant de remettre du contenu.

### 2.6 Permissions et cache (SSH)

```bash
chmod -R 775 storage bootstrap/cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 2.7 Tâche planifiée (cron cPanel)

Une seule tâche, chaque minute :

```cron
* * * * * /usr/local/bin/php /home/<compte>/optileads-lms/artisan schedule:run >> /dev/null 2>&1
```

Le scheduler Laravel déclenche lui-même, chaque minute,
`queue:work --stop-when-empty --max-time=50` (défini dans `routes/console.php` en phase 3+
— pas de worker permanent sur le mutualisé). Adapter le chemin de `php` (souvent
`/usr/local/bin/php` ou une version spécifique chez LWS).

---

## 3. Mise à jour (à chaque release) — script `deploy.sh`

Un upload FTP n'est **pas atomique** : sans mode maintenance, le site sert un mélange
d'ancien et de nouveau code. L'ordre ci-dessous est impératif.

1. **Sauvegarde de la base AVANT toute chose** (voir §5). Non négociable.
2. En local : `npm run build`, puis transférer par FTP les fichiers modifiés
   (`public/build` inclus), en respectant les exclusions du §1.
3. Au terminal SSH, dans `~/optileads-lms` :

```bash
php artisan down                                   # mode maintenance
composer install --no-dev --optimize-autoloader    # si dépendances modifiées
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan up                                     # remise en ligne
```

Contenu recommandé de `deploy.sh` (à exécuter côté serveur, une fois le transfert FTP
terminé) :

```bash
#!/usr/bin/env bash
set -euo pipefail
cd ~/optileads-lms

php artisan down
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan up
echo "Déploiement terminé."
```

---

## 4. Vérifications post-déploiement

- [ ] `APP_DEBUG=false` et `APP_ENV=production` (aucune trace d'erreur visible)
- [ ] Config, routes et vues en cache (`php artisan about` le confirme)
- [ ] `.env` **inaccessible** par URL directe : `https://domaine/.env` → 403/404
- [ ] `storage/` et `vendor/` inaccessibles par URL directe
- [ ] La page d'accueil répond en **HTTPS**
- [ ] `/admin` refuse un compte `learner` (403) et accepte `instructor`/`admin`
- [ ] Aucune requête vers un CDN de polices (onglet Réseau) : Sora servie localement
- [ ] Une leçon non-preview renvoie 403 en accès direct par l'URL sans inscription

---

## 5. Sauvegarde et exploitation (phase 6)

### 5.1 Sauvegardes automatiques — `spatie/laravel-backup`

La base est sauvegardée **chaque nuit** par le scheduler (déjà déclaré dans
`routes/console.php`, déclenché par le cron cPanel de la §2.7) :

- `backup:run --only-db` à 03h00 — dump MySQL gzippé, zippé sur le disque `backups`.
- `backup:clean` à 03h30 — applique la **rétention de 14 jours**.
- Notification email à l'admin **uniquement en cas d'échec** (voir `config/backup.php`).

Variables `.env` de production :

```env
DB_DUMP_BINARY_PATH=/usr/bin          # dossier de mysqldump sur LWS (souvent /usr/bin)
BACKUP_DISK=backups                    # disque local ; voir 5.2 pour une copie hors serveur
BACKUP_NOTIFICATION_EMAIL=admin@opti-leads.com
```

> **Trouver mysqldump sur LWS** : `which mysqldump` (souvent `/usr/bin/mysqldump`).
> Renseigner le **dossier** (sans le nom du binaire) dans `DB_DUMP_BINARY_PATH`, ou
> laisser vide si `mysqldump` est déjà dans le `PATH`.

### 5.2 Copie hors serveur (recommandé)

Le disque `backups` est **local au serveur**. Pour une vraie sécurité, ajouter un disque
distant (S3 ou Cloudflare R2) dans `config/filesystems.php`, puis pointer `BACKUP_DISK`
dessus (ou ajouter le disque à `backup.destination.disks`). Aucun changement de code.

### 5.3 Sauvegarde manuelle — **obligatoire avant tout `migrate --force`**

```bash
php artisan backup:run --only-db
# ou, en direct :
mysqldump -u <user> -p <base> | gzip > ~/backups/optileads-$(date +%F-%H%M).sql.gz
```

### 5.4 Restauration (procédure testée)

La restauration a été **testée en local le 24/07/2026** (dump extrait → réimporté dans une
base neuve → données vérifiées). Sur le serveur :

```bash
# 1. Récupérer l'archive voulue depuis storage/app/backups/OptiLeads Formation/
# 2. Extraire le dump SQL de l'archive .zip (dossier db-dumps/ à l'intérieur)
unzip -o "AAAA-MM-JJ-HH-MM-SS.zip" -d /tmp/restore
gunzip /tmp/restore/db-dumps/*.sql.gz

# 3. Réimporter (⚠️ écrase les données — faire une sauvegarde de l'état courant d'abord)
mysql -u <user> -p <base> < /tmp/restore/db-dumps/mysql-<base>.sql

# 4. Vider les caches applicatifs
php artisan optimize:clear
```

### 5.5 Logs, erreurs et RGPD

- **Logs** : driver `daily`, rétention **14 jours** (`LOG_STACK=daily`, `LOG_DAILY_DAYS=14`
  en `.env`). Sans rotation, les inodes du mutualisé saturent.
- **Notification d'erreur** : toute exception non gérée envoie un email throttlé à l'admin
  (`config/lms.php` → `error_notifications` ; actif dès `APP_DEBUG=false`, au plus un email
  par type d'erreur toutes les 30 min). Page 500 à la charte pour l'utilisateur.
- **RGPD** : depuis Filament → **Utilisateurs**, l'action « Exporter les données » télécharge
  toutes les données personnelles d'un compte en JSON ; « Supprimer le compte » efface le
  compte **et ses fichiers** (PDF de certificats, avatar). L'apprenant peut aussi supprimer
  lui-même son compte depuis son profil.

---

## 6. Installation réelle en production (29/07/2026) — paramètres et pièges

### 6.1 Emplacements et versions effectifs

| Élément | Valeur réelle |
|---|---|
| Racine de l'application | `~/public_html/formation` (LWS impose un document root **dans** `public_html`) |
| Document root du sous-domaine | `public_html/formation/public` |
| Racine du compte FTP | déjà `~/public_html/formation` → `server-dir: ./` dans le workflow |
| PHP (web et CLI) | **8.4** — binaire CLI : `/usr/local/bin/php` |
| Base | `cp2540294p02_formation`, hôte `localhost` (socket), `utf8mb4_unicode_ci` |
| Serveur web | LiteSpeed + cache edge « fastestcache » (Varnish) |

### 6.2 Cron (créé dans cPanel, chaque minute)

```cron
* * * * * /usr/local/bin/php /home/cp2540294p02/public_html/formation/artisan schedule:run >> /dev/null 2>&1
```

### 6.3 Pièges rencontrés — à relire avant tout dépannage

- **OPcache (le plus coûteux)** : LiteSpeed garde en mémoire une version compilée du code.
  Après un déploiement ou une correction de `.env`, le site peut continuer à servir
  **l'ancien code** et renvoyer un 500 **sans aucune trace dans les logs**. Symptôme
  caractéristique : `curl` en local répond 200 mais le navigateur affiche 500.
  **Remède** : changer la version PHP dans cPanel puis revenir à 8.4 (cela redémarre le
  gestionnaire PHP et vide l'OPcache). C'est la première chose à tenter face à une erreur
  incohérente avec les logs.
- **Cache edge « fastestcache »** : il a mis en cache une page 500 et la resservait après
  correction. Le désactiver pour ce sous-domaine ; un back-office ne doit jamais être
  mis en cache par un CDN.
- **`.htaccess` racine** : LiteSpeed applique le `.htaccess` du dossier parent **aussi**
  au sous-domaine. Un `Require all denied` global bloque donc tout le site (403). D'où le
  blocage ciblé par extensions (voir le `.htaccess` versionné à la racine).
- **`tinker` non fiable** : psysh plante (*segmentation fault*) et renvoie des compteurs
  faux sur cet hébergement. Ne pas s'y fier pour diagnostiquer.
- **Compteurs `db:show --counts` faux** : l'utilisateur MySQL LWS n'a pas accès aux
  statistiques `information_schema` — toutes les tables sont annoncées à 0 ligne.
- **`.env` fragile** : une valeur contenant un espace non protégé casse dotenv, et une
  `APP_KEY` absente provoque un 500 global. Après toute manipulation du `.env`, vérifier :
  `grep '^APP_KEY=base64:' .env`.
- **SSH depuis GitHub Actions impossible** : LWS filtre le SSH par IP, celles de GitHub
  sont dynamiques. D'où la finalisation manuelle (§7).
- **FTPS instable** : erreur TLS `decode error` sur le canal de données lors des gros
  transferts. Le workflow utilise donc le FTP simple.
- **Pare-feu LWS** : l'IP du poste de travail peut être bloquée (site injoignable en
  HTTP/HTTPS alors que le `ping` passe). Débloquer l'IP depuis l'espace client LWS.

---

## 7. Mise à jour via GitHub (procédure courante)

1. En local : commit puis `git push` sur `main`.
2. Sur GitHub : onglet **Actions → « Déploiement LWS » → Run workflow**
   (build Vite + transfert FTP ; déclenchement **manuel**, rien ne part tout seul).
3. En SSH, une seule commande :

```bash
bash ~/public_html/formation/deploy-finalize.sh
```

Elle enchaîne `composer install`, `migrate --force`, `optimize`, avec mode maintenance.

4. **Si les modifications n'apparaissent pas** : c'est l'OPcache (voir §6.3) — changer la
   version PHP dans cPanel puis revenir à 8.4.

---

## 8. Vidéos — pilote « local » (Palier 1) puis Bunny (Palier 2)

Deux pilotes interchangeables. Le passage de l'un à l'autre est **un réglage `.env`** :
aucune leçon n'est à recréer.

### 8.1 Mode local (démarrage, tests, jusqu'à ~20-30 apprenants)

```env
VIDEO_DRIVER=local
VIDEO_DELIVERY=php          # ou « sendfile » si l'hébergeur le supporte (voir 8.3)
```

**Encodage avant mise en ligne** (indispensable — `faststart` conditionne le démarrage
immédiat de la lecture, sinon la vidéo ne joue qu'une fois entièrement téléchargée) :

```bash
ffmpeg -i source.mp4 -c:v libx264 -profile:v main -crf 25 -preset slow \
  -vf "scale=-2:720" -c:a aac -b:a 96k -af loudnorm -movflags +faststart lecon-01.mp4
```

Repère de poids : **~70 Mo pour 10 min** en 720p, soit ~1,3 Go pour une formation de 3 h.

**Dépôt des fichiers** : par **FTP** dans `~/public_html/formation/storage/app/videos/`
(sous-dossiers libres, ex. `formation-1/lecon-01.mp4`). Le FTP est nécessaire car les gros
fichiers dépassent les limites d'upload PHP du mutualisé. Les fichiers apparaissent ensuite
dans la liste déroulante « Fichier vidéo » du back-office.

> Le dossier `storage/` est **exclu du transfert par le workflow GitHub** : les vidéos ne
> sont donc jamais écrasées par un déploiement.

### 8.2 Sécurité

Les fichiers vivent **hors du dossier public** : aucune URL directe. Le lecteur reçoit un
lien **signé et temporaire** (4 h), et le contrôleur **re-vérifie l'autorisation** à chaque
requête — un lien partagé à un non-inscrit renvoie 403 (couvert par
`tests/Feature/LocalVideoTest.php`).

⚠️ Ce n'est pas du DRM : un apprenant déterminé peut toujours récupérer le fichier via les
outils du navigateur. C'est vrai aussi avec Bunny. On protège contre le partage d'URL, pas
contre le piratage motivé.

### 8.3 Performance — à tester en production

En `VIDEO_DELIVERY=php`, chaque spectateur **mobilise un worker PHP** pendant toute la
lecture. Sur mutualisé (~20-30 processus pour tout le compte), cela plafonne vite.

Tester donc la délégation au serveur web :

```env
VIDEO_DELIVERY=sendfile
VIDEO_SENDFILE_HEADER=X-LiteSpeed-Location
```

Laravel n'envoie alors qu'un en-tête et **LiteSpeed sert le fichier en statique**, libérant
PHP immédiatement. Si la vidéo ne se lance plus (en-tête non supporté par l'hébergeur),
revenir à `php` — c'est sans risque.

### 8.4 Quand basculer sur Bunny

Dès que **l'un** de ces signaux apparaît : plus de **20 apprenants actifs simultanés**,
plus de **15 Go** de vidéos, des retours « ça coupe / ça rame », ou un rappel de LWS sur
l'usage du mutualisé (leurs CGU restreignent le streaming vidéo). La bascule :

```env
VIDEO_DRIVER=bunny
BUNNY_LIBRARY_ID=...
BUNNY_CDN_HOSTNAME=...
BUNNY_TOKEN_SECURITY_KEY=...
```

Puis renseigner l'identifiant Bunny de chaque leçon dans le back-office.

---

## 9. Catalogue de démonstration

Trois formations couvrant les trois niveaux, avec miniatures, leçon d'essai lisible
sans compte, quiz et codes d'accès :

```bash
php artisan db:seed --class=CatalogDemoSeeder --force
```

| Formation | Niveau | Code d'accès |
|---|---|---|
| Démonstration : prise en main de la plateforme | Débutant | `DEMOVIDEO1` |
| Générer des leads B2B avec LinkedIn et l'e-mailing | Intermédiaire | `LEADSB2B24` |
| Piloter l'acquisition par la donnée | Avancé | `PILOTAGE01` |

Le seeder est **idempotent** (relançable sans doublon) et **n'utilise aucune factory** :
Faker est absent en production, il est donc exécutable sur le serveur.

Les **miniatures sont générées par le seeder** (SVG 1200×675 aux couleurs de la charte,
~1,4 Ko) dans `storage/app/public/courses/covers/`. Aucune image tierce, donc aucune
licence à vérifier, et rien à transférer par FTP. Remplaçables par de vraies photos
depuis le back-office à tout moment.

> Prérequis : `php artisan storage:link` doit avoir été exécuté, sinon les miniatures
> renvoient 403.

### Aperçu public

Une leçon marquée `is_preview` d'un cours **publié** est **réellement lisible sans
compte** — c'est le levier de conversion de la fiche formation. Le lecteur s'affiche,
suivi d'un appel à l'action. Aucune progression n'est enregistrée (l'invité n'a pas de
compte). Le contenu non-preview reste fermé, et les 5 tests de
`tests/Feature/LocalVideoTest.php` verrouillent cette frontière.

---

## 10. Indexation — tenir le site hors des moteurs et des robots d'IA

Tant que le contenu n'est pas prêt, le site est maintenu **non indexable** par défaut.
Un seul réglage gouverne trois barrières :

```env
SITE_INDEXABLE=false     # défaut. false = bloqué (moteurs ET robots d'IA)
```

Quand `false` :
- balise `<meta name="robots" content="noindex, nofollow">` sur toutes les pages ;
- en-tête HTTP `X-Robots-Tag: noindex, nofollow, noai, noimageai` sur toutes les réponses
  (couvre aussi PDF et flux, là où la balise meta ne s'applique pas) ;
- `robots.txt` renvoie `User-agent: * / Disallow: /` (le fichier est servi par une route,
  plus par un fichier statique).

**Le jour du lancement**, une fois tout le contenu en place :

```bash
# dans .env de production
SITE_INDEXABLE=true
php artisan config:cache
```

Le `robots.txt` rouvre alors l'indexation en ne fermant que les zones privées
(`/admin`, `/mon-espace`, `/apprendre`, `/activer`, `/certificats`, `/profil`) et annonce
le sitemap. Comportement verrouillé par `tests/Feature/IndexingPolicyTest.php`.

> Rappel : la config étant mise en cache en production, tout changement de
> `SITE_INDEXABLE` exige `php artisan config:cache` pour prendre effet.

---

## 11. Créer une instance de DÉMONSTRATION (présentation prospects)

Modèle « 1 code / N instances » : la démo est une instance **séparée** (sous-domaine,
base, `.env` propres), copie du pilote interne. Elle ne partage rien avec `formation`.

### 11.1 Prérequis (cPanel)

1. **Déployer d'abord la dernière version sur `formation`** (Run workflow + `deploy-finalize.sh`),
   pour que la copie parte du code à jour.
2. **Sous-domaine** `demo.opti-leads.com`, document root → `public_html/demo/public`.
3. **Base MySQL dédiée** + utilisateur (utf8mb4), notés.

### 11.2 Copie et configuration (SSH) — pas de re-transfert

La copie se fait côté serveur (instantanée), l'instance `formation` étant déjà complète
(code + vendor + `public/build` + vidéos) :

```bash
# 1. Copier l'app (inclut vendor, build, storage/videos) vers la démo
cp -a ~/public_html/formation ~/public_html/demo

# 2. Nouvel environnement isolé
cd ~/public_html/demo
cp .env .env.backup
php artisan key:generate            # nouvelle APP_KEY, propre à la démo
```

Éditer `~/public_html/demo/.env` :

```env
APP_URL=https://demo.opti-leads.com
DB_DATABASE=<base démo>
DB_USERNAME=<utilisateur démo>
DB_PASSWORD=<mot de passe démo>
SITE_INDEXABLE=false                 # la démo n'est jamais indexée
DEMO_MODE=true                       # bandeau + commande de réinitialisation
```

### 11.3 Remplir la démo

```bash
cd ~/public_html/demo
php artisan migrate:fresh --seed --seeder=DemoDataSeeder --force
php artisan app:demo-reset           # crée l'admin démo + le jeu de données + le mode
php artisan optimize
```

> `app:demo-reset` (refusée si `DEMO_MODE≠true`) remet la démo à neuf en une commande —
> à relancer périodiquement, ou via un cron, pour effacer ce que les prospects ont saisi.
> Admin de démo : `admin@demo.opti-leads.com` / `demo1234`.

### 11.4 En rendez-vous

Depuis **Administration → Configuration**, basculer **Commercial ↔ Entreprise** en direct :
même code, deux modèles économiques. Le bandeau « Environnement de démonstration » signale
aux prospects qu'ils manipulent une copie.

> ⚠️ Si une nouveauté n'apparaît pas après copie : OPcache — changer la version PHP dans
> cPanel puis revenir (voir §6.3).

### 11.5 Marque blanche — démo à marque fictive (et gabarit des instances clients)

Toute instance peut porter **une autre marque** (nom, logo, couleurs, contacts) via le
seul `.env`, sans toucher au code (voir `config/brand.php`). Une instance qui ne définit
rien reste « OptiLeads ». C'est ce qui rend le socle **réutilisable pour chaque client**.

Ajouter au `.env` de l'instance (bloc complet et commenté dans `.env.production.example`) :

```env
APP_NAME="Nimba Académie"
BRAND_SHORT_NAME="Nimba"
BRAND_LEGAL_NAME="Nimba Académie"
BRAND_TAGLINE="Montez en compétences, où que vous soyez."
BRAND_SLOGAN="Apprendre, progresser, réussir."
BRAND_EMAIL="contact@nimba-academie.com"
BRAND_CITY="Abidjan · Dakar"
BRAND_REGION="Afrique de l’Ouest"
BRAND_LOGO_IMAGE=                       # vide -> emblème coloré + nom généré (aucun fichier)
BRAND_LOGO_IMAGE_WHITE=                 # vide -> emblème + nom en blanc (pied de page)
BRAND_FAVICON="images/favicon-nimba.svg"
BRAND_COLOR_PRIMARY="#0f6e5c"           # définir une couleur active le re-thème complet
BRAND_COLOR_ACCENT="#e0851e"
BRAND_COLOR_HIGHLIGHT="#f5b841"
```

Puis, **impérativement**, régénérer le cache de configuration (sinon l'ancienne marque
reste) :

```bash
cd ~/public_html/<instance> && php artisan optimize
```

Notes :

- **Logo sans fichier** : laisser `BRAND_LOGO_IMAGE` vide affiche l'emblème (recoloré par
  les couleurs de marque) suivi du nom en toutes lettres. Pour un vrai logo, déposer le
  fichier dans `public/images/` et pointer `BRAND_LOGO_IMAGE` dessus.
- **Couleurs** : donner `BRAND_COLOR_PRIMARY` suffit — toute l'échelle (50→900) est générée
  et injectée au rendu. Tant qu'aucune couleur n'est définie, la charte compilée est gardée
  telle quelle (l'instance OptiLeads n'est jamais modifiée).
- **Favicon SVG** fourni pour la démo Nimba : `public/images/favicon-nimba.svg`.
- Ce bloc est indépendant de `DEMO_MODE` : on peut brander une instance **réelle** de client
  de la même façon (sans le bandeau de démonstration).

---

## Annexe — environnement de développement local (rappel)

MAMP Windows, détaillé dans `CLAUDE.md` §3.

```bash
# Base de dev (une fois)
"C:/MAMP/bin/mysql/bin/mysql.exe" -h 127.0.0.1 -P 8889 -u root -proot \
  -e "CREATE DATABASE IF NOT EXISTS optileads_lms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Base de test (une fois) — le test de concurrence exige un vrai MySQL
"C:/MAMP/bin/mysql/bin/mysql.exe" -h 127.0.0.1 -P 8889 -u root -proot \
  -e "CREATE DATABASE IF NOT EXISTS optileads_lms_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

php artisan migrate:fresh --seed   # base de dev + jeu de démo
php artisan serve                  # http://localhost:8000

composer check                     # Pint + Larastan + Pest en une commande
```
