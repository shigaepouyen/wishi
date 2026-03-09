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
    parent (ex : Zoé 🦄, Chloé 🎮).
-   **Identité visuelle :** chaque profil choisit sa couleur de thème
    (rose, bleu, vert, indigo, orange) et son emoji.
-   **Zone admin :** modifiez les réglages de l'univers (nom, couleur,
    emoji) directement depuis l'interface sans toucher au code.

### 📝 Gestion des listes

-   **Listes illimitées :** créez des listes par événement (Noël 2026,
    anniversaire, etc.).
-   **Scraping magique :** collez un lien (Amazon, Fnac, AliExpress...) et Wishi
    récupère automatiquement le titre, le prix (avec conversion de devise), la photo haute résolution et la
    description.
-   **Organisation intelligente :** classez vos idées par catégories avec suggestions automatiques basées sur vos anciens ajouts.
-   **Drag and drop :** réorganisez les souhaits par simple
    glisser-déposer grâce à SortableJS.
-   **Priorités :** marquez les coups de cœur 🔥 pour guider les
    proches.

### 👨‍👩‍👧‍👦 Partage et réservations

-   **URLs propres (slugs) :** aucun ID technique n'est visible. Les
    partages utilisent des slugs lisibles et personnalisés pour chaque profil et chaque liste.
-   **Système de réservation :** la famille peut réserver un cadeau en
    indiquant son nom. L'objet est alors marqué comme « pris » pour
    éviter les doublons.
-   **Annulation sécurisée :** une réservation peut être annulée immédiatement via un cookie de session (valable 1h) ou ultérieurement via une vérification par email, permettant de libérer un cadeau sans compte utilisateur.
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

### 4. Lancement

Utilisez le serveur intégré de PHP pour tester localement :

``` bash
php -S localhost:8000 -t public
```

Puis ouvrez :

    http://localhost:8000

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
     ├─ hub.php        # Accueil (sélection du profil)
     ├─ universe.php   # Liste des listes d'un utilisateur
     ├─ list.php       # Gestion d'une liste (Admin)
     ├─ view.php       # Consultation d'une liste (Public)
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

------------------------------------------------------------------------

## 🔒 Sécurité et Réservations

Wishi a été conçu pour être simple à utiliser sans nécessiter la création de compte pour les donateurs.

-   **Données donateurs :** Lorsqu'un proche réserve un cadeau, il peut laisser son nom et optionnellement son email.
-   **Annulation par cookie :** Juste après une réservation, un cookie est posé sur le navigateur, permettant d'annuler le choix pendant **1 heure**.
-   **Annulation par email :** Si le donateur a renseigné son email, il peut demander l'annulation à tout moment via un code de vérification envoyé sur sa boîte (système sécurisé sans mot de passe).

------------------------------------------------------------------------

Développé avec ❤️ pour Zoé, Soline et toute la famille.