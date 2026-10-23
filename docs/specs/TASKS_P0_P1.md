# MDO Tasks Workspace — Specifica tecnica P0/P1 per Codex

**Stato:** specifica operativa ricostruita e versionata per sbloccare Codex, 23 ottobre 2026.  
**Repository:** `dmontebello/Modern-Dashboard-Overhaul---a-GLPI-plugin`.  
**Branch di implementazione:** `Dev` (non `Main`).  
**Baseline del codice:** Modern Dashboard Overhaul 0.8.0 Pilot già integrato. Verificare il commit **corrente** con `git fetch origin && git rev-parse origin/Dev`; non usare il vecchio `9446820` come HEAD.  
**Priorità:** implementare P0 prima di P1, senza introdurre automaticamente P2/P3.  
**Ambiente:** GLPI 11.0.x, con attenzione alle API di GLPI 11.0.9. Nessun test sulla GLPI di produzione viene presunto superato.

> Questa è la formalizzazione **nuova** della richiesta Tasks/iTasks per Codex, ricostruita dal codice 0.8.0 e dal workflow funzionale di riferimento. Il testo integrale della precedente conversazione Codex non è stato recuperato: non presentare requisiti non verificati come accordi storici.

## 1. Obiettivo e invarianti

Realizzare una sezione **Tasks** sotto **Assistance** con esperienza simile a una lista attività e a un Kanban, ispirata al workflow iTasks/iProjects, senza confonderla con i ticket di supporto.

1. **Un solo plugin:** id/cartella `mdoverhaul` e namespace `GlpiPlugin\Mdoverhaul`. NON creare plugin figlio, seconda installazione, nuova cartella sotto `plugins/` o clone del core GLPI.
2. **Moduli indipendenti:** i flag nativi `task_workspace` e `kanban_restyling` sono gestibili dalla schermata `front/config.php` tramite `src/Settings.php`; non cambiare gli altri cinque flag né la loro configurazione esistente.
3. **Separazione dei dati:** usare le **ProjectTask** native di GLPI, NON `TicketTask`. L'attività non deve creare Ticket, Change, Problem o Request.
4. **Compatibilità:** preservare Dashboard Restyling, widget RSS, identificativi persistenti delle card RSS, tema, mini-dashboard Ticket e impostazioni esistenti.
5. **Sicurezza:** nessun bypass ACL, nessun dato di progetti/team/entità fuori permesso, escaping HTML, controlli server-side. Nessuna migrazione distruttiva, nuova tabella o scrittura SQL diretta senza specifica e approvazione separata.
6. **Release:** lavorare solo su `Dev`; `Main` è pubblicazione/produzione. Non fare merge, release o deploy automatico. L'artefatto finale, quando approvato, sarà un unico ZIP `mdoverhaul/` contenente **tutti** i moduli.

## 2. Situazione rilevata nella baseline 0.8.0

File esistenti da esaminare prima di implementare:

| File | Ruolo |
| --- | --- |
| `setup.php` | Hook dei moduli e menu `Hooks::MENU_TOADD` |
| `src/Settings.php`, `front/config.php` | Sette flag configurabili, Tasks e Kanban OFF per default |
| `src/TaskMenu.php` | Voce Assistance → Tasks |
| `front/tasks.php` | Switch elenco nativo `Search::show(ProjectTask::class)` / board read-only |
| `src/TaskBoard.php` | Lettura `ProjectTask`, filtro, ACL, limiti |
| `public/css/task-workspace.css` | UI del workspace |
| `public/css/task-kanban.css` | Restyling visuale del Kanban Vue nativo |
| `tests/unit_php.php`, `tests/ui_playwright.py` | Regressioni e test; i mock non equivalgono a GLPI reale |
| `tools/maintainer/package_from_repo.py` | Costruzione dello ZIP unico |

**Difetti/ambiguità concreti da verificare e correggere in P0:**

