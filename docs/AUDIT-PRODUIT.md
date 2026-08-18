# Audit produit — OptiLeads Formation

**Date** : 30 juillet 2026
**Périmètre** : plateforme LMS en production sur `formation.opti-leads.com`
**Angle d'analyse** : préparation à un lancement à l'échelle / levée de fonds
**État de référence** : Laravel 13.21 · Filament 5.7 · Livewire 4.3 · 100 tests · PHPStan niveau 6 · hébergement mutualisé LWS

---

## Avertissement de cadrage

Trois constats structurants conditionnent tout le reste de cet audit. Ils ne sont pas des
détails d'implémentation mais des **plafonds de verre** :

1. **L'hébergement mutualisé LWS est incompatible avec une mise à l'échelle.** ~20-30
   processus PHP pour tout le compte, SSH filtré par IP, FTPS instable, pas de Redis, pas
   de Node, OPcache non maîtrisé, CGU restreignant le streaming vidéo. Aucune des
   fonctionnalités « croissance » de cet audit ne tiendra sur cette infrastructure.
2. **Il n'y a aucun paiement en ligne.** L'accès se fait par code distribué manuellement.
   C'est un choix assumé au démarrage, mais cela signifie qu'il n'existe **aucun tunnel de
   conversion mesurable** — donc rien à optimiser, rien à présenter à un investisseur.
3. **Il n'y a aucune mesure.** Zéro événement tracké, zéro tableau de bord analytique.
   Impossible aujourd'hui de répondre à « combien d'apprenants actifs ? », « quel taux de
   complétion ? », « où décrochent-ils ? ». C'est le point le plus grave de tout l'audit,
   car il rend toute décision produit arbitraire.

Le socle technique, en revanche, est **au-dessus de la moyenne du marché** : sécurité par
policies systématiques, URLs signées, 100 tests, analyse statique niveau 6, sauvegardes
testées, RGPD implémenté. La dette n'est pas dans la qualité du code, elle est dans le
**périmètre produit** et l'**infrastructure**.

Enfin, une note d'honnêteté : le cahier des charges initial excluait explicitement la
gamification, le forum, le coach IA, l'application mobile et l'affiliation. Je les
réintègre ici quand ils deviennent stratégiquement nécessaires, en le signalant — libre à
vous de maintenir l'exclusion.

---

## 1. Front Office (application utilisateur)

| Élément | Ce qui manque | Pourquoi c'est important | Priorité | Bénéfice utilisateur | Impact business |
|---|---|---|---|---|---|
| **Parcours utilisateur** | Aucun tunnel d'acquisition : « Demander l'accès » renvoie vers l'inscription, sans formulaire de demande ni suivi | Le visiteur intéressé disparaît sans laisser de trace exploitable | **Critique** | Sait quoi faire après l'aperçu | Chaque visiteur perdu est un lead perdu ; aucun pipeline commercial |
| **Onboarding** | Aucun. Après inscription, l'apprenant arrive sur un espace vide sans guidage | 40 à 60 % de l'abandon d'un LMS se joue dans les 3 premières minutes | **Critique** | Comprend immédiatement quoi faire | Rétention à 7 jours ; c'est le levier n°1 de votre objectif « 50 apprenants actifs » |
| **Inscription** | Pas de connexion sociale, pas de pré-remplissage depuis un code d'accès | Sur mobile en Afrique de l'Ouest, un formulaire long fait chuter la conversion de 20-30 % | Haute | Moins de friction | Taux de création de compte |
| **Authentification** | Pas de 2FA (même pour les admins), pas de « rester connecté » explicite, pas de journal de connexions | Un compte admin compromis = tout le catalogue exfiltré | Haute | Confiance | Risque de sécurité et de réputation |
| **Tableau de bord apprenant** | Pas de bandeau « Reprendre où vous en étiez » global, pas de prochaine action suggérée, pas d'échéance | La reprise est le moment de vérité de la rétention | **Critique** | Reprend en 1 clic | Complétion, donc valeur perçue et bouche-à-oreille |
| **Recherche** | **Aucune recherche**, ni catalogue ni contenu | Au-delà de 10 formations, la navigation devient inutilisable | Haute | Trouve vite | Découverte, donc ventes croisées |
| **Filtres** | Filtre par niveau seulement, côté client, sans pagination : tout le catalogue est chargé en une requête | Ne passe pas l'échelle (mémoire, temps de rendu, données mobiles) | Haute | Rapidité sur réseau faible | Coût d'infrastructure, taux de rebond |
| **Notifications** | Aucune notification in-app ; seuls 2 e-mails transactionnels existent | L'apprenant inactif n'est jamais relancé | **Critique** | Sait qu'il a repris du retard | Réengagement = premier levier de rétention à coût nul |
| **Chat / support** | Aucun. Ni chat, ni FAQ, ni centre d'aide, ni formulaire de contact | Un apprenant bloqué abandonne au lieu de demander de l'aide | Haute | Débloqué en minutes | Réduction du churn, réduction du support téléphonique |
| **Paiement** | Absent (choix assumé). Les tables `orders` et `coupons` existent mais **aucun code ne les utilise** | Sans paiement : pas de revenu automatisé, pas de tunnel mesurable, aucune scalabilité commerciale | **Critique** (V1) | Achète quand il est motivé | Débloque le revenu récurrent ; indispensable à une levée |
| **Géolocalisation** | Aucune (non pertinente ici, hors adaptation devise/langue par pays) | Marginal pour un LMS | Faible | — | — |
| **Performance** | Pas de CDN, pas de lazy-loading systématique, pas de budget de performance, images non optimisées en plusieurs formats | 75 % de mobile sur réseau instable : chaque 100 ko compte | Haute | Chargement rapide | Rebond, complétion, coût data de l'apprenant |
| **Responsive** | Implémenté et correct | — | — | — | — |
| **Accessibilité** | Partielle : focus visible et libellés présents, mais pas d'audit contrastes, pas de navigation clavier vérifiée sur le lecteur, pas de sous-titres vidéo | Exclut une partie des apprenants ; obligation légale sur marchés publics | Haute | Accessible à tous | Éligibilité aux appels d'offres publics et grands comptes |
| **Personnalisation** | Aucune : pas de recommandations, pas de parcours suggéré, pas de préférences | Le catalogue est identique pour tous | Moyenne | Contenu pertinent | Ventes croisées, engagement |
| **Fidélisation** | Aucun mécanisme : pas de série de jours, pas de rappel, pas de récompense de progression | La complétion moyenne d'un LMS sans relance est de 5-15 % | Haute | Motivation à revenir | Complétion = renouvellement et recommandation |
| **Gamification** | Aucune (exclue au cahier des charges) | Badges et progression visible augmentent la complétion de 20-40 % en LMS B2C | Moyenne | Sentiment d'avancement | À reconsidérer : l'exclusion coûte de la complétion |
| **Sécurité** | Bon niveau (policies systématiques, URLs signées, limitation de débit à la connexion, contenu hors dossier public). Manque : 2FA, en-têtes de sécurité (CSP), journal d'audit | Le contenu payant est l'actif ; sa fuite détruit le modèle | Haute | Confiance | Protection de l'actif principal |
| **Gestion des erreurs** | Pages 403/404/419/429/500/503 à la charte + notification admin throttlée. Bien traité | — | — | — | — |
| **États vides** | Présents mais passifs : ils informent sans proposer d'action | Un état vide est une opportunité de conversion | Moyenne | Sait quoi faire | Activation |
| **Loading states** | Partiels (`wire:loading` sur certains boutons). Pas de squelettes de chargement | Sur réseau lent, l'absence de retour visuel est perçue comme une panne | Moyenne | Perception de rapidité | Abandon en cours de chargement |
| **Mode hors ligne** | Progression mise en file dans `localStorage` (bien vu). Mais aucun contenu consultable hors ligne, pas de PWA | Coupures fréquentes sur le marché cible | Haute | Apprend sans connexion | Différenciant fort en Afrique de l'Ouest |
| **Internationalisation** | **Français uniquement**, aucun mécanisme de changement de langue, pas de gestion de devise | Bloque l'Afrique anglophone et le Maghreb | Moyenne (Haute si international) | Langue maternelle | Taille du marché adressable |
| **SEO** | Bien couvert : métadonnées, Open Graph, `sitemap.xml`, `schema.org/Course`, `robots.txt`. Manque : blog/contenu, données structurées `FAQPage`, maillage interne | Le SEO est le canal d'acquisition le moins cher sur ce marché | Haute | Trouve la plateforme | CAC divisé par 3 à 5 vs publicité |

