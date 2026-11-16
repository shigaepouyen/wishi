![Status: Stable](https://img.shields.io/badge/status-Stable-brightgreen)

# 🎁 Wishi --- L'Univers des Souhaits Familiaux

**Wishi** est une application web légère et élégante conçue pour
centraliser les listes de vœux de toute la famille. Chaque membre
possède son propre "univers" personnalisable, et les listes peuvent être
partagées avec les proches pour simplifier les cadeaux tout en gardant
l'effet de surprise.

------------------------------------------------------------------------

## ✨ Fonctionnalités

### 🪐 Univers personnalisables

-   **Profils multiples :** créez un espace dédié pour chaque enfant ou
    parent (ex : Malcolm 🦄, Dewey 🎮).
-   **Identité visuelle :** chaque profil choisit sa couleur de thème
    (rose, bleu, vert, indigo, orange) et son emoji.
-   **Zone admin :** modifiez les réglages de l'univers (nom, couleur,
    emoji) directement depuis l'interface sans toucher au code.

### 📝 Gestion des listes

-   **Listes illimitées :** créez des listes par événement (Noël 2026,
    anniversaire, etc.).
-   **Scraping magique :** collez un lien (Amazon, Fnac, AliExpress...) et Wishi
    récupère automatiquement le titre, le prix (avec conversion de devise) et la photo haute résolution.
