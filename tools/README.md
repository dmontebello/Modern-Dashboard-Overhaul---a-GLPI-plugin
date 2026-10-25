# Controlli e backup - Modern Dashboard Overhaul 0.8.1-dev.4 - VM Linux

> **Corrente 0.8.1-dev.2:** vedere la sezione aggiornata in fondo; le descrizioni precedenti restano riferimento storico delle versioni indicate.

Questi script NON installano il plugin, NON modificano Docker Compose, NON fermano servizi
ed NON modificano il database. L'unico script che crea un archivio e backup-plugin.sh.

## Un comando per i controlli

```bash
bash /opt/glpi/plugins/mdoverhaul/tools/verifica-tutto.sh
```

1. Verifica SHA-512 di tutti i file distribuiti; rileva file mancanti, diversi, extra o symlink.
2. Se gli hash non corrispondono, si ferma prima dei parser.
3. PHP: invia ciascun file LOCALE allo stdin di `php -l` nel container `glpi-glpi-1`.
   Questo funziona anche PRIMA di montare mdoverhaul nel container e non esegue il codice PHP.
4. Bash: `bash -n` sugli script. Non li esegue.
5. JavaScript: `node --check`, se Node e disponibile nella VM o nel container GLPI.
   Senza Node: AVVISO giallo, codice 2. Non dichiara superato un controllo non svolto.
6. CSS e comportamento reale: si verificano nel browser, non con php -l.

Controlli separati:

```bash
bash /opt/glpi/plugins/mdoverhaul/tools/verifica-sha512.sh
bash /opt/glpi/plugins/mdoverhaul/tools/verifica-sintassi.sh
```

Container con altro nome:

```bash
bash /opt/glpi/plugins/mdoverhaul/tools/verifica-tutto.sh --container NOME_CONTAINER
```

Solo ambiente di sviluppo con PHP installato localmente:

```bash
bash tools/verifica-tutto.sh --local
```

## Controllare anche JavaScript senza installare Node su GLPI
Facoltativo. Il comando pull scarica UN'IMMAGINE AGGIUNTIVA e occupa spazio su disco;
non e necessario al funzionamento del plugin. Non e eseguito automaticamente.

```bash
docker pull node:22-alpine
bash /opt/glpi/plugins/mdoverhaul/tools/verifica-tutto.sh --node-image node:22-alpine
```

Il controllo crea un container temporaneo senza rete, non-root, read-only, senza mount,
con limiti di risorse. Gli passa il JavaScript via stdin e lo elimina al termine. Usa una
immagine gia presente (--pull never) e non installa Node nel container GLPI.
L'immagine puo essere eliminata in seguito solo se non serve ad altri processi:
`docker image rm node:22-alpine` (nessun prune generalizzato).

## Colori ed esiti
- [OK] verde: quel controllo specifico e superato.
- [ERRORE] rosso: controllo fallito; non attivare la nuova versione.
- [AVVISO] giallo: controllo saltato o file locale fuori dal manifest.
- Exit 0: tutti i controlli richiesti/disponibili di PHP, Bash e JS superati.
- Exit 1: errore di integrita, sintassi o dipendenze obbligatorie.
- Exit 2: integrita e PHP/Bash superati; JavaScript non verificato.

In SSH interattiva i colori sono automatici. `FORCE_COLOR=1 bash ...` li forza;
`NO_COLOR=1 bash ...` li disabilita. I messaggi testuali restano leggibili nei log.
La verifica non cambia file, non corregge errori e non equivale a collaudo funzionale.

## Integrita e fiducia
Il checksum esterno dello ZIP va controllato PRIMA di eseguire script estratti.
SHA512SUMS interno copre tutti i file della release tranne se stesso. Non e una firma digitale:
un attaccante che sostituisce sia file sia checksum puo falsificare entrambi. Conservare il
pacchetto e il checksum ottenuti dal canale fidato. Non rigenerare SHA512SUMS per nascondere errori.
`config/ui.local.php` e l'unica eccezione ammessa: e configurazione locale non distribuita;
la sua presenza viene segnalata e la sintassi viene comunque controllata.

## Backup del solo plugin

```bash
bash /opt/glpi/plugins/mdoverhaul/tools/backup-plugin.sh
```

Crea un archivio datato e un .sha512 in /root/glpi-plugin-backups, senza sovrascrivere
backup precedenti e senza riavviare niente. Conservare a parte un backup completo di
GLPI (database, file persistenti, Compose, certificati, immagini personalizzate).
Per una destinazione diversa passarla come primo argomento. Non dentro la cartella plugin.

## Solo per manutentori: nuove release documentate

Gli strumenti **opzionali** `tools/maintainer/check_release.py` e
`tools/maintainer/package_mdo.py` richiedono Python 3 **sulla macchina di build**,
non in GLPI. Impongono file README/manuali/versioni coerenti e creano il manifest SHA-512,
la ZIP e il checksum esterno. Sono descritti in [docs/RELEASE_PROCESS.md](../docs/RELEASE_PROCESS.md).

