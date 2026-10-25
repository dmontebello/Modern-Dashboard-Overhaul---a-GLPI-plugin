# MDO — Tasks Workspace unificato e UX nativa (proposta esecutiva per Codex)

**Stato: SPECIFICA / NON IMPLEMENTATO.** Feedback di collaudo dell'utente, 25 ottobre 2026.  
Repository: `dmontebello/Modern-Dashboard-Overhaul---a-GLPI-plugin` — **lavorare solo su `Dev`, non su `Main`**.  
Baseline osservata: Dev `4b681e21830d3f828695ea5fbf0f800e77b99ae8`, ZIP installabile `0.8.1-dev.5` (sorgente testato `60fb656aad0400a31788bf68760edf390961f166`). Codex **deve rifare `git fetch origin` e leggere il nuovo HEAD** prima di intervenire.  
Riferimenti obbligatori: `AGENTS.md`, `docs/dev/STATUS.md`, `docs/dev/STAGING_0_8_1.md`, `docs/dev/VERSIONING_POLICY.md`, `docs/specs/MDO_NEXT_SPRINT.md`, `docs/specs/TASKS_OPEN_DECISIONS.md`.

## 0. Evidenza e requisiti approvati dall'utente

Screenshot di staging (non includere screenshot, nomi utente, hostname o task reali nel repo): la StandaloneTask si crea e appare in Search; anche una ProjectTask appare nella board MDO. Gli screenshot **non provano** ACL multi-profilo, API, assenza di errori console, drag & drop o migrazioni.

Richiesta:
1. All'apertura di Assistance > Tasks mostrare **tutte le attività accessibili, sia ProjectTask sia StandaloneTask**. Se non è subito possibile una lista nativa unica, mantenere i filtri Progetto/Indipendenti ma evidenziare quello attivo e posizionarli immediatamente sopra elenco e board. Preferire un default `Tutte`.
2. Aggiornare in modo fluido contenuti, tab e filtri senza ricaricare l'intero shell GLPI quando tecnicamente sicuro.
3. Kanban con **tre colonne sempre presenti: To do / Doing / Done**, anche vuote. Rendere spostabili le card tra le colonne con drag & drop persistente e controllato dai diritti.
4. **Un solo form di nuova attività**: progetto facoltativo. Al momento del POST, con progetto valido -> ProjectTask **nativa**; senza progetto -> StandaloneTask; niente schermata preliminare di scelta tipo.
5. Nella scheda standalone, layout del form verticale: pulsanti **Salva** e **Torna alle attività** in fondo a destra, sotto l'ultimo campo; Delete distinto e non pericolosamente accostato a Salva.
6. Allineare i campi e i componenti della form standalone alla UX nativa ProjectTask dove supportati, dichiarando gli scarti strutturali.

## 1. Vincoli architetturali e di sicurezza

- **Un solo plugin** `mdoverhaul`; no modifica a GLPI core, no secondo plugin, no progetto nascosto o `ProjectTask.projects_id=0`.
- `ProjectTask` estende `CommonDBChild`, il progetto è obbligatorio. In GLPI 11.0.9 `ProjectTask::prepareInputForAdd` nega il record senza `projects_id`. Usare soltanto metodi GLPI pubblici per creazione/aggiornamento/ACL; niente INSERT/UPDATE SQL diretti a tabelle core.
- `StandaloneTask` resta `CommonDBTM` plugin, tabella `glpi_plugin_mdoverhaul_tasks`, schema 1 e ACL separate `READMY/READALL/CREATE/UPDATE/DELETE`.
- Le attività devono mantenere ID tipizzato (`project:123`, `standalone:123`), collegamenti ai form corretti, diritti nativi, isolamento delle entità e accessi di team/gruppi. Mai aggregare righe prima del controllo autorizzativo né esporre conteggi o filtri vietati.
- La configurazione `task_workspace` e `tasks_standalone` rimane opt-in; chi possiede diritti soltanto su uno dei due tipi deve poter usare la pagina.
- Nessun import Notion, conversione implicita di task già salvate da StandaloneTask a ProjectTask, cancellazione dati alla disattivazione, modifica RSS, Dashboard, Kanban Vue core, né DnD del Kanban GLPI nativo.
- Restare coerenti con `docs/specs/TASKS_OPEN_DECISIONS.md`: **Stage visuale non coincide automaticamente con Status nativo**.

