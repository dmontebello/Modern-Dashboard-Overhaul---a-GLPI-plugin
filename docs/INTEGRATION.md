# Integrazione con GLPI 11 — Modern Dashboard Overhaul 0.8.1-dev.2

> **Corrente 0.8.1-dev.2:** vedere la sezione aggiornata in fondo; le descrizioni precedenti restano riferimento storico delle versioni indicate.

Queste note identificano **i punti d'integrazione del codice attuale**; non garantiscono compatibilità con futuri aggiornamenti GLPI. Fonte di verifica: implementazioni GLPI 11.0.9 e file distribuiti nel plugin.

## Hook PHP in `setup.php`

| Hook GLPI | Quando viene registrato | Scopo |
| --- | --- | --- |
| `Hooks::CONFIG_PAGE` | Sempre (plugin attivo) | Apre `front/config.php` dal pulsante Configura |
| `Hooks::ADD_CSS` | Impostazioni sempre; moduli condizionali | CSS pagina Configura, Restyling e/o RSS |
| `Hooks::ADD_JAVASCRIPT` | Solo con Restyling ON | Bridge dashboard nativa nel browser |
| `Hooks::DASHBOARD_TYPES` | Solo con RSS ON | Tipi RSS 5/10/15/20 |
| `Hooks::DASHBOARD_CARDS` | Solo con RSS ON | Un catalog item per RSS attivo/visibile |

`plugin_mdoverhaul_install()` e `plugin_mdoverhaul_uninstall()` non creano/eliminano tabelle proprie. **Non abilitare contemporaneamente** `arensecurity` e `mdoverhaul`; la registrazione dei card ID entrerebbe in conflitto.

## Contratti frontend GLPI 11.0.9

- `src/Glpi/Dashboard/Grid.php`: `dashboard_select` e `add-dashboard` (bottone `+`) sono creati da GLPI.
- `js/modules/Dashboard/Dashboard.js`: il cambio di `.dashboard_select` effettua il refresh nativo dei contenuti, registra l'ultima dashboard e inizializza i filtri; la classe `.theme-dark` viene attivata dal toggle `.night-mode`.
- `Dashboard.js`: `fitNumbers()`/fitText opera dentro `.formatted-number`; adattamenti di solo `.content` non bastano a controllare i KPI. La presentazione MDO preserva i valori e limita lo scorrimento indesiderato.
- `css/standalone/dashboard.scss`: markup `.big-number`, `.multiple-numbers`, `.g-chart`, `.filters_toolbar`, `.grid-stack`.
- `src/Glpi/Dashboard/Widget.php`: HTML dei widget nativi, tipi di grafico e opzioni ECharts. MDO cambia presentazione, non dati/serie.
- `src/Glpi/Dashboard/Grid.php` + `Widget.php`: i plugin contribuiscono card e tipi tramite `Hooks::DASHBOARD_CARDS` e `Hooks::DASHBOARD_TYPES`.

## Identificativi storici da NON cambiare

```text
Card:  arensecurity_feed_<ID numerico RSS>
Type:  arensecurity_rss       [default 10]
       arensecurity_rss_5
       arensecurity_rss_15
       arensecurity_rss_20
```

**Sono chiavi persistite dentro GLPI**, non nomi di cartelle/plugin. I renderer e provider ora risiedono in `GlpiPlugin\Mdoverhaul\Dashboard`. Nessuna classe legacy deve essere caricata.

## Configurazione

`front/config.php` richiede `Session::checkRight('config', UPDATE)`, include `_glpi_csrf_token` ed effettua il salvataggio tramite `Settings::save()` con allowlist rigorosa. Gli handler GLPI e il middleware ufficiale gestiscono la sessione e il CSRF. `Config::getConfigurationValues('plugin:mdoverhaul')` legge i sette valori nella tabella nativa. Verificare i permessi sul GLPI reale durante il collaudo.

## Limiti

