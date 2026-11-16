#!/usr/bin/env bash
# Enregistre la cle Gemini (Google AI Studio) dans data/gemini.key, sans qu'elle
# apparaisse a l'ecran, ni dans l'historique du shell, ni dans la liste des processus.
#
#   ./scripts/set_gemini_key.sh
#
# A lancer sur le serveur, a la racine de Wishi. data/ n'est jamais touche par le
# deploiement : la cle survit aux mises a jour. Pour couper l'IA, supprimer le fichier.
set -euo pipefail

cd "$(dirname "$0")/.."

KEY_FILE=data/gemini.key

printf 'Cle Gemini (AI Studio) (saisie masquee, rien ne s affiche) : '
read -rs GEMINI_KEY
printf '\n'

if [ -z "${GEMINI_KEY}" ]; then
    echo "Aucune cle saisie, rien n a ete modifie." >&2
    exit 1
fi

mkdir -p data
umask 077
printf '%s\n' "$GEMINI_KEY" > "$KEY_FILE"
chmod 600 "$KEY_FILE"
unset GEMINI_KEY

echo "Cle enregistree dans $KEY_FILE. Les descriptions par IA sont actives."
