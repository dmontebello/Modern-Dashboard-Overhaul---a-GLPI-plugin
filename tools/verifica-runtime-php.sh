#!/usr/bin/env bash
# Non-invasive critical PHP2Shell release checker (2026-10-21).
# CLI PHP may differ from web SAPI, and distribution backports require vendor validation.
set -uo pipefail
HERE=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P) || exit 1
source "$HERE/lib-output.sh"
MODE=docker
CONTAINER=${MDO_GLPI_CONTAINER:-glpi-glpi-1}
if [[ ${1:-} == --local ]]; then
    MODE=local
elif [[ $# -gt 0 ]]; then
    mdo_err 'Uso: bash tools/verifica-runtime-php.sh [--local]'
    exit 1
fi
if [[ $MODE == docker ]]; then
    command -v docker >/dev/null 2>&1 || { mdo_err 'Docker non disponibile.'; exit 1; }
    version=$(docker exec "$CONTAINER" php -r 'echo PHP_VERSION;' 2>/dev/null) || {
        mdo_err "Impossibile interrogare PHP nel container $CONTAINER."; exit 1;
    }
    mdo_info "Runtime PHP CLI nel container: $version"
    modules=$(docker exec "$CONTAINER" sh -c 'apache2ctl -M 2>/dev/null | grep -i php || true' 2>/dev/null) || modules=''
    if [[ -n $modules ]]; then
        mdo_warn "Apache carica un modulo PHP: ${modules//$'\n'/; }. Verificare subito anche la versione web effettiva."
    else
        mdo_info 'Modulo mod_php non rilevato da apache2ctl (non esclude FPM/CGI).'
    fi
else
    command -v php >/dev/null 2>&1 || { mdo_err 'PHP locale non disponibile.'; exit 1; }
    version=$(php -r 'echo PHP_VERSION;' 2>/dev/null) || { mdo_err 'PHP locale non eseguibile.'; exit 1; }
    mdo_info "PHP CLI locale: $version (NON prova la versione del container GLPI)"
fi
if [[ ! $version =~ ^([0-9]+)\.([0-9]+)\.([0-9]+) ]]; then
    mdo_warn "Versione PHP non riconosciuta: $version; verifica manuale necessaria."; exit 2
fi
major=${BASH_REMATCH[1]} minor=${BASH_REMATCH[2]} patch=${BASH_REMATCH[3]}
minimum=''
if (( major == 8 )); then
    case "$minor" in
       2) minimum=35 ;;
       3) minimum=36 ;;
       4) minimum=27 ;;
       5) minimum=12 ;;
    esac
fi
if [[ -z $minimum ]]; then
    mdo_warn 'Ramo PHP fuori dai rami 8.2-8.5 verificati: controllare advisory vendor e supporto.'
    exit 2
fi
if (( patch < minimum )); then
    mdo_err "PHP ${major}.${minor}.${patch} e precedente a ${major}.${minor}.${minimum}: CVE-2026-109618 / CVE-2026-109619 (CVSS 9.2)."
    mdo_warn 'Possibili backport Debian/Ubuntu: controllare il changelog di sicurezza dei PACCHETTI nel container; non basarsi sulla stringa PHP_VERSION.'
    mdo_warn 'Non esiste mitigazione completa alternativa alla patch. Nessun aggiornamento e stato eseguito.'
    exit 2
fi
mdo_ok "PHP CLI ${major}.${minor}.${patch} >= ${major}.${minor}.${minimum} (versione upstream corretta)."
mdo_warn 'Questo test NON certifica il runtime web: verificare mod_php/FPM, versione pacchetti e riavvio worker.'
exit 0
