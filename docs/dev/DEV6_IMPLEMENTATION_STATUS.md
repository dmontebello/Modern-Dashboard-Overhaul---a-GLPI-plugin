# 0.8.1-dev.6 — stato implementazione sul branch di lavoro

**Stato: WORK IN PROGRESS; NON COLLAUDATO LIVE; NESSUNA ZIP dev.6 pubblicabile.** Ultima release verificata rimane 0.8.1-dev.5. Il branch `feature/mdo-0.8.1-dev.6` è separato da `Dev` e `Main`.

## Implementato nel branch, NON ANCORA VERIFICATO

- `src/TaskStageService.php`: autorizzazioni READ/UPDATE, interpretazione Stage dalla percentuale nativa di ProjectTask o stato standalone; aggiornamenti via modelli, con controllo `date_mod` e rifiuto per avanzamento ProjectTask automatico.
- `front/task-stage.php`: route POST JSON same-origin; affida al middleware GLPI 11 la verifica CSRF, gestisce errori senza divulgare dettagli.
- `js/task-workspace.js`: spostamento Kanban via fetch, drag & drop e select da tastiera; aggiornamento del DOM solo a risposta positiva, fallback del form select.
- `front/tasks.php`: vista `source=all`, lista mista con sottolista paginata (campione, non ricerca completa), origine attiva, board To do / Doing / Done sempre presenti per le task standalone/unificate, card tipizzate e controllo di spostamento.
- `public/css/task-workspace.css`: colori per selettore attivo, layout del form standalone in colonna, azioni in fondo a destra.
- `setup.php`: JS caricato soltanto quando task_workspace è attivo.
- `tests/task_bootstrap.php`: doppio ProjectTask dotato dei metodi necessari allo Stage.

## Problemi e limiti da correggere prima di una candidata

1. **BLOCCANTE** — nessuna suite PHP/Playwright, sintassi CLI, CI o integrazione GLPI 11.0.9 eseguita su questi commit. Le nuove chiamate e i layout possono avere regressioni; non fare merge in Dev e non distribuire ZIP.
2. **BLOCCANTE** — completare diario cronologico paginato e allegati delle task indipendenti. Verificare i diritti per ogni elemento: la semplice apertura di Notepad e Document_Item del core non dimostra ACL parent READMY/READALL.
3. **BLOCCANTE** — verificare robustezza endpoint POST (middleware CSRF, ACL, cache/locking, conflitti concorrenti, errori core, progresso automatico e DateTime).
4. **APERTO** — lista mista usa fino a 1000 candidate autorizzate per fonte; non equivale all'intera Search GLPI. Migliorare paginazione server-side e filtri, conservando le due sottoviste native.
5. **APERTO** — navigazione fra origine/elenco/board comporta ancora GET full-page. Introdurre progressive navigation con fallback, senza rompere GLPI Search, Select2 e le modali.
6. **APERTO** — test UI e PHP esistenti erano scritti per default `source=project`, elenco Search nativo e vecchi raggruppamenti; aggiornare assert e fixture dopo verifiche.
7. **APERTO** — verifica reale dei colori, pulsanti e pagine form anche in dark mode e mobile.
8. **APERTO** — completare docs/QA/version bump, validare codice e pubblicare soltanto dopo CI verde. Nessun numero ZIP dev.6 deve essere considerato già pubblicato.

## Fix completati durante questa lavorazione

- Corretto il mapping delle StandaloneTask: lo Stage si ricava da `status=1/2/3`, NON da `percent_done` simulato (che vale zero per Doing).
- Aggiunta base per `source=all` senza mescolare gli ID omonimi; ogni card trasporta il tipo e usa il link del modello corretto.
- Board senza task non elimina più il rendering delle colonne nel percorso Kanban.

## Prossimi passi obbligatori

1. Eseguire gate sintassi PHP/JS, test unitari e browser in ambiente locale o CI; correggere tutte le regressioni.
2. Completare note/allegati con parent ACL, test di upload/download e pagina completa dello storico.
3. Concludere UI asincrona e aggregazione paginata; testare 0/1/100/1000+ task.
4. Aggiornare numero a `0.8.1-dev.6`, tutte le note release, QA, README, security/schema/install/staging nello stesso commit codice, validare `check_dev_docs.py`, manifest e ZIP.
5. Collaudo sul server GLPI dell'utente e verifica backup/rollback prima di considerare dev.6 disponibile.
