-- 1. Table des PROFILS (Les membres de la famille)
-- Utilisation de INTEGER PRIMARY KEY AUTOINCREMENT pour la compatibilité SQLite parfaite
CREATE TABLE IF NOT EXISTS profiles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    slug TEXT UNIQUE NOT NULL,       -- Utilisé pour universe.php?slug=zoe
    emoji TEXT,                      -- Ex: 🦄, 🎮
    color TEXT DEFAULT 'indigo'      -- Thème visuel (rose, sky, emerald...)
);

-- 2. Table des LISTES (Liées à un profil)
CREATE TABLE IF NOT EXISTS lists (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    profile_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    slug_admin TEXT UNIQUE NOT NULL,  -- Utilisé pour list.php?slug=abc123... (Admin)
    slug_public TEXT UNIQUE NOT NULL, -- Utilisé pour view.php?s=xyz789... (Famille)
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE
);

-- 3. Table des SOUHAITS (Cadeaux dans une liste)
CREATE TABLE IF NOT EXISTS items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    list_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    price DECIMAL(10, 2),
    url TEXT,
    image_url TEXT,
    description TEXT,
    category TEXT,
    priority INTEGER DEFAULT 1,      -- 1: Normal, 2: Important, 3: Coup de coeur
    position INTEGER DEFAULT 0,      -- Pour le drag-and-drop
    is_taken INTEGER DEFAULT 0,      -- 0: Libre, 1: Réservé
    taken_by TEXT,                   -- Nom de la personne qui offre
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (list_id) REFERENCES lists(id) ON DELETE CASCADE
);