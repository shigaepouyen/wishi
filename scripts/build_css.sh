#!/usr/bin/env bash
# Recompile public/assets/css/tailwind.css apres toute modification de classes dans les vues.
# A lancer en local avant de committer (le CSS compile est versionne, rien a construire sur le serveur).
set -euo pipefail
cd "$(dirname "$0")/../tailwind"
npm install --silent --no-audit --no-fund
npm run --silent build