## 2. Workspace: vista unica di default e navigazione

- Nuovo valore `source=all` e default `all` quando entrambi i tipi sono leggibili; fallback al solo tipo autorizzato. Menu/GET diretti non devono permettere la lettura di un tipo privo di diritti.
- Spostare la barra di origine **subito sopra Elenco/Kanban e il contenuto della vista**, non nella testata alta, con tre opzioni `Tutte / Progetto / Indipendenti`; rendere la scelta attiva visivamente distinta (contrast ratio >= 3:1), `aria-current` e focus da tastiera. Tenere le due sottoviste native accessibili.
- Introduzione di un adattatore di lettura, es. `src/UnifiedTaskService.php`, che normalizza in una view-model **solo dopo i controlli server-side** `ProjectTask::can(id,READ)`, `StandaloneTask::can(id,READ)`, entità e team/owner. Campi comuni: `source`, `id`, `name`, `project` (facoltativo), `stage`, `native_status`, `due`, `assignee` se lecito, `form_url`, `can_update`, `date_mod`.
- Niente `UNION SQL` fra i modelli per aggirare la Search nativa, e niente falsa promessa di totalità se vengono lette solo le prime 1000 candidate. La lista `Tutte` deve poter continuare a caricare le attività autorizzate con paginazione/cursore per origine, ordinamento stabile, token di tipo e ID; se la board usa scansione limitata, comunicarlo in modo evidente e non mostrare totali esatti. Non caricare senza limiti tutte le righe in memoria; batch efficienti e ACL per item.
- Per le ricerche avanzate specifiche continuare a offrire `Search::manageParams/showGenericSearch/showList` per `ProjectTask` e `StandaloneTask`: non simulare Search GLPI con HTML proprio o mescolare state machine e query native. I filtri della vista unificata sono dedicati e chiaramente etichettati.
- Pulsante `Nuova task` unico nella testata. La selezione della vista non deve costringere a scegliere preventivamente il tipo di creazione.

## 3. Rendering fluido, senza flash della pagina

- Nuovo JS locale opzionale (`public/js/task-workspace.js`) caricato esclusivamente quando il modulo è abilitato, con URL/asset versionati secondo il plugin.
- Per tab origine, vista, filtri e paginazione della **UI unificata o Kanban MDO**: navigazione progressiva con `fetch` same-origin/credentials/sessione GLPI, sostituzione del solo contenuto della regione, URL aggiornato con history.pushState/popstate, loading e error state accessibili, preservazione focus/scroll. Il normale link GET deve funzionare se JS è assente o fallisce.
- Non iniettare con innerHTML l'intera pagina di GLPI, né rompere l'inizializzazione AJAX/Select2, Search nativa, CSRF, header, breadcrumb e modali. Valutare Search nativa separatamente: se non è sicuro intercettare la sua navigazione, conservarla nativa e documentare l'eccezione. POST scrittura e form GLPI non devono promettere zero reload senza un flusso testato.
- Nessun polling o dipendenza JS di terze parti aggiunta per sostituire le capacità core.

## 4. Kanban: tre colonne sempre presenti, modello dati coerente

- Renderizzare sempre `todo`, `doing`, `done`, anche a 0 elementi. I conteggi derivano soltanto da record autorizzati e sono marcati parziali quando la scansione lo è. Evidenziare origine `Project` / `Independent` su ogni card.
- **StandaloneTask:** `status` 1/2/3 -> To do / Doing / Done.
- **ProjectTask:** per questa fase usare uno **Stage operativo derivato da `percent_done`** (0% = To do, 1-99% = Doing, 100% = Done), sempre distinto dal vero `projectstates_id` / `ProjectState` (mostrato come badge `Stato GLPI`). GLPI 11.0.9 possiede anche configurazioni per stati automatici delle ProjectTask: non sovrascrivere forzatamente `projectstates_id` se non previsto dal core. Non chiamare questa fase "stato GLPI" e non pretendere che esistano tre ProjectState distinte configurate.
- **DnD da implementare con endpoint POST autenticato del plugin**: payload `source, id, target_stage, expected_date_mod, _glpi_csrf_token`, solo valori allowlist. Caricamento fresco dell'item, diritto `UPDATE` per item e per entità con i controlli core, diritto di lettura ove richiesto; nessun bypass di profilo, gruppo o team.
- Su StandaloneTask usare il metodo nativo `update` con `status=1/2/3`; su ProjectTask usare `ProjectTask::update` con `percent_done=0/50/100` (quando si entra in Doing con percentuale fuori 1-99 usare un valore esplicitamente documentato, es. 50; dentro Doing preservare percentuale già valida). Lasciare al core le sue regole di `auto_projectstates`, fine effettiva e audit.
- Ricontrollare `date_mod` per un eventuale conflitto concorrente, risposta strutturata 200/400/403/409/500 senza dettagli sensibili; aggiornamento UI ottimistico **solo con rollback** su errore, o UI pessimistica se più affidabile.
- Accessibilità: spostamento anche da tastiera e menu azioni per touchscreen, messaggi aria-live, focus visibile; card senza UPDATE non trascinabili ma sempre apribili con READ.
- Nessun movimento cambia tipo o progetto, nessuna tabella nuova richiesta per questo Stage derivato. Una diversa implementazione con Stage persistente indipendente richiederebbe **decisione separata e migrazione approvata**.

