# Architettura — Modern Dashboard Overhaul 0.8.1-dev.2

> **Corrente 0.8.1-dev.2:** vedere la sezione aggiornata in fondo; le descrizioni precedenti restano riferimento storico delle versioni indicate.

## 1. In sintesi

**Un solo plugin `mdoverhaul`, quattro moduli configurabili**, nessun servizio o database parallelo.

```text
                     GLPI 11 (plugin mdoverhaul)
                              │
                   setup.php + Settings.php
                              │
          ┌───────────────────┴───────────────────┐
          ▼                                       ▼
  Dashboard Restyling                         Widget RSS
          │                                       │
          ├─ CSS dashboard-*                      ├─ Hooks DASHBOARD_TYPES/CARDS
          ├─ CSS compact-filters / chart-text      ├─ src/Dashboard.php
          ├─ JS dashboard-overhaul.js              │      │
          │                                       │      ├─ query RSS visibili
          ▼                                       │      ├─ RSSFeed::getRSSFeed()
  Dashboard native GLPI                          │      └─ FeedFormatter.php
  • contatori/grafici                            │             │
  • cambio light/dark                            │             ▼
  • dashboard_select/change                      └──────► Widget HTML/CSS
  • pulsante nativo +

  Configura → front/config.php → Settings::save()
                                 │
                                 ▼
                         glpi_configs (CORE)
                      context plugin:mdoverhaul
```

I moduli utilizzano i permessi e le dashboard di GLPI. RSS generici e RSS di sicurezza sono trattati allo stesso modo; eventuali badge CVE derivano solo dal testo e **non implicano gravità o presenza della vulnerabilità su un asset**.

## 2. Componenti e responsabilità

| Componente | Funzione | Dove viene eseguito |
| --- | --- | --- |
| `setup.php` | Metadati 0.8.0, hook, registrazione selettiva dei moduli in base alle impostazioni | PHP GLPI nel container |
| `hook.php` | Ciclo installazione/disinstallazione; nessuna tabella propria | PHP GLPI |
| `src/Settings.php` | Sette flag, validazione, lettura/scrittura configurazione GLPI; fallback configurazione 0.5 | PHP GLPI |
| `front/config.php` | WebGUI amministrativa, controllo `config:UPDATE`, token CSRF, POST/redirect/GET | PHP GLPI |
| `src/Dashboard.php` | Catalogo RSS per feed attivi/visibili, provider dati, renderer HTML | PHP GLPI |
| `src/FeedFormatter.php` | Normalizzazione feed, date/fusi, deduplica e validazione collegamenti | PHP GLPI |
| `public/js/dashboard-overhaul.js` | UI responsiva, tab, gestione filtri vuoti, cambio tema e adattamento testi grafici | Browser dell'utente |
| `public/css/dashboard-*.css` | Aspetto e comportamento dashboard native | Browser |
| `public/css/security-watch.css` | CSS RSS: **nome storico del file**, non dipendenza dalla sicurezza | Browser |
| `tools/*` | Verifiche integrità/sintassi e backup del solo codice | Shell sul server, su richiesta |

Il plugin non contiene un server web autonomo: GLPI richiama PHP tramite i propri hook; Apache/GLPI servono CSS e JavaScript al browser.

## 3. Configurazione e runtime

- I sette flag sono `dashboard_restyling`, `rss_widgets`, `compact_empty_filters`, `dashboard_tabs`, `chart_text`, `task_workspace`, `kanban_restyling`. Questi ultimi sono OFF per default.
- `src/Settings.php` usa `Config::getConfigurationValues('plugin:mdoverhaul')` e `Config::setConfigurationValues(...)` in `glpi_configs` **già esistente**.
- Quando non esiste ancora un valore salvato nel DB, si usa il fallback `config/ui.php`, eventualmente sovrascritto da `config/ui.local.php` locale. Dopo il primo salvataggio via GUI prevale il DB.
- `dashboard_restyling=false`: non vengono caricati gli script di manipolazione della dashboard. Il CSS minimo della schermata Configura rimane disponibile.
- `rss_widgets=false`: non vengono registrati i provider/tipi RSS. I dati GLPI non sono rimossi, ma una dashboard salvata che li usa può visualizzare schede non disponibili; **non salvarla mentre il modulo è disabilitato**.
- Il bind mount Docker può restare **`:ro`**: i cambi di configurazione scrivono nel database GLPI, non nei file del plugin.

## 4. Restyling: cosa tocca e cosa non tocca

**Modificabile dal plugin:** stile e responsive di contatori `.big-number`, superficie delle schede native, etichette e grafici supportati, barra `.filters_toolbar` vuota, selettore `.dashboard_select` rappresentato come tab orizzontali. I tab attivano la **change event nativa GLPI**; le azioni di creazione/edit/salvataggio restano native. Il toggle `.night-mode` aggiunge/rimuove `.theme-dark` e il plugin ne deriva la palette.