---

## 2. Back Office (administration)

### Gestion des utilisateurs

| Fonctionnalité | Utilité | Valeur métier | Priorité | Dépendances |
|---|---|---|---|---|
| Rôles / permissions | 3 rôles en dur (learner/instructor/admin) — suffisant aujourd'hui, insuffisant dès qu'un assistant ou un correcteur apparaît | Délégation opérationnelle | Moyenne | — |
| Suspension de compte | **Absente** : on ne peut que supprimer (irréversible) | Gestion des impayés et des abus sans destruction de données | Haute | — |
| Vérification / KYC | Non pertinent (pas de paiement, pas de flux financier utilisateur) | — | Faible | Paiement |
| Historique par utilisateur | **Absent** : aucune vue chronologique (connexions, activations, tentatives, certificats) | Support client et résolution de litiges | Haute | Journal d'audit |
| Impersonation | **Absente** : impossible de voir la plateforme « comme » un apprenant | Réduit de moitié le temps de résolution d'un ticket | Haute | Journal d'audit (traçabilité obligatoire) |
| Import CSV en masse | **Absent** : chaque apprenant est créé à la main | Indispensable pour vendre des cohortes entreprise | Haute | — |

### Gestion du contenu

| Fonctionnalité | Utilité | Valeur métier | Priorité | Dépendances |
|---|---|---|---|---|
| CRUD formations/modules/leçons | Complet et bien fait (Filament, relations imbriquées, réordonnancement) | — | — | — |
| Médiathèque | **Absente** : les vidéos se déposent par FTP, aucune vue des fichiers, aucun lien fichier↔leçon visible | Autonomie du formateur, évite les fichiers orphelins qui consomment le quota | Haute | — |
| Catégories / tags | **Absents** : seul le niveau existe | Navigation, SEO, recommandations | Haute | Recherche |
| Contenus dynamiques (CMS) | **Absent** : page d'accueil, mentions légales et CGU sont codées en dur dans les vues | Chaque correction de texte exige un déploiement complet | Moyenne | — |
| Prévisualisation avant publication | **Absente** | Évite de publier une formation cassée | Moyenne | — |
| Versionnement du contenu | Absent | Traçabilité pédagogique, retour arrière | Faible | — |

### Gestion métier

| Fonctionnalité | Utilité | Valeur métier | Priorité | Dépendances |
|---|---|---|---|---|
| Inscriptions | Présentes (création/révocation manuelles, périmètre formateur) | — | — | — |
| Codes d'accès | Bien traités : génération, suivi des utilisations, désactivation, alerte de stock bas | — | — | — |
| **Résultats de quiz** | **Absents du back-office** — alors que le cahier des charges impose qu'un formateur voie « ses inscrits **et leurs résultats** » | Sans cela, le formateur ne peut pas améliorer son contenu | **Critique** | — |
| Commandes / paiements / remboursements / litiges | Absents (pas de paiement) | Prérequis à toute vente en ligne | Haute (V1) | Paiement |
| Cohortes / sessions | **Absentes** : impossible de gérer un groupe entreprise avec dates | Le B2B se vend par cohorte, pas à l'unité | Haute | Import CSV |

