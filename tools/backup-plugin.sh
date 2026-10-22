#!/usr/bin/env bash
# Backup CODE ONLY. Never stops services or deletes existing backups.
set -Eeuo pipefail
umask 077
HERE=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)
ROOT=$(cd -- "$HERE/.." && pwd -P)
source "$HERE/lib-output.sh"
DEST=${1:-/root/glpi-plugin-backups}
trap 'mdo_err "Backup del codice non completato (riga $LINENO). Conservati eventuali file parziali."' ERR
install -d -m 700 -- "$DEST"
DEST=$(cd -- "$DEST" && pwd -P)
case "$DEST/" in "$ROOT/"*) mdo_err 'La destinazione non deve trovarsi dentro il plugin.'; exit 1;; esac
ARCHIVE=$(mktemp "$DEST/mdoverhaul-$(date +%Y%m%d-%H%M%S).XXXXXX.tar.gz")
tar -czf "$ARCHIVE" -C "$(dirname -- "$ROOT")" "$(basename -- "$ROOT")"
tar -tzf "$ARCHIVE" > /dev/null
chmod 600 "$ARCHIVE"
(cd "$DEST" && sha512sum "$(basename -- "$ARCHIVE")" > "$(basename -- "$ARCHIVE").sha512")
mdo_ok "Backup codice: $ARCHIVE"
mdo_info 'Non include database, configurazione Compose, altri plugin o volumi GLPI. Non sostituisce il backup completo.'
