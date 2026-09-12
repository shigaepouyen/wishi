# Hub public par profil + listes privées/publiques Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Permettre de partager une URL publique par profil (`wishi.shi-ga.net/malcolm`) qui liste uniquement les listes marquées "visibles dans le hub", les autres restant accessibles uniquement par leur lien direct existant.

**Architecture:** Nouvelle colonne `lists.hub_visible` (défaut privé) ; nouveau contrôleur public `public/profile.php` + `ProfileController::publicHub()` qui ne renvoie que les listes visibles d'un profil trouvé par son `slug` ; nouvelle vue `views/profile_public_view.php` ; règle de réécriture Apache (`public/.htaccess`) qui mappe `/malcolm` vers `profile.php?slug=malcolm` ; toggle ajouté dans la modale de réglages de liste existante.

**Tech Stack:** PHP 8.1+, PDO/SQLite, Alpine.js, Tailwind (CDN), Apache mod_rewrite.

## Global Constraints

- `hub_visible` par défaut à `0` (privée), y compris pour toutes les listes existantes après migration — aucune liste ne doit devenir visible sans action explicite de l'utilisateur.
- Aucune information sensible (`admin_pin_hash`, `admin_slug`) ne doit jamais être transmise à la vue publique.
- Profil inexistant et profil existant sans liste visible doivent rendre exactement la même page (pas de fuite d'information sur l'existence d'un slug).
- Ce projet n'a pas de suite de tests automatisés (pas de PHPUnit, pas de dossier `tests/`) : la vérification de chaque tâche se fait manuellement via `php -S` (serveur intégré PHP) + `curl`/navigateur + `sqlite3` CLI, en suivant les patterns déjà utilisés dans le repo (aucune divergence introduite par ce plan).
- Le serveur intégré PHP (`php -S`) ignore totalement les fichiers `.htaccess` : la tâche 3 (rewrite) se vérifie donc par un test de la regex isolé (`preg_match` en CLI), pas par une requête HTTP bout-en-bout. Le test HTTP bout-en-bout du rewrite ne peut être fait qu'après déploiement réel sur l'hébergement Apache (Infomaniak).

---

### Task 1: Migration DB — colonne `lists.hub_visible`

**Files:**
- Modify: `src/Utils/Database.php:60-68` (bloc de migration `is_surprise`, ajouter juste après)
- Modify: `src/Utils/Database.php:121-130` (`CREATE TABLE lists` dans `Database::init()`)

**Interfaces:**
- Produces: colonne `lists.hub_visible` (INTEGER, DEFAULT 0), lisible par toute requête `SELECT * FROM lists` existante (aucun changement d'API PHP à ce stade).

- [ ] **Step 1: Ajouter la migration automatique dans `Database::getConnection()`**

Dans `src/Utils/Database.php`, juste après le bloc existant (lignes 60-68) :
```php
                // Migration : assure l'existence de la colonne 'is_surprise' dans 'lists'
                $listColumns = self::$instance->query("PRAGMA table_info(lists)")->fetchAll();
                $hasIsSurprise = false;
                foreach ($listColumns as $col) {
                    if ($col['name'] === 'is_surprise') $hasIsSurprise = true;
                }
                if (!$hasIsSurprise) {
                    self::$instance->exec("ALTER TABLE lists ADD COLUMN is_surprise INTEGER DEFAULT 1;");
                }
```
ajouter :
```php
                // Migration : assure l'existence de la colonne 'hub_visible' dans 'lists'
                $hasHubVisible = false;
                foreach ($listColumns as $col) {
                    if ($col['name'] === 'hub_visible') $hasHubVisible = true;
                }
                if (!$hasHubVisible) {
                    self::$instance->exec("ALTER TABLE lists ADD COLUMN hub_visible INTEGER DEFAULT 0;");
                }
```

- [ ] **Step 2: Mettre à jour le schéma de référence dans `Database::init()`**

Dans le `CREATE TABLE lists` (lignes 121-130), remplacer :
```php
        $db->exec("CREATE TABLE IF NOT EXISTS lists (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            profile_id INTEGER, -- Lien vers la personne
            name TEXT NOT NULL,
            slug_admin TEXT UNIQUE NOT NULL,
            slug_public TEXT UNIQUE NOT NULL,
            is_surprise INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE
        )");
```
par :
```php
        $db->exec("CREATE TABLE IF NOT EXISTS lists (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            profile_id INTEGER, -- Lien vers la personne
            name TEXT NOT NULL,
            slug_admin TEXT UNIQUE NOT NULL,
            slug_public TEXT UNIQUE NOT NULL,
            is_surprise INTEGER DEFAULT 1,
            hub_visible INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE
        )");
```

Note : `scripts/migrate.php` n'est pas touché — ce fichier est déjà désynchronisé du schéma réel (il lui manque `is_surprise`, `admin_slug`, `admin_pin_hash`) et n'est appelé par aucun autre script du repo ; `Database::init()` (via `init_db.php`) est la seule source d'init utilisée. Le mettre à jour créerait une fausse impression de parité.

- [ ] **Step 3: Vérifier la migration sur une base de test fraîche**

```bash
cd /Users/jc/Documents/Scripts/shigaepouyen/wishi
rm -f /tmp/wishi_test_migration.sqlite
cp data/database.sqlite /tmp/wishi_test_migration.sqlite 2>/dev/null || true
php -r '
define("WISHI_TEST_DB", "/tmp/wishi_test_migration.sqlite");
require __DIR__ . "/vendor/autoload.php";
'
```
Si `data/database.sqlite` n'existe pas encore en local, initialiser d'abord :
```bash
mkdir -p data
php init_db.php
```
Puis vérifier la colonne :
```bash
sqlite3 data/database.sqlite "PRAGMA table_info(lists);" | grep hub_visible
```
Expected: une ligne contenant `hub_visible` type `INTEGER` avec `dflt_value` = `0`.

```bash
sqlite3 data/database.sqlite "SELECT COUNT(*) FROM lists WHERE hub_visible IS NULL OR hub_visible != 0;"
```
Expected: `0` (aucune liste existante n'est visible par défaut).

- [ ] **Step 4: Commit**

```bash
cd /Users/jc/Documents/Scripts/shigaepouyen/wishi
git add src/Utils/Database.php
git commit -m "Ajoute colonne lists.hub_visible (defaut prive) pour le hub public"
```

---

### Task 2: `ProfileController::publicHub()`

**Files:**
- Modify: `src/Controllers/ProfileController.php` (ajouter une méthode, ne pas toucher aux méthodes existantes)

**Interfaces:**
- Consumes: table `profiles` (`id`, `name`, `slug`, `emoji`, `color`), table `lists` (`id`, `profile_id`, `name`, `slug_public`, `hub_visible`), table `items` (pour le compte).
- Produces: `ProfileController::publicHub(string $slug): ?array` retournant `null` si profil introuvable **ou** si le profil existe mais n'a aucune liste avec `hub_visible = 1` ; sinon `['profile' => [...], 'lists' => [...]]` où chaque élément de `lists` contient au minimum `id`, `name`, `slug_public`, `count`.

- [ ] **Step 1: Ajouter la méthode dans `ProfileController`**

Dans `src/Controllers/ProfileController.php`, ajouter après la méthode `universeById()` (après la ligne 86, avant `private function slugify`) :
```php
    /**
     * Hub public : profil + uniquement ses listes marquées hub_visible=1.
     * Retourne null si profil introuvable OU si aucune liste visible,
     * pour ne jamais laisser deviner l'existence d'un slug par la réponse.
     */
    public function publicHub(string $slug): ?array {
        $db = Database::getConnection();

        $stmt = $db->prepare("SELECT id, name, slug, emoji, color FROM profiles WHERE slug = ?");
        $stmt->execute([$slug]);
        $profile = $stmt->fetch();

        if (!$profile) {
            return null;
        }

        $stmtLists = $db->prepare("
            SELECT l.id, l.name, l.slug_public,
            (SELECT COUNT(*) FROM items WHERE list_id = l.id) as count
            FROM lists l
            WHERE l.profile_id = ? AND l.hub_visible = 1
            ORDER BY l.created_at DESC
        ");
        $stmtLists->execute([$profile['id']]);
        $lists = $stmtLists->fetchAll();

        if (empty($lists)) {
            return null;
        }

        return [
            'profile' => $profile,
            'lists' => $lists
        ];
    }
```

- [ ] **Step 2: Vérifier manuellement avec un script de test jetable**

Créer un fichier temporaire (hors repo) `/tmp/test_public_hub.php` :
```php
<?php
require '/Users/jc/Documents/Scripts/shigaepouyen/wishi/vendor/autoload.php';

$db = \App\Utils\Database::getConnection();

// Setup : un profil "test-hub" avec 2 listes, une visible une privee
$db->exec("DELETE FROM profiles WHERE slug = 'test-hub'");
$db->exec("INSERT INTO profiles (name, slug, admin_slug, admin_pin_hash, emoji, color) VALUES ('Test Hub', 'test-hub', 'adminslugtest', 'hash', '🧪', 'indigo')");
$profileId = $db->lastInsertId();
$db->exec("INSERT INTO lists (profile_id, name, slug_admin, slug_public, hub_visible) VALUES ($profileId, 'Liste Visible', 'admslug1', 'pubslug1', 1)");
$db->exec("INSERT INTO lists (profile_id, name, slug_admin, slug_public, hub_visible) VALUES ($profileId, 'Liste Privee', 'admslug2', 'pubslug2', 0)");

$controller = new \App\Controllers\ProfileController();

$result = $controller->publicHub('test-hub');
echo "=== Profil avec 1 liste visible + 1 privee ===\n";
echo "Nb listes retournees : " . count($result['lists']) . " (attendu: 1)\n";
echo "Nom liste retournee : " . $result['lists'][0]['name'] . " (attendu: Liste Visible)\n";

$db->exec("UPDATE lists SET hub_visible = 0 WHERE profile_id = $profileId");
$result2 = $controller->publicHub('test-hub');
echo "=== Profil sans aucune liste visible ===\n";
var_dump($result2 === null); // attendu: true

$result3 = $controller->publicHub('slug-qui-nexiste-pas-du-tout');
echo "=== Slug inexistant ===\n";
var_dump($result3 === null); // attendu: true

$db->exec("DELETE FROM profiles WHERE slug = 'test-hub'");
```

```bash
php /tmp/test_public_hub.php
```
Expected:
```
=== Profil avec 1 liste visible + 1 privee ===
Nb listes retournees : 1 (attendu: 1)
Nom liste retournee : Liste Visible (attendu: Liste Visible)
=== Profil sans aucune liste visible ===
bool(true)
=== Slug inexistant ===
bool(true)
```

```bash
rm /tmp/test_public_hub.php
```

- [ ] **Step 3: Commit**

```bash
cd /Users/jc/Documents/Scripts/shigaepouyen/wishi
git add src/Controllers/ProfileController.php
git commit -m "Ajoute ProfileController::publicHub pour le hub public par profil"
```

---

### Task 3: Règle de réécriture `public/.htaccess`

**Files:**
- Create: `public/.htaccess`

**Interfaces:**
- Produces: toute requête vers un chemin qui n'est ni un fichier ni un dossier existant sous `public/` (donc pas `api/`, `assets/`, `hub.php`, `manifest.json`, etc.) et composée d'un seul segment alphanumérique/tirets est réécrite vers `profile.php?slug=<segment>`.

- [ ] **Step 1: Créer le fichier**

```apache
RewriteEngine On

# Ne pas toucher aux fichiers/dossiers existants (api/, assets/, hub.php, manifest.json, ...)
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]

# Un seul segment de chemin type /malcolm -> profile.php?slug=malcolm
RewriteRule ^([a-z0-9-]+)/?$ profile.php?slug=$1 [L,QSA]
```

Sauvegarder dans `public/.htaccess`.

- [ ] **Step 2: Vérifier la regex isolément (le serveur PHP intégré ignore .htaccess)**

```bash
php -r '
$tests = [
    "malcolm" => true,
    "chloe-2" => true,
    "api" => true, // matche la regex mais RewriteCond -d empeche la reecriture (dossier existant), verifie a part
    "malcolm/sous-chemin" => false,
    "" => false,
];
foreach ($tests as $path => $shouldMatch) {
    $matches = preg_match("#^([a-z0-9-]+)/?$#", $path, $m);
    $ok = ($matches === 1) === $shouldMatch;
    echo ($ok ? "OK   " : "FAIL ") . var_export($path, true) . " -> " . var_export((bool)$matches, true) . "\n";
}
'
```
Expected: toutes les lignes préfixées `OK`.

- [ ] **Step 3: Commit**

```bash
cd /Users/jc/Documents/Scripts/shigaepouyen/wishi
git add public/.htaccess
git commit -m "Ajoute rewrite Apache pour URL publique de profil (/malcolm)"
```

---

### Task 4: Contrôleur public `public/profile.php`

**Files:**
- Create: `public/profile.php`

**Interfaces:**
- Consumes: `ProfileController::publicHub(string $slug): ?array` (Task 2).
- Produces: page HTML rendue via `views/layouts/main.php` (variables `$title`, `$content`, `$body_class`) ; en cas de `null`, affiche `views/profile_public_view.php` en mode "introuvable" (voir Task 5) avec code HTTP 404.

- [ ] **Step 1: Créer le fichier, calqué sur `public/view.php` (aucune session, aucun CSRF, page 100% publique)**

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

$slug = $_GET['slug'] ?? '';

if (!$slug) {
    http_response_code(404);
    die("Page introuvable.");
}

$controller = new \App\Controllers\ProfileController();
$data = $controller->publicHub($slug);

if (!$data) {
    http_response_code(404);
    $title = "Wishi";
    $body_class = "bg-slate-50";
    ob_start();
    include __DIR__ . '/../views/profile_public_view.php';
    $content = ob_get_clean();
    include __DIR__ . '/../views/layouts/main.php';
    exit;
}

$profile = $data['profile'];
$lists = $data['lists'];
$color = $profile['color'] ?: 'indigo';

$title = "Wishi - L'univers de " . htmlspecialchars($profile['name']);
$body_class = "bg-$color-50/30";

ob_start();
include __DIR__ . '/../views/profile_public_view.php';
$content = ob_get_clean();

include __DIR__ . '/../views/layouts/main.php';
```

- [ ] **Step 2: Vérifier via le serveur intégré PHP (accès direct par query string, sans passer par le rewrite)**

```bash
cd /Users/jc/Documents/Scripts/shigaepouyen/wishi
php -S localhost:8099 -t public > /tmp/wishi_server.log 2>&1 &
sleep 1
curl -s "http://localhost:8099/profile.php?slug=slug-qui-nexiste-pas" -o /tmp/wishi_404.html -w "%{http_code}\n"
```
Expected: `404` imprimé, et `cat /tmp/wishi_404.html` doit contenir du HTML de page (pas d'erreur PHP brute — la Task 5 doit être faite avant que le contenu soit correct, mais la page ne doit pas planter même sans Task 5, `views/profile_public_view.php` sera géré au step suivant).

```bash
kill %1 2>/dev/null
```

- [ ] **Step 3: Commit**

```bash
cd /Users/jc/Documents/Scripts/shigaepouyen/wishi
git add public/profile.php
git commit -m "Ajoute controleur public profile.php (hub public par profil)"
```

---

### Task 5: Vue publique `views/profile_public_view.php`

**Files:**
- Create: `views/profile_public_view.php`

**Interfaces:**
- Consumes (variables PHP injectées par `public/profile.php`) :
  - Cas trouvé : `$profile` (array avec `name`, `emoji`, `color`), `$lists` (array de `['id','name','slug_public','count']`)
  - Cas non trouvé : aucune variable `$profile`/`$lists` définie (le fichier doit gérer les deux cas avec `isset()`)

- [ ] **Step 1: Créer le fichier**

```php
<?php if (!isset($profile)): ?>
    <div class="max-w-md mx-auto py-24 px-4 text-center">
        <div class="text-6xl mb-6 opacity-20">🔍</div>
        <h1 class="text-2xl font-bold text-slate-800 mb-2">Rien à voir ici</h1>
        <p class="text-slate-400 text-sm font-medium">Ce lien n'existe pas ou ne contient aucune liste partagée.</p>
    </div>
<?php else: ?>
    <?php $color = $profile['color'] ?: 'indigo'; ?>
    <div class="max-w-3xl mx-auto py-12 px-4">
        <header class="flex flex-col items-center text-center mb-12">
            <div class="text-6xl mb-4"><?= htmlspecialchars($profile['emoji'] ?: '👤') ?></div>
            <h1 class="text-3xl font-black text-slate-900 tracking-tight">
                L'univers de <span class="text-<?= $color ?>-600"><?= htmlspecialchars($profile['name']) ?></span>
            </h1>
            <p class="text-slate-400 mt-1 text-[10px] font-bold uppercase tracking-widest">
                Choisissez une liste pour offrir un cadeau
            </p>
        </header>

        <div class="grid gap-4">
            <?php foreach ($lists as $l): ?>
                <a href="view.php?s=<?= htmlspecialchars($l['slug_public']) ?>"
                   class="group bg-white p-6 rounded-2xl flex justify-between items-center shadow-sm border border-slate-100 hover:border-<?= $color ?>-200 hover:shadow-md transition-all">
                    <div class="flex items-center gap-5">
                        <div class="w-12 h-12 bg-<?= $color ?>-50 text-<?= $color ?>-500 rounded-xl flex items-center justify-center text-2xl">
                            🎁
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-800 text-xl tracking-tight"><?= htmlspecialchars($l['name']) ?></h3>
                            <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest">
                                <?= (int)$l['count'] ?> souhait<?= $l['count'] > 1 ? 's' : '' ?>
                            </p>
                        </div>
                    </div>
                    <div class="text-slate-300 group-hover:text-<?= $color ?>-500 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="3" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
```

- [ ] **Step 2: Vérifier les deux rendus via le serveur intégré**

```bash
cd /Users/jc/Documents/Scripts/shigaepouyen/wishi
php -S localhost:8099 -t public > /tmp/wishi_server.log 2>&1 &
sleep 1
curl -s "http://localhost:8099/profile.php?slug=slug-qui-nexiste-pas" | grep -o "Rien à voir ici"
```
Expected: `Rien à voir ici`

Créer un profil de test avec une liste visible (via sqlite3 CLI) puis vérifier le rendu positif :
```bash
sqlite3 data/database.sqlite "DELETE FROM profiles WHERE slug='test-hub-e2e';"
sqlite3 data/database.sqlite "INSERT INTO profiles (name, slug, admin_slug, admin_pin_hash, emoji, color) VALUES ('Test E2E', 'test-hub-e2e', 'x', 'x', '🧪', 'sky');"
sqlite3 data/database.sqlite "INSERT INTO lists (profile_id, name, slug_admin, slug_public, hub_visible) VALUES ((SELECT id FROM profiles WHERE slug='test-hub-e2e'), 'Ma Liste Test', 'admtest', 'pubtest', 1);"
curl -s "http://localhost:8099/profile.php?slug=test-hub-e2e" | grep -o "Ma Liste Test"
```
Expected: `Ma Liste Test`

```bash
sqlite3 data/database.sqlite "DELETE FROM lists WHERE slug_public='pubtest';"
sqlite3 data/database.sqlite "DELETE FROM profiles WHERE slug='test-hub-e2e';"
kill %1 2>/dev/null
```

- [ ] **Step 3: Commit**

```bash
cd /Users/jc/Documents/Scripts/shigaepouyen/wishi
git add views/profile_public_view.php
git commit -m "Ajoute vue publique du hub par profil"
```

---

### Task 6: Toggle "Visible dans le hub public" sur une liste

**Files:**
- Modify: `src/Controllers/ListController.php:125-160` (`updateSettings()`)
- Modify: `public/list.php` (sérialisation `listSettings`)
- Modify: `views/list_view.php:260-272` (modale réglages, ajouter le toggle après celui de `is_surprise`)

**Interfaces:**
- Consumes: aucune nouvelle dépendance externe.
- Produces: `ListController::updateSettings()` accepte désormais en plus un champ JSON optionnel `hub_visible` (booléen) ; `listSettings.hub_visible` disponible côté Alpine.js dans `list.php`.

- [ ] **Step 1: Étendre `ListController::updateSettings()`**

Dans `src/Controllers/ListController.php`, la méthode actuelle (lignes 125-160) :
```php
    public function updateSettings() {
        AdminAuth::start();

        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        $newName = $input['name'] ?? null;
        $regenSlug = $input['regen_slug'] ?? false;
        $isSurprise = isset($input['is_surprise']) ? ($input['is_surprise'] ? 1 : 0) : null;

        if (!$id || !$newName) return json_encode(['error' => 'Données manquantes']);
        if ($error = AdminAuth::ensureListAccessJson((int)$id)) return $error;

        $db = \App\Utils\Database::getConnection();
        
        $sql = "UPDATE lists SET name = ?";
        $params = [$newName];

        if ($regenSlug) {
            $newSlug = bin2hex(random_bytes(8));
            $sql .= ", slug_public = ?";
            $params[] = $newSlug;
        }

        if ($isSurprise !== null) {
            $sql .= ", is_surprise = ?";
            $params[] = $isSurprise;
        }

        $sql .= " WHERE id = ?";
        $params[] = $id;

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        return json_encode(['success' => true]);
    }
```
devient (ajout de `$hubVisible` et de son bloc SQL, juste après le bloc `is_surprise`) :
```php
    public function updateSettings() {
        AdminAuth::start();

        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        $newName = $input['name'] ?? null;
        $regenSlug = $input['regen_slug'] ?? false;
        $isSurprise = isset($input['is_surprise']) ? ($input['is_surprise'] ? 1 : 0) : null;
        $hubVisible = isset($input['hub_visible']) ? ($input['hub_visible'] ? 1 : 0) : null;

        if (!$id || !$newName) return json_encode(['error' => 'Données manquantes']);
        if ($error = AdminAuth::ensureListAccessJson((int)$id)) return $error;

        $db = \App\Utils\Database::getConnection();
        
        $sql = "UPDATE lists SET name = ?";
        $params = [$newName];

        if ($regenSlug) {
            $newSlug = bin2hex(random_bytes(8));
            $sql .= ", slug_public = ?";
            $params[] = $newSlug;
        }

        if ($isSurprise !== null) {
            $sql .= ", is_surprise = ?";
            $params[] = $isSurprise;
        }

        if ($hubVisible !== null) {
            $sql .= ", hub_visible = ?";
            $params[] = $hubVisible;
        }

        $sql .= " WHERE id = ?";
        $params[] = $id;

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        return json_encode(['success' => true]);
    }
```

- [ ] **Step 2: Étendre la sérialisation `listSettings` dans `public/list.php`**

Dans `public/list.php` (autour de la ligne 72), remplacer :
```php
        listSettings: ' . json_encode([
            'id' => $list['id'],
            'name' => $list['name'],
            'is_surprise' => $list['is_surprise'] == 1
        ]) . ',
```
par :
```php
        listSettings: ' . json_encode([
            'id' => $list['id'],
            'name' => $list['name'],
            'is_surprise' => $list['is_surprise'] == 1,
            'hub_visible' => $list['hub_visible'] == 1
        ]) . ',
