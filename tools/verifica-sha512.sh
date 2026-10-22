#!/usr/bin/env bash
# Read-only integrity check. No installation, no network, no database access.
set -uo pipefail
HERE=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P) || exit 1
ROOT=$(cd -- "$HERE/.." && pwd -P) || exit 1
source "$HERE/lib-output.sh"
command -v sha512sum >/dev/null 2>&1 || { mdo_err 'sha512sum non disponibile.'; exit 1; }
MANIFEST="$ROOT/SHA512SUMS"
[[ -f $MANIFEST && ! -L $MANIFEST ]] || { mdo_err 'SHA512SUMS assente o symlink.'; exit 1; }
declare -A listed=()
errors=0 count=0
mdo_info "Controllo SHA-512: $ROOT"
while IFS= read -r line || [[ -n $line ]]; do
    [[ -n $line ]] || continue
    hash=${line:0:128}
    separator=${line:128:2}
    file=${line:130}
    if [[ ! $hash =~ ^[0-9a-fA-F]{128}$ ]] || [[ $separator != '  ' && $separator != ' *' ]] ||
       [[ ! $file =~ ^[a-zA-Z0-9_.][a-zA-Z0-9_./-]*$ ]] ||
       [[ $file == /* || $file == '..' || $file == ../* || $file == */../* || $file == */.. ]]; then
        mdo_err 'Riga non valida nel manifest (percorso/hash).'
        errors=$((errors+1)); continue
    fi
    if [[ -n ${listed[$file]+x} ]]; then
        mdo_err "Voce duplicata: $file"; errors=$((errors+1)); continue
    fi
    listed[$file]=1
    count=$((count+1))
    if [[ ! -f $ROOT/$file || -L $ROOT/$file ]]; then
        mdo_err "File assente o symlink: $file"; errors=$((errors+1)); continue
    fi
    if output=$(sha512sum -- "$ROOT/$file" 2>&1) && [[ ${output%% *} == "${hash,,}" ]]; then
        mdo_ok "SHA512 $file"
    else
        mdo_err "SHA512 non corrispondente: $file"; errors=$((errors+1))
    fi
done < "$MANIFEST"
# Unlisted release files can be leftovers from a bad extraction. Do not ignore them.
while IFS= read -r -d '' path; do
    file=${path#"$ROOT/"}
    [[ $file == SHA512SUMS ]] && continue
    if [[ $file == config/ui.local.php ]]; then
        if [[ -L $path ]]; then
            mdo_err 'Override locale ui.local.php: symlink non ammesso.'
            errors=$((errors+1))
        else
            mdo_warn 'Override locale ui.local.php: escluso dal manifest, verificato dalla sintassi.'
        fi
        continue
    fi
    if [[ -L $path || -z ${listed[$file]+x} ]]; then
        mdo_err "File extra/non coperto o symlink: $file"; errors=$((errors+1))
    fi
done < <(find "$ROOT" \( -type f -o -type l \) -print0)
if (( count == 0 )); then mdo_err 'Manifest vuoto.'; errors=$((errors+1)); fi
if (( errors > 0 )); then mdo_err "Integrita NON verificata: $errors errori."; exit 1; fi
mdo_ok "Integrita verificata: $count file. Nessun file modificato."
mdo_info 'Un hash rileva alterazioni, non certifica la provenienza o la sicurezza del codice.'