- Nel sorgente GLPI 11.0.9, `ProjectTask` usa **`projectstates_id`** e la tabella degli stati è **`glpi_projectstates`**. L'attuale `src/TaskBoard.php` consulta invece `projecttaskstates_id` / `glpi_projecttaskstates`: il filtro e il raggruppamento Stato potrebbero essere errati. Correggere dopo confronto con schema/runtime GLPI in uso.
- `tests/unit_php.php` imposta tutte le opzioni a `1`, poi verifica "Tasks off by default" senza aver azzerato esplicitamente `task_workspace`. Riparare il test e aggiungere i casi ON/OFF senza perdere verifiche esistenti.
- Il pulsante "Nuova task" in `front/tasks.php` rimanda alla lista dei progetti. Una `ProjectTask` GLPI richiede `projects_id > 0`: definire il percorso corretto verso il form nativo e mantenere una scelta esplicita del progetto.
- Il limite `TaskBoard::MAX_CANDIDATES = 1000` è globale e i conteggi della board possono essere **parziali**: chiarire sempre visivamente il limite; non visualizzare numeri parziali come totali esatti.
- Verificare che menu e controller possano essere disabilitati dalla GUI **anche dopo refresh e nuovo login**, e che `Search::show(ProjectTask::class)` funzioni davvero con GLPI 11.0.x.

## 3. Modello funzionale di riferimento (iTasks, non copia 1:1)

- **Stage operativo:** `Todo`, `Doing`, `Done`. È il macroflusso; non confonderlo automaticamente con una percentuale di avanzamento.
- **Status di dettaglio:** `Da iniziare`, `Attivo`, `Bloccato`, `In attesa`, `In Review`, e, se supportato, `Archiviato`. Stage e Status sono concetti diversi.
- **Date:** scadenza e data reale di completamento; visualizzare le due senza scambiarle. Le date di GLPI (es. `plan_end_date`, `real_end_date`) vanno verificate semanticamente.
- **Relazione:** una task ha un contesto principale; nel workflow di riferimento può essere legata a un progetto, a un'iniziativa, a una richiesta, oppure essere personale.
- **Sessioni di lavoro:** registrazione del tempo impiegato è parte del modello di riferimento.

**Scarto strutturale non negoziabile:** `ProjectTask::prepareInputForAdd()` in GLPI 11.0.9 rifiuta una task senza `projects_id`. Pertanto P0/P1 possono lavorare sulle **ProjectTask con progetto**. Le task "Personal" prive di progetto, i parent iRequest/iNiziativa, l'indipendenza persistente Stage/Status e le worklog session non hanno una mappatura automaticamente fedele: trattarli come **gap espliciti**, non inventare una persistenza o creare di nascosto progetti-container. Proporre una soluzione tecnica separata prima di implementare quei dati.

## 4. P0 — Funzionamento corretto, permessi e configurazione (obbligatorio)

### P0.1 — Stabilizzare GLPI Tasks

- Correggere lo schema dei campi `ProjectTask` (in particolare gli stati) e controllare nomi reali di tabella, chiavi, ACL e URL attraverso il codice GLPI 11.0.x.
- Nella sezione Assistance → Tasks, rendere accessibile una **lista globale di ProjectTask** che riusa la ricerca, filtri e paginazione **native GLPI**; il riferimento è Assistance → Tickets come disposizione della pagina, NON come modello dati.
- La board alternativa deve mostrare le medesime task autorizzate, con link al form `ProjectTask` e al relativo `Project`. Restare read-only in P0.
- Il pulsante "Nuova task" deve proporre la selezione esplicita di un progetto GLPI accessibile e, quando noto, puntare al form nativo con `projects_id`; non eseguire INSERT custom.
- Stati vuoti, progetti rimossi/invisibili, percentuali e date mancanti non devono generare warning PHP fatali o errori 500.

**Accettazione P0.1:** con `task_workspace=ON`, un utente autorizzato apre Assistance → Tasks, vede lista e board per i soli progetti leciti, apre task/progetto e crea una task dal form GLPI con progetto selezionato. La board mostra lo stato GLPI reale; niente `projecttaskstates_id` inesistente.

### P0.2 — Flag indipendenti e menu