## 5. Un solo form Nuova task, progetto facoltativo

- GET di creazione unico proposto `front/task.form.php` (o route equivalente documentata), pulsante unico `Nuova task`.
- Form GLPI-integrato con nome, descrizione, selettore progetto AJAX opzionale, scadenza, entità/assegnazioni e altri campi comuni solo se compatibili, con componenti nativi GLPI e layout coerente. La disponibilità del campo progetto dipende da diritti `ProjectTask::canCreate` e progetto ammesso. Nessun bypass con POST manipolato.
- **Solo il server decide il tipo al POST**: `projects_id > 0` validato -> `ProjectTask::check(-1, CREATE, ['projects_id'=>...])` e `ProjectTask::add` tramite contratto pubblico GLPI; `projects_id = 0`/campo vuoto -> `StandaloneTask::check(-1, CREATE,...)` e `StandaloneTask::add`. Se l'utente non può creare il tipo richiesto, rifiutare l'operazione. Non creare due record, non creare un progetto implicito, non fare SQL diretto.
- Il modello `ProjectTask` ha campi/relazioni differenti (es. `plan_end_date`, `projectstates_id`, `percent_done`, `ProjectTaskTeam`). Definire esplicitamente una mappatura dei soli campi davvero condivisibili, validazioni e date timezone-safe; NON copiare `users_id_assign` in un campo inesistente sul core né trattare `status` 1/2/3 come `projectstates_id`.
- Per le funzionalità native non riproducibili in modo sicuro (team multipli, allegati, parent task, pianificazione avanzata, durata, note, sotto-task), preservare il form core `ProjectTask` e accessibile dopo la creazione. Valutare i componenti `templates/pages/tools/project_task.html.twig` e `ProjectTask::showForm`, ma non importare il template core nel plugin.
- Errore di validazione: mantenere valori senza doppio inserimento; redirect GLPI fuori da `catch(Throwable)` per non intercettare `RedirectException` (regressione già risolta in dev.3); CSRF gestito dal middleware GLPI 11.
- **Modifica task esistente:** non consentire la conversione tacita da `StandaloneTask` a `ProjectTask` solo perché l'utente cambia il campo Progetto. Eventuale conversione è una funzionalità futura con migrazione dati auditata.

## 6. Correzione immediata layout form standalone

- Il codice attuale ha `.mdo-tasks-create form { display:flex; align-items:center; flex-wrap:wrap; ... }`, che interferisce con la griglia `.mdo-standalone-form fieldset`, causando pulsanti affiancati ai campi.
- Introdurre layout **più specifico** per `.mdo-standalone-form` (colonna, width 100%, flex/grid sicura), `fieldset` a tutta larghezza, riga azioni separata in fondo `justify-content:flex-end`, `Save` primario e `Back to tasks` secondario. `Delete` visivamente separato e protetto da conferma; mobile i pulsanti possono impilarsi senza overflow. Niente CSS globale su form/GLPI.
- Campi a larghezza coerente, label sopra il controllo, spaziature uniformi, componenti Dropdown/User/Group/Entity nativi, tema chiaro/scuro, zoom 200%, 390/768/1024/1440px. `fieldset disabled` e ACL devono rimanere funzionanti.

## 7. Split consigliato in release Dev

