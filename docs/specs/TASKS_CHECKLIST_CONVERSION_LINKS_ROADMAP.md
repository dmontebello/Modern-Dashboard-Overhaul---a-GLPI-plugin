# MDO Tasks — Roadmap dev.8, dev.9 e progetto 0.8.2

**Stato: REQUISITI APPROVATI DALL'UTENTE / NON IMPLEMENTATI.** Decisione del 25 ottobre 2026, dopo la specifica `TASKS_UNIFIED_WORKSPACE_V2.md`. Questo è un documento di pianificazione: **non è un changelog né una dichiarazione di funzionalità implementate**.

**Repository:** `dmontebello/Modern-Dashboard-Overhaul---a-GLPI-plugin`. Operare soltanto su branch **`Dev`**, non su `Main`. La candidata `0.8.1-dev.6` è ora pubblicata su Dev con CI PASS ma NON COLLAUDATA LIVE; dev.7/dev.8/dev.9 e 0.8.2 restano pianificate e non pubblicate. Codex deve fare `git fetch origin`, consultare `AGENTS.md`, `docs/dev/STATUS.md`, `docs/dev/VERSIONING_POLICY.md`, `docs/specs/TASKS_UNIFIED_WORKSPACE_V2.md`, le note di release e il codice attuale prima di iniziare.

## 1. Decisioni consolidate / sequenza di release

| Release obiettivo | Scope | Esclusioni |
| --- | --- | --- |
| `0.8.1-dev.6` | Vista Tutte, navigazione fluida, tre colonne Kanban permanenti e drag&drop autorizzato, note cronologiche consultabili integralmente e allegati per entrambi i tipi, correzione layout form standalone | Checklist, conversione e relazione ad asset |
| `0.8.1-dev.7` | Form di creazione unico con progetto facoltativo; POST crea `ProjectTask` nativa se progetto selezionato, altrimenti `StandaloneTask`; i record esistenti non sono convertiti implicitamente | Checklist, migrazione task già esistenti |
| **`0.8.1-dev.8`** | Checklist spuntabile, riordinabile, modificabile e persistente **all'interno di ogni task** | Non modificare automaticamente `percent_done`, `projectstates_id` o lo Stage globale |
| **`0.8.1-dev.9`** | Conversione `StandaloneTask` → `ProjectTask` (anche avviabile dalla scheda progetto) **e collegamenti bidirezionali** con asset, contratti, licenze e altre task | Nessuna conversione automatica per semplici link con asset o altre task |
| **`0.8.2`** | Scheda strutturata di progetto `Goals`, `In scope`, `Out of scope`; ulteriore UX iProjects da definire | Non anticipare la modifica dello schema Project in 0.8.1-dev.N |

La precedente etichetta "dev.8" per il Kanban DnD **non si applica più**: l'utente ha già spostato quel lavoro alla dev.6. La nuova dev.8 riguarda esclusivamente le checklist e la relativa UX/test.

## 2. Requisito già in dev.6: tutte le annotazioni, non solo l'ultima

Il diario della task deve conservare e mostrare **l'intero storico autorizzato**, con ordinamento discendente per data/ID stabile, autore, data/ora server e contenuto. Nessuna policy di "solo ultima nota". La pagina della task mostra un primo blocco di aggiornamenti recenti e un comando **"Mostra precedenti" / "Carica altri"** paginato che consente di raggiungere anche i più vecchi, fino al primo disponibile. Mantenerne accesso da tastiera e deep link se opportuno; nessun troncamento irreversibile, deduplica o omissione silenziosa. La paginazione deve rispettare le ACL task a ogni chiamata e non assumere che `Notepad::getAllForItem()` sia già paginato o che garantisca ACL del parent. Non introdurre modifiche o eliminazioni retroattive durante la sola lettura.

**Acceptance dev.6:** task con 25 annotazioni di test; le prime visibili subito, tutte le precedenti accessibili tramite "Mostra precedenti", autore/timestamp corretti, ordine stabile anche a parità di data, nessuna nota doppia, controllo di accesso su ogni pagina, funzionamento dopo refresh e da browser mobile. Allegati eventualmente inseriti in Note/Documenti rimangono accessibili solo ai profili abilitati.

## 3. `0.8.1-dev.8` — checklist per task

