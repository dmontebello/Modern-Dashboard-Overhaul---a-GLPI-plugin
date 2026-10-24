# Integrazione con GLPI 11 — Modern Dashboard Overhaul 0.8.0

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
