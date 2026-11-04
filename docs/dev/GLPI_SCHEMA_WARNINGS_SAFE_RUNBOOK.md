# Avvisi GLPI 11: TIMESTAMP e chiavi UNSIGNED — diagnosi non invasiva

**4 novembre 2026 · MDO-TECH-008 / MDO-TECH-009 · STATO BLOCCATA (manca evidenza SQL dell'istanza).**
La schermata GLPI segnala **2 colonne DATETIME** e **3 chiavi signed**. Il conteggio è dell'istanza, non prova che tutte le colonne appartengano al plugin MDO. L'intervento deve essere verificato prima di ogni modifica. Nessuna password, dump o screenshot di dati privati va pubblicato sul GitHub pubblico.

## Diagnosi dal codice verificato (non dal DB installato)

- `0.8.1-dev.10` definiva `date_creation datetime NOT NULL` nelle tabelle `glpi_plugin_mdoverhaul_tasknotes` e `glpi_plugin_mdoverhaul_taskfiles`; le versioni recenti definiscono già `date_creation timestamp NOT NULL` **solo per le nuove tabelle**. L'installer salta correttamente la `CREATE TABLE` quando la tabella esiste: non migra strutture esistenti.
- `src/TaskActivitySchema.php` usa `DBConnection::getDefaultPrimaryKeySignOption()` per `id`, `items_id` e `users_id`. Questo è corretto per nuove installazioni in GLPI 11, ma non è prova dei tipi SQL effettivi nelle tabelle già presenti.
- Il vecchio `glpi_plugin_mdoverhaul_tasks` è **ritirato ma conservato**. Non droppare, cancellare o modificare le sue righe; conservare anche le righe `source=standalone` e gli allegati privati.
- Il numero delle **tre** colonne signed non identifica le colonne. Potrebbero essere in altri plugin o nel core: niente ALTER diretto, deduzioni o migrazioni preventive automatiche.

## Fase 1: comandi GLPI di sola lettura

Entrare nella **directory reale** di GLPI (non dentro `plugins/mdoverhaul`), nello **stesso container e con lo stesso utente di servizio** che gestisce GLPI. Verificare prima `pwd`, `php -v`, `php bin/console --version`, `php bin/console list`.

```bash
php bin/console db:check_schema_integrity --help
php bin/console db:check_schema_integrity --check-timestamps-migration --check-unsigned-keys-migration
```

Il secondo comando è un **check**, non un comando di conversione; l'exit code può indicare uno schema diverso. Verificare il nome effettivo attraverso `bin/console list`: alcune versioni usano `glpi:database:check_schema_integrity`. Il confronto core potrebbe non elencare tutte le colonne dei plugin; non dedurre l'assenza di problemi dal solo esito core. Se supportato, eseguire separatamente il confronto per MDO: `php bin/console db:check_schema_integrity --plugin=mdoverhaul --check-timestamps-migration --check-unsigned-keys-migration` (può dipendere dai file di schema del plugin).

## Fase 2: individuare le colonne SQL senza modificare nulla

Connettersi **in sola lettura**, dalla VM o dal container MariaDB/MySQL, al database GLPI **già selezionato**. Evitare credenziali in comando, cron, cronologia shell, chat o documentazione pubblica. Verificare `SELECT DATABASE(), VERSION();` prima delle query.

Query A: tutte le colonne DATETIME candidate (l'insieme può essere più ampio del conteggio dell'avviso GLPI, che usa criteri propri).

```sql
SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME LIKE 'glpi%'
  AND DATA_TYPE = 'datetime'
ORDER BY TABLE_NAME, COLUMN_NAME;
```

Query B: candidati signed per chiavi primarie o collegamenti (NON è un algoritmo identico alla diagnostica GLPI; possono comparire più di tre risultati).

```sql
SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, COLUMN_KEY, COLUMN_DEFAULT
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME LIKE 'glpi%'
  AND DATA_TYPE IN ('int','bigint','smallint','mediumint','tinyint')
  AND COLUMN_TYPE NOT LIKE '%unsigned%'
  AND (COLUMN_KEY = 'PRI' OR COLUMN_NAME = 'items_id'
       OR COLUMN_NAME REGEXP '(^id$|_id$)')
ORDER BY TABLE_NAME, COLUMN_NAME;
```

Query C: focus esclusivo sull'origine MDO, compresa la tabella storica inattiva.

```sql
SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, COLUMN_KEY, COLUMN_DEFAULT
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('glpi_plugin_mdoverhaul_tasks',
                     'glpi_plugin_mdoverhaul_tasknotes',
                     'glpi_plugin_mdoverhaul_taskfiles')
  AND (DATA_TYPE = 'datetime'
       OR (DATA_TYPE IN ('int','bigint','smallint','mediumint','tinyint')
           AND COLUMN_TYPE NOT LIKE '%unsigned%'))
ORDER BY TABLE_NAME, COLUMN_NAME;
```

Per ogni colonna candidata alla conversione, **prima su staging**, esaminare min/max dei valori data, eventuali date incompatibili con `TIMESTAMP`, fusi orari e valori/default negativi dei campi signed. Non correggere valori sentinella `-1` senza comprendere il significato applicativo; GLPI potrebbe rifiutare per questo la conversione. Verificare le relazioni e i vincoli SQL, anche verso altre tabelle.

## Fase 3: backup e staging, PRIMA di qualsiasi operazione di scrittura

1. Eseguire un **backup consistente, recuperabile e con test di restore**, del database GLPI, configurazione e `glpicrypt.key`, directory plugin e `GLPI_DOC_DIR/mdoverhaul` (anche dati storici). Per database InnoDB, `mariadb-dump --single-transaction` può essere adatto; verificare lo strumento e il piano di backup del deployment in uso.
2. Ripristinare una copia di staging, verificare conteggi e confrontare i valori critici; selezionare una finestra di manutenzione per il servizio reale.
3. Verificare `php bin/console migration:timestamps --help` e `php bin/console migration:unsigned_keys --help` **prima** di eseguire i comandi. Queste migrazioni sono native GLPI e possono coinvolgere tabelle del core e di altri plugin, non solo MDO. Non usare `--no-interaction`, `--force`, `migration:migrate_all` o ALTER SQL manuali.
4. **Solo su copia staging e solo con autorizzazione del gestore della VM**, eseguire una migrazione per volta e verificare output, conteggi, schema, record, date visualizzate (timezone), relazioni, log e funzioni del plugin. Ripetere la diagnostica. Se emergono valori fuori range o negativi, interrompere e fare triage senza alterazioni ad hoc.
5. Su produzione ripetere soltanto dopo esito staging e backup confermato, in finestra autorizzata. Ripetere le tre query e controllare che il warning sia sparito; documentare risultato e rollback effettivo.

Comandi **operativi (NON eseguiti né automaticamente autorizzati)**:
```bash
php bin/console migration:timestamps
php bin/console migration:unsigned_keys
```

La documentazione GLPI riporta anche i nomi estesi `glpi:migration:timestamps` e `glpi:migration:unsigned_keys`: verificare gli alias sull'istanza. GLPI indica che la migrazione DATETIME→TIMESTAMP può essere lunga ed è separata dall'update normale; prima di procedere leggere la sezione ufficiale sui fusi orari e verificare il backup.

## Criteri di chiusura

Chiudere MDO-TECH-008 / MDO-TECH-009 **solo dopo** evidenza SQL esatta delle colonne, backup verificato, prova su clone e nuova verifica che GLPI non mostri gli avvisi; registrare se gli oggetti erano di MDO/core/altri plugin. Nessun commit o ZIP MDO è di per sé una conversione del database. Non modificare `Main` per questo runbook.

Fonti ufficiali:
- https://glpi-install.readthedocs.io/en/latest/command-line.html
- https://help.glpi-project.org/documentation/cli