**UX proposta:** sezione "Checklist" sotto descrizione e prima del diario, con righe a checkbox; aggiungere voce, modificare testo, spuntare/riaprire, riordinare con drag&drop **e** controlli da tastiera, eliminare con conferma/undo quando compatibile. Mostrare contatore `n/N` e barra di avanzamento locale della checklist. In fase iniziale una checklist semplice per task; checklist multiple/annidate e assegnazioni per singola riga sono backlog futuro se non richieste.

**Dati:**
- Le checklist appartengono a un identificatore **tipizzato** (`ProjectTask` o `StandaloneTask`, ID numerico), non direttamente al progetto e non all'ID senza namespace. Evitare collisioni `ProjectTask#1` vs `StandaloneTask#1`.
- Preferire riuso native API se disponibile; altrimenti nuova tabella dedicata nel plugin con `id`, `itemtype` allowlist, `items_id`, `label`, `is_checked`, `position`, `users_id_lastupdater`, `date_creation`, `date_mod`, eventuale `is_deleted` secondo semantica di audit; indici per parent+posizione e constraints di unicità. Prima di introdurre lo schema scrivere migrazione idempotente, backup/rollback e conservazione dei dati alla disattivazione.
- Una checklist **non è** una `ProjectTask` figlia, un `TicketTask`, un `Reminder` o un `Notepad`; niente creazione di ProjectTask surrogate, niente cambi di percentuale o Stage senza scelta separata.

**Accessi e API:** la lista di righe è visibile soltanto se `task->can(id,READ)` e contesto entità autorizzato; aggiunta/spunta/riordino/cancellazione soltanto con `task->can(id,UPDATE)`. POST autenticato con CSRF GLPI, validazione input, batch reorder atomico, controllo concorrenza e audit, limitazione di lunghezza/testo escaped. Link diretti, contatori, download/export e AJAX non devono rivelare checklist su task non visibili.

**Acceptance:** due task tipi differenti, 0/1/100 voci, cambio ordine e spunta persistenti dopo refresh, n/N corretto; sola lettura non può scrivere, utente cross-entità/READMY non può leggere o scrivere, tampering id/source/position negato, niente variazioni a `percent_done` e `ProjectState`. Test PHP, Playwright e staging reale.

## 4. `0.8.1-dev.9` — conversione vera di task indipendente in task di progetto

**Due punti di ingresso per la STESSA operazione di conversione:**
1. Dalla scheda di una `StandaloneTask` → azione esplicita **"Converti in task di progetto"** → selezione progetto nativo autorizzato → anteprima dei dati da trasferire → conferma.
2. Dalla scheda di un `Project` GLPI → **"Aggiungi task indipendente esistente"** → ricerca delle sole StandaloneTask accessibili/convertibili → anteprima → conferma. Questa è un'azione circoscritta da realizzare **nella dev.9**, benché l'ulteriore evoluzione iProjects sia rinviata a `0.8.2`.

L'utente richiede **conversione/migrazione**, non un mero collegamento al progetto, né un "progetto fittizio" né due task attive duplicate. La `ProjectTask` ha `projects_id > 0` obbligatorio nel core GLPI 11 e va creata usando ACL e API native (`ProjectTask::check(-1,CREATE,input)`, `ProjectTask::add`), non INSERT SQL diretti sul core.

