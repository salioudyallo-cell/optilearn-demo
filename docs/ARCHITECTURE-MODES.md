# Architecture — modes de plateforme (Commercial B2C / Entreprise B2B)

**Date** : 30 juillet 2026
**Statut** : analyse préalable — **aucune implémentation avant validation**
**Objet** : une base logicielle unique servant deux modèles économiques, sans fork de code

---

## 1. Diagnostic de l'existant

Le produit est déjà, sans le savoir, une **plateforme en mode Entreprise dégradé** : l'accès
se fait par **code** ou **inscription manuelle**, il n'y a **aucun paiement**, et le
catalogue affiche des prix qui ne servent à rien fonctionnellement. Autrement dit :

| Brique | État | Mode concerné |
|---|---|---|
| Cœur pédagogique (cours, modules, leçons, quiz, certificats, progression) | **Fait** | **Commun aux deux** |
| Accès par code / inscription manuelle | **Fait** | Surtout Entreprise |
| `EnrollmentSource` : code / manuel / **achat (réservé)** | **Fait** | L'abstraction d'accès existe déjà |
| Tables `orders` / `coupons` | Créées, **inutilisées** | Commercial |
| Paiement, panier, promotions | **Absent** | Commercial |
| Organisations, groupes, affectation, import CSV, tableaux RH | **Absent** | Entreprise |
| Réglage de « mode plateforme » | **Absent** | Les deux |

**Conclusion structurante** : le **cœur d'apprentissage est déjà mode-agnostique**. Ce qui
diffère entre les deux modèles, ce n'est **jamais** la façon d'apprendre — c'est uniquement
la façon d'**acquérir l'accès** (achat vs affectation) et ce que l'**interface montre**. Ça
change tout : il ne faut surtout pas dupliquer la plateforme, il faut isoler la fine couche
qui varie.

---

## 2. Principe directeur : le mode n'est qu'un préréglage de capacités

L'erreur classique serait de semer des `if (mode === 'commercial')` dans tout le code. Ça
devient ingérable et bloque tout troisième modèle.

**La bonne approche : découpler le *mode* des *capacités*.**

- Une **capacité** (feature flag) est une fonctionnalité activable indépendamment :
  `catalogue_public`, `tarification`, `panier`, `paiement_en_ligne`, `promotions`,
  `avis`, `inscription_libre`, `organisations`, `groupes`, `affectation`,
  `import_massif`, `tableaux_de_bord_rh`…
- Un **mode** n'est qu'un **préréglage nommé** de ces capacités.

```
Commercial  = { catalogue_public, tarification, panier, paiement_en_ligne,
                promotions, avis, inscription_libre }
Entreprise  = { organisations, groupes, affectation, import_massif,
                tableaux_de_bord_rh }
```

Le code ne teste **jamais le mode**, il teste une **capacité** :
`Platform::allows('paiement_en_ligne')`. Conséquence directe :

- ajouter un **3ᵉ modèle** (abonnement, marketplace de formateurs, hybride…) = définir un
  nouveau préréglage, **zéro réécriture** ;
- on peut même autoriser un client à **composer** ses capacités hors préréglage (ex. une
  entreprise qui veut quand même des avis internes) sans toucher au code.

C'est exactement le patron que vous avez déjà adopté pour le **pilote vidéo** : une
interface, des implémentations interchangeables, un réglage. On applique la même
philosophie à l'échelle de la plateforme.

---

## 3. Où vit le réglage, et à quelle granularité

### Décision : le mode est un réglage **par instance**, pas par utilisateur

Votre stratégie produit est « **1 code / N instances** » (chaque client a son
installation). Le mode est donc une **propriété de l'installation**, choisie une fois :
une instance est commerciale **ou** entreprise. C'est le cas simple, robuste, et il couvre
100 % du besoin exprimé.

> **À ne pas confondre avec le multi-tenant.** Faire cohabiter, dans **une seule
> instance**, plusieurs organisations dont certaines commerciales et d'autres entreprises,
> est une architecture radicalement plus lourde (isolation des données par tenant, sous-
> domaines, facturation par tenant). Ce n'est **pas** ce que le cahier demande. Je le
> signale comme évolution future majeure, à ne pas embarquer maintenant.

### Stockage : une table `settings` en base, éditable dans Filament

Le mode n'est pas une constante technique (`.env`) mais un **réglage métier** que
l'administrateur change depuis l'interface. Recommandation :

- une petite table `settings` clé/valeur (ou le paquet `spatie/laravel-settings`) ;
- exposée dans une page Filament **« Configuration de la plateforme »** ;
- **mise en cache** (le mode est lu à presque chaque requête) et cache invalidé à
  l'enregistrement ;
