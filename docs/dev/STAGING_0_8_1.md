# Revisione staging GLPI 11.0.9 — 0.8.1-dev.5

**NON COLLAUDATO LIVE.** L'utente ha confermato che lo staging non è raggiungibile
da questo ambiente e svolgerà il collaudo dopo la pubblicazione su Dev.
Nessun upgrade, migrazione o deploy è stato eseguito sul suo GLPI.
I risultati automatici e i loro limiti sono in [QA](../../QA.md).

## Preparazione e provenienza

1. Usare una copia isolata di GLPI **11.0.9**, DB e volumi; bloccare invii e
   integrazioni esterne della copia secondo le procedure locali. Registrare
   versione precedente, profili, entità attive e configurazione dei sette flag.
2. Salvare un backup completo e coordinato di DB, plugin precedente, configurazione
   e volumi GLPI, fuori dal webroot e dal repository. Per il DB, usando un file
   client già predisposto e protetto, senza credenziali nei comandi:

   ```bash
   bash tools/backup-standalone.sh /percorso/privato/client.cnf glpi /backup/privato/mdo
   ```

   Lo script non salva plugin/volumi e non avvia upgrade. Verificare SHA512 e
   provare il ripristino DB/volumi su una seconda copia prima di proseguire.
3. Scaricare da **Dev** il pacchetto versionato
   `Modern_Dashboard_Overhaul_0.8.1-dev.5.zip` e il suo `.sha512`. Verificare con
   `sha512sum -c Modern_Dashboard_Overhaul_0.8.1-dev.5.zip.sha512`.
   Confrontare il record [build](https://github.com/dmontebello/Modern-Dashboard-Overhaul---a-GLPI-plugin/blob/Dev/docs/releases/0.8.1-dev.5.build.json) con lo SHA
   sorgente e la relativa GitHub Actions verde. Lo SHA del publisher è distinto.
   `MDO_Dev_Latest.zip` è un alias mobile: conservare sempre la copia versionata.
4. Estrarre in una directory di verifica: unica radice `mdoverhaul/`, nessun ZIP
   annidato. Da quella directory eseguire `sha512sum -c SHA512SUMS` e controllare
   `build-info.json`. Non usare un overlay che lasci vecchi file nel plugin.

## Upgrade e attivazione

1. Seguire il normale flusso amministrativo GLPI di sostituzione/aggiornamento
   del plugin nella copia staging, con sessioni e scritture sospese durante il
   cambio. Non modificare il core. Conservare fuori dal webroot il plugin vecchio.
2. Eseguire l'upgrade dal gestore plugin GLPI: lo hook install/upgrade crea o
   completa **schema 1**, tabella `glpi_plugin_mdoverhaul_tasks` e indici di
   entità/owner/assegnatario/gruppo/stato/scadenza/cestino. Nessuna tabella core
   viene alterata; GLPI conserva configurazione e diritti nei suoi registri nativi.
3. Verificare tabella, indici, config `task_schema_version=1` e log GLPI/PHP.
   Ripetere il flusso amministrativo su una copia: nessuna duplicazione o perdita
   di righe/diritti. Una GET non deve creare o modificare lo schema.
4. Nei profili, scheda **MDO · Attività indipendenti**, assegnare esplicitamente
   READMY oppure READALL e gli eventuali CREATE/UPDATE/DELETE. I nuovi diritti
   iniziano a zero per tutti i profili, anche amministratori. Non esiste purge.
   CREATE/UPDATE/DELETE non concedono automaticamente lettura o altre entità.
5. In Settings abilitare `task_workspace`, poi `tasks_standalone` (opt-in).
   L'ottavo flag non cambia gli altri sette. Rifare logout/login per menu/diritti.
   Se lo schema non è pronto, l'opt-in deve essere rifiutato.
6. Settings e gestore plugin devono mostrare `0.8.1-dev.5`; Settings deve mostrare
   canale Dev, source SHA, run CI e UTC uguali al record build. Negli strumenti
   rete del browser controllare URL/versione e contenuto effettivo degli asset,
   senza dedurre il cache refresh dal solo nome dello ZIP.

## Checklist live da registrare PASS/FAIL

Per ogni prova conservare profilo, entità, ID non sensibili, esito e screenshot
oscurati. Usare viewport 390/768/1024/1440, temi light/dark e zoom 100/200%.

- **ProjectTask native:** Nuova task → Task di progetto → PROGETTO-TEST tramite
  Select2 → Continua. Ispezionare `project=ID`, redirect al vero
  `/front/projecttask.form.php?projects_id=ID`, quindi Salva nel form GLPI. Solo
  il salvataggio deve inserire la task; verificarla nel progetto e nelle viste
  MDO. Provare selezione vuota/0, ID manipolato, progetto cancellato/template,
  altra entità e progetto non aggiornabile: messaggio sicuro, nessun inserimento.
- **ACL ProjectTask:** confrontare form, Search e board usando READMY/READALL,
  creatore non membro del team, membro team utente/gruppo, non membro, entità
  singola e profilo ricorsivo. L'autorità resta il core ProjectTask/Project;
  l'esito dei doubles non chiude questa verifica.
- **Standalone:** creare senza progetto, aprire da elenco/Kanban, modificare,
  completare, filtrare, cestinare e ripristinare. Verificare che `glpi_projects`
  e `glpi_projecttasks` non ricevano righe. Verificare status, scadenza e timezone.
- **ACL standalone:** owner/assegnatario/gruppo con READMY, altro utente negato,
  READALL limitato alle entità attive, gruppo ricorsivo nella sottoentità
  autorizzata, attori fuori entità negati. Provare solo-ProjectTask,
  solo-Standalone, sola lettura, senza diritti e tentativi POST/cross-entity/ID
  manipolati; nessun leak tramite ricerca, contatori, tooltip, export o API.
  Provare API legacy list/item/CRUD con sessioni equivalenti e CSRF UI
  mancante/scaduto/riutilizzato; purge deve restare impossibile.
- **Search nativa:** Select2 User/Group/Project, filtri e ricerche salvate,
  paginazione, AJAX ed export su entrambe le sottoviste. La board ha un limite
  dichiarato di 1000 candidati, non sostituisce l'elenco nativo completo.
- **Settings:** tutte le otto descrizioni leggibili; testo normale >=4.5:1 e
  controlli >=3:1, focus e salvataggio con tastiera, messaggi e metadati inclusi.
- **Mini Ticket:** card/KPI dentro bordo del pannello, dataset vuoto/pieno,
  resize che cresce e si riduce, nessuna sovrapposizione con toolbar/ricerca.
- **Dashboard:** toolbar separata dai tab in edit/fullscreen/RTL; grafico reale
  `ticket_status` con legenda lunga separata da plot e assi. Controllare refresh,
  tooltip/click/serie/palette, assenza di loop o errori console.
- **Regressioni:** RSS e ID legacy salvati, dashboard personali e condivise,
  flag combinati OFF/ON e DnD Vue nativo su progetti. Nessun DnD con scrittura
  viene aggiunto dalla board MDO.
- **Conservazione:** spegnere figlio e padre, disattivare/riattivare plugin e
  reinstallare sulla copia: dati mantenuti, URL non autorizzati negati e diritti
  già assegnati preservati. Provare uninstall solo sulla copia: nessun DROP.

## Rollback e decisione

Rollback funzionale: spegnere `tasks_standalone`; eventualmente spegnere
`task_workspace`. Nessuna cancellazione: tabella, audit e configurazione restano.
Prima di tornare alla versione precedente esportare le task create dopo il
backup; il vecchio codice non le gestisce. Ripristinare plugin, DB, configurazione
e volumi dallo stesso snapshot verificato, con scritture sospese; un restore DB
perde le modifiche successive allo snapshot. Non eseguire DROP automatici.

Annotare i difetti osservati e l'esito reale in QA/registro Dev. I bug P0 e la
migrazione rimangono **NON COLLAUDATO LIVE** fino a questa verifica. Promozione
`Dev` → `Main` solo dopo collaudo e approvazione dell'utente, senza automatismi.

## Prova prioritaria per 0.8.1-dev.3

Il fix elimina il catch(Throwable) intorno al redirect nativo GLPI. Prima di testare controllare in Settings versione 0.8.1-dev.3 e source SHA; usare ZIP versionato e backup coordinato. Selezionare PROGETTO-TEST nel dropdown GLPI, premere Continua e verificare in barra URL `/front/projecttask.form.php?projects_id=ID`. Solo il successivo Salva nel form crea la task. Controllare progetto, elenco, Kanban e permessi. Con ID non valido/nessuna scelta deve apparire errore senza redirect. Annotare PASS/FAIL reali, non dedotti dai mock.

## Sequenza di verifica dopo conferma installazione 0.8.1-dev.3 (25 ottobre 2026)

L'utente riferisce che l'aggiornamento ora funziona, dopo aver distinto `unzip -t` (test archivio) dalla vera estrazione. Rimangono da verificare i singoli flussi live senza dichiarare automaticamente tutto PASS:

1. Assistenza > Tasks > Nuova task > **Task di progetto** > PROGETTO-TEST > Continua > modulo nativo > Salva > ricerca e Kanban.
2. Settings > `tasks_standalone` opt-in e profilo con lettura + CREATE/UPDATE/DELETE, schema 1; Nuova task > **Indipendente** > crea/edita/completa/cancella/ripristina, senza Project. Non fare concessioni di diritti globali in produzione.
3. Utente con READMY e utente altra entità, verificare le ACL; ricontrollare versione/commit in Settings.
4. Ripetere il confronto screenshot sui bug mini Ticket, dashboard Assistenza, legenda, settings dark e spaziature Tasks.

Registrare ogni esito in [STATUS](STATUS.md). Next sprint: [MDO_NEXT_SPRINT](../specs/MDO_NEXT_SPRINT.md).

## Focus 0.8.1-dev.4 — filtro Status/Search nativa

Su GLPI 11.0.9 staging, con Tasks Standalone attive e profilo READMY/READALL, crea tre task: Da fare, In corso, Completata. Apri Assistenza → Tasks → Indipendenti → Elenco → Ricerca avanzata → Stato; verifica il dropdown senza output fuori dal suo contenitore, le tre scelte, l'ordinamento, AJAX/ricerche salvate/export, e ripeti con profili in entità differenti. Se il filtro resta vuoto, annota il DOM e i log.

Conserva il backup completo prima di caricare la ZIP `dev.4`; non ci sono nuove migrazioni dopo schema 1. Riesegui le prove ProjectTask e le regressioni grafiche già note. Riporta PASS/FAIL LIVE nel STATUS; screenshot e dati privati non vanno in un repo pubblico.

## Aggiornamento 0.8.1-dev.5

Dopo backup coordinato e installazione dello ZIP dev.5, controllare il Kanban Progetto con zero e piu task, e verificare che non appaiano nuovi errori SQL 1054. Per usare Standalone: abilitare i due flag MDO, verificare schema e assegnare READMY oppure READALL e CREATE nella scheda MDO del profilo GLPI; salvare e fare logout/login. Provare quindi la route standalone, elenco, board e creazione senza Project. Non usare l'errore di accesso come motivo per allargare automaticamente i permessi. Verificare che il form ProjectTask resti nativo e che il salvataggio funzioni. Screenshot e prove private non vanno pubblicati nel repo.

## Provenienza release 0.8.1-dev.5 verificata

Sorgente testato `60fb656aad0400a31788bf68760edf390961f166`; GitHub Actions #40189878021 PASS; checksum SHA512 ZIP `f12a4c5b7a0c13daadc6ccff60a85b83a2e3a2dfa8571d26a6d9265d9a875b206eff10577fc3674e9e57abe24176bbe7ae1e0b4624d623ec466e7a3b97475ef5`. Alias Latest corrispondente alla ZIP versionata. Il commit publisher `bda3d9352d3c8fef8c7131df8a9ce9280db3658b` è diverso dal sorgente testato. La CI non sostituisce l'upgrade con backup e il collaudo GLPI sul server dell'utente.


## 0.8.1-dev.6 — test staging ancora aperti

Verificare schema 2, ACL, POST Kanban e upload/download storage GLPI. Per rollback servono insieme snapshot DB, plugin e volume documenti; nessun PASS LIVE registrato.


## Aggiornamento 0.8.1-dev.7

Verificare su staging dev.7 nuova task unica con/senza progetto, CSRF, permessi, dropdown GLPI AJAX, scadenza, inserimento e redirect senza duplicati.

## Test aggiuntivi 0.8.1-dev.8 — Kanban e lista (26/10/2026)

Registrare screenshot/video e log senza caricarli nel repository pubblico: 1) ripetere tre spostamenti consecutivi della stessa card Project e Independent senza aggiornare; 2) verificare ghost che segue mouse, colonna drop evidenziata, stato in database, percent_done/status corretto, token rinnovato e fallback tastiera; 3) tabella Tutte, pulsante cestino esclusivamente con DELETE, confirm esplicita, DELETE su item e entità negati, nessun purge e refresh dei contatori; 4) fallimenti 403/409 con retry e test JS disattivato; 5) backup coordinato prima dell'upgrade. Nessun PASS LIVE attribuito finché non verificato in staging.


## Dev.9 — collaudo Kanban 26 ottobre 2026

0.8.1-dev.9 — verify native ProjectState columns including zero counts, repeated native status drag, mixed All stages for both sources and immediate 2→1/1→2 header counts. Check server-side ACL, browser POST and real MariaDB with coordinated DB/plugin/storage snapshot; do not mark PASS LIVE from fixture tests.