**Regole invarianti:**
- Prima di ogni operazione verificare lettura e diritti adeguati di modifica sulla sorgente StandaloneTask e `ProjectTask::can(-1,CREATE,...)` sul progetto target, con ACL di entità e team; richiedere conferma esplicita, senza side effect dalla semplice navigazione, dalla ricerca o da un link di relazione.
- Mappare i dati con una matrice verificata: titolo, descrizione, date (due_date ↔ plan_end_date con timezone), stato `1/2/3` ↔ avanzamento `0/50/100` salvo regole core, proprietario/assegnatari e gruppi verso team nativo se ammessi, checklist dev.8, storico note e allegati dev.6, relazioni dev.9. Campi non equivalenti devono essere presentati nell'anteprima come conservati solo in audit/metadata oppure bloccanti, **mai persi silenziosamente**.
- Usare i meccanismi Note/Documenti GLPI dove consentito; mantenere autore, data, riferimenti e file o una copia fedele con provenienza. Non riscrivere `glpi_notepads` o `glpi_documents_items` con SQL diretto bypassando ACL/audit. Se le API non permettono conservazione adeguata di metadati, bloccare la conversione e documentare la limitazione prima di procedere.
- Definire un **registro di conversione persistente** (itemtype/id originali, ProjectTask target, progetto, autore conversione, timestamp, esito/versione schema) per idempotenza e tracciabilità. Valutare una tabella MDO dedicata con migrazione/rollback idempotenti. Gli URL storici devono poter mostrare un collegamento al nuovo elemento SOLO se l'utente ha il diritto di leggerlo. Non creare redirect che divulghino task.
- Procedura sicura: backup; preflight ACL+esistenza+entità; preparare la ProjectTask nel core; migrare dati associati con API supportate; verificare totale checklist/note/documenti/link; registrare mappatura e **archiviare/nascondere la fonte dall'elenco attivo soltanto a conversione riuscita**. Prevenire doppio submit e collisioni concorrenti. La gestione di file e API non sempre è racchiudibile in una sola transazione DB: progettare un flusso recuperabile/idempotente con clean-up compensativo o stato di migrazione e logging, senza eliminazione irreversibile in caso di errore.
- Dopo conversione una sola task compare nelle viste operative; la scheda progetto e il Kanban nativi mostrano la nuova ProjectTask; il riferimento all'origine rimane nell'audit. Non "restaurare" automaticamente la standalone convertita come nuovo duplicato. Definire il comportamento esplicito di disattivazione plugin, cestino, restore e cancellazione ProjectTask.

**Acceptance:** conversione da entrambe le UI; descrizione, scadenza, assegnazioni consentite, 25 note, 2 allegati, checklist con completati/mancanti e 3 link sono preservati e verificati; niente perdita anche in errore simulato a metà, doppio submit idempotente, account con sola lettura e altra entità non convertono, URL storico non divulga target, backup/restore testati su staging.

## 5. `0.8.1-dev.9` — collegamenti bidirezionali a elementi GLPI

**Dalla task:** sezione **"Elementi collegati"** con scelta tipo e ricerca secondo la ACL nativa del target. Tipi richiesti: **asset** (es. Computer e altri asset nativi), **Contratto** (`Contract`), **Licenza software** (`SoftwareLicense`), **altra task** (`ProjectTask` o `StandaloneTask`). Aggiunta/rimozione di relazioni indipendenti dal progetto; opzionalmente etichette di relazione generica "Correlata a" nella prima fase.

**Dal record collegato:** tab/sezione MDO **"Task collegate"** in schede native di asset, contratto, licenza e task, con link di ritorno, tipo di task, Stage, scadenza, stato e possibilità di aprire la task autorizzata. Visibilità in entrambe le direzioni per lettura, ma non tutte le schede GLPI hanno necessariamente un identico hook: Codex deve verificare le registration API `Plugin::registerClass(..., ['addtabon'=>...])` o estensioni pubbliche, UI responsive ed eventuali limitazioni per singolo tipo.

**Ricognizione codice GLPI 11.0.9:** `ProjectTaskLink` collega già coppie di `ProjectTask`; `Contract_Item`, `Item_Project` e `Item_SoftwareLicense` sono relazioni native *fra categorie diverse*, ma nessuna di queste costituisce da sola un sistema universale task↔asset/contratto/licenza. Non riutilizzare arbitrariamente tabelle di relazioni native per scopi diversi; progettare un `CommonDBRelation` plugin o tabella polimorfica dedicata e valutare quando interoperare con `ProjectTaskLink` senza duplicare relazioni visibili.

**Persistenza proposta:** tabella MDO con `source_itemtype`, `source_items_id`, `target_itemtype`, `target_items_id`, `relation_type`, autore, date, indici su entrambe le estremità, identità/canonicalizzazione di coppie simmetriche e vincolo anti-duplicati. I tipi devono essere in allowlist GLPI validata (niente classi arbitrarie da input) e i link non possono essere riflessivi (task a sé stessa). Le sottocategorie di asset vanno aggiunte solo dopo verifica di ACL e hook.