- CSS/JS dipendono da classi e markup nativi **di GLPI 11.0.9**. Aggiornamenti GLPI possono richiedere revisione.
- L'adattamento di ECharts è *best effort*, disattivabile dalla GUI.
- Il modulo RSS usa `RSSFeed::getRSSFeed()` e segue il parser/cache/visibilità GLPI. Non interroga API CVSS e non crea task.

## 0.8.0 — ProjectTask in Assistance

Registrazione condizionale `Hooks::MENU_TOADD` (`helpdesk` → `TaskMenu`); pagina `/plugins/mdoverhaul/front/tasks.php`, sempre protetta da `ProjectTask::canView()` e flag `task_workspace`. L'elenco chiama `Search::show(ProjectTask::class)` e sfrutta ricerca/autorizzazioni native GLPI. La board alternativa usa `TaskBoard::load()` e `ProjectTask::canViewItem()` per ogni record e limita i candidati a 1000; nessuna scrittura. Il CSS `task-kanban.css` si limita a `#kanban-app`, senza sostituire Vue o gli handler drag/drop. Verifiche di integrazione live ancora pendenti.


## Aggiornamento 0.8.1-dev.1

ProjectTask, Search, Dropdown AJAX, CommonDBChild e cache versione GLPI rimangono nativi. La geometria ECharts cambia solo per il card_id ticket_status; serie, palette e interazioni non vengono sostituite. Main e asset del core invariati.

Stato: NON COLLAUDATO LIVE. Note: [0.8.1-dev.1](releases/0.8.1-dev.1.md).

## Aggiornamento 0.8.1-dev.2

Nuovo oggetto nello stesso namespace/plugin, CommonDBTM, stato singolo e schema
proprio. Nessun ProjectTask orfano, progetto contenitore, TicketTask o Reminder
sostitutivo. La classe esplicita itemtype nelle opzioni Search per la tabella
custom; usa l'hook pubblico addDefaultWhere e ExtraVisibilityCriteria per i
percorsi nativi compatibili. Liste/board dei due tipi sono separate; card con
tipo e ID evitano collisioni tra ProjectTask #1 e StandaloneTask #1.

Il form nuovo usa controller/plugin router, Entity/User/Group/Dropdown e token
CSRF nativi. Non cambia API Vue, dati di ECharts o layout GridStack persistito.
Collaudare Search/AJAX/export e API native del nuovo oggetto: non sono stati
eseguiti su GLPI 11.0.9 reale in questo ambiente.

Stato: NON COLLAUDATO LIVE. [Note complete](releases/0.8.1-dev.2.md).

## Aggiornamento MDO 0.8.1-dev.3 (25 ottobre 2026)

Integrazione GLPI 11.0.9, 0.8.1-dev.3: Html::redirect() lancia RedirectException, che deve raggiungere il router. La form vera si trova in /front/projecttask.form.php e il GET projects_id serve soltanto ad aprirla; il POST Salva nativo crea la ProjectTask. Le task autonome restano nel distinto itemtype StandaloneTask.

## Aggiornamento 0.8.1-dev.4 (25 ottobre 2026)

API GLPI 11.0.9: Dropdown::showFromArray default display=true fa echo HTML e ritorna random integer; quando chiamato da StandaloneTask::getSpecificValueToSelect per Search deve essere display=false e restituisce HTML. La form nativa GLPI continua a usare i suoi Dropdown con echo senza cambiamenti. Publisher documenti-only preserva ZIP immutable.

## Aggiornamento 0.8.1-dev.5

GLPI 11.0.9: DBmysqlIterator::handleOrderClause itera i VALORI di ORDER, quindi ['glpi_projecttasks.id'=>'DESC'] produce ORDER BY `DESC` (MySQL 1054). In dev.5 si usa ['glpi_projecttasks.id DESC'] e lo stesso criterio per Project e StandaloneTask. Le classi native e il comportamento Search restano invariati.


## 0.8.1-dev.6 — GLPI Hooks