```

- [ ] **Step 3: Ajouter le toggle dans `views/list_view.php`**

Après le bloc du toggle `is_surprise` (lignes 261-271) :
```php
                <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl border border-slate-100 mb-4">
                    <div>
                        <label class="text-sm font-bold text-slate-800">Mode Surprise 🎁</label>
                        <p class="text-[9px] text-slate-400 uppercase font-black tracking-widest leading-tight">Masquer les réservations sur cet écran</p>
                    </div>
                    <div class="relative inline-block w-10 align-middle select-none transition duration-200 ease-in">
                        <input type="checkbox" x-model="listSettings.is_surprise"
                               class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer border-gray-300 z-10"/>
                        <label class="toggle-label block overflow-hidden h-6 rounded-full bg-gray-300 cursor-pointer"></label>
                    </div>
                </div>
```
ajouter :
```php
                <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl border border-slate-100 mb-4">
                    <div>
                        <label class="text-sm font-bold text-slate-800">Visible dans le hub public 🏠</label>
                        <p class="text-[9px] text-slate-400 uppercase font-black tracking-widest leading-tight">Apparaît dans l'univers public du profil. Sinon, uniquement accessible par le lien direct.</p>
                    </div>
                    <div class="relative inline-block w-10 align-middle select-none transition duration-200 ease-in">
                        <input type="checkbox" x-model="listSettings.hub_visible"
                               class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer border-gray-300 z-10"/>
                        <label class="toggle-label block overflow-hidden h-6 rounded-full bg-gray-300 cursor-pointer"></label>
                    </div>
                </div>
