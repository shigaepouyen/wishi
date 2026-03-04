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
-   **Scraping magique :** collez un lien (Amazon, Fnac...) et Wishi
    récupère automatiquement le titre, le prix, la photo et la
    description.
-   **Drag and drop :** réorganisez les souhaits par simple
    glisser-déposer grâce à SortableJS.
-   **Priorités :** marquez les coups de cœur 🔥 pour guider les
    proches.

### 👨‍👩‍👧‍👦 Partage et réservations

-   **URLs propres (slugs) :** aucun ID technique n'est visible. Les
    partages utilisent des slugs lisibles (ex :
    `view.php?s=liste-de-noel-de-zoe`).
-   **Système de réservation :** la famille peut réserver un cadeau en
    indiquant son nom. L'objet est alors marqué comme « pris » pour
    éviter les doublons.
-   **Mode admin vs public :** la personne propriétaire gère sa liste
    tandis que les invité·es voient une interface simplifiée de
    réservation.

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

Lancez le script de migration pour créer le fichier SQLite et les
profils de base :

``` bash
php scripts/migrate.php
```

Cette commande génère les tables **profiles**, **lists** et **items**
avec les contraintes de clés étrangères nécessaires.

### 4. Lancement

Utilisez le serveur intégré de PHP pour tester localement :

``` bash
php -S localhost:8000 -t public
```

Puis ouvrez :

    http://localhost:8000

------------------------------------------------------------------------

## 🛠️ Stack technique

-   **Backend :** PHP (architecture MVC légère)
-   **Base de données :** SQLite (zéro configuration, un seul fichier
    `.sqlite`)
-   **Frontend :** Tailwind CSS (interface moderne et responsive)
-   **Interactivité :** AlpineJS (modales et interactions)
-   **Utilitaires :** SortableJS (réorganisation des items)

------------------------------------------------------------------------

## 📁 Structure du projet

    public/
     ├─ hub.php
     ├─ universe.php
     └─ api/
          ├─ scrape.php
          ├─ add.php
          └─ sort.php

    src/
     ├─ Controllers/
     └─ Models/

    views/
     ├─ layouts/
     └─ components/

    scripts/
     └─ migrate.php

-   **public/** : point d'entrée de l'application.
-   **public/api/** : endpoints pour les actions asynchrones.
-   **src/** : logique métier et controllers.
-   **views/** : templates HTML/PHP.
-   **scripts/** : scripts de maintenance et migration.

------------------------------------------------------------------------

## 📝 À noter

Pour que le scraping fonctionne correctement, le serveur doit autoriser
les requêtes HTTP sortantes (utilisé par `api/scrape.php`).

------------------------------------------------------------------------

Développé avec ❤️ pour Zoé, Soline et toute la famille.