- Confermare `task_workspace` e `kanban_restyling` salvati con `Settings::save()` in `plugin:mdoverhaul`.
- `task_workspace=OFF` rimuove menu, CSS workspace e accesso alla pagina (anche tramite URL diretta); `kanban_restyling` continua a funzionare indipendentemente.
- `task_workspace=ON` inserisce la voce **Tasks** nel settore **Assistance** solo per profili aventi i diritti `ProjectTask` richiesti.
- I moduli Dashboard e RSS sono invariati in tutte le quattro combinazioni dei due nuovi switch; la schermata Configura resta disponibile anche quando tutti i moduli sono OFF.
- Non alterare il flag di attivazione del plugin o il suo identificativo tecnico.

**Accettazione P0.2:** matrice 2×2 ON/OFF, sia amministratore sia profilo senza diritti, nessun 403/404 inatteso per chi ha autorizzazione e nessuna esposizione se OFF.

### P0.3 — ACL, sicurezza, affidabilità

- Applicare in PHP i controlli di lettura a **ogni** ProjectTask prima del rendering; usare controlli di visibilità progetto/entità/team del core GLPI, non soltanto filtri JS.
- Proteggere `id`, testo di ricerca, filtri stato/progetto e tutti gli URL da input malformati e HTML injection.
- Nessun conteggio deve includere record che il profilo non può vedere. Se la fonte è troncata per limiti prestazionali, mostrare badge o testo "parziale" senza presentare totali certi.
- Evitare query illimitate, N+1 non necessari e accessi a progetti di altre entità. Documentare compromessi di prestazione senza implementare query raw che aggirano ACL.
- Verificare che menu, CSS e JavaScript non introducano regressioni in Ticket/Project/Dashboard/RSS.

**Accettazione P0.3:** test almeno due profili e due entità/progetti con diritti differenti; accesso diretto con parametro manipolato non svela titolo/descrizione/progetto né dati aggregati vietati.

### P0.4 — Test, stabilità e consegna

- Riparare i test baseline incoerenti ed estenderli per flag ON/OFF, stato GLPI corretto, board read-only, input ostili, nessun leakage ACL e limite 1000.
- Eseguire `php -l` su tutti i PHP modificati, `php tests/unit_php.php`, i test UI disponibili, e la build con `python3 -B tools/maintainer/package_from_repo.py` seguita da `unzip -t` e `sha512sum -c`. Riportare risultati **effettivi**.
- Se manca un runtime GLPI 11, distinguere esplicitamente i test offline da quelli da effettuare su staging live; non affermare collaudo completo.
- Non pubblicare lo ZIP modificato su `Main` prima di approvazione. Evitare commit di file temporanei, dump, segreti, dati Notion e path locali.

**Accettazione P0.4:** PR/commit `Dev` con diff circoscritto e report test; il pacchetto unico (generato localmente o CI) continua a contenere Dashboard, RSS, Tasks e Settings.

## 5. P1 — Avvicinamento UX al workflow iTasks (dopo P0)

### P1.1 — Lista e board coerenti

- Fornire nella sezione Tasks due viste **Elenco** e **Kanban**, con navigazione chiara, stato attivo, descrizioni sintetiche, stato vuoto/errore, layout desktop/tablet/mobile.
- Filtri: testo, progetto, stato GLPI, task aperte/completate/scadute; ordinamento almeno per data/stato/progetto o delega esplicita alla ricerca nativa.
- La board deve essere leggibile: colonne abbastanza larghe, etichette orizzontali anche se compattate, nessuna card schiacciata, scrollbar orizzontale esplicita; preservare interazioni native e accessibilità.
- Visualizzare titolo, progetto, stato, avanzamento, scadenza; usare badge o testo "scaduta" solo per attività non concluse con scadenza trascorsa.
- Preservare i filtri quando si passa da lista a board **solo se** possibile senza rompere la ricerca nativa GLPI; in alternativa documentare chiaramente che le due viste conservano sistemi di filtro diversi.

**Accettazione P1.1:** confronti responsive a 1440, 1024, 768 e 390 px, light/dark, tastiera, titoli lunghi, 0 e 100 task; nessun overflow orizzontale dell'intera pagina.

### P1.2 — Stage/Status senza false equivalenze