Non sostituiscono `verifica-tutto.sh` e non richiedono Python sulla VM GLPI.

## Runtime PHP critico (0.7.0)

```bash
bash tools/verifica-runtime-php.sh
# oppure, per il solo PHP CLI locale:
bash tools/verifica-runtime-php.sh --local
```

Controllo **read-only** distinto da `verifica-tutto.sh`, perché una distribuzione può usare backport PHP e il CLI può differire da Apache/FPM. Restituisce `2` quando la versione è antecedente o fuori ramo noto: è necessaria una verifica urgente del runtime effettivo, non un auto-update. Vedi [docs/SECURITY_PHP_2026.md](../docs/SECURITY_PHP_2026.md).

### Test di sviluppo (non necessari sulla VM GLPI)

- `php tests/unit_php.php` — unit test PHP con classi GLPI simulate
- `python tests/ui_playwright.py` — test responsive / navigazione in Chromium headless con Python Playwright (test locale, non GLPI reale)
- `python tests/benchmark_ui.py --previous-js <path-to-0.6-js> --output result.json` — benchmark dei timer per grafici

Questi test non richiedono rete, database GLPI o credenziali. Gli script operativi `verifica-tutto.sh` e `backup-plugin.sh` restano utilizzabili senza Python Playwright.

## 0.8.0 — Un unico ZIP dalla checkout

```bash
python3 -B tools/maintainer/package_from_repo.py
sha512sum -c Modern_Dashboard_Overhaul_0.8.0_Pilot.zip.sha512
unzip -t Modern_Dashboard_Overhaul_0.8.0_Pilot.zip
```

Lo script usa una copia temporanea con radice `mdoverhaul/`, esclude il codice GitHub non distribuibile e genera ZIP e SHA512. Nessun kit di patch; l'update dal plugin 0.6/0.7 è diretto. La ZIP distribuita e il checksum vengono pubblicati su `Main` dopo la validazione del codice sviluppato su `Dev`.



## Aggiornamento 0.8.1-dev.1

package_from_repo.py genera Modern_Dashboard_Overhaul_0.8.1-dev.1.zip e sidecar. check_dev_docs.py verifica note e versioni; publish_dev.py valida sorgenti/metadata e pubblica soltanto Dev dopo i gate CI. Un checkout dirty produce metadata dirty e non può essere pubblicato.

Stato: NON COLLAUDATO LIVE. Note: [0.8.1-dev.1](../docs/releases/0.8.1-dev.1.md).

## Aggiornamento 0.8.1-dev.2

Aggiunti standalone_php.php e standalone_ui_playwright.py ai gate CI; publisher
rimane vincolato al source testato e al branch Dev. check_dev_docs.py controlla
anche la dichiarazione versione corrente di README. Per il backup prima del
collaudo: bash tools/backup-standalone.sh /percorso/client.cnf database /backup-esterno.
Richiede mariadb-dump o mysqldump; nessun valore credenziale nel comando o nel
repository. Lo script crea dump con umask 077 e checksum, non avvia upgrade.
Backup/restore DB reali non eseguiti da Codex.

Stato: NON COLLAUDATO LIVE. [Note complete](../docs/releases/0.8.1-dev.2.md).

## Aggiornamento MDO 0.8.1-dev.3 (25 ottobre 2026)

Tooling candidato 0.8.1-dev.3: eseguire php tests/tasks_php.php per verificare anche il redirect-eccezione GLPI 11, poi test unit/standalone/browser e il gate documentazione/pacchetto; pubblicazione ZIP Dev e SHA512 solo dopo CI verde. Non usare lo ZIP 0.8.1-dev.2 per testare questo fix.

## Aggiornamento 0.8.1-dev.4 (25 ottobre 2026)

Tooling 0.8.1-dev.4: php tests/standalone_php.php verifica la modalità return-only della Dropdown GLPI. python3 -B tests/packaging_test.py riproduce con git fixture docs-only vs code changed; gate versioned archive unchanged. GitHub Actions e checksum restano fonti per PASS effettivi. Nessun deploy eseguito da questi script.

## Aggiornamento 0.8.1-dev.5

Regression di dev.5: php tests/tasks_php.php e php tests/standalone_php.php verificano il formato ORDER delle tre query MDO. Il double TaskFakeDB::request rifiuta l'associativo che causava il vero MySQL 1054. I test e il packaging restano isolati, non sostituiscono una prova su GLPI 11 reale. Installare la ZIP soltanto dopo CI verde e verifica sha512sum -c.


## 0.8.1-dev.6

Tasks: schema2, diario e allegati. ZIP solo con pipeline e SHA512 verificati; NON COLLAUDATO LIVE. Consultare docs/releases/0.8.1-dev.6.md.