**Non modificato:** provider dati dei widget nativi, query degli asset, coordinate dimensioni salvate nella griglia, permessi, filtri configurati, azioni sui ticket. L'adattamento ECharts aggiorna opzioni di presentazione senza sostituire serie o dati; comportamento e resa richiedono test reali per ogni grafico.

## 5. Widget RSS: percorso dei dati

1. GLPI espone RSS tramite Strumenti → RSS Feed, con URL, visibilità, refresh e attivazione.
2. `getVisibleFeeds()` verifica utente autenticato, diritto RSS e visibilità/proprietario; usa le normali strutture RSS di GLPI. Non pubblica automaticamente feed privati ad altri utenti.
3. Il provider richiama **`RSSFeed::getRSSFeed()`** e il parser/cache GLPI. Non impiega download JS nel browser.
4. `FeedFormatter` normalizza date e link, deduplica gli articoli disponibili e limita gli elementi.
5. Il renderer produce HTML con escaping; i collegamenti aprono la fonte. Preset: 5/10/15/20; il default è 10. Il feed non può mostrare più articoli di quanti ne restituisce la fonte.
6. La data/ora nasce dal feed; una pubblicazione sconosciuta **non** viene sostituita dall'ora del download. L'utente vede il fuso noto/fallback GLPI.

I contenuti RSS esterni sono **dati non fidati**. Consultare [SECURITY.md](SECURITY.md) per limiti e prove negative.

## 6. Identificativi e compatibilità

| Identificativo persistito GLPI | Perché non si cambia |
| --- | --- |
| `arensecurity_feed_<ID>` | Identifica la scheda del feed salvata nella dashboard |
| `arensecurity_rss`, `arensecurity_rss_5`, `_15`, `_20` | Identificano i tipi/preselezioni delle schede RSS |
| `mdoverhaul` | È invece il nome tecnico attuale del plugin e della cartella |

Non rinominare le chiavi dei widget senza migrazione formale e collaudata. Il vecchio plugin `arensecurity` deve restare disattivato.

## 7. Dipendenze, dati persistenti e confini

- **Runtime:** GLPI 11.0.x, PHP incluso nella distribuzione GLPI; browser moderno e librerie JS che già utilizza GLPI. Nessun Node/Python richiesto per l'esecuzione (solo per alcuni test/release).
- **Storage:** database/volumi di GLPI già esistenti, nessuna tabella propria.
- **Rete:** il server GLPI contatta soltanto gli URL RSS configurati dall'amministratore, tramite i meccanismi GLPI; il plugin non invia telemetria.
- **Permessi:** gli utenti vedono soltanto i feed consentiti dal contesto GLPI, mentre Configura richiede il diritto `config:UPDATE`.
- **Aggiornamenti:** ZIP → cartella host → bind mount nel container; il manifest SHA-512 verifica integrità, non autenticità del software.

## 0.7.0 — Profilo prestazionale e gestione degli eventi

- PHP: la configurazione viene memorizzata soltanto **per la durata della richiesta**, evitando letture ripetute di `Config::getConfigurationValues()`. Il salvataggio invalida la cache locale.
- JavaScript: nessuna richiesta AJAX/fetch aggiuntiva, telemetria o polling. La scoperta DOM globale non viene avviata sulle pagine GLPI senza dashboard, salvo le route dashboard che possono caricarla asincronamente.
- ResizeObserver solo sui KPI e grafici che hanno bisogno di reflow; i numeri vengono ricalcolati quando cambia la loro geometria/numero di cifre.
- Un massimo di tre timeout di recupero per **dashboard** (anziché per singolo grafico). ECharts riceve patch stilistiche per fasce di 32px, non a ogni singolo pixel di resize. Le serie, dati e colori originali restano proprietà di GLPI.
- RSS: il parser/cache native GLPI resta la fonte dei dati; il formatter scansiona al massimo 100 elementi e deduplica per GUID, URL o titolo, non per ID CVE contenuto nella notizia.

Il benchmark UI descritto in [QA](../QA.md) è un carico simulato, non un profilo complessivo di GLPI reale.

## 0.8.0 — Componenti aggiuntivi

- `task_workspace`: `Hooks::MENU_TOADD` porta `TaskMenu` dentro Assistance; `front/tasks.php` espone ricerca nativa `ProjectTask` e Kanban read-only (TaskBoard con `canViewItem()` per record).
- `kanban_restyling`: CSS per `#kanban-app` nativo GLPI senza modificare logica Vue o DnD; indipendente dal Tasks Workspace.
- `dashboard_restyling`: aggiunge `ticket-mini-dashboard.css/js` nella pagina Ticket per contenere la mini-dashboard; le serie e i dati restano proprietà di GLPI.
- `rss_widgets`: identificativi di feed e tipi storici immutati; tutte le sette impostazioni sono salvate in `plugin:mdoverhaul`.
- `tools/maintainer/package_from_repo.py` copia i soli file del plugin da checkout nella staging `mdoverhaul/`, poi richiama il packager verificato con manifest SHA512. Il pacchetto risultante è completo; GitHub Actions può riprodurlo.



