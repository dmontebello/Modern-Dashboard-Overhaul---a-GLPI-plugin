# Mappa cartelle — Modern Dashboard Overhaul 0.8.1-dev.2

> **Corrente 0.8.1-dev.2:** vedere la sezione aggiornata in fondo; le descrizioni precedenti restano riferimento storico delle versioni indicate.

Questo albero è un **esempio Docker Compose**, non l'output di una verifica remota. I nomi di servizi/cartelle possono variare. I percorsi di montaggio `mdoverhaul` descrivono la configurazione di riferimento.

```text
PACCHETTO DISTRIBUITO (sul PC o file server)
Modern_Dashboard_Overhaul_0.8.0_Pilot.zip
└── mdoverhaul/
    ├── setup.php                 [metadati e hook GLPI]
    ├── hook.php                  [install / uninstall]
    ├── README.md                 [introduzione + indice]
    ├── CHANGELOG.md              [storia delle versioni]
    ├── UPDATE.md                 [aggiornamento e rollback]
    ├── QA.md                     [stato dei test]
    ├── LICENSE                   [MIT]
    ├── SHA512SUMS                [integrità dei file distribuiti]
    ├── config/
    │   ├── ui.php                [fallback impostazioni 0.5]
    │   └── ui.local.php.example  [esempio, NON file locale reale]
    ├── src/
    │   ├── Dashboard.php         [RSS: card e HTML]
    │   ├── FeedFormatter.php     [RSS: normalizzazione]
    │   └── Settings.php          [sette flag GLPI]
    ├── front/
    │   └── config.php            [WebGUI Configura]
    ├── public/
    │   ├── css/                  [Restyling, RSS, impostazioni]
    │   └── js/                   [integrazione dashboard nativa]
    ├── docs/                      [architettura, installazione, sicurezza ...]
    └── tools/                     [controlli SHA-512/sintassi, backup]

DISCO LOCALE DELLA VM/SERVER DOCKER
/opt/glpi/
├── docker-compose.yml              [servizi GLPI e MySQL]
├── docker-compose.override.yml     [mount plugin, HTTPS se presente]
├── plugins/
│   ├── mdoverhaul/                <-- CODICE ATTIVO ESTRATTO DALLO ZIP
│   └── *.zip                      [pacchetti locali, opzionali]
├── certs/                        [se usati: chiavi TLS, RISERVATE]
└── https/                        [se usata: personalizzazione HTTPS]

BACKUP SEPARATO (esempio)
/root/glpi-plugin-backups/
└── mdoverhaul-<data>.<id>.tar.gz    [solo codice plugin, non database]

BIND MOUNT DOCKER (non è una seconda copia):
/opt/glpi/plugins/mdoverhaul/
       │
       └── read-only (:ro) ──►  CONTAINER GLPI
                                /var/www/glpi/plugins/mdoverhaul/
                                     ├── setup.php, hook.php, src/, front/
                                     └── public/css/, public/js/

DATI PERSISTENTI (NON NELLO ZIP)
Container GLPI: /var/glpi/          [volume Docker GLPI: config, file, log...]
Container MySQL: /var/lib/mysql/   [volume Docker database GLPI]
    ├── dashboard e widget salvati
    ├── feed RSS nativi GLPI
    └── glpi_configs / plugin:mdoverhaul [sette opzioni]
```

**Flusso d'installazione:** file ZIP → estrazione sul server host → bind mount nel container → PHP caricato da GLPI → CSS/JS serviti al browser. Uno ZIP non è un programma in esecuzione e il bind mount non è una sincronizzazione di cartelle.

**Flusso d'uso:** modifica del tema o della dashboard nel browser; quando si salva **Configura**, i 5 flag vanno nel database GLPI. Il container può continuare ad avere il codice del plugin in sola lettura.

