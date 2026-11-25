#!/bin/bash
# =====================================================
#  Met à jour le code du site SANS toucher à :
#    - tes photos          (public/uploads/)
#    - tes emails de test  (storage/)
#    - tes réglages        (config/config.local.php)
#
#  Utilisation (dans le Terminal) :
#    bash /Applications/MAMP/htdocs/projet-echange/outils/mettre-a-jour.sh ~/Downloads/projet-echange
#  (le chemin à la fin = le dossier de la NOUVELLE version que tu viens de dézipper)
#
#  Variante : un 2e chemin pour indiquer le site à mettre à jour
#    bash ~/Downloads/projet-echange/outils/mettre-a-jour.sh ~/Downloads/projet-echange /Applications/MAMP/htdocs/projet-echange
# =====================================================
set -e

NOUVELLE="${1%/}"
SITE="${2:-$(dirname "$0")/..}"
if [ ! -f "$SITE/public/index.php" ]; then
  echo "❌ Site introuvable : $SITE"
  exit 1
fi
SITE="$(cd "$SITE" && pwd)"

if [ -z "$NOUVELLE" ] || [ ! -f "$NOUVELLE/public/index.php" ]; then
  echo "❌ Indique le dossier de la nouvelle version, par exemple :"
  echo "   bash $0 ~/Downloads/projet-echange"
  exit 1
fi
if [ "$(cd "$NOUVELLE" && pwd)" = "$SITE" ]; then
  echo "❌ C'est le même dossier que le site actuel."
  exit 1
fi

echo "📦 Nouvelle version : $NOUVELLE"
echo "🌐 Site mis à jour  : $SITE"
echo

rsync -a --itemize-changes \
  --exclude 'public/uploads/' \
  --exclude 'storage/' \
  --exclude 'config/config.local.php' \
  --exclude '.git/' \
  "$NOUVELLE/" "$SITE/" | grep -E '^[<>c]f' | awk '{print "   ✏️  " $2}' || true

chmod 777 "$SITE/public/uploads" "$SITE/storage/emails" 2>/dev/null || true

echo
echo "✅ Code mis à jour. Tes photos, emails de test et réglages n'ont pas été touchés."
echo "👉 Ouvre le site connecté en admin : s'il y a des mises à jour de la base, la page s'affiche toute seule."
