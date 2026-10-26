# Matrice di test verso Modern Dashboard Overhaul 1.0 (baseline 0.6.0)

> **Corrente 0.8.1-dev.5:** vedere la sezione aggiornata in fondo; le descrizioni precedenti restano riferimento storico delle versioni indicate.

> Revisione per **0.7.0**: vedere [note di release](RELEASE_NOTES_IT.md) e [QA](../QA.md) per le variazioni di prestazioni e sicurezza.

**Distinguere sempre**: test statico del pacchetto ≠ test GUI simulato ≠ test su GLPI reale. Gli esiti già documentati sono in [QA.md](../QA.md). Questa matrice è ripetibile a ogni versione.

| Area | Prova concreta | Criterio di accettazione |
| --- | --- | --- |
| Plugin | Installazione nuova da ZIP su GLPI 11 | Installazione/abilitazione senza eccezioni |
| Plugin | Upgrade 0.5 → 0.6; nuovo caricamento | Versione corretta, nessuna perdita dashboard/RSS |
| Plugin | Disabilitazione e ripristino backup | GLPI utilizzabile senza plugin, rollback coerente |
| Configura | Super-Admin apre e salva 5 flag | Valori persistono fra sessioni/ricariche |
| Configura | Profilo senza `config:UPDATE` prova GET/POST | Accesso e scrittura negati |
| Configura | POST manomesso/mancante, CSRF errato | Nessun salvataggio parziale, risposta protetta |
| Moduli | Restyling ON, RSS ON | Entrambi operativi |
| Moduli | Restyling OFF, RSS ON | Dashboard nativa, widget RSS utilizzabili |
| Moduli | Restyling ON, RSS OFF | Stile attivo; schede RSS temporaneamente assenti, non modificare/salvare dashboard |
| Moduli | Restyling OFF, RSS OFF | Pagina Configura ancora disponibile |
| KPI nativi | Card alta/bassa/larga/stretta | Numero leggibile, icona e nome proporzionati, niente scrollbar sui valori |
| KPI nativi | Etichetta lunga, numero molto lungo, zoom 125/150% | Nessun valore azzerato, messaggi utilizzabili; documentare eventuale clipping |
| Tema | Toggle dashboard dark→light→dark | Palette e testi coerenti, anche con tema globale opposto |
| Grafici | Pie/donut/bar con/senza dati | Nessuna perdita grafico; testi ad alto contrasto, senza alterare dati |
| Dashboard | Selettore con 1/3/38+ voci | Tab corretti, scorrimento orizzontale, `+` visibile |
| Dashboard | Crea/duplica/rinomina/elimina | Stesse azioni native e autorizzazioni |
| Dashboard | Modifica drag/resize/filter | Layout persistito correttamente e filtri non scompaiono |
| RSS | Feed pubblico/privato, 2 utenti | Vede soltanto feed autorizzati |
| RSS | Preset 5/10/15/20 e feed con pochi articoli | Conteggio corretto, no duplicati artificiali |
| RSS | Offline/feed vuoto/errore server | Messaggio leggibile senza rompere dashboard |
| RSS | Titolo HTML pericoloso, link `javascript:`, date Atom/RSS | Output escape, link pericoloso non apribile, data/ora corretta o N/D |
| QA tecnica | SHA-512 valido / singolo CSS manomesso | OK verde / ERRORE rosso + uscita non zero |
| QA tecnica | PHP/Bash/JS sintassi | Controlli superati o warning esplicito per Node non presente |
| QA tecnica | Monitorare log e carico con molti feed | Nessun errore PHP/JS né regressione rilevante |

## Condizioni per considerare pronta la 1.0.0

- Test essenziali passati almeno su installazione reale **e** installazione GLPI pulita, non solo su mock.
- Nessun difetto bloccante noto, backup/rollback verificato e documentato.
- Plugin identificato come `mdoverhaul`, codice e documentazione allineati; nessun dato sensibile nei pacchetti.
- Moduli utilizzabili indipendentemente senza danni ai dati GLPI.
- Changelog, release notes, QA e checksum aggiornati nello stesso ZIP.