-   **Description par IA (Gemini) :** une fois le lien récupéré, si la description est vide,
    l'IA rédige une fiche neutre de 2 à 3 phrases à partir du titre, de la catégorie, du nom
    de la liste, des autres envies et du texte de la page produit. Le texte arrive dans le
    champ, à relire avant d'enregistrer. Le bouton « ✨ Rédiger / Réécrire avec l'IA » la
    régénère (à l'ajout comme à la modification), en gardant les précisions déjà saisies
    (taille, couleur…). Sans clé, le bouton n'apparaît pas.
-   **Descriptions IA de toute la liste :** le bouton « ✨ Descriptions IA » de la page admin
    rédige et enregistre d'un coup les descriptions des cadeaux sans description, ou de tous
    (en gardant leurs précisions), avec une barre de progression et un bouton Arrêter.
    Environ un cadeau toutes les 4 secondes ; 200 appels IA par jour au maximum.
-   **Organisation intelligente :** classez vos idées par catégories avec suggestions automatiques basées sur vos anciens ajouts.
-   **Drag and drop :** réorganisez les souhaits par simple
    glisser-déposer grâce à SortableJS.
-   **Priorités :** marquez les coups de cœur 🔥 pour guider les
    proches.

### 👨‍👩‍👧‍👦 Partage et réservations

-   **URLs propres (slugs) :** aucun ID technique n'est visible. Les
    partages utilisent des slugs lisibles et personnalisés pour chaque profil et chaque liste.
-   **Hub public par personne :** chaque profil dispose d'une adresse
    publique lisible (ex : `wishi.shi-ga.net/malcolm`) qui regroupe ses listes
    partagées, pour envoyer un seul lien aux proches au lieu d'un par liste.
-   **Listes privées par défaut :** une liste n'apparaît dans ce hub que si
    son propriétaire active « Visible par la famille » dans ses réglages.
    Sinon elle reste accessible uniquement via son lien direct.
-   **Listes de la famille :** un bouton en haut de `hub.php` ouvre
    `famille.php`, qui regroupe par personne toutes les listes « Visible par
    la famille » pour les retrouver sans chercher le lien. Réservé aux
    membres connectés avec leur PIN.
-   **Connexion admin simple :** chaque profil peut se déverrouiller avec un PIN à 4 chiffres, soit depuis le hub, soit directement depuis l'URL de son univers sur iPhone.
-   **Lien admin secret de secours :** chaque profil conserve aussi un lien admin personnel qui peut rouvrir une session sécurisée ou servir à recréer un raccourci iPhone.
-   **Système de réservation :** la famille peut réserver un cadeau en
    indiquant son prénom (email facultatif). L'objet disparaît alors de la
    liste publique pour éviter les doublons. Un encadré « Comment ça marche ? »
    et la fenêtre de réservation rappellent que Wishi ne commande rien : on
    achète le cadeau soi-même. Après la réservation, un écran « C'est noté »
    propose le lien vers la boutique.
-   **Mes cadeaux (`/mes-cadeaux`) :** chaque navigateur qui réserve reçoit un
    identifiant anonyme (cookie d'un an, colonne `items.donor_token`). La page
    regroupe tous ses cadeaux, toutes personnes et listes confondues, avec le
    lien boutique et « Annuler ». Un bouton « Mes cadeaux (n) » est affiché en
    haut des listes et des univers publics.
-   **Changer d'appareil :** un lien personnel (`/mes-cadeaux?k=…`) à s'envoyer
    par SMS/email via la feuille de partage du téléphone, ou l'email saisi à la
    réservation (limite d'essais) rattachent un autre appareil au même
    donateur. Seul le donateur peut annuler sa réservation.
-   **Mode admin vs public :** le propriétaire gère ses envies (ajout, édition, tri) tandis que les proches accèdent à une vue dédiée pour offrir.

### 📱 Expérience mobile (PWA)

-   **Installation native :** Wishi est une Progressive Web App
    installable sur l'écran d'accueil d'un iPhone ou Android.
-   **Service worker :** fonctionnement fluide et icône dédiée dans le
    lanceur d'applications.

------------------------------------------------------------------------

## 🚀 Installation rapide

### 1. Prérequis

-   PHP 8.x
-   Composer
-   SQLite3

### 2. Clonage et dépendances

``` bash
git clone https://github.com/votre-compte/wishi.git
cd wishi
composer install
```

### 3. Initialisation de la base de données

Créez le dossier `data/` s'il n'existe pas, puis lancez le script d'initialisation pour créer le fichier SQLite et les profils de base :

``` bash
mkdir -p data
php init_db.php
```

Cette commande génère les tables **profiles**, **lists** et **items** avec les contraintes nécessaires et crée un premier profil de démonstration.

### 4. IA (facultatif)

Les descriptions par IA utilisent Gemini (clé gratuite sur Google AI Studio). Sur le
serveur, à la racine de Wishi :

``` bash
./scripts/set_gemini_key.sh
```

La clé est enregistrée dans `data/gemini.key` (jamais versionnée ni écrasée par le
déploiement). Pour couper l'IA, supprimez ce fichier.

### 5. Lancement

Utilisez le serveur intégré de PHP pour tester localement :

``` bash
php -S localhost:8000 -t public
```

Puis ouvrez :

    http://localhost:8000

------------------------------------------------------------------------

## 📱 Accès Admin Sur iPhone

Wishi est pensé pour fonctionner confortablement comme web app ajoutée à
l'écran d'accueil sur iPhone.

### Recommandation

-   Ajoutez à l'écran d'accueil l'**URL directe du profil** du type
    `universe.php?id=...`.
-   Au quotidien, l'ado ouvre directement **son univers**.
-   Si la session n'est pas déjà ouverte, Wishi affiche un écran de
    déverrouillage dédié à ce profil avec **clavier PIN visuel**.
-   Une fois la session ouverte, la navigation interne reste propre et
    fluide sans ressaisie permanente.

### Après une migration ou une purge iOS

-   Si une ancienne icône n'ouvre plus l'admin, ouvrez le profil voulu
    via `universe.php?id=...` ou passez par `hub.php`.
-   Si besoin, recréez l'icône avec l'**URL directe du profil**
    affichée dans l'univers du profil.
-   Le **lien secret de secours** peut rouvrir une session ou aider à
    recréer un raccourci si nécessaire.
-   Les anciennes URLs publiques restent destinées au partage avec les
    proches, pas à l'administration.

------------------------------------------------------------------------

## 🛠️ Stack technique

-   **Backend :** PHP 8.1+ (architecture MVC personnalisée)
-   **Base de données :** SQLite (zéro configuration, un seul fichier `.sqlite`)
-   **Scraping & API :** GuzzleHTTP & Embed (extraction robuste de métadonnées)
-   **Frontend :** Tailwind CSS (design "Anti-IA", épuré et moderne)
-   **Interactivité :** Alpine.js (gestion d'état légère et réactive)
-   **Utilitaires :** SortableJS (réorganisation des items), Lucide (icônes)

------------------------------------------------------------------------

## 📁 Structure du projet

    public/
     ├─ hub.php        # Accueil / accès secondaire par choix de profil
     ├─ universe.php   # Univers d'un profil + écran de déverrouillage PIN
     ├─ list.php       # Gestion d'une liste (Admin)
     ├─ view.php       # Consultation d'une liste (Public)
     ├─ profile.php    # Hub public d'un profil (listes visibles uniquement)
     └─ api/           # Endpoints JSON (scrape, add, reserve...)

    src/
     ├─ Controllers/   # Logique de navigation
     ├─ Services/      # Logique complexe (ScraperService)
     └─ Utils/         # Connexion DB et Helpers

    views/
     ├─ layouts/       # Structure commune (Header, Footer, JS)
     └─ components/    # Éléments réutilisables (Cards, Modals)

    data/              # Contient la base SQLite (exclue de Git)
    init_db.php        # Script d'initialisation de la base

    scripts/
     └─ migrate.php    # Scripts utilitaires

-   **public/** : Fichiers accessibles aux navigateurs.
-   **src/** : Code PHP organisé selon les standards PSR-4.
-   **views/** : Templates PHP pour le rendu HTML.
-   **data/** : Données persistantes de l'application.

------------------------------------------------------------------------

## 📝 À noter

Pour que le scraping fonctionne correctement, le serveur doit autoriser
les requêtes HTTP sortantes (utilisé par `api/scrape.php`).

Le scraping refuse désormais les URLs locales, privées ou non sûres
(`localhost`, IP internes, ports exotiques, etc.) afin de limiter les
risques SSRF.

------------------------------------------------------------------------

## 🔒 Sécurité et Réservations

Wishi a été conçu pour rester simple, notamment sur mobile, sans
imposer de mot de passe aux membres de la famille ni de compte aux
donateurs.

-   **Données donateurs :** Lorsqu'un proche réserve un cadeau, il peut laisser son nom et optionnellement son email.
-   **PIN profil + session :** l'administration peut se faire depuis le
    hub ou directement depuis l'univers du profil, puis sur une session
    serveur sécurisée.
-   **PIN par défaut après migration :** les profils existants reçoivent
    le PIN initial `0000`, modifiable ensuite dans l'admin de chaque profil.
-   **Lien admin secret de récupération :** chaque profil garde aussi un
    lien secret utilisable comme secours ou pour recréer un raccourci iPhone.
-   **Protection des actions admin :** les écritures admin sont protégées
    côté serveur par contrôle d'accès et token CSRF.
-   **Annulation par cookie signé :** juste après une réservation, le
    même appareil peut l'annuler pendant **1 heure**.
-   **Annulation par email :** si le donateur a renseigné son email, il
    peut annuler plus tard depuis la vue publique en ressaisissant cet
    email.
-   **Validation des entrées :** les champs sensibles côté profil (couleur,
    emoji, etc.) sont filtrés côté serveur.

------------------------------------------------------------------------

Développé avec ❤️ pour mes filles et toute la famille.