**Flusso di backup:** `tools/backup-plugin.sh` salva **solo il codice**; un ripristino completo richiede anche database/volumi, configurazioni Compose, certificati e tutte le altre risorse GLPI secondo la procedura generale dell'ambiente.

Il nome vecchio `arensecurity` non deve essere reinstallato. Le stringhe `arensecurity_feed_...` e `arensecurity_rss...` che restano nel codice sono **chiavi salvate da GLPI**, mantenute per compatibilità delle dashboard precedenti.

## 0.8.0 — Distribuzione unica

Il file `Modern_Dashboard_Overhaul_0.8.0_Pilot.zip` contiene una sola cartella `mdoverhaul/` (con `setup.php`, `hook.php`, `src/`, `front/`, `public/`, `config/`, `tests/`, `tools/`, `docs/`, `README.md`, `SHA512SUMS`). Esclude `.git/`, `.github/`, precedenti ZIP distribuiti e `config/ui.local.php`. Usare l'unica cartella host `plugins/mdoverhaul/`, senza creare plugin secondari.



## Aggiornamento 0.8.1-dev.1

Aggiunti src/BuildInfo.php, front/runtime.php, tools/maintainer/versioning.py, publish_dev.py, check_dev_docs.py, test di Settings e packaging, docs/releases. build-info.json viene generato solo nel pacchetto.

Stato: NON COLLAUDATO LIVE. Note: [0.8.1-dev.1](releases/0.8.1-dev.1.md).

## Aggiornamento 0.8.1-dev.2

src/StandaloneTask.php (modello/ACL/Search), TaskSchema.php (schema 1),
TaskProfile.php (matrice native Profile); front/standalonetask.php e
standalonetask.form.php (controller Search e CRUD); hook.php gestisce upgrade e
criterio pubblico Search. tools/backup-standalone.sh prepara backup privato.
Nuovi tests/common_db_double.php, standalone_php.php, render_standalone.php e
standalone_ui_playwright.py sono esclusivamente test dichiarati, non runtime GLPI.

Stato: NON COLLAUDATO LIVE. [Note complete](releases/0.8.1-dev.2.md).

## Aggiornamento MDO 0.8.1-dev.3 (25 ottobre 2026)

Mappa 0.8.1-dev.3: front/tasks.php corregge il redirect al form GLPI; tests/task_bootstrap.php aggiunge SimulatedGLPIRedirect; tests/tasks_php.php aggiunge la regressione. Le note del candidato sono in docs/releases/0.8.1-dev.3.md; nessun modulo nuovo o file core modificato.

## Aggiornamento 0.8.1-dev.4 (25 ottobre 2026)

0.8.1-dev.4 file chiave: src/StandaloneTask.php (native Search status markup), tests/task_bootstrap.php (Dropdown mock fedele), tests/standalone_php.php (regressione), tools/maintainer/publish_dev.py (gate immutable docs-only), tests/packaging_test.py (git fixture). Note in docs/releases/0.8.1-dev.4.md.

## Aggiornamento 0.8.1-dev.5

Dev.5 modifica src/TaskBoard.php, src/StandaloneTask.php (ORDER SQL), front/tasks.php (solo avviso ACL admin), tests/task_bootstrap.php, tests/tasks_php.php, tests/standalone_php.php (regressioni), setup.php, note docs/releases/0.8.1-dev.5.md e documentazione correlata. Nessun file core GLPI, nuova tabella, volume o plugin separato.


## 0.8.1-dev.6 — Aggiornamento documentale

MDO Tasks Workspace: vista unificata ProjectTask/StandaloneTask, Kanban con aggiornamento ACL, storico completo delle annotazioni e allegati MDO privati; schema 2 e relative verifiche di sicurezza. Vedere docs/releases/0.8.1-dev.6.md, QA.md e docs/dev/STATUS.md per test, rischi, rollback e prossimi passi. NON COLLAUDATO LIVE.

## 0.8.1-dev.7 — allineamento

