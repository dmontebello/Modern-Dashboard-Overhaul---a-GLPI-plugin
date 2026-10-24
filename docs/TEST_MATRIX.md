# Matrice di test verso Modern Dashboard Overhaul 1.0 (baseline 0.6.0)

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
