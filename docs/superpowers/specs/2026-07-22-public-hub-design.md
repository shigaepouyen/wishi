# Hub public par profil + listes privées/publiques

Date : 2026-07-22

## Contexte

Wishi propose déjà le partage d'une liste individuelle via `view.php?s=<slug_public>`.
Besoin : partager un "hub" regroupant toutes les listes d'un profil (ex : Zoé) avec
une URL lisible (`https://wishi.shi-ga.net/zoe`), sans authentification, tout en
gardant la possibilité de garder certaines listes strictement liens-privés (non
listées dans ce hub, mais toujours accessibles par leur lien direct existant).

## Décisions

- URL propre `wishi.shi-ga.net/zoe` via réécriture Apache (`.htaccess`, mod_rewrite
  confirmé disponible sur l'hébergement Infomaniak).
- Nouvelle colonne `lists.hub_visible` (INTEGER DEFAULT 0). `0` = liste privée
  (par défaut, y compris pour les listes existantes après migration). `1` = liste
  listée dans le hub public du profil.
- `hub_visible` est indépendant de `slug_public` : une liste privée reste
  consultable via son lien direct existant, elle est juste absente du hub.
- Le slug de profil (`profiles.slug`, ex. "zoe") devient de facto public/devinable
  par cette fonctionnalité. Accepté : même niveau de "secret" que `slug_public`
  des listes (pas de secret cryptographique, juste non indexé). Rien de sensible
  (`admin_pin_hash`, `admin_slug`) n'est exposé par la nouvelle route.

## Composants

### 1. Migration DB (`src/Utils/Database.php`)

Suivre le pattern existant des migrations (`is_surprise`, `admin_slug`) dans
`Database::getConnection()` / méthode de migration : vérifier colonne
`hub_visible` sur `lists`, sinon `ALTER TABLE lists ADD COLUMN hub_visible
INTEGER DEFAULT 0;`. Aucune action de rattrapage supplémentaire nécessaire :
`DEFAULT 0` couvre déjà les lignes existantes.

Mettre à jour aussi `scripts/migrate.php` (schema de référence pour install
fraîche) pour inclure `hub_visible INTEGER DEFAULT 0` dans le `CREATE TABLE lists`.

### 2. Réécriture d'URL (`public/.htaccess`)

Nouveau fichier. Règle mod_rewrite : si la requête ne correspond à aucun fichier
ni dossier existant (`api/`, `assets/`, `hub.php`, etc. restent atteints
normalement), réécrire vers `profile.php?slug=$1`. Un seul segment de chemin
géré (`/zoe`), pas de sous-chemins.

### 3. Contrôleur public de profil (`public/profile.php` + `ProfileController`)

Nouveau fichier public, distinct de `universe.php` (qui reste 100% admin/PIN).
Utilise une nouvelle méthode `ProfileController::publicHub(string $slug)` :
- lookup `profiles WHERE slug = ?`
- si profil introuvable → page "rien à voir ici" (pas de redirect révélateur,
  pas de 404 technique qui logue différemment un slug existant vs inexistant)
- si trouvé : récupère uniquement les listes `WHERE profile_id = ? AND
  hub_visible = 1`, avec le compte d'items (même requête pattern que
  `getProfileUniverse`)
- si zéro liste visible → même page "rien à voir ici" (ne pas distinguer
  "profil existe mais tout est privé" de "profil n'existe pas", pour ne pas
  laisser deviner l'existence d'un profil par listes toutes privées)

Aucune donnée sensible du profil (pin hash, admin_slug) ne doit transiter vers
la vue.

### 4. Vue publique (`views/profile_public_view.php`)

Nouveau fichier, calqué visuellement sur `hub_view.php`/`universe_view.php`
(emoji, nom, couleur du profil) mais :
- cards cliquables vers `view.php?s=<slug_public>` de chaque liste visible
- aucun bouton admin (pas d'édition, pas de suppression, pas de création de
  liste, pas de modale réglages profil)
- affichage minimal : nom liste + nombre de souhaits, dans le même style que
  les cards existantes

### 5. Toggle liste privée/publique (`views/list_view.php` + `ListController`)

- Ajout d'un toggle dans la modale réglages de liste existante (à côté du
  toggle `is_surprise` déjà présent), libellé clair type "Visible dans le hub
  public" (off par défaut visuellement cohérent avec la donnée)
- `ListController::updateSettings()` : ajouter la gestion de `hub_visible`,
  même pattern que `is_surprise` (paramètre optionnel, updaté seulement si
  fourni)

## Hors scope

- Pas de gestion fine des permissions par personne (le hub reste public à qui
  a le lien/slug, comme le reste de l'app)
- Pas de renommage/regénération du slug de profil dans cette itération (le
  slug existe déjà, généré à la création du profil)
- Pas de sous-chemins multiples dans l'URL (`/zoe/autre-chose` non géré)

## Tests / vérification

- Migration : lancer sur une base existante, vérifier colonne ajoutée,
  vérifier que les listes existantes ont bien `hub_visible = 0`
- `/zoe` avec au moins une liste `hub_visible=1` → page publique affiche
  uniquement les listes visibles, liens fonctionnels vers `view.php`
- `/zoe` avec profil existant mais toutes listes privées → page "rien à voir
  ici", identique au cas profil inexistant
- `/slug-inexistant` → même page "rien à voir ici"
- Toggle dans `list.php` : bascule `hub_visible`, rechargement, liste apparaît/
  disparaît du hub
- Vérifier que `/api/...`, `/hub.php`, `/manifest.json` etc. continuent de
  fonctionner normalement après ajout du `.htaccess` (pas de collision avec la
  règle catch-all)