`src/TaskCreationService.php` (validazione/modello) e `front/task.form.php` (GET form e POST protetto), test `tests/task_creation_php.php`; coinvolti `front/tasks.php`, asset di stile Tasks e setup.php.


## 0.8.1-dev.8 — stabilizzazione Tasks UX

Questa candidata corregge la ripetizione dei trascinamenti del Kanban con token CSRF GLPI11 rinnovato, aggiunge la card ghost e la colonna di drop evidenziata, e consente il soft-delete da lista combinata soltanto se l'item ha il diritto DELETE. Modulo StandaloneTask conservato, senza nuove tabelle o migrazioni. CI automatica non sostituisce test sul GLPI reale; Main invariato. Dettagli e rollback in `docs/releases/0.8.1-dev.8.md`.


## 0.8.1-dev.9 — Kanban, stati e contatori

Nuova candidata di correzione del collaudo: catalogo completo ProjectState GLPI, comprese colonne a zero task; per ProjectTask spostamento autorizzato su stato nativo, nella vista All stage To do/Doing/Done per entrambi i tipi; aggiornamento immediato dei contatori sulla risposta POST senza reload. Nessuna migrazione, Main invariato. Backup coordinato database/plugin/config/storage privato prima della verifica su GLPI reale. **NON COLLAUDATO LIVE.** Vedere `docs/releases/0.8.1-dev.9.md` e backlog MDO-TASK-016/017/018.


## 0.8.1-dev.10 — Project Kanban native stage/defaults

Candidato UX: Project raggruppa per stati GLPI (non %), All Kanban apre come default, mapping nativo GLPI core ProjectState per drag di ProjectTask con avanzamento automatico, senza toccare percent_done. Card compatte, dettagli in form e diario. Non introduce nuove tabelle. **NON COLLAUDATO LIVE**. Test e rollback in `docs/releases/0.8.1-dev.10.md`.

## 0.8.1-dev.11 — allineamento release e CI (27 ottobre 2026)

La candidata di sviluppo 0.8.1-dev.11 corregge la vista Kanban Project: le colonne seguono gli stati GLPI e non la percentuale di completamento, anche con URL legacy `group=progress`. Nella vista All, se non esiste una mappatura nativa univoca per le tre fasi condivise, le attività Project utilizzano colonne ProjectState proprie e quelle Independent conservano le loro fasi; i movimenti restano vincolati al tipo e ai permessi GLPI. Il codice delle nuove tabelle diario/allegati usa `TIMESTAMP` per `date_creation`; gli impianti esistenti richiedono verifica amministrativa e migrazione GLPI separata, dopo backup coordinato DB, plugin e storage. **NON COLLAUDATO LIVE**: i test fixture non certificano il server reale. ZIP immutabile e SHA512 soltanto dopo CI completa e pubblicazione verificata; Main invariata. Per dettagli, prove e rollback vedere `docs/releases/0.8.1-dev.11.md`.

## 0.8.1-dev.12 — Solo ProjectTask GLPI (29 ottobre 2026)

Decisione MDO-D-009: le nuove task appartengono obbligatoriamente a un progetto GLPI e utilizzano ProjectTask nativa. Elenco con Search GLPI, Kanban con tutti gli stati ProjectState (anche colonne vuote), aggiornamento con permessi item, CSRF e date_mod; percent_done non viene trattato come stato. Sono ritirati tab All/Independent, creazione senza progetto, opzione di configurazione, scheda diritti profilo e CRUD standalone. Gli endpoint legacy non devono consentire nuove scritture.

I dati già esistenti non sono eliminati: tabella glpi_plugin_mdoverhaul_tasks, record note/file source=standalone e volume GLPI_DOC_DIR/mdoverhaul restano intatti per recupero/esportazione separata. Non vengono convertiti automaticamente in ProjectTask. Main resta invariato, test GLPI LIVE NON ESEGUITI. Vedi docs/releases/0.8.1-dev.12.md per dettagli, CI, rollback.
