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
