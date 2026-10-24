# Revisione staging GLPI 11.0.9 — 0.8.1-dev.2

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
   `Modern_Dashboard_Overhaul_0.8.1-dev.2.zip` e il suo `.sha512`. Verificare con
   `sha512sum -c Modern_Dashboard_Overhaul_0.8.1-dev.2.zip.sha512`.
   Confrontare il record [build](https://github.com/dmontebello/Modern-Dashboard-Overhaul---a-GLPI-plugin/blob/Dev/docs/releases/0.8.1-dev.2.build.json) con lo SHA
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
6. Settings e gestore plugin devono mostrare `0.8.1-dev.2`; Settings deve mostrare
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
