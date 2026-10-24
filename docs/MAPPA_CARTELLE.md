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
