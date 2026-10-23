# Tasks P0/P1 — implementazione e verifica (MDO 0.8.0)

## Baseline e verifica nativa

Sviluppo iniziato dal vero `origin/Dev` `dd275eb3b3c76618b5dc83ee0b10fc3c8e0cf600`,
dopo fetch dei branch con nomi esatti `Dev` e `Main`. Nessuna modifica a `Main`.
Già presenti: due flag indipendenti OFF, menu Assistance, ricerca ProjectTask,
board read-only, skin del Kanban Vue, configurazione nativa e ZIP unico.
La correzione del test dei default è già in `dd275eb`: non è stata riapplicata.

Confronto effettuato con i sorgenti pubblici del tag GLPI **11.0.9**:

- [ProjectTask](https://github.com/glpi-project/glpi/blob/11.0.9/src/ProjectTask.php):
  `projectstates_id`, ACL su entità/manager/team, `post_getFromDB()` carica il team,
  creazione con progetto obbligatorio e diritto UPDATE del progetto padre.
- [Schema](https://github.com/glpi-project/glpi/blob/11.0.9/install/mysql/glpi-empty.sql):
  `glpi_projectstates`, task con `is_template`, `is_deleted` e date native.
- [Search](https://github.com/glpi-project/glpi/blob/11.0.9/src/Search.php) e
  [Toolbox](https://github.com/glpi-project/glpi/blob/11.0.9/src/Toolbox.php):
  `Search::show(ProjectTask::class)` imposta il target `/front/projecttask.php`,
  file assente. Il workspace usa le API pubbliche `manageParams`,
  `showGenericSearch`, `showList` con target fisso al controller Tasks.
  Filtri, ordinamento, colonne, paginazione e permessi della lista restano nativi.
  Nessuna dipendenza diretta dalle classi `Glpi\Search` marcate `@internal`.
- [Project](https://github.com/glpi-project/glpi/blob/11.0.9/src/Project.php):
  Kanban Vue nativo con progetti **e** task. Il globale parte dai progetti visibili,
  esclude task di progetti chiusi e non copre l'accesso soltanto alle task.
  Non offre una vista globale di sole ProjectTask equivalente al workspace.
  Questo giustifica mantenere l'adattatore read-only già esistente, senza
  sostituire Vue, drag & drop o endpoint di scrittura nativi.

## P0

- `TaskBoard`: corretti campo e tabella degli stati; esclusi template e cancellati;
  prefiltraggio entità tramite funzione GLPI; `can(id, READ)` su ogni task per
  rispettare anche gli hook `ITEM_CAN`. Idratazione da riga completa con
  `getFromResultSet` e **`post_getFromDB`** per conservare le ACL dei team.
- Controllo distinto del progetto: padre inesistente/cancellato/template o in
  entità non accessibile escluso; task lecita con progetto non leggibile mantenuta,
  ma nome, link e opzione filtro progetto nascosti. Nessuna ricerca nel nome nascosto.
- Nessun conteggio di record negati. Al massimo 1000 candidati recenti nelle
  entità attive, poi ACL individuali; il record 1001 rileva il limite. Se raggiunto,
  tutti i conteggi e le opzioni filtro sono dichiarati parziali. La board può
  mostrare meno di 1000 task anche quando ne esistono altre autorizzate più vecchie.
- La query carica le righe una volta. Le ACL native caricano team/progetto per
  record; i nomi/proprietà dei progetti e gli stati sono memorizzati per richiesta.
  Non si riscrive l'SQL delle ACL del core per eliminare questi costi; su grandi
  dataset usare la ricerca nativa paginata. Nessuna cache tra utenti/richieste.
- `front/tasks.php`: controllo interfaccia centrale, plugin attivo, flag e diritto
  nativo; URL con `root_doc`; input scalari/ID validati ed escaping.
- Creazione: scelta esplicita tramite `Dropdown::showFromArray` nativo, con
  progetti leggibili/modificabili e `ProjectTask::can(-1, CREATE, input)` verificato
  dal server. Redirect al form nativo `?projects_id=...`, nessuna scrittura MDO.
  Anche la scelta progetti è limitata a 1000 candidati con avviso se parziale;
  gli altri restano raggiungibili dalla ricerca Progetti e dalla scheda Attività.
- Menu registrato soltanto per utenti autorizzati. Due flag indipendenti, nessuna
  variazione ai cinque flag precedenti o agli ID RSS.
- Errori di lettura della board/progetti mostrano un messaggio generico, dettagli
  soltanto nei log GLPI. Date vuote, zero e non valide non diventano scadenze.

## Verifica P0 offline

PHP 8.4.27 disponibile nel runtime locale predisposto; Git 2.52.0, Node e Chromium.
Eseguiti `php tests/unit_php.php` (67 controlli) e `php tests/tasks_php.php`
(40 controlli iniziali), parser PHP/Bash/JS e `git diff --check`, tutti superati.
I test Tasks includono due profili, due entità, accesso solo come team, progetto
non leggibile, hook di negazione, input ostili, creazione, accesso diretto OFF,
limite 1000 e gestione errori. **Sono doubles isolati, non un collaudo GLPI.**

Non è collegata un'istanza GLPI/database di staging. Restano obbligatori il
collaudo reale della ricerca AJAX/paginazione/export, menu e impostazioni dopo
nuovo login, creazione/modifica, entità ricorsive e gruppi, timezone, tema e DnD
nativo. Nessuna pubblicazione, deploy, migrazione, bump o modifica al core.

Dal sorgente GLPI emerge anche un caso da verificare specificamente su staging:
`ProjectTask::canViewItem()` ammette il creatore oltre ai membri del team, mentre
`SQLProvider::getDefaultWhereCriteria(ProjectTask::class)` per chi ha soltanto
READMY sulle task costruisce un criterio sul team. Verificare quindi un creatore
non presente nel team e i profili con diritti progetto combinati. La board usa
il diritto nativo dell'elemento; la lista usa l'ACL della ricerca del core.
Non si certifica una parità completa tra le due viste senza collaudo, oltre al
limite dichiarato del campione. Nessuna riscrittura o monkey patch delle ACL SQL.

## P1 e mappatura dei requisiti

| Requisito | Implementazione | Prova offline |
| --- | --- | --- |
| P0.1 schema/ricerca/form | `TaskBoard`, `front/tasks.php` | `tasks_php.php`: schema, target, progetto obbligatorio |
| P0.2 flag/menu indipendenti | `TaskMenu`, `setup.php`, `Settings` nativo invariato | `unit_php.php`: 2×2 con/senza diritti, Dashboard/RSS invariati |
| P0.3 ACL/robustezza | `TaskBoard`, controller | `tasks_php.php`: entità/attori/team, hook, input ostili, limite, errori |
| P1.1 lista/board | Controller e CSS workspace | `tasks_ui_playwright.py`: output PHP reale, GET filtri, reset, link, keyboard e responsive |
| P1.2 semantica | Stato GLPI predefinito, gruppi opzionali 0%/1–99%/100%, date distinte | `tasks_php.php` e UI: nessuno Stage manuale inventato |
| P1.3 gestione nativa | Dropdown, ACL CREATE/UPDATE/READ, form ProjectTask | `tasks_php.php`: lettore, selezione ostile e progetto fuori dal campione |
| P1.4 Kanban | Skin nativa invariata; link al tab nativo `Project$3` | UI: scope e geometria DOM; DnD Vue reale NON TESTATO |

La board usa filtri combinabili per testo/progetto/stato/completamento/scadenza
(tutte, scadute, oggi, prossimi sette giorni, assente), ordinamento per scadenza,
titolo, progetto, stato o avanzamento. Usa l'orologio di richiesta nativo
`Session::getCurrentTime()` e il fuso del runtime GLPI; le date visualizzate
passano da `Html::convDateTime()`. Verificati offline anche i confini di calendario
durante il cambio dell'ora legale di Europe/Rome, senza imporre quel fuso al plugin.

Il pannello filtri è un `details/summary` HTML nativo, richiudibile da tastiera e
aperto se ci sono filtri attivi. I filtri GLPI e quelli della board sono separati,
come dichiarato in pagina; i link conservano le preferenze della board, senza
tradurre criteri nativi o modificare la sessione di ricerca GLPI nel plugin.
I conteggi rapidi rispettano testo/progetto/stato/scadenza selezionati.

La creazione con un progetto noto lo verifica direttamente anche quando non
rientra nel campione del selettore. Le card mostrano stato, percentuale, scadenza
prevista, badge scaduta solo se incompleta e fine effettiva solo se presente.
Nessuna mutazione al cambio vista; modifiche, allegati/notepad e gestione tempo
restano nei form del core. Niente nuovo JS applicativo o endpoint di scrittura.

## Esiti complessivi offline

- PHP 8.4.27: **67** controlli moduli/RSS e **59** controlli Tasks superati.
- Sintassi: **15 PHP, 6 Bash, 2 JavaScript** superati, più compilazione sintattica
  dei Python modificati e controllo YAML della workflow.
- UI dashboard: **80** controlli Chromium superati.
- UI Tasks: **167** controlli Chromium superati, output del controller reale con
  doubles GLPI, a **1440/1024/768/390 px**, light/dark e **0/100 task**; titoli
  lunghi, nessun overflow della pagina, tastiera, filtri GET, reset e navigazione.
  Screenshot desktop/mobile ispezionati. CSS di contesto GLPI simulato: questo
  non certifica la resa di ogni tema reale o di Vue.
- Build locale del plugin completo e verifica ZIP/SHA512 previste dal processo;
  esito registrato in [QA](../../QA.md).

La workflow Actions ora esegue anche i test Tasks e UI, oltre alla build unica.
Non è stata eseguita una nuova Actions remota: i cambiamenti restano locali.
Le decisioni strutturali sono in [TASKS_OPEN_DECISIONS](TASKS_OPEN_DECISIONS.md).
La proposta di PR è in [TASKS_PR_PROPOSAL](TASKS_PR_PROPOSAL.md).