```

- [ ] **Step 4: Vérifier manuellement le cycle complet via le serveur intégré**

```bash
cd /Users/jc/Documents/Scripts/shigaepouyen/wishi
sqlite3 data/database.sqlite "DELETE FROM profiles WHERE slug='test-toggle';"
sqlite3 data/database.sqlite "INSERT INTO profiles (name, slug, admin_slug, admin_pin_hash, emoji, color) VALUES ('Test Toggle', 'test-toggle', 'tok123', 'hash', '🧪', 'indigo');"
sqlite3 data/database.sqlite "INSERT INTO lists (profile_id, name, slug_admin, slug_public, hub_visible) VALUES ((SELECT id FROM profiles WHERE slug='test-toggle'), 'Liste Toggle', 'admtoggle', 'pubtoggle', 0);"
LISTID=$(sqlite3 data/database.sqlite "SELECT id FROM lists WHERE slug_admin='admtoggle';")

php -S localhost:8099 -t public > /tmp/wishi_server.log 2>&1 &
sleep 1

# Avant toggle : absente du hub
curl -s "http://localhost:8099/profile.php?slug=test-toggle" | grep -o "Rien à voir ici"

# Simule l'appel API de mise à jour (hub_visible=true)
curl -s -X POST "http://localhost:8099/api/update_list_settings.php" \
  -H "Content-Type: application/json" \
  -d "{\"id\": $LISTID, \"name\": \"Liste Toggle\", \"hub_visible\": true}"