**Sicurezza:** mostrare un collegamento solo se l'utente ha **READ sulla task e sul target** ed entrambi sono nel suo scope di entità; non mostrare nome, ID, badge o conteggi che permettano l'inferenza di dati nascosti. Per creare/eliminare il link richiedere il diritto UPDATE sul record task e un controllo di accessibilità del target, CSRF, verifica backend e idempotenza. Se link creato da UI target, controllare ugualmente UPDATE della task. Non conferire diritti su asset o task mediante un link. Multi-tenant e ACL per target sono server-side su tutte le route, incluse paginazione/export/API; un endpoint generico non deve diventare una search bypass delle ACL.

**Semantica:** collegare task↔task non comporta conversione in ProjectTask né cambio Stage; collegare task↔asset/contratto/licenza non equivale ad assegnare la licenza o modificare il contratto/asset. Conversione da progetto è un'**azione separata, esplicita e confermata**. Evitare regressioni nel Kanban, in Search e nei tab nativi.

**Acceptance:** collegamento e vista inversa testati su almeno Computer, Contract, SoftwareLicense, ProjectTask↔StandaloneTask e ProjectTask↔ProjectTask; nessun doppione, link rimossi da entrambe le UI, collegamenti su task convertita conservati, ACL full vs partial vs denied, cross-entity, URL manomessi, test CSRF/race, 0/100 collegamenti, asset cancellati o spostati, tooltip e contatori privi di leak. Verificare l'assenza di scritture dirette core e dati non autorizzati.

## 6. `0.8.2` — iProjects: Goals, In Scope, Out of Scope

**Rinviato per esplicita decisione dell'utente.** Progettare successivamente una scheda iniziale/brief di progetto con `Goals`, `In scope`, `Out of scope`, eventualmente stakeholder, criteri di successo, date, versioni di revisione, note operative e allegati. Preferire tab/pannello integrato nel `Project` GLPI tramite estensioni pubbliche: non modificare `glpi_projects` direttamente; qualsiasi nuovo schema/relazione MDO richiede ACL, migrazione, versionamento, export e backup. La presenza di Notepad/Documenti nativi in `Project` non equivale a questi campi strutturati.

**Eccezione consapevole:** la dev.9 include **solo l'azione contestuale "Aggiungi task indipendente esistente → converti" nella scheda del progetto**, necessaria per la migrazione delle task. Il redesign complessivo della gestione progetti resta per `0.8.2`.

## 7. Definizione di completamento e handoff Codex

- Ogni sviluppo modifica `setup.php` a una nuova prerelease **solo quando esiste codice da pubblicare**; ZIP immutabile per `0.8.1-dev.N`, alias Latest solo dopo CI verde e documenti/versioni allineati. Non considerare la modifica di questa roadmap una release.
- Esiti test differenziati: fixture/mocks, lint, UI Playwright, GLPI live con profili/entità, back-up+restore, audit e URL di download. Segnare `NON COLLAUDATO LIVE` per qualunque funzionalità non provata direttamente.
- Aggiornare nello stesso commit di ogni versione README, CHANGELOG, QA, STATUS, note `docs/releases/<version>.md`, UPDATE/INSTALLAZIONE, architettura, sicurezza/ACL, matrici test e schema se necessario.
- Nessuna tabella nuova o modifica core senza schema documentato, idempotenza, rollback e test reali; niente perdita dati e nessuna scrittura automatica su produzione.
- Per implementare nuove versioni **partire dal commit HEAD aggiornato di Dev** e aprire/chiudere i gate in ordine. PR `Dev` → `Main` solo dopo collaudo dell'utente e approvazione esplicita.

**Collegamento alla specifica precedente:** [Tasks Workspace V2](TASKS_UNIFIED_WORKSPACE_V2.md). Questa roadmap integra e prevale sui rinvii generici "checklist, conversione, relazioni, progetto" presenti nelle note storiche.


## Checkpoint 25 ottobre 2026 — Dev.6 pubblicata

Build `0.8.1-dev.6` verificata da GitHub Actions #40225944788, source SHA `080532ab6c6e5774e5445ddd80dec0c938e43726`; ZIP e checksum sulla branch Dev, non Main. Dev.8 checklist, dev.9 conversione + link bidirezionali e 0.8.2 iProjects rimangono REQUISITI NON IMPLEMENTATI. Manca il collaudo live della dev.6. Documentazione ufficiale: `docs/dev/STATUS.md` e `docs/releases/0.8.1-dev.6.build.json`.
