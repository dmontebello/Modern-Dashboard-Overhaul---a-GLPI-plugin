#!/usr/bin/env bash
# Integrity FIRST; do not lint files from a damaged/incomplete release.
set -uo pipefail
HERE=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P) || exit 1
source "$HERE/lib-output.sh"
bash "$HERE/verifica-sha512.sh" || { mdo_err 'STOP: integrita non verificata; sintassi non eseguita.'; exit 1; }
bash "$HERE/verifica-sintassi.sh" "$@"
rc=$?
case "$rc" in
    0) mdo_ok 'Controlli completati senza errori.' ;;
    2) mdo_warn 'Controlli parziali: leggere i controlli saltati. Nessuna modifica al sistema.' ;;
    *) mdo_err 'Controlli falliti: non attivare la nuova versione.' ;;
esac
exit "$rc"
