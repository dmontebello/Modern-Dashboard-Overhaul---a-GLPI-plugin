#!/usr/bin/env bash
# Full staging DB backup before plugin upgrade; never embeds credentials or dumps in plugin.
set -euo pipefail
umask 077
if [[ $# != 3 ]]; then
    printf 'Uso: bash tools/backup-standalone.sh /percorso/client.cnf database /directory/backup-esterna\n' >&2
    exit 2
fi
client_config=$1
database_name=$2
backup_directory=$3
[[ -f $client_config && $database_name =~ ^[a-zA-Z0-9_]+$ ]] || { printf 'Configurazione o database non valido.\n' >&2; exit 2; }
plugin_root=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd -P)
mkdir -p -- "$backup_directory"
backup_directory=$(cd -- "$backup_directory" && pwd -P)
[[ $backup_directory != "$plugin_root" && $backup_directory != "$plugin_root/"* ]] || { printf 'Il backup deve stare fuori dal plugin/repository.\n' >&2; exit 2; }
if command -v mariadb-dump >/dev/null; then dump_command=mariadb-dump; else dump_command=mysqldump; fi
command -v "$dump_command" >/dev/null
backup_file=$(mktemp "$backup_directory/mdo-pre-upgrade-$(date -u +%Y%m%dT%H%M%SZ).XXXXXX.sql")
if ! "$dump_command" --defaults-extra-file="$client_config" --single-transaction --no-tablespaces --hex-blob --result-file="$backup_file" "$database_name"; then
    rm -f -- "$backup_file"
    printf 'Backup fallito: nessun upgrade eseguito.\n' >&2
    exit 1
fi
sha512sum -- "$backup_file" > "$backup_file.sha512"
printf 'Backup privato creato: %s\nVerificare il ripristino in staging prima dell’upgrade.\n' "$backup_file"
