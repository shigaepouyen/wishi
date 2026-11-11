#!/usr/bin/env python3
"""Compare le paquet à envoyer avec le manifeste du dernier déploiement (empreintes SHA-256).

Usage : deploy_plan.py DOSSIER_PAQUET MANIFESTE_PRECEDENT|- MANIFESTE_SORTIE SCRIPT_LFTP [--complet]

- MANIFESTE_PRECEDENT : fichier récupéré sur le serveur, ou « - » s'il n'y en a pas (premier envoi).
- Écrit le nouveau manifeste dans MANIFESTE_SORTIE et un script lftp (mkdir + put) pour les seuls
  fichiers nouveaux ou modifiés. Ne supprime jamais rien : les fichiers disparus du dépôt restent sur le serveur.
- --complet : ignore le manifeste précédent et renvoie tout (répare une dérive côté serveur).
"""
import hashlib
import json
import os
import sys

MANIFEST_NAME = ".deploy-manifest.json"


def sha256(path):
    h = hashlib.sha256()
    with open(path, "rb") as f:
        for chunk in iter(lambda: f.read(1 << 20), b""):
            h.update(chunk)
    return h.hexdigest()


def main():
    args = [a for a in sys.argv[1:] if a != "--complet"]
    complet = "--complet" in sys.argv[1:]
    if len(args) != 4:
        sys.exit(__doc__)
    paquet, precedent, sortie, script = args

    new = {}
    for racine, _, fichiers in os.walk(paquet):
        for nom in fichiers:
            chemin = os.path.join(racine, nom)
            rel = os.path.relpath(chemin, paquet).replace(os.sep, "/")
            if any(c in rel for c in '"$`\\\n'):
                sys.exit(f"Nom de fichier non pris en charge : {rel!r}")
            new[rel] = sha256(chemin)

    old = {}
    if not complet and precedent != "-" and os.path.isfile(precedent):
        with open(precedent) as f:
            old = json.load(f).get("files", {})

    changes = sorted(p for p, h in new.items() if old.get(p) != h)
    dirs = sorted({os.path.dirname(p) for p in changes if os.path.dirname(p)})

    os.makedirs(os.path.dirname(sortie) or ".", exist_ok=True)
    with open(sortie, "w") as f:
        json.dump({"version": 1, "files": new}, f, sort_keys=True, indent=0)

    with open(script, "w") as f:
        f.write("set cmd:fail-exit yes\n")
        for d in dirs:
            f.write(f'mkdir -p -f "/{d}"\n')
        for p in changes:
            d = os.path.dirname(p)
            f.write(f'put -O "/{d}" "{os.path.join(paquet, p)}"\n')
        # Le manifeste part en dernier : si un envoi échoue, il reste l'ancien et le prochain passage réessaie.
        f.write(f'put -O "/" "{sortie}" -o "{MANIFEST_NAME}"\n')

    mode = "complet (manifeste ignoré)" if complet else ("premier envoi" if not old else "différentiel")
    print(f"{len(changes)} fichier(s) à envoyer sur {len(new)} — {mode}")
    for p in changes[:200]:
        print(f"  {p}")
    if len(changes) > 200:
        print(f"  … et {len(changes) - 200} autres")


if __name__ == "__main__":
    main()