- valeur par défaut sûre : **Entreprise** (aucun prix, aucun paiement exposé — cohérent
  avec l'état actuel et sans risque de fuite commerciale).

---

## 4. Les couches à créer

### 4.1 Le noyau de décision (le seul point de vérité)

- `enum PlatformMode { Commercial, Enterprise }` (extensible).
- Un service `PlatformConfig` / façade `Platform` qui lit le mode en cache et expose :
  - `Platform::mode()`
  - `Platform::allows('capacite')` — la seule méthode que le reste du code utilise
  - `Platform::isCommercial()` / `isEnterprise()` (sucre syntaxique)
- Une table de correspondance **mode → capacités** (un simple tableau de config), pour
  que les préréglages soient lisibles et modifiables en un endroit.

### 4.2 L'application des capacités — **toujours côté serveur d'abord**

Masquer dans l'UI ne suffit **jamais** : un apprenant en mode Entreprise ne doit pas
pouvoir atteindre une page de paiement **même en tapant l'URL**. Trois niveaux, complémentaires :

| Niveau | Mécanisme | Exemple |
|---|---|---|
| **Route** | Middleware `feature:paiement_en_ligne` | `/panier`, `/checkout` renvoient 404 hors mode commercial |
| **Vue** | Directive Blade `@feature('tarification')` | Le prix, le bouton « Acheter », le panier ne sont pas rendus |
| **Back-office** | `shouldRegisterNavigation()` / `canAccess()` des ressources Filament | La gestion des Organisations n'apparaît qu'en mode entreprise |

Règle d'or : **la vue reflète une décision déjà prise et déjà appliquée côté serveur**,
elle ne la prend pas.

### 4.3 La stratégie d'octroi d'accès (patron Strategy, comme la vidéo)

Comment un apprenant obtient l'accès à une formation diffère selon le mode. On abstrait :

```
interface AccessGrantStrategy {
    fn grantsAutomaticAccess(): bool          // achat → accès immédiat ?
    fn enroll(User, Course, context): Enrollment
}

PurchaseAccessStrategy    (Commercial)  → après paiement confirmé, EnrollmentSource::Purchase
AssignmentAccessStrategy  (Enterprise)  → affectation individuelle/groupe, Source::Manual
CodeRedemptionStrategy    (les deux)    → existe déjà, fonctionne dans les deux modes
```

`EnrollmentSource` couvre **déjà** ces trois cas : l'abstraction est amorcée, il ne reste
qu'à formaliser la stratégie. Le reste (progression, quiz, certificat) est **strictement
identique** — on ne le touche pas.

### 4.4 Modèle de données — additif, jamais destructif

| Mode | Tables à activer / créer | Réutilisation |
|---|---|---|
| **Commercial** | `orders`, `coupons` (existent), `payments`, `reviews`, panier (session ou table) | `Enrollment` inchangé, créé après paiement |
| **Entreprise** | `organizations`, `groups`, `group_user`, `course_group` (affectation) | `Enrollment` inchangé, créé par affectation |
| **Commun** | — | `courses`, `modules`, `lessons`, `quizzes`, `certificates`, `enrollments`, `progress` |

Point clé : **on n'ajoute jamais une colonne « mode » sur les cours ou les utilisateurs**.
Le mode est global à l'instance ; le modéliser sur chaque ligne créerait des incohérences
et des fuites (un cours « commercial » visible dans une instance entreprise).

---

## 5. Cartographie complète — capacité par capacité

| Élément d'interface / fonction | Capacité | Commercial | Entreprise |
|---|---|---|---|
| Catalogue public (non connecté) | `catalogue_public` | ✅ | ❌ (catalogue = formations affectées, après connexion) |
| Affichage des prix | `tarification` | ✅ | ❌ |
| Bouton « Acheter » / panier | `panier` | ✅ | ❌ |
| Paiement en ligne | `paiement_en_ligne` | ✅ | ❌ |
| Codes promo / promotions | `promotions` | ✅ | ❌ |
| Avis et notes | `avis` | ✅ | ⚪ (option : avis internes) |
| Inscription libre (auto-création de compte) | `inscription_libre` | ✅ | ❌ (comptes créés/invités par l'organisation) |
| Gestion des organisations | `organisations` | ❌ | ✅ |
| Groupes d'apprenants | `groupes` | ❌ | ✅ |
| Affectation de formations (indiv./groupe) | `affectation` | ❌ | ✅ |
| Import massif CSV/Excel | `import_massif` | ⚪ (utile aussi en B2C cohortes) | ✅ |
| Invitation par e-mail | `invitation` | ⚪ | ✅ |
| Tableaux de bord RH / rapports | `tableaux_de_bord_rh` | ⚪ (analytics vendeur) | ✅ |
| Certificats | `certificats` | ✅ | ✅ |
| SSO / LDAP / Azure AD | `sso` | ❌ | 🔮 (évolution future) |

✅ activé · ❌ masqué et **bloqué serveur** · ⚪ optionnel selon préréglage · 🔮 futur

Ce qu'un apprenant en mode Entreprise ne doit **jamais** voir ni atteindre : prix, panier,
paiement, promotions. Garanti par le middleware de route, pas seulement par l'UI.

---

## 6. Expérience utilisateur cohérente dans les deux modes

- **Même design system, mêmes composants** : on ne fabrique pas deux thèmes. Les
  composants (carte de formation, en-tête, tableau de bord) rendent conditionnellement
  leurs éléments commerciaux via `@feature(...)`.
- **La carte de formation** : en commercial elle montre prix + « Acheter » ; en entreprise
  elle montre « Affectée le… » + progression. Même composant, même mise en page, blocs
  conditionnels.
- **Le tableau de bord apprenant est identique** dans les deux modes (mes formations,
  reprise, certificats) — c'est le cœur, il ne change pas.
- **Lien avec l'interrupteur d'indexation déjà en place** : le mode Entreprise devrait
  forcer `SITE_INDEXABLE=false` (un intranet de formation n'a rien à faire dans Google) ;
  le mode Commercial l'autorise. Les deux réglages se coordonnent naturellement.

---

## 7. Extensibilité — préparer les modèles futurs sans les coder

Parce que le code teste des **capacités** et non des modes, ajouter un modèle revient à
déclarer un préréglage :

- **Abonnement (SaaS learning)** : `catalogue_public + tarification + paiement_récurrent`
  → une nouvelle capacité `abonnement`, une nouvelle stratégie d'accès (accès tant que
  l'abonnement est actif).
- **Marketplace de formateurs** : `catalogue_public + tarification + commissions +
  multi-vendeurs` → nouvelles capacités, cœur inchangé.
- **Hybride** (catalogue public payant **et** espaces entreprise privés) → c'est le point
  de bascule vers le vrai multi-tenant, à traiter séparément.

Aucun de ces ajouts ne demande de réécrire l'existant : c'est la promesse de l'approche
par capacités.

---

## 8. Risques et points de vigilance

| Risque | Parade |
|---|---|
| **Fuite commerciale en mode entreprise** (un prix qui s'affiche) | Application **côté serveur** (middleware + directives), tests automatisés par mode |
| **Contournement d'URL** (apprenant entreprise qui atteint `/checkout`) | Middleware `feature:` sur toutes les routes commerciales, testé |
| **Double logique non testée** | Suite de tests exécutée **dans les deux modes** (jeu de tests paramétré) |
| **Cache du réglage obsolète** | Invalidation à l'enregistrement + `config:cache` documenté |
| **Dérive du code** (`if mode` qui réapparaît) | Interdire l'accès direct au mode ; une seule façade `Platform::allows()` |
| **Modèle de données pollué** | Aucune colonne « mode » sur les entités ; le mode reste global |
| **SEO** | Mode entreprise = non indexable forcé (déjà outillé) |

---

## 9. Recommandation et plan par étapes

### Approche retenue
Une **plateforme unique**, un **mode par instance** stocké en base et éditable dans
Filament, un **noyau de capacités** (`Platform::allows()`) appliqué **côté serveur**
(routes + vues + Filament), et un **patron Strategy** pour l'octroi d'accès — dans la
continuité exacte du pilote vidéo déjà en place.

### Séquencement proposé (à implémenter après votre validation)

1. **Socle du mode** (petit, sans risque) : table `settings`, `PlatformMode`, façade
   `Platform`, directive `@feature`, middleware `feature:`, page Filament « Configuration
   de la plateforme ». Rendre l'app pilotable **sans encore rien masquer**.
2. **Câbler le mode Entreprise** (le plus proche de l'existant) : masquer prix/panier/
   paiement quand la capacité est absente ; le produit devient un intranet de formation
   propre. **Livrable vendable en B2B rapidement.**
3. **Organisations & groupes** : entités, affectation individuelle/groupe, import CSV,
   tableaux de bord RH. Complète le mode Entreprise.
4. **Mode Commercial** : activer `orders`/`coupons`, panier, paiement (Wave/Orange Money/
   carte), avis. C'est le gros morceau, mais il arrive **derrière** un socle déjà prouvé.
5. **Stratégies d'accès formalisées** + suite de tests exécutée dans les deux modes.

> Ordre volontaire : on livre d'abord ce qui est **le plus proche du code actuel** (le
> B2B), on repousse le plus lourd (paiement) — cohérent avec votre logique « crescendo ».

### Ce que l'on ne fait PAS maintenant
Le multi-tenant (plusieurs organisations mixtes dans une instance), le SSO/LDAP, les
abonnements récurrents. Ils sont **rendus possibles** par cette architecture, mais ne
doivent pas être embarqués tant que le besoin n'est pas là.

---

## 10. Synthèse en une phrase

Le cœur d'apprentissage est déjà commun aux deux modèles ; il suffit d'isoler la **couche
d'acquisition** (achat vs affectation) derrière un **système de capacités piloté par un
réglage d'instance**, appliqué côté serveur — ce qui évite tout fork, garde une seule
expérience cohérente, et rend l'ajout d'un futur modèle aussi simple que déclarer un
nouveau préréglage.