### `0.8.1-dev.6` — UX & lettura
- Vista `Tutte` come default con servizio di lettura autorizzato e paginazione, filtri origine vicini ai risultati con selezione colorata, tre colonne sempre presenti, correzione footer del form e caricamento progressivo per le viste plugin.
- Non introdurre scritture DnD o schema DB nuovo. Se la paginazione unificata richiede più tempo, non etichettare come complete le prime 1000; mantenere le sottoviste native accessibili.
- Test mirati PHP, browser responsive, ACL, JS fallback; collaudo reale.

### `0.8.1-dev.7` — form unico
- Unico flusso `Nuova task`, progetto facoltativo e creazione corretta in uno dei due itemtype, mappatura sicura dei campi; progetto native-first; nessuna conversione implicita dei record.
- Test CSRF, ACL, entità, gruppo, Select2 AJAX, form ProjectTask effettivo, su staging.

### `0.8.1-dev.8` — Kanban scrivibile
- API di spostamento, DnD, controllo conflitti, audit, accessibilità, rollback UI, sincronizzazione con form e riapertura.
- Prima di considerare PASS testare su due profili e due entità sul GLPI 11.0.9 dell'utente. Nessuna modifica al Kanban Vue nativo.

La divisione è una **proposta**, non promessa di versioni o test; Codex può cambiare ordine/numero solo rispettando la policy immutable ZIP.

## 8. Verifiche di accettazione e documentazione

- Entità e ACL: admin, utente READMY, READALL, only-project, only-standalone, nessun diritto; owner/team/gruppo, entità fuori scope e ricorsive, conteggi/tooltip/API/ricerca, tampering di source/id/target.
- Funzionalità: lista mista e paginazione; tab attiva evidenziata; browser back/forward, JS disabilitato, network failure, 401/403, URL profondi, cache/versioning; Kanban 0/1/100 task, tutte le colonne, DnD negato, conflitti simultanei, timezone, salvataggio e ricarica.
- Form: progetto vuoto/valido/non autorizzato/rimosso, entità non congruente, descrizione XSS, lunghezza, scadenza, assegnazioni native e limiti; un solo record inserito per submit; la task appare nella vista corretta.
- Regressioni: Dashboard/RSS/settings/mini ticket, Search/export GLPI, native Vue Kanban DnD; plugin OFF e `tasks_standalone` OFF, no modifica ai core files.
- CI: `php tests/unit_php.php`, `tests/tasks_php.php`, `tests/standalone_php.php`, `tests/*playwright.py`, sintassi PHP/JS/Bash, package_test, gate docs, ZIP singola `mdoverhaul/`, SHA512 source/build; distinguere mocks da GLPI LIVE.
- Insieme al **commit con codice** aggiornare setup.php a nuovo prerelease, CHANGELOG, README, QA, STATUS, docs/releases, INSTALLAZIONE/UPDATE, CONFIGURAZIONE, ARCHITETTURA, SECURITY, INTEGRATION, TEST_MATRIX e gli altri documenti impattati. Pubblicare solo su Dev via CI verde, source SHA/built_at/run ID, pacchetto immutable, mai Main/deploy automatico.
- **Non fare commit di implementazione finché non si è verificato il HEAD corrente e non si è eseguito l'intero gate**. Se GLPI reale non è disponibile, marcare `NON COLLAUDATO LIVE` e specificare i test manuali da eseguire.

## 9. Punti del codice attuale

- `front/tasks.php`: scelta tipo, default `source=project`, tab origini, Search, board e colonne derivate solo dai dati.
- `src/TaskBoard.php`: vista ProjectTask read-only, `percent_done` e `projectstates_id`, scan 1000.
- `src/StandaloneTask.php`: ACL, `status=1/2/3`, Search e board, schema 1.
- `front/standalonetask.form.php`: form e post CRUD plugin con controlli nativi.
- `public/css/task-workspace.css`: causa probabile dei pulsanti a metà pagina e tab da riposizionare.
- `setup.php`: asset hooks e menu.
- `tests/tasks_php.php`, `tests/standalone_php.php`, `tests/tasks_ui_playwright.py`, `tests/standalone_ui_playwright.py`: estendere in modo verificabile.

Questo documento registra **requisiti/architettura proposti**, non indica un'implementazione già esistente. Il codice e lo ZIP restano `0.8.1-dev.5` fino a una nuova build con CI verde.