## Aggiornamento 0.8.1-dev.1

BuildInfo legge metadata pubblici generati, runtime.php li espone al JS autenticato. Il hook asset GLPI usa la versione di setup.php: niente query string fittizie. Nessuna nuova persistenza applicativa in P0.

Stato: NON COLLAUDATO LIVE. Note: [0.8.1-dev.1](releases/0.8.1-dev.1.md).

## Aggiornamento 0.8.1-dev.2

StandaloneTask estende CommonDBTM e implementa ExtraVisibilityCriteria dentro
GlpiPlugin\Mdoverhaul. La sola tabella nuova è glpi_plugin_mdoverhaul_tasks:
id, entities_id, name varchar(255), content mediumtext, users_id_owner,
users_id_assign, groups_id_assign, status (1/2/3), due_date, date_creation,
date_mod, is_deleted. Indici su entità, owner, assignee, gruppo, stato, scadenza
e cancellazione; nessuna FK a Project e nessuna modifica DDL alle tabelle core.

Le ProjectTask restano nel core. Due sottoviste Search native separate evitano
una UNION/paginazione artificiale tra modelli con ACL differenti. La board
riusa gli stessi criteri di visibilità e controlla can(id,READ) per ogni riga.
Per la tabella custom, le search options dichiarano itemtype esplicito; hook
addDefaultWhere in array DBIterator, nessuna dipendenza da Search @internal.
I gruppi assegnati possono essere ricorsivi, la singola task non è ricorsiva:
la sua entità è fissa. Il suo stato minimo è un unico asse, non Stage+Status.

Stato: NON COLLAUDATO LIVE. [Note complete](releases/0.8.1-dev.2.md).

## Aggiornamento MDO 0.8.1-dev.3 (25 ottobre 2026)

Versione 0.8.1-dev.3: il controller Tasks consulta ProjectTask e Project nativi, poi esegue Html::redirect FUORI dal try/catch. In GLPI 11 la RedirectException è parte intenzionale del protocollo del router, non un errore applicativo da sopprimere. Nessuna alterazione a StandaloneTask, dashboard, RSS o schema.

## Aggiornamento 0.8.1-dev.4 (25 ottobre 2026)

In 0.8.1-dev.4 il campo Status di StandaloneTask restituisce il markup tramite Dropdown::showFromArray(display=false) richiesto da Search GLPI, senza sostituire ricerca nativa. Persistenza standalone schema 1 invariata; native ProjectTask e RSS/Charts invariati. Publisher confronta sorgente e documentazione.

## Aggiornamento 0.8.1-dev.5

GLPI 11 DBmysqlIterator::handleOrderClause accetta stringhe o liste di espressioni ORDER; nel vecchio array associativo ['table.id'=>'DESC'] ignorava la chiave e generava ORDER BY DESC. Usare ['table.id DESC']. Modificati i soli tre adattatori bounded-read per TaskBoard e StandaloneTask. Le task di progetto restano native ProjectTask; la board non scrive dati. Un avviso solo per amministratori con config:UPDATE segnala READMY/READALL Standalone mancanti.


## Architettura dev.6

TaskStageService scrive via API GLPI; TaskActivity e TaskAttachment hanno ACL parent. Schema2 aggiunge tabelle MDO note/file; upload privato in GLPI_DOC_DIR/mdoverhaul. Vista all unisce due campioni autorizzati. NON COLLAUDATO LIVE.


## 0.8.1-dev.6 — Aggiornamento documentale

MDO Tasks Workspace: vista unificata ProjectTask/StandaloneTask, Kanban con aggiornamento ACL, storico completo delle annotazioni e allegati MDO privati; schema 2 e relative verifiche di sicurezza. Vedere docs/releases/0.8.1-dev.6.md, QA.md e docs/dev/STATUS.md per test, rischi, rollback e prossimi passi. NON COLLAUDATO LIVE.

## 0.8.1-dev.7 — allineamento

Form unificato `front/task.form.php` con TaskCreationService: lo stesso POST valida progetto facoltativo e inoltra a ProjectTask::check/add del core o StandaloneTask::check/add del plugin. Nessuna tabella, nessun endpoint SQL autonomo o conversione. Il form avanzato ProjectTask nativo continua dopo la creazione.

## Dev.8 — Kanban/lista UX

`front/task-stage.php` rinnova token GLPI dopo POST validato dal middleware (anche errore applicativo). `public/js/task-workspace.js` aggiorna il token, evita full reload, mostra ghost HTML5 e target di drop. `front/task-delete.php` delega soft-delete a `CommonDBTM::delete` dopo `can(DELETE)` per item ed entità, senza SQL core; solo in lista combinata e solo con diritti. Nessuna nuova tabella, modello separato StandaloneTask conservato.
