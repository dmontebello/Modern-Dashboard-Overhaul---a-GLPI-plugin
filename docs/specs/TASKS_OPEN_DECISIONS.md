> **Nuovo requisito del 24 ottobre 2026:** task realmente senza progetto richieste dall'utente. Questo file descrive il modello storico P0/P1 MDO 0.8.0, non un divieto permanente. Il progetto tecnico NON IMPLEMENTATO e in [MDO 0.8.1 Dev](MDO_0_8_1_DEV_WORKPLAN.md); eventuali nuove tabelle richiedono ACL, migrazione e test staging.

# Tasks — decisioni sul modello dati

Stato: **proposte da valutare**, nessuna persistenza aggiunta in P0/P1.
Non è richiesta una decisione per usare il workspace con le ProjectTask attuali.
Le estensioni sotto richiedono approvazione separata prima dell'implementazione.

## Mappatura verificata su GLPI 11.0.9

| Concetto | Supporto nativo | Comportamento P0/P1 / gap |
| --- | --- | --- |
| Attività | `ProjectTask` | Modello unico; form, ACL, team, allegati e notepad nativi |
| Progetto | `ProjectTask.projects_id` → `Project` | Obbligatorio; scelta esplicita, nessun progetto fittizio |
| Status di dettaglio | `projectstates_id` → `ProjectState` | Stato GLPI reale; usare gli stati configurati dall'amministratore, nessun inserimento automatico |
| Stage Todo/Doing/Done | Nessun secondo campo indipendente | Non persistito. Raggruppamento percentuale opzionale esplicitamente chiamato avanzamento |
| Completamento | `percent_done` | Filtri aperte/completate, non uno Stage di Notion |
| Scadenza prevista | `plan_end_date` | Visualizzata e filtrabile; date zero/vuote/invalide trattate come assenti |
| Fine effettiva | `real_end_date` | Visualizzata solo se presente, senza derivarla nel plugin dalla percentuale |
| Kanban | `Project::showKanban()` e Vue GLPI | Riutilizzabile per il progetto. Il globale nativo mescola progetti/task e filtra i progetti chiusi |
| Task personali senza progetto | Non supportate da `ProjectTask::prepareInputForAdd()` | Nessuna creazione senza progetto e nessun contenitore automatico |
| Parent iniziativa/richiesta | Non equivalente al progetto obbligatorio | Eventuali relazioni native (es. ticket) non rappresentano un parent unico iRequest/iNiziativa |
| Tempo pianificato/effettivo | `planned_duration`, `effective_duration` | Totali gestiti nel form GLPI; non equivalgono a un registro di sessioni |

Il core può aggiornare `real_end_date` durante le sue regole di coerenza del
completamento. MDO visualizza il valore restituito da GLPI e non introduce una
regola di completamento alternativa. Anche `auto_projectstates` e
`auto_percent_done` rimangono sotto il controllo del core.

## Stage e Status indipendenti

Opzione consigliata per P0/P1: conservare il solo stato nativo, senza simulare
l'indipendenza dei due assi. Eventualmente, in una richiesta successiva, una
mappatura configurata tra ID di ProjectState e macrocolonne può raggruppare gli
stati senza duplicare dati; lo Stage sarebbe però **derivato**, non indipendente.

Se serve uno Stage manuale indipendente, proposta futura: metadato del plugin
indicizzato per `projecttasks_id`, con stato nativo mantenuto in ProjectTask.
Questo richiederebbe schema, migrazione/installazione/rollback, pulizia alla
cancellazione e comportamento per cloni/importazioni da progettare. Ogni lettura
o scrittura deve passare per `ProjectTask::can()` e per le ACL del progetto,
con controlli CSRF e isolamento entità; i metadati non possono concedere diritti.

Il vantaggio è una semantica fedele ai due assi; lo svantaggio è nuova persistenza
e un flusso di modifica aggiuntivo. **Non implementato né approvato.**

## Attività personali, altri parent e sessioni

- Per il lavoro attuale, scegliere consapevolmente un progetto reale già
  autorizzato. È interamente nativo ma non soddisfa il requisito "senza progetto".
- Per promemoria personali, valutare separatamente il modello nativo `Reminder`:
  non è una ProjectTask e non viene mescolato al workspace. Un modello personale
  nuovo richiederebbe un'analisi separata di ACL, ciclo di vita e ricerca.
- Per i parent iniziativa/richiesta occorre prima definire il modello GLPI di
  riferimento e quali relazioni native siano sufficienti. Nessun nuovo Ticket o
  sostituzione con TicketTask in questo sviluppo.
- Per sessioni di lavoro granulari occorre confrontare le API native di planning
  e rendicontazione con il requisito, poi proporre un modello separato se manca
  la registrazione delle singole sessioni. I totali nativi non sono un worklog.

Qualunque estensione deve restare in `mdoverhaul`, evitare dati duplicati già
gestiti dal core e ricevere approvazione specifica prima di nuove tabelle o
migrazioni. Queste decisioni non bloccano le funzionalità P0/P1 compatibili con GLPI.

## Compatibilità della ricerca nativa

La lettura della scheda e la ricerca SQL GLPI 11.0.9 presentano criteri differenti
in alcuni casi READMY (creatore non nel team, diritti progetto combinati).
Prima dell'approvazione della PR serve una verifica con profili reali: se il
disallineamento si conferma, documentare/segnalare il comportamento del core e
valutare un extension point pubblico disponibile. Non alterare il core né
aggiungere nel plugin un secondo motore ACL per forzare l'equivalenza.


## Decisione utente del 24/10/2026 — 0.8.1-dev.2

La persistenza di task realmente indipendenti è ora esplicitamente autorizzata
nella specifica 0.8.1: implementata come StandaloneTask/CommonDBTM nello stesso
plugin, con tabella e ACL dedicate. Le valutazioni precedenti rimangono storiche.
Stage/Status indipendenti, altri parent, Notion e worklog restano non approvati.
Migrazione e comportamento GLPI reale NON COLLAUDATO LIVE; vedere STATUS.