Plugin TaskActivityTab aggiunge un collegamento al diario MDO nelle ProjectTask, conservando Note/Documenti nativi; TaskStageService usa ProjectTask::update e StandaloneTask::update secondo ACL. Il middleware GLPI deve validare CSRF sugli endpoint del plugin. Caricamento JS limitato a task_workspace. Search nativa non viene intercettata. NON COLLAUDATO LIVE.

## 0.8.1-dev.7 — allineamento

Il form task.form.php usa Dropdown::show(Project::class) nativo e CommonDBTM/ProjectTask pubblici; il ramo progetto conserva il core GLPI per team, parent e workflow avanzato. Il ramo indipendente usa il modello MDO e la propria ACL. Nessuna modifica al core.


## 0.8.1-dev.8 — stabilizzazione Tasks UX

Questa candidata corregge la ripetizione dei trascinamenti del Kanban con token CSRF GLPI11 rinnovato, aggiunge la card ghost e la colonna di drop evidenziata, e consente il soft-delete da lista combinata soltanto se l'item ha il diritto DELETE. Modulo StandaloneTask conservato, senza nuove tabelle o migrazioni. CI automatica non sostituisce test sul GLPI reale; Main invariato. Dettagli e rollback in `docs/releases/0.8.1-dev.8.md`.


## 0.8.1-dev.9 — Kanban, stati e contatori

Nuova candidata di correzione del collaudo: catalogo completo ProjectState GLPI, comprese colonne a zero task; per ProjectTask spostamento autorizzato su stato nativo, nella vista All stage To do/Doing/Done per entrambi i tipi; aggiornamento immediato dei contatori sulla risposta POST senza reload. Nessuna migrazione, Main invariato. Backup coordinato database/plugin/config/storage privato prima della verifica su GLPI reale. **NON COLLAUDATO LIVE.** Vedere `docs/releases/0.8.1-dev.9.md` e backlog MDO-TASK-016/017/018.


## 0.8.1-dev.10 — Project Kanban native stage/defaults

Candidato UX: Project raggruppa per stati GLPI (non %), All Kanban apre come default, mapping nativo GLPI core ProjectState per drag di ProjectTask con avanzamento automatico, senza toccare percent_done. Card compatte, dettagli in form e diario. Non introduce nuove tabelle. **NON COLLAUDATO LIVE**. Test e rollback in `docs/releases/0.8.1-dev.10.md`.

## 0.8.1-dev.11 — allineamento release e CI (27 ottobre 2026)

La candidata di sviluppo 0.8.1-dev.11 corregge la vista Kanban Project: le colonne seguono gli stati GLPI e non la percentuale di completamento, anche con URL legacy `group=progress`. Nella vista All, se non esiste una mappatura nativa univoca per le tre fasi condivise, le attività Project utilizzano colonne ProjectState proprie e quelle Independent conservano le loro fasi; i movimenti restano vincolati al tipo e ai permessi GLPI. Il codice delle nuove tabelle diario/allegati usa `TIMESTAMP` per `date_creation`; gli impianti esistenti richiedono verifica amministrativa e migrazione GLPI separata, dopo backup coordinato DB, plugin e storage. **NON COLLAUDATO LIVE**: i test fixture non certificano il server reale. ZIP immutabile e SHA512 soltanto dopo CI completa e pubblicazione verificata; Main invariata. Per dettagli, prove e rollback vedere `docs/releases/0.8.1-dev.11.md`.

## 0.8.1-dev.12 — ProjectTask GLPI only (29 ottobre 2026)

MDO-D-009 replaces the former standalone model. Only native ProjectTask associated with an existing authorized GLPI project is available for creation, search and Kanban. Native ProjectState columns including empty statuses; per-item ACL and CSRF. All/Independent tabs, standalone CRUD, profile settings and optional-project creation are retired. Never delete the historical glpi_plugin_mdoverhaul_tasks table, source=standalone note/file rows, GLPI_DOC_DIR/mdoverhaul, or old config/rights on upgrade. No automated conversion or DROP. Legacy data is for separately authorized export/restore. GLPI LIVE tests not performed; CI and SHA512 of new candidate to be verified. Main unchanged. Release details docs/releases/0.8.1-dev.12.md.