- Presentare **Stage** e **Status GLPI** come due concetti separati. Se lo Stage è ricavato da `percent_done`, chiamarlo esplicitamente **"raggruppamento per avanzamento"**, non salvare/mostrare falsamente lo Stage manuale di Notion.
- Per raggruppamento per Status usare la vera `projectstates_id` e descrizione da `glpi_projectstates`.
- Verificare le date native `plan_end_date` e `real_end_date`, e non compilare automaticamente una "completata il" senza regola applicativa approvata.
- Documentare in una tabella le mappature supportate e i gap (stati indipendenti, Personal, parent unico non-Project, worklog), senza introdurre colonne/tabelle ad hoc.

**Accettazione P1.2:** colonna raggruppata per stato e colonna raggruppata per avanzamento mostrano dati semanticamente corretti e non alterano le task quando l'utente cambia vista.

### P1.3 — Esperienza di creazione/gestione coerente con GLPI

- Dove consentito, CTA "Nuova task" che guidi prima al progetto e poi alla creazione della ProjectTask nativa. Dove non consentito, CTA assente.
- Edit completo, permessi di scrittura e gestione allegati/notepad **restano nel form nativo GLPI**: niente form di scrittura parziale o endpoint REST non autenticato.
- Migliorare link a scheda attività, scheda progetto ed eventuali filtri "mie task" solo dopo verifica con le ACL.
- Registrare in documentazione cosa è possibile fare nel plugin e cosa richiede l'interfaccia GLPI nativa.

**Accettazione P1.3:** creare, aprire, modificare con permessi e tornare all'elenco non obbliga a utilizzare Ticket; un lettore non vede controlli di creazione/scrittura.

### P1.4 — Compatibilità con il Kanban nativo

- La skin CSS controllata da `kanban_restyling` è **separata** dal Kanban read-only del Workspace.
- Non alterare il drag & drop nativo, classi globali o API Vue del core.
- Ridurre selettori CSS `!important` se causano errori; limitare scope a `#kanban-app`.
- Se emerge un difetto del core GLPI non correggibile dal plugin senza monkey patch, documentarlo e proporre alternativa, non modificare `js/src/vue` del core.

**Accettazione P1.4:** skin OFF = resa GLPI ordinaria; skin ON = colonne leggibili e DnD nativo funzionante dove disponibile; nessun effetto su Ticket.

## 6. Fuori scope P0/P1: non implementare senza approvazione

- Importazione/sincronizzazione da Notion e gestione token o credenziali.
- Implementazione completa di `iProjects`, `iRequests`, `iNiziative`, worklog e sessioni di lavoro.
- Nuovi database custom, migrazioni strutturali, un "progetto Personale" creato silenziosamente o task native senza `projects_id`.
- Nuovi automatismi che modificano Stage/Status e completamento di massa senza conferma.
- Riscrivere Ticket, installare un secondo plugin, cambiare identificativi RSS o rimuovere moduli Dashboard esistenti.
- Merge automatico su `Main`, deploy server, bump versione e nuova release senza richiesta.

**Se un requisito non è realizzabile sulle ProjectTask native:** creare `docs/specs/TASKS_OPEN_DECISIONS.md` con decisione proposta, impatto dati/ACL, opzioni, pro/contro e chiedere approvazione; non forzare il comportamento.

## 7. Ordine di lavoro Codex e artefatti attesi

1. **Inventory:** `git fetch origin`; assicurare branch `Dev`, leggere `AGENTS.md`, questo documento e i sorgenti indicati.
2. **Analisi differenziale:** P0/P1 già implementati, difetti reali e punti non coperti; documentare test rotti, compatibilità API GLPI.
3. **Implementazione P0:** commit atomici su `Dev` con test e note.
4. **Implementazione P1:** dopo P0, solo attività non bloccate da scelte sul modello dati; eseguire smoke e regressioni.
5. **Validazione:** test statici/unit, build ZIP unico e checksum in ambiente di lavoro; test live GLPI 11 se accessibile, indicando esattamente cosa è stato verificato.
6. **Resoconto finale:** elenco file cambiati, mapping requisito → file/test, risultati, problemi aperti, link commit `Dev`, proposta PR `Dev` → `Main`. Nessun merge automatico.

**Criterio di stop:** se Codex Cloud resta in `starting` o non legge il repository, non inventare HEAD, test o modifiche. Segnalare l'ostacolo preciso e fermarsi.
