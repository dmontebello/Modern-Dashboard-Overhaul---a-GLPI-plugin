#!/usr/bin/env bash
# PHP is parsed by the existing GLPI container via STDIN, even before mounting
# this new plugin. No PHP code is executed. Node JS is optional, never downloaded.
set -uo pipefail
HERE=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P) || exit 1
ROOT=$(cd -- "$HERE/.." && pwd -P) || exit 1
source "$HERE/lib-output.sh"
MODE=docker
CONTAINER=${MDO_GLPI_CONTAINER:-glpi-glpi-1}
NODE_IMAGE=${MDO_NODE_IMAGE:-}
while (( $# > 0 )); do
    case "$1" in
        --local) MODE=local; shift ;;
        --container) [[ $# -ge 2 && -n $2 ]] || { mdo_err 'Manca il nome container.'; exit 1; }; CONTAINER=$2; shift 2 ;;
        --node-image) [[ $# -ge 2 && -n $2 ]] || { mdo_err 'Manca il nome immagine Node.'; exit 1; }; NODE_IMAGE=$2; shift 2 ;;
        --help|-h)
            printf 'Uso: bash tools/verifica-sintassi.sh [--container glpi-glpi-1] [--local] [--node-image node:22-alpine]\n'
            printf 'Exit: 0=PHP/Bash/JS superati; 1=errore; 2=PHP/Bash superati ma JS non controllato.\n'
            printf 'Non installa dipendenze. CSS non e verificato da questi parser.\n'; exit 0 ;;
        *) mdo_err "Opzione sconosciuta: $1"; exit 1 ;;
    esac
done
errors=0 php_count=0 sh_count=0 js_count=0 incomplete=0
if [[ $MODE == docker ]]; then
    command -v docker >/dev/null 2>&1 || { mdo_err 'Docker non disponibile; usare --local solo per un test locale con PHP.'; exit 1; }
    if ! docker exec "$CONTAINER" php --version >/dev/null 2>&1; then
        mdo_err "PHP non raggiungibile nel container $CONTAINER (controllare stato/permessi Docker)."; exit 1
    fi
    mdo_info "Parser PHP: container $CONTAINER. I file locali passano via stdin, senza mount temporanei."
else
    command -v php >/dev/null 2>&1 || { mdo_err 'PHP locale non disponibile.'; exit 1; }
    mdo_info 'Parser PHP locale: questo NON prova il caricamento nel GLPI reale.'
fi
while IFS= read -r -d '' file; do
    relative=${file#"$ROOT/"}
    if [[ $MODE == docker ]]; then
        output=$(docker exec -i "$CONTAINER" php -l < "$file" 2>&1); rc=$?
    else
        output=$(php -l "$file" 2>&1); rc=$?
    fi
    php_count=$((php_count+1))
    if (( rc == 0 )); then mdo_ok "PHP $relative";
    else mdo_err "PHP $relative"; printf '%s\n' "$output"; errors=$((errors+1)); fi
done < <(find "$ROOT" -type f \( -name '*.php' -o -name '*.php.example' \) -print0)
while IFS= read -r -d '' file; do
    relative=${file#"$ROOT/"}
    output=$(bash -n "$file" 2>&1); rc=$?
    sh_count=$((sh_count+1))
    if (( rc == 0 )); then mdo_ok "Bash $relative";
    else mdo_err "Bash $relative"; printf '%s\n' "$output"; errors=$((errors+1)); fi
done < <(find "$ROOT/tools" -type f -name '*.sh' -print0)
JS_MODE=none
if [[ -n $NODE_IMAGE ]]; then
    if command -v docker >/dev/null 2>&1 && docker image inspect "$NODE_IMAGE" >/dev/null 2>&1; then
        JS_MODE=image
    else
        mdo_err "Immagine Node non presente localmente: $NODE_IMAGE. Nessun download automatico."
        errors=$((errors+1))
    fi
elif command -v node >/dev/null 2>&1; then JS_MODE=local
elif [[ $MODE == docker ]] && docker exec "$CONTAINER" node --version >/dev/null 2>&1; then JS_MODE=container
fi
if [[ $JS_MODE == none ]]; then
    mdo_warn 'JavaScript NON controllato: Node non disponibile. Non equivale a un test superato.'
    mdo_info 'Facoltativo: predisporre node:22-alpine e rieseguire con --node-image node:22-alpine.'
    incomplete=1
else
    while IFS= read -r -d '' file; do
        relative=${file#"$ROOT/"}
        case "$JS_MODE" in
            local) output=$(node --check "$file" 2>&1); rc=$? ;;
            container) output=$(docker exec -i "$CONTAINER" node --check < "$file" 2>&1); rc=$? ;;
            image) output=$(docker run --rm -i --pull never --network none --read-only --cap-drop ALL --security-opt no-new-privileges --user 65534:65534 --memory 256m --cpus 1 --pids-limit 64 --entrypoint node "$NODE_IMAGE" --check < "$file" 2>&1); rc=$? ;;
        esac
        js_count=$((js_count+1))
        if (( rc == 0 )); then mdo_ok "JavaScript $relative";
        else mdo_err "JavaScript $relative"; printf '%s\n' "$output"; errors=$((errors+1)); fi
    done < <(find "$ROOT/public" -type f \( -name '*.js' -o -name '*.mjs' \) -print0)
fi
(( php_count > 0 && sh_count > 0 )) || { mdo_err 'File PHP/Bash mancanti.'; errors=$((errors+1)); }
mdo_info "Controllati: $php_count PHP, $sh_count Bash, $js_count JavaScript. CSS/layout: da collaudare nel browser."
if (( errors > 0 )); then mdo_err "Sintassi NON superata: $errors errori."; exit 1; fi
if (( incomplete > 0 )); then mdo_warn 'PHP e Bash OK; verifica sintassi parziale (JavaScript non eseguito).'; exit 2; fi
mdo_ok 'Sintassi PHP, Bash e JavaScript superata. Non sostituisce un test funzionale in GLPI.'