### Support client

| Fonctionnalité | Utilité | Valeur métier | Priorité | Dépendances |
|---|---|---|---|---|
| Tickets | Absent | Traçabilité des demandes | Moyenne | — |
| Live chat | Absent | Conversion et déblocage immédiat | Haute | — |
| FAQ / centre d'aide | **Absent** | Divise le volume de support par 2 à 3 | Haute | CMS |
| CRM | Absent | Suivi commercial des prospects et cohortes | Moyenne | Tunnel d'acquisition |

### Marketing

| Fonctionnalité | Utilité | Valeur métier | Priorité | Dépendances |
|---|---|---|---|---|
| Campagnes e-mail | **Absent** : Brevo n'est utilisé que pour 2 e-mails transactionnels | Réengagement, lancement de formation | Haute | Segmentation |
| Coupons / promotions | Table `coupons` existante mais **inexploitée** | Levier de conversion classique | Moyenne | Paiement |
| Notifications push | Absent | Réengagement sans coût par message | Moyenne | PWA |
| SMS / WhatsApp | Absent | **Sur ce marché, WhatsApp est le canal dominant** — plus lu que l'e-mail | Haute | Intégration API |
| Segmentation | Absente | Cibler les inactifs, les non-démarrés, les proches de la fin | Haute | Analytics |

### Analytics

| Fonctionnalité | Utilité | Valeur métier | Priorité | Dépendances |
|---|---|---|---|---|
| Tableaux de bord | 3 widgets bruts (totaux, dernières inscriptions, codes en stock bas) | Insuffisant pour piloter | **Critique** | Plan de tracking |
| KPIs produit | **Aucun** : ni apprenants actifs, ni taux de complétion, ni taux de réussite au quiz | Impossible de mesurer l'objectif « 50 apprenants / 1 module à 90 jours » | **Critique** | Plan de tracking |
| Cohortes / entonnoirs / rétention | Absents | Sans cela, aucune décision produit fondée | Haute | Plan de tracking |
| Exports | Absent (hors export RGPD individuel) | Reporting client, facturation entreprise | Moyenne | — |

### Finance

| Fonctionnalité | Utilité | Valeur métier | Priorité | Dépendances |
|---|---|---|---|---|
| Facturation | **Absente** : aucune facture émise, alors que le B2B l'exige systématiquement | Bloque la vente aux entreprises et aux ONG | Haute | — |
| Commissions formateurs | Absentes | Nécessaire dès qu'un formateur externe intervient | Moyenne | Paiement |
| Rapprochement bancaire / comptabilité | Absents | Conformité, clôture comptable | Moyenne | Paiement |

### Sécurité et configuration