## Registro esiti per versione

Scrivere in [QA.md](../QA.md) data/ambiente/risultato per ogni test effettuato. **Non dichiarare PASS se manca uno screenshot, un output o un test realmente eseguito.** I test non eseguiti sono `NON TESTATO`, non `OK`.

## 0.8.0 — Matrice aggiuntiva

| Area | Test | Atteso |
| --- | --- | --- |
| ZIP unico | Radice e SHA512 | `mdoverhaul/` completa, versione 0.8.0, nessuna patch |
| Impostazioni | Tasks Workspace ON/OFF | Mostra/nasconde Assistance → Tasks |
| Impostazioni | Kanban Restyling ON/OFF | Cambia CSS Kanban indipendentemente |
| Sicurezza | Utenti/progetti con ACL differenti | Solo `ProjectTask` permesse per utente |
| Dashboard Ticket | Sette contatori e finestra stretta | Nessun overflow oltre il riquadro |
| RSS | Dashboard esistenti con feed salvati | Stessi ID, nessuna perdita di schede |
| Release | Update/rollback su GLPI 11.x | Nessuna disinstallazione o modifica core |

## Tasks P0/P1 — sviluppo non pubblicato

| Area | Test automatizzato offline | Da verificare su staging GLPI 11.0.9 |
| --- | --- | --- |
| Schema e form | `tests/tasks_php.php`: ProjectState, progetto obbligatorio, URL root | Stati veri, nuova task e modifica nel form nativo |
| ACL | Due attori/entità, team, hook negazione, progetto nascosto, accesso diretto | Profili reali, gruppi, entità ricorsive e nuove sessioni |
| Moduli | `tests/unit_php.php`: matrice 2×2 con/senza permessi, RSS/Dashboard | Persistenza impostazioni e invalidazione menu dopo logout/login |
| Ricerca | Target fisso, criteri e paginazione delegati al core | AJAX, ordinamento, colonne, export e ricerche salvate reali |
| UX | `tests/tasks_ui_playwright.py`: 1440/1024/768/390, 0/100 task, light/dark | Temi GLPI reali, browser mobile e zoom |
| Calendario | Oggi/settimana, date mancanti, fine effettiva, cambio ora | Timezone DB/PHP/sessione dell'installazione |
| Kanban Vue | Geometria e scope CSS su DOM rappresentativo | Trascinamento, scrittura autorizzata e persistenza del Kanban nativo |
| Pacchetto | Build unica, manifest, checksum e integrità ZIP | Installazione/rollback di prova dopo approvazione |

Risultati effettivi e limiti in [rapporto Tasks](specs/TASKS_IMPLEMENTATION.md).



## Gate nuovo MDO 0.8.1 Dev, NON ESEGUITO — 2026-10-24

Checklist da eseguire su GLPI 11.0.9 staging: contrasto Settings (>=4.5:1) in light/dark; selezione AJAX progetto con ID, "Continua" porta al form ProjectTask?projects_id=ID, Salva e lettura della task in progetto/lista/Kanban; scelta task autonoma senza alcun glpi_projects; ACL owner/team/profili/entita ricorsive; form responsive 390/768/1024/1440 e zoom 200%; mini Ticket rientra nei bordi; toolbar superiore ha padding; legenda "Stato chiamate per mese" non copre assi/plot; nessuna regressione RSS, Export, Search, Vue DnD; metadata ver/version+SHA+build ID coerenti con ZIP, install/update/rollback/migrazione. CI mock non sostituisce questi test. Dettagli e responsabilita nella [specifica 0.8.1](specs/MDO_0_8_1_DEV_WORKPLAN.md).


## Aggiornamento 0.8.1-dev.1

Testare 390/768/1024/1440, light/dark, zoom 200%, contrasto testo >=4.5 e controlli >=3, selezione Project AJAX/GET/redirect/salvataggio, resize e riduzione GridStack, toolbar/legenda ticket_status. Mock e staging devono avere esiti separati.

