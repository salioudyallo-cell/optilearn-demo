# CLAUDE.md — Plateforme e-learning OptiLeads

Document de référence du projet. Toute décision technique structurante est consignée ici.
À maintenir à jour à chaque fin de phase (cf. §15 du cahier des charges).

---

## 1. Le projet en une page

LMS de production pour **OptiLeads**, agence de génération de leads B2B (Dakar — Sénégal,
Côte d'Ivoire, Mali). Formation aux métiers du digital : marketing digital, SEO, Meta Ads,
Google Ads, WordPress, IA, automatisation, génération de leads, CRM, vente.

Deux audiences : équipes de clients OptiLeads (achat entreprise, plusieurs places) et
professionnels indépendants en AOF.

**Le paiement en ligne est hors périmètre.** La transaction se fait hors plateforme
(virement, Wave, Orange Money, espèces, facture entreprise). La plateforme ne gère que
**l'octroi d'accès par code**.

Métrique de succès à 90 jours : 50 apprenants actifs ayant terminé au moins un module.

---

## 2. Stack et versions

| Couche | Techno | Version retenue |
|---|---|---|
| Framework | Laravel | **13.21.1** (requiert PHP ^8.3) |
| PHP | PHP | **8.3 strictement** (local `8.3.1`, prod LWS 8.3) |
| Base de données | **local : MySQL 5.7.24** (MAMP) · **prod : MariaDB 11.4.10** (LWS/CloudLinux) | divergence assumée, voir §3.4 |
| Back-office | Filament | **5.7.3** |
| Front apprenant | Blade + Alpine.js + **Livewire 4** | voir la note ci-dessous ; répartition stricte §6 |
| CSS | Tailwind CSS + Vite | build local, `public/build` **versionné** |
| Auth apprenant | Laravel Breeze (stack Blade) | Filament a son propre garde |
| Autorisation | Policies + Gates Laravel | voir §8 |
| Vidéo | Bunny Stream | URLs signées côté serveur, TTL 4 h |
| Emails | Brevo (SMTP ou API) | Mailables Laravel |
| PDF | `barryvdh/laravel-dompdf` | pur PHP, compatible mutualisé |
| Queue / cache / session | driver `database` | pas de Redis |
| Tests | Pest | |
| Qualité | Laravel Pint + Larastan niveau 6 minimum | 0 erreur exigé |

> **Écart assumé vs cahier des charges — Livewire 4 au lieu de 3.** Filament 5 (dernière
> stable) impose Livewire ^4. Le cahier des charges mentionnait Livewire 3, mais figer
> Filament sur une version antérieure pour garder Livewire 3 irait contre la consigne
> « dernière version stable ». La règle §6 (Livewire seulement s'il y a persistance)
> s'applique de façon identique en v4 : aucun impact sur l'architecture. À valider par le
> client — retour arrière possible en descendant Filament, au prix de la fraîcheur.

### Interdits absolus

Redis · Docker · Horizon · Reverb · Octane · Inertia · React · Vue · Meilisearch ·
tout paquet exigeant une extension PHP non standard ou un binaire système.

**Avant toute nouvelle dépendance** : vérifier qu'elle tourne sur mutualisé cPanel sans
binaire externe ni extension exotique. En cas de doute → demander.

---

## 3. Environnement local (Windows + MAMP)

### 3.1 Chemins

```
Projet         C:\MAMP\htdocs\elearning
PHP CLI        C:\MAMP\bin\php\php8.3.1\php.exe
php.ini CLI    C:\MAMP\conf\php8.3.1\php.ini
Client MySQL   C:\MAMP\bin\mysql\bin\mysql.exe
```

> `php8.3.0` existe aussi dans `C:\MAMP\bin\php\` : **ne pas l'utiliser**, on travaille
> exclusivement avec `php8.3.1`.

### 3.2 Point de vigilance — php.ini du CLI

Appelé nu, le PHP CLI de MAMP **ne charge aucun php.ini** (`php --ini` → `(none)`) et
tourne donc sans `mbstring`, `openssl`, `curl`, `fileinfo`, `gd`, `pdo_mysql`. Laravel et
Composer sont inutilisables dans cet état.

Correctif appliqué en Phase 0 — ajouter au PATH utilisateur Windows **et** définir `PHPRC` :

```powershell
[Environment]::SetEnvironmentVariable('PATH', "$env:PATH;C:\MAMP\bin\php\php8.3.1", 'User')
[Environment]::SetEnvironmentVariable('PHPRC', 'C:\MAMP\conf\php8.3.1\php.ini', 'User')
```

Rouvrir le terminal, puis vérifier :

```powershell
php -v
php -m
```

Extensions **confirmées présentes** avec ce php.ini : `bcmath`, `curl`, `fileinfo`, `gd`,
`mbstring`, `openssl`, `pdo_mysql`, `zip`.
Extension **à activer** (DLL présente, ligne commentée dans le php.ini) : `intl`
(décommenter `extension=intl`). `sodium` est disponible si besoin ultérieur.

### 3.3 Base de données

| Paramètre | Valeur |
|---|---|
| Nom | `optileads_lms` |
| Interclassement | `utf8mb4_unicode_ci` |
| Hôte | `127.0.0.1` |
| Port | `8889` |
| Utilisateur | `root` |
| Mot de passe | `root` |

Connexion **vérifiée** avec ces identifiants exacts (port 8889, root/root) → MySQL 5.7.24.

Création :

```powershell
& "C:\MAMP\bin\mysql\bin\mysql.exe" -h 127.0.0.1 -P 8889 -u root -proot -e "CREATE DATABASE IF NOT EXISTS optileads_lms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

> Le serveur MAMP est configuré en `character-set-server=utf8` / `collation-server=utf8_general_ci`.
> On **ne touche pas** à `my.ini` : le charset est imposé au niveau de la base et dans
> `config/database.php` (`utf8mb4` / `utf8mb4_unicode_ci`).

### 3.4 Divergence base local / production — règle du plus petit dénominateur

| | Local (MAMP) | Production (LWS) |
|---|---|---|
| Moteur | MySQL **5.7.24** | **MariaDB 11.4.10** (`-cll-lve`, CloudLinux) |
| Jeu de caractères serveur | `utf8` / `utf8_general_ci` | **`cp1252` / latin1** |
| Connexion | TCP 127.0.0.1:8889 | socket UNIX, `localhost` |

Ce sont **deux moteurs différents**. On n'aligne pas : MAMP ne fournit pas MariaDB et le
mutualisé ne se change pas. On écrit donc au **plus petit dénominateur commun**, ce qui
sert directement la règle de portabilité n°2 (§5) :

- **Pas de contrainte `CHECK`**, pas de colonne générée, pas de fonction fenêtre
  (`ROW_NUMBER`, `RANK`) : absentes de MySQL 5.7 même si MariaDB 11.4 les propose.
- **Pas de type `JSON` natif supposé** : sur MariaDB, `JSON` n'est qu'un alias de
  `LONGTEXT`. `quiz_attempts.answers` est déclaré en `json` par la migration mais **n'est
  jamais interrogé en SQL** — lecture/écriture par le cast Eloquent uniquement.
- Pas de `DEFAULT` sur colonne `TEXT`/`JSON`.
- Longueur d'index : colonnes indexées explicitement dimensionnées, ou
  `Schema::defaultStringLength(191)` dans `AppServiceProvider` si un index `string` coince
  sur 5.7.
- **Aucune fonction propriétaire** dans une requête, MySQL comme MariaDB.

**Charset — point de vigilance production.** Le serveur LWS est en `cp1252`/latin1. Le
`utf8mb4` ne peut donc pas être hérité du serveur : il doit être imposé **à trois niveaux**
— à la création de la base (`CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci`), dans
`config/database.php`, et via `DB_CHARSET=utf8mb4` / `DB_COLLATION=utf8mb4_unicode_ci`
dans le `.env` de production. Sans cela, les accents et le contenu francophone se
corrompent silencieusement à l'insertion. **À vérifier explicitement au premier
déploiement.**

### 3.5 Serveur de développement

Workflow de dev recommandé — **serveur PHP + Vite ensemble** :

```powershell
composer dev
```

Cela lance en parallèle `php artisan serve` (port 8000), le worker de queue, les logs
(`pail`) et **Vite** (`npm run dev`). → http://localhost:8000

**Pourquoi Vite en dev est nécessaire ici.** `php artisan serve` s'appuie sur le serveur
web intégré de PHP, **mono-thread sur Windows** (les workers `PHP_CLI_SERVER_WORKERS`
reposent sur `fork`, absent de Windows). Sous le chargement concurrent d'un navigateur
(HTML + CSS + JS + polices en parallèle), il **abandonne certaines requêtes d'assets**
(`ERR_INVALID_HTTP_RESPONSE`, page sans style). Lancer Vite déporte le service des assets
CSS/JS sur Node (multi-thread) et supprime le problème. En production Apache, ce cas
n'existe pas.

Pour une simple vérification back-end sans assets, `php artisan serve` seul suffit. Pour
tester le rendu visuel, utiliser `composer dev` (ou `npm run dev` en plus de `artisan serve`).

**On n'utilise pas l'Apache de MAMP** et on ne configure **aucun VirtualHost** : seul le
MySQL de MAMP est utilisé. Aucun temps à passer sur un document root Windows vers `/public`.

> **Limitation connue — panel Filament et `artisan serve`.** Le back-office `/admin`
> charge en rafale ~9 fichiers JS statiques (Filament) plus des allers-retours Livewire.
> Le serveur PHP intégré étant mono-thread sur Windows, il **abandonne certaines de ces
> requêtes concurrentes** (`ERR_INVALID_HTTP_RESPONSE`), ce qui peut empêcher le
> formulaire de connexion de se valider. Les assets se servent pourtant correctement un à
> un (vérifié). Contournements, du plus simple au plus robuste :
> 1. Recharger la page une ou deux fois (les fichiers déjà en cache ne sont plus
>    redemandés, la concurrence retombe).
> 2. Pour un usage confortable du back-office en local, servir le projet via **l'Apache
>    de MAMP** (multi-thread) avec un docroot vers `public/` — c'est le seul cas où un
>    vhost local se justifie. Le front public, lui, reste parfaitement servi par
>    `composer dev` (Vite).
>
> **Ceci n'affecte pas la production** (Apache mutualisé, multi-thread) ni les tests :
> la totalité du back-office est couverte par des tests Filament qui pilotent directement
> les composants Livewire (`tests/Feature/Filament/`).

> **Correctif MAMP Apache — extension `intl` (fait le 24/07/2026).** Servi via l'Apache de
> MAMP (`http://localhost/elearning/public/`), le back-office plantait sur **toutes** les
> tables Filament : `The "intl" PHP extension is required to use the [format] method`
> (Filament formate les nombres via `Number::format`, qui exige `intl`). Cause : sous
> Apache, `php_intl.dll` ne trouvait pas ses dépendances **ICU** (`icudt72.dll`,
> `icuin72.dll`, `icuio72.dll`, `icuuc72.dll`). En CLI/`artisan serve`, `php.exe` tourne
> depuis `C:\MAMP\bin\php\php8.3.1\` et y trouve les ICU ; sous Apache, c'est `httpd.exe`
> qui charge, et il cherche ailleurs. **Fix : copier les 4 `icu*.dll` de
> `C:\MAMP\bin\php\php8.3.1\` vers `C:\MAMP\bin\apache\bin\`** (à côté de `httpd.exe`),
> puis **redémarrer Apache**. Vérifié : `extension_loaded('intl')` → `true`, ICU 72.1.
> ⚠️ Une mise à jour de MAMP peut réécraser `apache\bin` : refaire la copie si l'erreur
> réapparaît. Sans effet en production (LWS a `intl`, cf. §4 et `DEPLOY.md`).

> Le php.ini du CLI a été **copié dans le dossier du binaire**
> (`C:\MAMP\bin\php\php8.3.1\php.ini`) pour que `artisan serve` et ses sous-processus le
> chargent automatiquement (sinon `pdo_mysql`/`intl` manquent). `intl` y est activé.

### 3.6 Outillage — installé et vérifié

Composer et Node.js étaient absents de la machine. Installés **sans droits administrateur**,
sur le modèle de WP-CLI (installation permanente sous `C:\MAMP\bin\`) :

| Outil | Version | Emplacement |
|---|---|---|
| PHP CLI | 8.3.1 | `C:\MAMP\bin\php\php8.3.1\php.exe` |
| Composer | 2.10.2 | `C:\MAMP\bin\composer\` (`composer.phar` + `composer.bat`) |
| Node.js | v24.18.0 LTS « Krypton » | `C:\MAMP\bin\nodejs\` (archive zip, pas d'installeur) |
| npm | 11.16.0 | idem |
| Git | 2.55.0 | déjà présent |

`composer.bat` force le bon PHP **et** le bon php.ini, donc Composer fonctionne quel que
soit l'état de `PHPRC`.

Variables d'environnement **utilisateur** (portée `User`, le PATH machine n'est pas touché) :

```
PATH  = C:\Users\HP\AppData\Local\Microsoft\WindowsApps;C:\MAMP\bin\php\php8.3.1;C:\MAMP\bin\composer;C:\MAMP\bin\nodejs
PHPRC = C:\MAMP\conf\php8.3.1\php.ini
```

Vérification dans un terminal neuf : `php -v` → 8.3.1 · `composer --version` → 2.10.2 ·
`node -v` → v24.18.0 · `npm -v` → 11.16.0. Extensions confirmées : `bcmath`, `curl`,
`fileinfo`, `gd`, `mbstring`, `openssl`, `pdo_mysql`, `zip`.

> **Reste à faire (Phase 0)** : activer `intl` en décommentant `extension=intl` dans
> `C:\MAMP\conf\php8.3.1\php.ini`. Ce fichier est **partagé avec tous les autres sites
> MAMP** de la machine : sauvegarde du php.ini avant modification.

---

## 4. Production — hébergement mutualisé cPanel (LWS)

Cette contrainte **prime sur toute considération d'élégance technique**.

### 4.1 Ce qui est établi par le précédent déploiement OptiLeads

Le **Dashboard OptiLeads** (`C:\MAMP\htdocs\projetclient`, PHP 8.3 MVC maison) a été mis
en ligne sur le même hébergement LWS le **23/07/2026** → `app.opti-leads.com`, avec
2 crons LWS actifs et sauvegarde testée. Sa documentation (`dashboard-optileads/11_DEPLOYMENT.md`)
sert de référence terrain.

Confirmé par ce déploiement réussi :

- **PHP 8.3 disponible** sur le compte LWS, avec `pdo_mysql`, `mbstring`, `openssl`,
  `curl`, `zlib`, `fileinfo`, `gd`.
- **Cron cPanel disponible** (2 tâches planifiées en production).
- **HTTPS / Let's Encrypt** actif.
- Un **proxy LWS (Varnish, `Server: fastestcache`)** est placé devant les sites : il
  masque `Server`/`X-Powered-By` et **duplique certains en-têtes de sécurité**
  (`X-Frame-Options: DENY,SAMEORIGIN` observé). À prendre en compte au moment de définir
  nos propres en-têtes, et pour tout cache HTTP.

Relevé sur le cPanel LWS (24/07/2026) :

| | Valeur constatée |
|---|---|
| Base de données | **MariaDB 11.4.10-cll-lve**, socket UNIX, `localhost` |
| Jeu de caractères serveur | `cp1252` West European (latin1) → **forcer `utf8mb4`**, voir §3.4 |
| Serveur web | `cpsrvd 11.134.0.47` (cPanel sur CloudLinux) |
| Utilisateur DB | préfixé `cpses_…` (schéma cPanel : base et utilisateur dédiés, préfixés par le compte) |

> La « Version de PHP : 8.4.23 » affichée par phpMyAdmin est celle du **PHP interne de
> cPanel**, pas celle du site. Le domaine reste à basculer en **PHP 8.3** dans
> « Select PHP Version ». Ne pas confondre les deux.

- **SSH activé** sur le compte, confirmé par le client : il a servi au chargement du
  Dashboard.

**Non confirmé, à vérifier avant la Phase 5 :**

- **Déplacement du document root** vers un dossier hors `public_html`.

### 4.2 Méthode de déploiement retenue — FTP + terminal SSH

**Décision du client** : les fichiers sont transférés **par FTP**, la finalisation se fait
**au terminal SSH**. Git reste l'outil de versionnement local (une branche par phase), mais
**n'est pas le transport de déploiement** : pas de `git clone` ni de `git pull` sur le serveur.

Répartition des rôles :

| Étape | Canal |
|---|---|
| Transfert du code applicatif | **FTP** |
| `composer install --no-dev --optimize-autoloader` | **SSH** |
| `php artisan key:generate`, `migrate --force`, `storage:link` | **SSH** |
| `config:cache`, `route:cache`, `view:cache` | **SSH** |
| `php artisan down` / `up` autour de la mise à jour | **SSH** |

Conséquences directes :

- **`vendor/` n'est jamais transféré par FTP.** Il est installé sur le serveur par Composer
  via SSH. Uploader `vendor/` en FTP représenterait plusieurs milliers d'inodes et un
  transfert très long, sur un hébergement où les inodes sont comptés.
- **`public/build` est compilé en local et transféré par FTP** (Node absent du serveur).
- **`.env` de production n'est jamais transféré** : il est créé et édité une seule fois
  au terminal, puis laissé en place à chaque mise à jour.
- **Ne jamais transférer par FTP** : `/.git`, `/tests`, `/node_modules`, `/vendor`,
  `phpunit.xml`, `storage/logs/*`, `storage/framework/cache/*`, le `.env` local.
- **Mode maintenance obligatoire** pendant le transfert : un upload FTP n'est pas atomique,
  le site servirait un mélange d'ancien et de nouveau code. `php artisan down` **avant** le
  transfert, `php artisan up` après les migrations et la mise en cache.
- L'ordre est impératif : `down` → FTP → `composer install` → `migrate --force` →
  caches → `up`.
- **Sauvegarde de la base avant tout `migrate --force`**, sans exception.

Procédure détaillée et commandes exactes : voir `DEPLOY.md` (livré en Phase 0).
- **PHP 8.3 strictement**, identique au local. L'hébergeur propose jusqu'à 8.5 :
  ne pas l'utiliser (parité local/prod, et dompdf manque de recul sur 8.5).
- **Document root modifiable** → pointera vers `~/optileads-lms/public`.

Contraintes non négociables :

| Contrainte | Conséquence directe |
|---|---|
| Pas de Docker ni conteneur | PHP, MySQL, Apache uniquement |
| Pas de Redis | cache, sessions, queues en driver `database` |
| Pas de worker permanent | cron cPanel chaque minute → `schedule:run` → `queue:work --stop-when-empty --max-time=50` |
| Pas de Node.js sur le serveur | assets Vite compilés en local, `public/build` **versionné** |
| Pas de binaire externe | PDF via dompdf, jamais Chromium ni wkhtmltopdf |
| Pas de vidéo sur le serveur | tout sur Bunny Stream, aucun fichier vidéo dans `storage/` ni `public/` |
| Disque et inodes limités | pas de dépendance superflue, logs rotés (driver `daily`, 14 jours) |

Extensions PHP à activer côté cPanel : `bcmath`, `mbstring`, `intl`, `gd`, `zip`,
`fileinfo`, `pdo_mysql`.

**Aucune instruction Docker ne doit apparaître dans la documentation générée.**

---

## 5. Les 5 règles de portabilité

Le mutualisé sera quitté un jour pour un VPS, puis éventuellement du cloud managé. Ce
passage doit rester une opération d'infrastructure, **jamais une réécriture**.

1. **Aucune logique métier dans un contrôleur ou un composant Livewire.**
   Toute règle métier vit dans `app/Services/`. Les contrôleurs valident (Form Request),
   appellent un service, retournent une vue.

2. **Aucun SQL brut spécifique à MySQL.**
   Eloquent et Query Builder uniquement. Pas de `DB::raw` MySQL-only, pas de
   `GROUP_CONCAT`, pas d'interrogation JSON par syntaxe propriétaire.
   *Objectif : un passage à PostgreSQL reste possible.*
   Larastan est configuré pour signaler ce qui est détectable automatiquement.

3. **Tout accès fichier passe par la facade `Storage`**, avec un disque nommé configuré
   en `.env`. Jamais de `file_get_contents`, jamais de chemin absolu en dur.
   *Objectif : bascule vers S3 ou Cloudflare R2 sans toucher au code.*

4. **Cache, queue et session toujours via les facades**, driver défini en `.env`
   uniquement. Aucune classe ne connaît le driver utilisé.
   *Objectif : activer Redis en changeant une ligne.*

5. **Aucune dépendance à l'environnement d'exécution dans la logique.**
   Pas de `if (app()->environment('production'))` dans un service. Les différences
   passent par la configuration.

---

## 6. Règle Livewire / Alpine — stricte

Livewire fait un aller-retour serveur à chaque interaction. Sur une 3G ouest-africaine à
400 ms de latence, une interface entièrement Livewire devient inutilisable.

> **Règle : Livewire uniquement quand il y a lecture ou écriture en base de données.
> Tout le reste en Alpine.**

| Interaction | Techno | Raison |
|---|---|---|
| Accordéon de module | Alpine | aucune donnée serveur |
| Navigation entre questions de quiz | Alpine | questions chargées d'un coup |
| Cocher une réponse de quiz | Alpine | état local jusqu'à soumission |
| Soumettre le quiz, calculer le score | **Livewire** | écriture en base |
| Filtrer le catalogue | Alpine si < 50 cours, sinon Livewire | éviter un aller-retour inutile |
| Barre de progression visuelle | Alpine | calcul côté client |
| Sauvegarder la position vidéo | **Livewire**, max toutes les 15 s | écriture en base, débounce obligatoire |
| Marquer une leçon terminée | **Livewire** | écriture en base |
| Afficher/masquer un mot de passe | Alpine | purement visuel |
| Menu mobile, modale, onglets | Alpine | purement visuel |

Contraintes complémentaires :

- Tout composant Livewire déclenché au clavier utilise `wire:model.blur` ou
  `wire:model.live.debounce.500ms`. **Jamais `wire:model.live` nu.**
- Le quiz charge **toutes** ses questions en une requête. Interdiction d'un aller-retour
  par question.
- La sauvegarde de progression vidéo est débouncée à 15 s et **tolérante aux coupures** :
  en cas d'échec réseau, la position est mise en file dans `localStorage` et renvoyée
  à la reconnexion.

### Registre des composants Livewire

À tenir à jour à partir de la Phase 3. Un composant sans justification de persistance
est un bug.

| Composant | Opération de persistance justifiant Livewire | Phase |
|---|---|---|
| `App\Livewire\ActivateCode` | Écriture : crée l'inscription et incrémente `used_count` via `AccessCodeRedeemer` (transaction). | 3 |
| `App\Livewire\LessonPlayer` | Écriture : `savePosition` (débounce 15 s) et `markCompleted` sur `lesson_progress`. | 3 |
| `App\Livewire\QuizPlayer` | Écriture : `submit` enregistre `quiz_attempts` (score, réussite). Questions chargées en une fois, sélection et navigation en Alpine — aucun aller-retour par question. | 4 |

> Les composants Filament (Resources, relation managers, widgets) sont aussi des composants
> Livewire, mais côté back-office `/admin` uniquement, hors du front apprenant soumis à la
> règle §6. La saisie de progression vidéo est débouncée à 15 s côté client et **tolérante
> aux coupures** : en cas d'échec réseau, la position est mise en file dans `localStorage`
> (`resources/js/lesson-video.js`) et rejouée à la reconnexion.

---

## 7. Charte OptiLeads

Contrainte d'entrée, jamais à réinventer.

```
Bleu        #0B66C2   primaire, confiance, headers
Orange      #FF7F00   CTA, actions, accents
Jaune       #FFC101   highlights, indicateurs positifs
Cream       #FFF6E8   fonds doux
Charcoal    #1F1F1F   titres
Gris texte  #333333   corps
Bleu nuit   #0A2540   sections CTA sombres uniquement
```

- Police **Sora** (variable), servie depuis `/public/fonts`. **Jamais de CDN Google.**
- Layouts à dominante claire (cream/blanc en premier).
- **Aucun emoji dans l'interface.** Icônes : Heroicons (fournies par Filament) ou SVG inline.
- **Vouvoiement systématique** dans tous les textes.
- Maximum 2 couleurs de marque dominantes par écran.
- Thème Filament personnalisé, **orange en couleur primaire d'action**.

Contexte AOF :

- **Mobile-first strict** (70-80 % du trafic, 3G/4G instable). Testé à 375 px.
- Budget JS initial **sous 100 Ko**.
- Montants **en FCFA uniquement**.

Accessibilité minimale : contraste AA partout, navigation clavier complète sur le lecteur
et le quiz, labels explicites sur tous les champs, `alt` sur toutes les images.

### 7.1 Marque blanche (1 code / N instances)

L'identité (nom, logo, favicon, couleurs, contacts, mentions légales) est **entièrement
pilotée par `.env`** via `config/brand.php` — aucune valeur de marque n'est codée en dur
dans une vue. Défauts = OptiLeads, donc une instance sans configuration reste « OptiLeads ».

- Nom affiché = `APP_NAME`. Nom court (`BRAND_SHORT_NAME`), raison sociale légale
  (`BRAND_LEGAL_NAME`), accroche, slogan, e-mail, ville, région : variables `BRAND_*`.
- **Couleurs** : `BRAND_COLOR_PRIMARY/ACCENT/HIGHLIGHT`. `App\Support\BrandPalette` génère
  l'échelle 50→900 et `<x-brand-theme />` l'injecte en `<style>` **après** `app.css` (donc
  la surcharge). Injecté **uniquement** si `BRAND_COLOR_PRIMARY` est défini → la charte
  compilée d'OptiLeads n'est jamais altérée. Pas de `color-mix()` (webviews anciens) :
  chaque teinte est un hex explicite.
- **Logo** : `<x-brand-mark />` affiche une image (`BRAND_LOGO_IMAGE`) ou, si vide,
  l'emblème recoloré (`<x-brand-emblem />`) + le nom en toutes lettres — aucun fichier à
  produire par instance. Procédure de duplication dans `DEPLOY.md` §11.5.

---

## 8. Rôles, périmètre et autorisation

### 8.1 Trois rôles — et trois seulement

| Rôle | Droits |
|---|---|
| `learner` | créer un compte, activer un code, suivre ses cours, passer les quiz, obtenir un certificat |
| `instructor` | idem + créer/éditer **ses propres** cours, voir ses inscrits et leurs résultats |
| `admin` | accès total, génération de codes, inscription et révocation manuelles |

Seuls `instructor` et `admin` accèdent à Filament (`canAccessPanel()`).

Rôles écartés, **à ne pas anticiper** : super-admin, coach, tuteur, entreprise cliente,
RH, affilié, support.

### 8.2 Dans le périmètre

Pages publiques (accueil, catalogue, fiche formation avec prix affiché et CTA
« Demander l'accès » vers WhatsApp, CGU, mentions légales, confidentialité) · inscription,
connexion, réinitialisation, vérification d'email · activation par code · espace apprenant
(mes formations, reprise, progression, certificats, profil) · lecteur (vidéo Bunny, texte
markdown, PDF téléchargeable) · quiz de fin de module · certificat PDF + page publique de
vérification · panel Filament complet · emails Brevo (bienvenue, accès accordé, certificat
obtenu) · SEO (métadonnées, sitemap, schema.org `Course`).

### 8.3 Hors périmètre — ne pas coder, ne pas préparer, ne pas mentionner

Paiement en ligne, panier, checkout, facturation, abonnement, coupons de réduction.
Gamification, badges, classements, forum, messagerie interne, live, coach IA, génération
de quiz par IA, flashcards, recommandations personnalisées, apprentissage adaptatif,
traduction automatique, application mobile, affiliation, marketplace multi-formateurs,
bac à sable WordPress, simulateur de campagnes, CRM d'entraînement, portfolio de compétences.

### 8.4 Sécurité d'accès au contenu — point le plus critique

**Il n'existe aucun garde-fou au niveau de la base.** Une Policy oubliée sur une seule
requête et le contenu payant fuite.

- Aucune requête retournant du contenu de leçon ne s'exécute sans `LessonPolicy::view`.
  **Interdiction d'un `Lesson::find($id)` non protégé** dans un contrôleur ou un composant
  Livewire.
- Toutes les routes de l'espace apprenant : middleware `auth` **et** autorisation explicite.
- Les `bunny_video_id` **ne sont jamais rendus dans le HTML**. Le player reçoit une URL
  signée valable 4 h, générée côté serveur **après** autorisation (`BunnySigner`).
- Les PDF de cours sont dans `storage/app/private`, servis par un contrôleur qui vérifie
  l'autorisation avant de streamer (`AssetController`).
- Filament : chaque Resource définit `canViewAny`, `canCreate`, `canEdit`, `canDelete`.
  Un `instructor` ne voit que ses cours (scope global).

**Quatre tests Pest obligatoires en Phase 0, avant toute interface**
(`tests/Feature/Security/`) :

1. Visiteur non authentifié sur une leçon non-preview → **403**.
2. `learner` authentifié mais non inscrit sur cette leçon → **403**.
3. `learner` sur `/admin` → **403**.
4. Deux activations concurrentes du même code à 1 usage restant → **exactement une**
   inscription.

---

## 9. Mécanisme d'accès par code

Le code d'accès **remplace le paiement**.

Flux : l'admin génère un code depuis Filament (formation, nombre d'usages, expiration,
libellé interne type « Cohorte SEO janvier — Société X ») → transmission hors plateforme →
l'apprenant crée son compte et saisit le code → inscription active créée, `used_count`
incrémenté → email « accès accordé ».

Règles :

- La validation vit dans `App\Services\AccessCodeRedeemer`. **Jamais** dans un contrôleur
  ni un composant Livewire.
- **Atomicité** : `DB::transaction()` avec `lockForUpdate()` sur la ligne du code. Deux
  requêtes concurrentes sur le dernier usage ne produisent jamais deux inscriptions.
- Un code déjà utilisé par le même utilisateur retourne « vous avez déjà accès à cette
  formation », **pas une erreur**.
- **Rate limiting** : `RateLimiter` Laravel, 5 tentatives par utilisateur et par heure.
  Sans cela le brute force est trivial.
- **Format** : 10 caractères, alphabet sans ambiguïté (ni `O`/`0`, ni `I`/`1`/`l`),
  généré avec `random_bytes`. Exemple : `OPT7K4M9XQ`.
- Chaque utilisation journalisée dans `access_code_redemptions` : on doit pouvoir répondre
  à « qui a utilisé ce code et quand ».
- Resource Filament affichant usages restants, bénéficiaires, et permettant la désactivation.

**Préparation du paiement futur** : les tables `orders` et `coupons` sont créées avec leurs
modèles, mais **aucun code applicatif ne les utilise** et **aucune Resource Filament ne les
expose**. `enrollments.order_id` est nullable.

---

## 10. Modèle de données

Toutes les tables : `id` bigint auto-increment, `created_at`, `updated_at`. Clés étrangères
contraintes. Index sur toutes les colonnes de jointure et de filtre.

```
users                (table Laravel étendue)
  name, email, email_verified_at, password, phone, company, country,
  role enum(learner|instructor|admin) default 'learner', avatar_path

courses
  slug (unique), title, subtitle, description (longText, markdown),
  cover_path, price_fcfa (unsignedInteger),      -- vitrine uniquement
  level enum(debutant|intermediaire|avance),
  status enum(draft|published|archived) default 'draft',
  instructor_id -> users, duration_minutes (unsignedInteger), published_at

modules
  course_id -> courses, position (unsignedSmallInteger), title
  unique(course_id, position)

lessons
  module_id -> modules, position (unsignedSmallInteger), title,
  type enum(video|text|pdf|quiz),
  bunny_video_id (nullable), content (longText nullable, markdown),
  asset_path (nullable), duration_seconds (unsignedInteger default 0),
  is_preview (boolean default false)
  unique(module_id, position)

access_codes
  code (unique, char 10), course_id -> courses,
  max_uses (unsignedSmallInteger default 1), used_count (unsignedSmallInteger default 0),
  expires_at (nullable), is_active (boolean default true),
  label (string), created_by -> users

access_code_redemptions
  access_code_id -> access_codes, user_id -> users, redeemed_at
  unique(access_code_id, user_id)

enrollments
  user_id -> users, course_id -> courses,
  status enum(active|expired|revoked) default 'active',
  source enum(access_code|manual|purchase),
  access_code_id (nullable), order_id (nullable),
  enrolled_at, expires_at (nullable = accès à vie)
  unique(user_id, course_id)

lesson_progress
  user_id -> users, lesson_id -> lessons,
  last_position_seconds (unsignedInteger default 0), completed_at (nullable)
  unique(user_id, lesson_id)

quizzes
  lesson_id -> lessons (unique), pass_score_pct (unsignedTinyInteger default 70),
  max_attempts (unsignedTinyInteger default 3)

quiz_questions
  quiz_id -> quizzes, position, prompt (text), explanation (text nullable)

quiz_options
  quiz_question_id -> quiz_questions, label (text), is_correct (boolean)

quiz_attempts
  user_id -> users, quiz_id -> quizzes,
  score_pct (unsignedTinyInteger), passed (boolean), answers (json), attempted_at

certificates
  user_id -> users, course_id -> courses,
  serial (unique, format OPT-YYYY-XXXXXX), issued_at, pdf_path
  unique(user_id, course_id)

-- Créées, non utilisées (préparation paiement) :
orders     user_id, course_id, amount_fcfa, provider, provider_ref (unique),
           status enum(pending|paid|failed|refunded), paid_at
coupons    code (unique), discount_pct, discount_fcfa, max_uses, used_count,
           expires_at, is_active
```

Chaque modèle a sa **factory**. Le **seeder de démo** crée : 1 admin, 1 instructor,
2 learners, 1 cours publié (2 modules, 5 leçons dont 1 preview, 1 quiz de 4 questions),
2 codes d'accès.

> `quiz_attempts.answers` est en colonne `json` mais **n'est jamais interrogé en SQL**
> (règle de portabilité n°2) : lecture/écriture via le cast Eloquent uniquement.

---

## 11. Arborescence

```
app/
  Filament/
    Resources/          CourseResource, ModuleResource, LessonResource,
                        AccessCodeResource, UserResource, EnrollmentResource
    Widgets/            inscriptions récentes, codes bientôt épuisés
  Http/
    Controllers/        CatalogController, CourseController, LearnController,
                        AccessCodeController, CertificateController, AssetController
    Middleware/  Requests/
  Livewire/
    Player/  Quiz/  ActivateCode/
  Models/
  Policies/             CoursePolicy, LessonPolicy, EnrollmentPolicy, CertificatePolicy
  Services/
    AccessCodeRedeemer.php    BunnySigner.php
    CertificateGenerator.php  ProgressTracker.php
  Mail/
database/  migrations/ factories/ seeders/
resources/
  views/  layouts/ public/ learn/ emails/ pdf/ errors/
  css/  js/
routes/web.php
tests/
  Feature/  Unit/  Feature/Security/    (les 4 tests du §8.4)
public/
  build/   assets Vite compilés, VERSIONNÉS
  fonts/   Sora
CLAUDE.md
DEPLOY.md
```

---

## 12. Conventions de code

- **Code en anglais** (classes, méthodes, variables, colonnes, commits).
  **Interface et contenu en français.**
- **URLs en français** : `/formations`, `/apprendre`, `/activer`, `/certificats`,
  `/verifier/{serial}`.
- Traductions dans `lang/fr/`. **Aucune chaîne en dur dans les vues.**
- Commits en français, format `type: description` (`feat:`, `fix:`, `chore:`, `test:`).
- **Une branche par phase** : `phase-0-fondations`, `phase-1-public`, etc.
  Merge sur `main` après validation explicite.
- Migrations : une par table ou par groupe cohérent. **Jamais de modification d'une
  migration déjà appliquée en production.**
- Pas de commentaire qui paraphrase le code. Commenter uniquement **le pourquoi** d'une
  décision non évidente. Les hypothèses prises sans validation sont marquées
  `// HYPOTHÈSE : ...`.

---

## 13. Phases d'exécution

Après chaque phase : arrêt, liste de ce qui a été livré, mode de vérification, attente du
feu vert. **Une phase = un commit propre.**

| Phase | Contenu | Validation |
|---|---|---|
| **0 — Fondations et sécurité** ✅ | Bootstrap MAMP, base, Laravel, Filament thémé, Tailwind + Sora, migrations complètes, modèles/relations/factories, seeder démo, Policies, `AccessCodeRedeemer`, les 4 tests de sécurité, Pint + Larastan, CLAUDE.md + DEPLOY.md. **Aucune interface publique.** | `migrate:fresh --seed` passe, `localhost:8000` répond, Pest vert, Pint et Larastan sans erreur |
| **1 — Public et auth** ✅ | Accueil, catalogue, fiche formation (programme visible, leçon preview accessible, autres verrouillées, prix FCFA, CTA WhatsApp). Breeze complet. CGU, mentions légales, confidentialité | création de compte OK, leçon non-preview → 403 même en forçant l'URL |
| **2 — Panel Filament** ✅ | Resources Course/Module/Lesson avec réordonnancement, upload vidéo vers Bunny depuis le formulaire, publication, Resource AccessCode, Resource User, inscription/révocation manuelles, widgets | créer une formation complète et un code à 5 usages sans toucher à la base — **validé par tests Filament** (`tests/Feature/Filament/`) ; upload direct Bunny en attente des identifiants (saisie manuelle du GUID en attendant) |
| **3 — Activation et apprentissage** ✅ | Page d'activation (rate limiting, messages explicites), email « accès accordé », espace apprenant, player Bunny signé, reprise à la position, progression débouncée et tolérante aux coupures, navigation, markdown, PDF protégé, registre Livewire §6 tenu | activer un code, regarder 30 s, fermer, revenir → reprise exacte. Réseau coupé/rétabli → aucune perte — **validé** (activation, progression et lecteur pilotés en direct via les composants Livewire ; player Bunny en attente des identifiants → placeholder gracieux) |
| **4 — Quiz et certificat** ✅ | Quiz QCM (chargement unique, état Alpine, soumission Livewire), score, tentatives limitées, explications. Certificat PDF dompdf à la charte, `/verifier/{serial}`, email « certificat obtenu ». Gestion du quiz dans Filament (questions + options via repeater) | terminer un cours, réussir le quiz, télécharger le PDF, la page de vérification confirme — **validé** (parcours quiz→certificat→PDF→vérification par tests + page de vérification live) |
| **5 — Durcissement et mise en ligne** ⏳ | Tests Pest sur les 3 parcours critiques ✅, SEO + sitemap ✅, schema.org `Course` ✅, accessibilité ✅, états d'erreur et vides ✅, pages 403/404/419/429/500/503 à la charte ✅. **Reste : premier déploiement LWS** (bloqué — nécessite accès SSH/FTP + base + Bunny + Brevo) | parcours complet fonctionnel en production |
| **6 — Exploitation** | Sauvegardes auto, rotation des logs, notification d'erreur, export et suppression de compte, doc d'exploitation | une restauration de sauvegarde testée et documentée |
| **7 — Paiement en ligne** | **Hors périmètre.** Décision séparée après validation du contenu par de vrais apprenants | — |

---

## 14. Exploitation (mise en place Phase 6, détaillée dans DEPLOY.md)

- **Sauvegarde base quotidienne** (`spatie/laravel-backup` ou `mysqldump` en cron),
  rétention 14 jours, copie hors serveur. **Sauvegarde manuelle obligatoire avant tout
  `migrate --force`.**
- **Logs** : driver `daily`, rétention 14 jours. Sans rotation, les inodes du mutualisé
  saturent.
- **Erreurs** : page 500 à la charte, notification email à l'admin sur exception non gérée,
  throttlée pour éviter le flood.
- **Données personnelles** : politique de confidentialité, export des données d'un
  utilisateur et suppression de compte depuis Filament.
- **Contenu initial** : le seeder de démo n'est **pas** du contenu de production. La
  première formation réelle est créée via Filament, pas en base.

---

## 15. Definition of Done — à valider à chaque phase

- [ ] `./vendor/bin/pest` vert
- [ ] `./vendor/bin/pint --test` sans correction en attente
- [ ] Larastan niveau 6 : 0 erreur
- [ ] Aucune valeur codée en dur qui devrait être dans `.env`
- [ ] Toute route retournant du contenu protégé a son test d'autorisation
- [ ] Les 5 règles de portabilité (§5) respectées
- [ ] La règle Livewire/Alpine (§6) respectée et le registre à jour
- [ ] Testé à 375 px de large
- [ ] Interface en français, vouvoiement, sans emoji
- [ ] Aucune dépendance ajoutée sans vérification de compatibilité mutualisé
- [ ] CLAUDE.md et DEPLOY.md à jour