# Après toggle : visible dans le hub
curl -s "http://localhost:8099/profile.php?slug=test-toggle" | grep -o "Liste Toggle"

kill %1 2>/dev/null
sqlite3 data/database.sqlite "DELETE FROM lists WHERE slug_admin='admtoggle';"
sqlite3 data/database.sqlite "DELETE FROM profiles WHERE slug='test-toggle';"
```
Expected: première ligne `Rien à voir ici`, appel API retourne `{"success":true}` (peut nécessiter une session admin selon `AdminAuth::ensureListAccessJson` — si l'appel échoue avec une erreur d'accès, vérifier directement en base après l'étape UI manuelle dans le navigateur plutôt que via curl brut), dernière ligne `Liste Toggle`.

Note : `AdminAuth::ensureListAccessJson()` peut exiger une session authentifiée (cookie PHP) selon l'implémentation existante — si le `curl` direct est rejeté, c'est le comportement de sécurité existant (inchangé par cette tâche) ; valider alors ce step via le navigateur (PIN profil test, puis toggle dans l'UI) plutôt que via `curl` sans session.

- [ ] **Step 5: Commit**

```bash
cd /Users/jc/Documents/Scripts/shigaepouyen/wishi
git add src/Controllers/ListController.php public/list.php views/list_view.php
git commit -m "Ajoute le toggle liste privee/visible dans le hub public"
```

---

### Task 7: Vérification finale bout-en-bout + nettoyage

**Files:**
- None (vérification uniquement)

- [ ] **Step 1: Relire le `.htaccess` produit en Task 3 dans le contexte réel du dossier `public/`**

```bash
cd /Users/jc/Documents/Scripts/shigaepouyen/wishi
ls public/ | grep -E "^(api|assets)$|\.php$|\.json$"
```
Confirmer que tous ces noms ne matchent jamais la regex catch-all seule (ils sont interceptés par les `RewriteCond -f`/`-d` avant d'atteindre la règle générique) — vérification visuelle : aucun de ces noms n'est un simple slug alphanumérique-tirets sans extension, donc pas de collision possible avec un futur `slug` de profil de toute façon (les profils ont des slugs générés depuis des prénoms, pas des noms de fichiers système).

- [ ] **Step 2: Test manuel complet dans un navigateur (après déploiement, ou en local avec un vrai serveur Apache si disponible)**

Checklist à dérouler :
- `/hub.php`, `/api/rates.php`, `/manifest.json` répondent comme avant (non cassés par le nouveau `.htaccess`)
- `/malcolm` (ou le slug d'un profil réel avec au moins une liste `hub_visible=1`) affiche le hub public, cartes cliquables vers les listes visibles uniquement
- `/malcolm` sur un profil dont toutes les listes sont privées affiche "Rien à voir ici"
- `/nimporte-quoi-qui-nexiste-pas` affiche la même page "Rien à voir ici"
- Toggle dans `list.php` : décocher/cocher "Visible dans le hub public", recharger `/malcolm`, vérifier apparition/disparition

- [ ] **Step 3: Mettre à jour le `readme.md` (section Structure du projet)**

Dans `readme.md`, après la ligne `└─ view.php       # Consultation d'une liste (Public)` (ligne 142), ajouter :
```
     └─ profile.php    # Hub public d'un profil (listes visibles uniquement)
```

- [ ] **Step 4: Commit final**

```bash
cd /Users/jc/Documents/Scripts/shigaepouyen/wishi
git add readme.md
git commit -m "Documente profile.php dans la structure du projet"
```