| Fonctionnalité | Utilité | Valeur métier | Priorité | Dépendances |
|---|---|---|---|---|
| Journal d'audit | **Absent** : aucune trace de qui a modifié/supprimé quoi | Indispensable dès qu'il y a plusieurs administrateurs ; obligatoire en cas de litige | Haute | — |
| 2FA administrateur | Absent | Un compte admin = tout le catalogue | Haute | — |
| Alertes de sécurité | Partielles (e-mail d'exception throttlé) | Détection d'incident | Moyenne | Monitoring |
| Paramètres globaux | **Absents** : tout est en `.env`, donc modifiable seulement en SSH | Autonomie de l'équipe métier | Moyenne | — |
| Langues / devises / taxes / zones | Absents | Prérequis à l'international | Moyenne | i18n |
| Intégrations | Aucune interface de configuration | Autonomie | Faible | — |

---

## 3. Architecture produit

| Axe | État | Analyse | Priorité |
|---|---|---|---|
| **Modularité** | Bonne | Logique métier isolée dans `app/Services`, pilote vidéo interchangeable via interface, aucun `env()` dans le code. C'est propre et extensible. | — |
| **Scalabilité** | **Bloquée** | Mutualisé LWS : ~20-30 processus, pas de Redis, pas d'autoscaling, streaming vidéo servi par PHP par défaut. Plafond réaliste : **quelques dizaines d'utilisateurs simultanés**. | **Critique** |
| **Performance** | Moyenne | Caches config/routes/vues actifs. Mais cache applicatif et sessions en base (donc en concurrence avec les requêtes métier), aucun cache de requêtes, pas d'index vérifiés sur les tables de progression. | Haute |
| **API** | **Inexistante** | Aucune API. Bloque : application mobile, intégrations client, écosystème partenaires. | Haute (V2) |
| **Webhooks** | Inexistants | Bloque l'automatisation externe (Zapier, Make, CRM). | Moyenne |
| **Architecture des données** | Solide | 18 tables normalisées, utf8mb4 forcé, contraintes de clés étrangères. **Anomalie** : tables `orders` et `coupons` créées mais jamais utilisées — schéma mort à assumer ou retirer. | Moyenne |
| **Monolithe / microservices** | Monolithe | **Bon choix** à ce stade. Ne pas fragmenter avant d'avoir un problème réel. | — |
| **Cache** | Driver base de données | Contraint par l'hébergement. Redis apporterait un gain immédiat lors de la migration. | Haute (post-migration) |
| **Stockage** | Disques nommés (`private`, `videos`, `public`, `backups`) — abstraction correcte, bascule S3/R2 sans toucher au code | Bien pensé | — |
| **CDN** | **Absent** | Sur un marché où la latence vers l'Europe est de 100-200 ms, un CDN change l'expérience perçue. | Haute |
| **Monitoring** | **Quasi absent** | Un e-mail throttlé en cas d'exception. Aucune métrique de disponibilité, de temps de réponse, ni d'alerte proactive. Vous apprendrez les pannes par vos apprenants. | **Critique** |
| **Observabilité** | Absente | Pas de traces, pas de corrélation, pas de tableau de bord technique. | Haute |

### Risque de sauvegarde — à traiter en urgence

`config/backup.php` sauvegarde **la base uniquement** (`files.include = []`). Or les
**vidéos auto-hébergées** et les **PDF de certificats** vivent sur le disque, sont exclus
du transfert FTP, et ne sont **sauvegardés nulle part**. Une défaillance disque LWS
détruirait tout le contenu pédagogique de façon irréversible. De plus, le disque de
sauvegarde est **local au serveur** — une panne matérielle emporterait base **et**
sauvegardes.

---

## 4. Expérience utilisateur (UX)

| Axe | Analyse | Priorité |
|---|---|---|
| **Friction** | Le parcours d'accès est le point noir : découvrir → demander → attendre un code → recevoir → créer un compte → activer. **6 étapes dont 2 hors ligne.** C'est le tunnel d'un produit pré-numérique. | **Critique** |
| **Nombre de clics** | Reprendre une leçon demande 3-4 clics depuis l'accueil (aucun raccourci « Reprendre »). | Haute |
| **Charge cognitive** | Correcte sur le lecteur. Élevée sur l'espace apprenant : aucune hiérarchie d'action, aucune prochaine étape mise en avant. | Haute |
| **Clarté** | Bonne rédaction, vouvoiement cohérent, pas de jargon. Vrai point fort. | — |
| **Vitesse** | Correcte en local ; non mesurée en conditions réelles (mobile 3G Dakar). Aucun budget de performance défini. | Haute |
| **Feedback utilisateur** | **Aucun mécanisme** : ni notation de formation, ni avis, ni enquête de satisfaction, ni signalement de problème. Vous ne saurez jamais pourquoi un apprenant décroche. | **Critique** |
| **Erreurs** | Bien traitées (pages à la charte, messages en français, notification admin). | — |
| **Confirmation** | Présente sur les actions destructrices Filament. Manque côté apprenant (suppression de compte). | Moyenne |
| **Animations / micro-interactions** | Soignées (transitions, `card-lift`, `animate-rise`). Manque : retour de progression après complétion d'une leçon, célébration à l'obtention du certificat. | Moyenne |

---

## 5. Interface (UI)

| Axe | Analyse | Priorité |
|---|---|---|
| **Cohérence graphique** | Très bonne. Refonte premium récente, langage visuel homogène front/back. | — |
| **Design system** | Réel mais partiel : jetons CSS (`@theme`), échelles de couleurs, ombres, rayons, composant bouton à variantes. **Manque** : documentation, composants formulaire unifiés, états (erreur/succès/désactivé) normalisés. | Moyenne |
| **Composants** | Une dizaine de composants Blade réutilisables. Pas de bibliothèque documentée. | Moyenne |
| **Responsive** | Bien traité. | — |
| **Hiérarchie visuelle** | Bonne sur les pages publiques, faible sur l'espace apprenant. | Haute |
| **Typographie** | Sora auto-hébergée, échelle cohérente. Excellent (et conforme à la règle « aucun CDN »). | — |
| **Couleurs** | Palette de marque complète et dérivée du logo réel. Contrastes non audités formellement. | Moyenne |
| **Spacing** | Cohérent. | — |
| **Icônes** | Heroicons, cohérent. | — |
| **Illustrations** | **Point faible** : aucune illustration propre, miniatures générées en SVG abstrait, états vides sans visuel. Le produit paraît austère face à un Udemy. | Moyenne |
| **Dark mode** | Présent sur le back-office, **absent du front**. | Faible |

---

## 6. Marketing & Growth

| Axe | Ce qui manque | Priorité | Impact business |
|---|---|---|---|
| **Activation** | Aucune définition de l'événement d'activation, aucune séquence pour l'atteindre | **Critique** | C'est la métrique qui prédit la rétention |
| **Onboarding** | Aucun (cf. §1) | **Critique** | Rétention à 7 jours |
| **Viralité** | Aucune. Le certificat n'est pas partageable (ni LinkedIn, ni WhatsApp) alors qu'il constitue **la preuve sociale idéale** | Haute | Acquisition à coût nul |
| **Parrainage** | Absent | Moyenne | CAC réduit |
| **SEO** | Base technique solide, mais **aucun contenu** : pas de blog, pas de pages thématiques, pas de maillage | Haute | Canal le moins cher du marché |
| **ASO** | Sans objet (pas d'application mobile) | Faible | — |
| **Contenu** | Aucune stratégie éditoriale | Haute | Autorité, SEO, nurturing |
| **Automation** | Aucune séquence automatisée | **Critique** | Rétention à coût marginal nul |
| **CRM** | Absent | Moyenne | Suivi commercial B2B |
| **Analytics** | Absent | **Critique** | Pilotage |
| **A/B testing** | Absent | Faible (prématuré) | — |
| **Acquisition** | Aucun canal instrumenté | Haute | Prévisibilité de la croissance |
| **Rétention** | Aucun mécanisme | **Critique** | Valeur vie client |
| **Réengagement** | Aucun | **Critique** | Récupération des inactifs |
| **Upsell / cross-sell** | Aucune recommandation, aucun parcours entre formations | Moyenne | Panier moyen |

---

## 7. Données & Analytics — plan de tracking

Aucun événement n'est actuellement tracké. Voici le plan minimal à implémenter **avant**
toute autre optimisation, car il conditionne la capacité à décider.

| Événement | Propriétés | Étape de l'entonnoir | KPI alimenté |
|---|---|---|---|
| `page_vue` | url, référent, appareil | Acquisition | Trafic, sources |
| `formation_consultee` | formation_id, niveau, prix | Intérêt | Taux de vue → demande |
| `apercu_lu` | formation_id, leçon_id, % visionné | **Intérêt qualifié** | Conversion de l'aperçu |
| `acces_demande` | formation_id, canal | Considération | Volume de leads |
| `compte_cree` | source, appareil | Inscription | Taux de création |
| `email_verifie` | délai depuis inscription | Inscription | Complétion d'inscription |
| `code_active` | code, formation_id, délai depuis réception | **Activation** | Taux d'activation |
| `premiere_lecon_ouverte` | formation_id, délai depuis activation | **Activation** | Time-to-value |
| `lecon_terminee` | leçon_id, type, durée réelle | Engagement | Progression, points de décrochage |
| `quiz_tente` | quiz_id, score, tentative n° | Engagement | Difficulté, qualité pédagogique |
| `quiz_reussi` | quiz_id, score, nb tentatives | Réussite | Taux de réussite |
| `formation_terminee` | formation_id, durée totale | **Complétion** | Complétion (KPI principal) |
| `certificat_obtenu` | formation_id, serial | Complétion | Preuve de valeur |
| `certificat_partage` | canal | Viralité | Acquisition organique |
| `session_reprise` | jours depuis dernière visite | Rétention | Rétention J7 / J30 / J90 |
| `inactivite_7j` | dernière leçon vue | Churn | Cible de réengagement |
| `abandon_lecon` | leçon_id, % visionné | Friction | Identification du contenu faible |

**KPIs à instrumenter** (aucun n'est mesurable aujourd'hui) : apprenants actifs
hebdomadaires, taux d'activation, time-to-value, taux de complétion par formation, taux de
réussite au quiz, rétention J7/J30/J90, taux d'abandon par leçon, LTV, CAC, churn.

**Choix d'outil** : privilégier une solution respectueuse du RGPD et auto-hébergeable
(Matomo ou Plausible) plutôt que Google Analytics, cohérent avec vos engagements de
confidentialité. Prévoir en complément une table d'événements interne pour les métriques
produit (celles ci-dessus), indépendante de l'outil web.

---

## 8. Automatisation

| Automatisation | Déclencheur | Priorité | Effort |
|---|---|---|---|
| Séquence de bienvenue (3 e-mails) | Création de compte | **Critique** | 2 j |
| Rappel « code non activé » | 48 h après envoi du code | **Critique** | 1 j |
| Relance d'inactivité | 7 puis 21 jours sans connexion | **Critique** | 2 j |
| Félicitations + invitation à partager | Certificat obtenu | Haute | 1 j |
| Encouragement à mi-parcours | 50 % de progression | Haute | 1 j |
| Alerte formateur | Quiz échoué 2 fois par le même apprenant | Haute | 1 j |
| **WhatsApp** (mêmes déclencheurs) | — | Haute | 4 j |
| Alerte admin de stock de codes | Déjà présent (widget) — à passer en e-mail | Moyenne | 0,5 j |
| Rapport hebdomadaire automatique | Chaque lundi | Moyenne | 2 j |
| Sauvegarde des fichiers hors serveur | Quotidien | **Critique** | 1 j |
| Purge des sessions/logs expirés | Quotidien | Faible | 0,5 j |

L'infrastructure d'exécution existe déjà (scheduler + file d'attente en base, cron LWS) :
ces automatisations sont donc peu coûteuses à ajouter. C'est le meilleur rapport
valeur/effort de tout l'audit.

---

## 9. Intelligence artificielle

| Usage | Description | Valeur | Priorité | Complexité |
|---|---|---|---|---|
| **Génération de quiz** | Produire les QCM depuis la transcription d'une leçon | Divise par 5 le temps de production pédagogique | Haute | Moyenne |
| **Transcription + sous-titres** | Whisper sur les vidéos | Accessibilité, SEO, apprentissage en réseau faible. **Vous avez déjà un pipeline Whisper local** | Haute | Faible |
| **Résumé automatique de leçon** | Fiche de synthèse générée | Révision, valeur perçue | Moyenne | Faible |
| **Recherche sémantique** | Recherche dans le contenu par le sens | Trouve la réponse, pas le mot-clé | Moyenne | Moyenne |
| **Assistant apprenant** | Répond aux questions sur le contenu suivi uniquement | Déblocage 24/7, réduit le support | Moyenne | Moyenne |
| **Détection de décrochage** | Score de risque d'abandon par apprenant | Relance ciblée avant l'abandon | Haute | Moyenne |
| **Recommandation de formation** | Suggestion selon le parcours | Ventes croisées | Moyenne | Faible |
| **Aide à la rédaction de fiches** | Descriptions, objectifs, SEO | Vitesse de mise en ligne | Faible | Faible |
| OCR / vision | Sans objet ici | — | Faible | — |

**Recommandation** : ne pas faire de l'IA un axe produit visible tant que les fondamentaux
(mesure, onboarding, rétention) ne sont pas en place. En revanche, **l'IA en coulisses**
(transcription, génération de quiz) est un gain de productivité immédiat et sans risque.

---

## 10. Sécurité

| Axe | État | Manque | Priorité |
|---|---|---|---|
| **RGPD** | Bon : export JSON des données, suppression du compte et des fichiers, politique de confidentialité | Registre des traitements, gestion du consentement analytics, durée de conservation documentée | Moyenne |
| **Chiffrement** | HTTPS actif, mots de passe hachés (bcrypt 12), cookies sécurisés | Pas de chiffrement au repos des données sensibles | Faible |
| **Sauvegardes** | Base quotidienne, rétention 14 j, **restauration testée** (rare et à saluer) | **Fichiers non sauvegardés** (vidéos, certificats) et **sauvegardes locales au serveur** | **Critique** |
| **Authentification** | Limitation de débit, vérification d'e-mail, politique de mot de passe | **2FA absente**, pas de détection de connexion suspecte, pas de journal de connexions | Haute |
| **Autorisations** | Excellent : policies systématiques, périmètre formateur, URLs signées, re-vérification après signature | — | — |
| **Protection contre la fraude** | Limitation sur les codes d'accès (5/60 min) | Pas de détection de partage de compte (même compte, plusieurs appareils/pays) | Moyenne |
| **Audit** | **Absent** | Journal d'audit des actions d'administration | Haute |
| **Conformité** | CGU, mentions légales, confidentialité présentes | Conditions de vente, politique de remboursement (avec le paiement) | Moyenne |
| **En-têtes de sécurité** | `Permissions-Policy` observé | CSP, HSTS, `X-Frame-Options`, `X-Content-Type-Options` | Haute |

---

## 11. Roadmap produit

### MVP — déjà livré
Catalogue, fiche formation, aperçu public lisible, accès par code, lecteur multi-format
(vidéo/texte/PDF), progression tolérante aux coupures, quiz, certificat PDF vérifiable,
back-office complet, RGPD, sauvegardes, SEO technique.

### Version 1 — « rendre le produit mesurable et rétentif » (0-3 mois)
Plan de tracking et tableau de bord KPI · onboarding guidé · bandeau « Reprendre » ·
séquences e-mail automatisées (bienvenue, code non activé, inactivité) · résultats de quiz
au back-office · sauvegarde des fichiers hors serveur · en-têtes de sécurité · 2FA admin ·
journal d'audit · FAQ · notation des formations · partage du certificat.

### Version 2 — « ouvrir le commerce et l'échelle » (3-6 mois)
**Migration vers un hébergement dédié (VPS)** · paiement en ligne (Wave, Orange Money,
carte) · facturation · cohortes et import CSV · recherche et catégories · notifications
in-app · WhatsApp transactionnel · PWA et mode hors ligne · CDN · monitoring et
observabilité · sous-titres automatiques.

### Version 3 — « industrialiser » (6-12 mois)
API publique et webhooks · application mobile · internationalisation (EN/AR) ·
multi-devises · espace entreprise (tableau de bord RH, rapports de cohorte) · commissions
formateurs · A/B testing · recommandations · génération de quiz par IA.

### Vision long terme — différenciation
Marketplace de formateurs africains · certifications reconnues par des partenaires
institutionnels · parcours métiers diplômants · IA de détection de décrochage et
remédiation personnalisée · mode « data-light » extrême (SMS/USSD pour zones sans data) ·
place de marché de l'emploi adossée aux certificats.

---

## 12. Benchmark

| Acteur | Ce qu'ils font mieux | Enseignement applicable |
|---|---|---|
| **Udemy** | Aperçu vidéo gratuit systématique, avis notés très visibles, recherche puissante, recommandations | Les avis sont leur premier moteur de conversion. Vous n'en avez aucun. |
| **OpenClassrooms** | Parcours diplômants, mentorat humain, projets évalués, débouché emploi affiché | Le débouché (emploi) justifie le prix bien mieux que le contenu. |
| **Coursera** | Certifications co-brandées avec des institutions reconnues | La crédibilité du certificat fait le prix. Un partenariat institutionnel local (patronat, université) vaut plus que 10 fonctionnalités. |
| **Teachable / Podia** | Autonomie totale du créateur, paiement intégré, e-mails automatisés natifs | L'automatisation marketing est un standard, pas un luxe. |
| **Duolingo** | Série de jours, rappels quotidiens, progression visible | Complétion 3 à 5 fois supérieure grâce aux seuls mécanismes de rétention. |
| **Linear / Notion** | Rapidité perçue, raccourcis clavier, interface épurée | Votre UI est déjà à ce niveau — c'est un atout réel à ne pas dégrader. |
| **Moodle** | Richesse pédagogique (devoirs, forums, notation), interopérabilité SCORM/xAPI | SCORM/xAPI est exigé par les grands comptes et les organismes de formation. |

### Différenciation possible sur votre marché

Vous n'avez pas à battre Udemy sur le volume. Trois angles réellement défendables en
Afrique de l'Ouest :

1. **Le mode data-light et hors ligne** — aucun acteur international ne l'optimise pour un
   réseau instable. C'est un avantage structurel.
2. **WhatsApp comme canal principal** de notification et de support — là où les
   concurrents s'obstinent à l'e-mail.
3. **Le paiement mobile local** (Wave, Orange Money) — barrière d'entrée réelle pour les
   plateformes étrangères, et lever cette friction change le taux de conversion du tout
   au tout.

---

## 13. Livrable — tableau de synthèse

| Fonctionnalité | Description | Pourquoi l'ajouter | Valeur utilisateur | Valeur business | Complexité | Priorité | Effort | Dépendances | KPI impactés |
|---|---|---|---|---|---|---|---|---|---|
| Plan de tracking + événements | Table d'événements + instrumentation du parcours | Aucune décision n'est fondée sans mesure | Indirect | Pilotage | Moyenne | **Critique** | 5 j | — | Tous |
| Tableau de bord KPI | Actifs, activation, complétion, réussite, rétention | Mesure l'objectif des 90 jours | Indirect | Pilotage, levée | Moyenne | **Critique** | 4 j | Tracking | Tous |
| Sauvegarde fichiers hors serveur | Vidéos + certificats vers S3/R2 | Perte de contenu actuellement irréversible | Confiance | Continuité | Faible | **Critique** | 1 j | Compte S3/R2 | — |
| Onboarding guidé | Accueil personnalisé, 3 étapes, première leçon en 1 clic | 40-60 % de l'abandon se joue là | Fort | Rétention | Moyenne | **Critique** | 4 j | — | Activation, J7 |
| Bandeau « Reprendre » | Reprise en 1 clic depuis l'accueil | Réduit la friction de reprise | Fort | Complétion | Faible | **Critique** | 1 j | — | Rétention |
| Séquences e-mail automatisées | Bienvenue, code non activé, inactivité 7/21 j | Réengagement à coût nul | Fort | Rétention | Faible | **Critique** | 4 j | Brevo (prêt) | Activation, rétention |
| Résultats de quiz (back-office) | Vue formateur des scores et réussites | **Exigence du cahier des charges non satisfaite** | Indirect | Qualité pédagogique | Faible | **Critique** | 2 j | — | Réussite |
| Paiement en ligne | Wave, Orange Money, carte | Débloque le revenu automatisé | Fort | **Revenu** | Élevée | **Critique** (V2) | 15 j | Comptes marchands | Conversion, CA |
| Migration VPS | Sortie du mutualisé | Plafond actuel : quelques dizaines d'utilisateurs | Vitesse | Scalabilité | Moyenne | **Critique** | 5 j | — | Performance |
| En-têtes de sécurité | CSP, HSTS, X-Frame-Options | Protection XSS/clickjacking | Confiance | Risque | Faible | Haute | 1 j | — | — |
| 2FA administrateur | Double authentification back-office | Un admin compromis = tout le catalogue | Confiance | Risque | Faible | Haute | 2 j | — | — |
| Journal d'audit | Trace des actions d'administration | Litiges, multi-admin | Indirect | Conformité | Faible | Haute | 2 j | — | — |
| Notation et avis | Note + commentaire après complétion | **Premier moteur de conversion d'Udemy** | Fort | Conversion | Moyenne | Haute | 4 j | — | Conversion |
| Partage du certificat | LinkedIn, WhatsApp | Preuve sociale gratuite | Fort | Acquisition | Faible | Haute | 1 j | — | Viralité, CAC |
| Notifications WhatsApp | Canal dominant du marché | Bien plus lu que l'e-mail localement | Fort | Rétention | Moyenne | Haute | 4 j | API WhatsApp | Rétention |
| Recherche + catégories | Recherche serveur, tags, pagination | Inutilisable au-delà de 10 formations | Fort | Découverte | Moyenne | Haute | 5 j | — | Découverte |
| FAQ / centre d'aide | Base de connaissances | Divise le support par 2-3 | Fort | Coût support | Faible | Haute | 3 j | CMS | Support |
| Import CSV + cohortes | Inscription groupée, gestion de groupe | Le B2B se vend par cohorte | Indirect | **Revenu B2B** | Moyenne | Haute | 5 j | — | CA B2B |
| Facturation | Factures PDF numérotées | Exigence systématique en B2B | Indirect | Revenu B2B | Moyenne | Haute | 4 j | — | CA B2B |
| Suspension de compte | Désactivation réversible | Alternative à la suppression | Indirect | Opérationnel | Faible | Haute | 1 j | — | — |
| PWA + hors ligne | Installation, contenu en cache | **Différenciant fort sur le marché cible** | Fort | Différenciation | Élevée | Haute | 10 j | — | Rétention |
| Sous-titres automatiques | Whisper sur les vidéos | Accessibilité, SEO, réseau faible | Fort | Marché adressable | Moyenne | Haute | 4 j | Pipeline Whisper (existant) | Accessibilité |
| Monitoring / alertes | Disponibilité, temps de réponse | Vous apprenez les pannes par vos apprenants | Indirect | Fiabilité | Faible | Haute | 2 j | — | Disponibilité |
| CDN | Distribution des assets | 100-200 ms de latence vers l'Europe | Vitesse | Expérience | Faible | Haute | 2 j | — | Performance |
| Médiathèque back-office | Vue des fichiers, liaison aux leçons | Autonomie du formateur, quota maîtrisé | Indirect | Opérationnel | Moyenne | Haute | 4 j | — | — |
| Impersonation | Voir la plateforme comme un apprenant | Divise par 2 le temps de résolution | Indirect | Coût support | Faible | Haute | 2 j | Audit | Support |
| Historique utilisateur | Chronologie par compte | Support et litiges | Indirect | Coût support | Faible | Haute | 2 j | Audit | Support |
| CMS pages éditoriales | Accueil, légal, FAQ éditables | Chaque texte exige aujourd'hui un déploiement | Indirect | Agilité | Moyenne | Moyenne | 5 j | — | — |
| Paramètres globaux | Réglages depuis le back-office | Autonomie de l'équipe | Indirect | Agilité | Faible | Moyenne | 3 j | — | — |
| Gamification légère | Progression, séries, jalons | Complétion +20-40 % en LMS | Fort | Complétion | Moyenne | Moyenne | 6 j | Tracking | Complétion |
| Recommandations | Suggestions selon le parcours | Ventes croisées | Moyen | Panier moyen | Moyenne | Moyenne | 4 j | Tracking | Cross-sell |
| Enquête de satisfaction | NPS après complétion | **Vous ignorez pourquoi on décroche** | Indirect | Produit | Faible | Moyenne | 2 j | — | NPS |
| API publique + webhooks | REST + événements sortants | Mobile, intégrations, partenaires | Indirect | Écosystème | Élevée | Moyenne (V2) | 12 j | — | — |
| Internationalisation | EN/AR, devises | Marché adressable ×3 | Fort | Croissance | Élevée | Moyenne | 12 j | — | Marché |
| SCORM / xAPI | Interopérabilité | Exigé par grands comptes et OF | Indirect | Revenu grands comptes | Élevée | Faible | 15 j | — | CA B2B |
| Dark mode front | Thème sombre public | Confort | Faible | — | Faible | Faible | 2 j | — | — |

---

## Conclusions

### 1. Les 20 fonctionnalités les plus critiques

1. Plan de tracking et table d'événements
2. Tableau de bord KPI produit
3. Sauvegarde des fichiers hors serveur (vidéos, certificats)
4. Onboarding guidé
5. Bandeau « Reprendre où vous en étiez »
6. Séquences e-mail automatisées (bienvenue, code non activé, inactivité)
7. Résultats de quiz au back-office *(exigence du cahier des charges non satisfaite)*
8. Migration vers un hébergement dédié
9. Paiement en ligne (Wave, Orange Money, carte)
10. En-têtes de sécurité (CSP, HSTS)
11. 2FA administrateur
12. Journal d'audit
13. Notation et avis des formations
14. Partage du certificat (LinkedIn, WhatsApp)
15. Recherche, catégories et pagination du catalogue
16. Notifications WhatsApp
17. Import CSV et gestion de cohortes
18. Facturation
19. Monitoring et alertes de disponibilité
20. FAQ / centre d'aide

### 2. Les 10 fonctionnalités différenciantes

1. **Mode hors ligne réel (PWA + contenu en cache)** — aucun acteur international ne
   l'optimise pour un réseau instable
2. **WhatsApp comme canal principal** de notification et de support
3. **Paiement mobile local** (Wave, Orange Money) — barrière d'entrée pour les
   plateformes étrangères
4. **Mode data-light** avec choix explicite de qualité vidéo et alternative texte/PDF
5. **Certificats vérifiables publiquement** — déjà en place, à valoriser commercialement
6. **Partenariats institutionnels locaux** pour la reconnaissance des certificats
7. **Espace entreprise** avec rapports de cohorte pour les DRH
8. **Détection IA du décrochage** et remédiation personnalisée
9. **Sous-titres et transcriptions automatiques** en français africain
10. **Passerelle emploi** adossée aux certificats obtenus

### 3. Quick wins (moins de 2 semaines)

| Action | Effort | Gain |
|---|---|---|
| Sauvegarde des fichiers vers S3/R2 | 1 j | Supprime un risque de perte irréversible |
| Bandeau « Reprendre » | 1 j | Friction de reprise divisée |
| Partage du certificat | 1 j | Acquisition organique gratuite |
| En-têtes de sécurité | 1 j | Protection XSS/clickjacking |
| Suspension de compte | 1 j | Alternative à la suppression |
| Alerte e-mail de stock de codes | 0,5 j | Évite la rupture d'accès |
| Résultats de quiz au back-office | 2 j | Conformité au cahier des charges |
| 3 séquences e-mail | 4 j | Rétention (Brevo est déjà configuré) |
| Impersonation + historique utilisateur | 3 j | Support divisé par 2 |
| Monitoring de disponibilité | 2 j | Fin des pannes découvertes par les apprenants |

**Total : environ 17 jours pour 10 actions à fort effet de levier.**

### 4. Risques majeurs en cas d'inaction

| Risque | Probabilité | Gravité | Conséquence |
|---|---|---|---|
| **Perte définitive des vidéos et certificats** | Moyenne | **Critique** | Contenu pédagogique irrécupérable, activité arrêtée |
| **Pilotage à l'aveugle** | **Certaine** | **Critique** | Décisions arbitraires, objectif des 90 jours non mesurable, levée impossible |
| **Effondrement sous la charge** | Élevée dès 20-30 utilisateurs simultanés | **Critique** | Plateforme inutilisable au moment précis du succès |
| **Complétion très faible (5-15 %)** | Élevée | Élevée | Aucun renouvellement, aucune recommandation, réputation entamée |
| **Absence de revenu automatisé** | Certaine | Élevée | Croissance plafonnée par le temps commercial disponible |
| **Compte administrateur compromis** | Faible | **Critique** | Exfiltration de tout le catalogue payant |
| **Suspension par LWS** (CGU streaming) | Moyenne | Élevée | Coupure de service sans préavis |
| **Départ silencieux des apprenants** | Certaine | Élevée | Aucun signal, aucune possibilité de correction |

### 5. Roadmap priorisée sur 12 mois

| Période | Thème | Livrables |
|---|---|---|
| **M1** | *Ne plus voler à l'aveugle* | Plan de tracking · tableau de bord KPI · sauvegarde fichiers hors serveur · en-têtes de sécurité · monitoring |
| **M2** | *Retenir les apprenants* | Onboarding guidé · bandeau « Reprendre » · 3 séquences e-mail · résultats de quiz · notation et avis |
| **M3** | *Crédibiliser et outiller* | 2FA · journal d'audit · impersonation · historique · FAQ · partage de certificat · alertes de stock |
| **M4** | *Sortir du plafond technique* | **Migration VPS** · Redis · CDN · sous-titres automatiques |
| **M5-M6** | *Ouvrir le commerce* | **Paiement (Wave, Orange Money, carte)** · facturation · coupons · conditions de vente |
| **M7** | *Vendre au B2B* | Cohortes · import CSV · espace entreprise · rapports de cohorte |
| **M8** | *Rendre le produit trouvable* | Recherche serveur · catégories et tags · CMS éditorial · blog SEO |
| **M9** | *Gagner le terrain mobile* | PWA · mode hors ligne · notifications push · WhatsApp |
| **M10** | *Industrialiser* | API publique · webhooks · exports · paramètres globaux |
| **M11** | *Élargir le marché* | Internationalisation EN · multi-devises · gamification légère |
| **M12** | *Différencier durablement* | Détection IA du décrochage · recommandations · génération de quiz par IA · partenariats de certification |

---

## Recommandation finale

Le produit est **techniquement sain et visuellement au niveau du marché** — c'est un point
de départ meilleur que celui de la plupart des projets à ce stade. Mais il est aujourd'hui
**aveugle** (aucune mesure), **fragile** (contenu non sauvegardé, infrastructure plafonnée)
et **commercialement manuel** (aucun paiement, aucune automatisation).

Si je devais ne retenir qu'une priorité pour les 30 prochains jours : **instrumenter la
mesure et sécuriser les sauvegardes de fichiers**. Tout le reste — onboarding, paiement,
rétention — dépend de votre capacité à savoir ce qui se passe réellement, et à ne pas
perdre ce que vous avez déjà construit.
