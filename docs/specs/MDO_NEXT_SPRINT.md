# Next sprint MDO — stabilizzazione Dev 0.8.1 e iTasks native-first

**Preparato:** 25 ottobre 2026 · **stato:** piano tecnico, non nuova release.  
**Baseline:** Dev `0.8.1-dev.3` (`4eec680` sorgente, publisher `3b053b0`) · **Main invariato** `0.8.0`.

Il 25 ottobre l'utente ha risposto «Funziona» dopo aver ricevuto istruzioni per installare realmente la ZIP (il precedente comando `unzip -t` verificava soltanto l'archivio). **Questo conferma un riscontro positivo sull'aggiornamento**, ma non documenta da solo test approfonditi del CRUD, diritti o interfaccia responsive; non marcare automaticamente PASS LIVE i singoli casi.

## Priorità e ordine

### S1 — Sicurezza e flusso task indipendenti (priorità alta)

Usare soltanto il plugin `mdoverhaul` e le API native GLPI. La persistenza standalone è **già implementata** da 0.8.1-dev.2: evitare di riscriverla.

1. Verificare che la migrazione GLPI sia attiva: `glpi_plugin_mdoverhaul_tasks`, configurazione `task_schema_version=1`; non eseguire SQL correttivi da una pagina GET.
2. Settings: `task_workspace` e `tasks_standalone` attivi solo per la prova; profilo GLPI con i diritti necessari `READMY`/`READALL`, `CREATE`, `UPDATE`, `DELETE`. Pericoloso attribuire soltanto CREATE senza la possibilità di vedere la task salvata.
3. Creare una task realmente indipendente senza un Project, modificare stato/scadenza, cercarla, aprirla nel Kanban e nell'elenco, cancellarla logicamente e ripristinarla. Controllare che `glpi_projects` e `glpi_projecttasks` non ricevano nuovi record per questa operazione.
4. Eseguire almeno due profili non Super-Admin e due entità differenti (anche ricorsive). Matrice: owner/assignee/gruppo, READMY/READALL/CREATE/UPDATE/DELETE, entità non autorizzata, URL `id` manomesso, API native, righe aggregate/board e Search. Verificare per ogni operazione read/write i controlli server-side e i log GLPI. Evitare leak di nomi nei conteggi.
5. Separare i risultati: **PASS LIVE**, **FAIL LIVE**, **NON VERIFICATO**. I mock Playwright e i PHP doubles confermano la struttura dei test, non l'integrazione su GLPI 11.0.9.

### S2 — Regressione progetto nativo e Tasks UI

1. Confermare che il nuovo progetto è selezionabile e `Continua` apra `/front/projecttask.form.php?projects_id=N`. Poi **Salva** deve creare la ProjectTask, che compare in Progetti e Tasks (Elenco, Kanban). Distinguere la segnalazione "aggiornamento funziona" dal test completo di questa sequenza.
2. Conservare `ProjectTask` come oggetto GLPI nativo: no doppia tabella, no wrapper custom di creazione, no override del core. Verificare diritti `READMY/READALL`, assegnatario/team e visibilità progetto.
3. Ricontrollare, sui viewport veri, lo spazio tra tab Elenco/Kanban e Search, la riga dei pulsanti di scelta progetto, i filtri AJAX, ricerca salvata/paginazione/export, 390/768/1024/1440 px e light/dark.

### S3 — UI dashboard ancora da collaudare

1. Assistenza → Chiamate: riga mini KPI completamente dentro il parent; verificare `.dashboard-card`, `.card-body`, `.dashboard.mini`, `.grid-stack` e ogni `.grid-stack-item` con `getBoundingClientRect` prima di aggiungere altri `!important`.
2. Dashboard Assistenza: toolbar destra non appoggiata al bordo, senza sovrapporsi a tab/filtri/edit/fullscreen.
3. Widget `Stato chiamate per mese`: legenda e area plotting separate; non alterare valori, legende del chart engine, colori, tooltips o serie native ECharts. Prove su widget ridotto, resize, dark/light.
4. Settings: contrasto dettagli opzioni leggibile in entrambi i temi, test WCAG AA e versione/build identificabili.
5. Se i difetti non si riproducono dopo il caricamento di `dev.3`, annotare una prova reale, non cercare di "correggere" CSS già valido.

## Nuove funzioni: P2 da progettare DOPO stabilizzazione

- Vista "Le mie attività" e filtri rapidi nativi per responsabile/assegnatario e scadenza.
- Mappatura concettuale Notion **Stage** (Todo, Doing, Done) distinta da **Status** (Attivo, Bloccato, In attesa, In Review); prima decidere come memorizzarli senza sovrapporre `ProjectTask.projectstates_id` o percent_done. Nessuna alterazione DB automaticamente autorizzata.
- Integrazione di task con il workflow Projects, valutando menu, scheda progetto, avanzamento e scadenza già nativi prima di costruire componenti MDO.
- Worklog/sessioni di lavoro: analizzare le funzionalità native e i trade-off prima di introdurre persistenza ulteriore.
- Eventuale vista unificata ProjectTask + StandaloneTask: **non fare una UNION SQL che salta gli ACL nativi**; preferire sottoviste finché non si dimostra la sicurezza di un'aggregazione.

## Procedura per Codex e disciplina Dev/Main

1. `git fetch origin`, confermare HEAD `origin/Dev`, leggere `AGENTS.md`, [Stato Dev](../dev/STATUS.md) e [staging](../dev/STAGING_0_8_1.md). Non fare commit P2 su Main.
2. Iniziare da una **gap analysis** fra codice 0.8.1-dev.3, feedback dell'utente e problemi aperti; scegliere solo difetti riproducibili o evidenti dal codice. Non fare refactoring massivo.
3. Ogni fix funzionale successivo diventa candidato **0.8.1-dev.4**, poi `dev.5`, ecc.; l'installazione della `dev.3` non genera da sola un nuovo numero. Non chiamare `0.8.1` stabile prima del collaudo.
4. Per ogni versione aggiornare **nello stesso commit** `CHANGELOG.md`, `docs/releases/<version>.md`, `docs/dev/STATUS.md`, `QA.md`, matrix test, README e documentazione config/install/security se pertinente. Indicare file, fix, test effettivi, prove NON eseguite, rischi, backup/rollback e next steps.
5. Testare PHP, JS, Playwright, lint, ZIP unica `mdoverhaul/`, SHA512SUMS e checksum .zip.sha512; verificare Actions verde prima di pubblicare alias `MDO_Dev_Latest.zip`. Non aggiornare Main o la macchina dell'utente.
6. Dopo ogni fix ottenere un collaudo GLPI reale; i test in fixture non chiudono le regressioni. Nessuna PR Dev→Main senza approvazione.