Stato: NON COLLAUDATO LIVE. Note: [0.8.1-dev.1](releases/0.8.1-dev.1.md).

## Aggiornamento 0.8.1-dev.2

Aggiunte prove standalone: superadmin/READALL, READMY owner/assegnatario/gruppo,
nessun diritto, solo standalone senza ProjectTask, entità/parent gruppo ricorsivo,
input ostili, owner/entity immutabili, assignee cross-entity, cestino/restore,
purge vietato, plugin/padre/sotto-opzione OFF e dati conservati. Test CRUD HTTP
su controller reali con persistenza simulata; middleware CSRF double dichiarato.

Schema: installazione nuova/reinstall idempotente, indici, grant zero e mantenimento
di grant/dati con fixture DB. SQL MariaDB, upgrade/rollback e backup veri NON
COLLAUDATO LIVE. Vedere STAGING_0_8_1 per prove GLPI obbligatorie prima di Main.

Stato: NON COLLAUDATO LIVE. [Note complete](releases/0.8.1-dev.2.md).

## 0.8.1-dev.3 — test aggiuntivi per il redirect nativo GLPI 11

- Automatico: Html::redirect che lancia SimulatedGLPIRedirect in corrispondenza di `?new=1&kind=project&continue=1&project=1`, senza intercettazione da parte del controller. Verifica URL `/front/projecttask.form.php?projects_id=1` e log vuoto.
- Staging: **NON COLLAUDATO LIVE**, selezione AJAX reale con ID valido, Continua -> modulo nativo, Salva -> task visibile; controllare ID assente/manomesso e ACL multi-entità. ZIP dev.3/Latest da validare con SHA512.

## Aggiornamento 0.8.1-dev.4 (25 ottobre 2026)

0.8.1-dev.4: controlli automatizzati per markup Search Stato returned/not echoed e selected value; per publisher documentazione-only (skip positivo senza sovrascrittura) e modifica codice senza bump (fail). Test su GLPI vero NON COLLAUDATO LIVE: ricerca AJAX/status/saved search, READMY/READALL, profili/entità, creazione form standalone.

## Aggiornamento 0.8.1-dev.5

Regressione SQL: TaskBoard::load, TaskBoard::creationProjects e StandaloneTask::loadBoard ordinano per 'table.id DESC' nella lista ORDER prevista da GLPI 11. Il double rifiuta lo schema associativo che produceva ORDER BY DESC. Controllare nel live il Kanban vuoto e popolato. Regressione ACL: modulo Standalone attivo ma senza READMY/READALL mostra solo avviso ad admin, non tab e non dati; accesso diretto resta vietato. Test live post-fix ancora mancanti.


## QA 0.8.1-dev.6

Testare su GLPI reale ACL, Stage, storico note paginato, allegati privati, upgrade schema2, responsive e rollback. CI automatica non equivale al collaudo live. NON COLLAUDATO LIVE.

## 0.8.1-dev.7 — allineamento

Test dev.7: nuovo form unico senza scelta, progetto vuoto/0/valido/negato/fuori entità, create grant solo-ProjectTask/solo-Standalone, manipolazione ID array e overflows, CSRF, redirect e fallimenti senza doppia task, Select2 AJAX reale, due date in timezone GLPI. NON COLLAUDATO LIVE.

## Matrice dev.8 UX

- DnD stessa ProjectTask 3+ volte e StandaloneTask 3+ volte senza reload, GET finale coerente; CSRF valido a ogni POST, 403/409 gestiti, no bypass ACL.
- Drag ghost segue mouse, drop target chiaro/scuro, mouse/touch/tastiera, focus; percent_done e status native non equivalenti.
- Lista Tutte 0/3/1000 righe, badge e colonna Azioni: profili READ, UPDATE senza DELETE, DELETE; entità diverse, URL POST diretto, doppio invio, confirm=0/1.
- Eliminazione nel cestino GLPI, senza purge; verificare cascade nativa dei subtasks, storico, allegati, backup/restore. **NON COLLAUDATO LIVE**.
