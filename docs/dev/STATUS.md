# MDO Dev — stato operativo e handoff

Data valutazione: 2026-10-24. Stato: REGRESSIONI GLPI LIVE APERTE. Questo documento descrive cosa è osservato, non ciò che è stato risolto.
Documenti da leggere: [specifica 0.8.1](../specs/MDO_0_8_1_DEV_WORKPLAN.md), [versioning](VERSIONING_POLICY.md).

| Elemento | Stato verificato al 24/10 |
| --- | --- |
| Dev prima dell'handoff | a866f3aa64174fc99e2462f373a145d39a2efca1 |
| Main invariato | 0dd4debe1b68088190132c0dfcca9c3bae068099 |
| Plugin | mdoverhaul 0.8.0, sette switch in Settings |
| CI Dev | Run #40074660688 PASS sul commit codice 7d22644c8d5c2b389e34358ce5564c83b65603d0 |
| Controlli CI | 67 PHP, 61 Tasks PHP, 84 UI dashboard, 167 UI Tasks — SOLO doubles/fixture |
| ZIP disponibile | Modern_Dashboard_Overhaul_0.8.0_Pilot.zip dal branch Dev; non ancora versione semver Dev |
| Staging utente | GLPI 11.0.9; screenshot effettivi del 24/10; accesso automatico browser non disponibile |
| Prossima release candidata | 0.8.1-dev.1 P0, poi almeno 0.8.1-dev.2 P1; NON IMPLEMENTATE |

## Problemi aperti, da non chiamare risolti

| ID | Priorità | Sintomo | Esito |
| --- | --- | --- | --- |
| SETTINGS-01 | P0 | Descrizioni degli switch illeggibili in dark mode | Aperto |
| TASK-01 | P0 | Nuova task > selezione progetto > Continua torna alla stessa pagina | Aperto; verificare GET e ACL reali |
| TASK-02 | P0 | Dropdown progetto e pulsanti senza spaziatura e proporzioni corrette | Aperto |
| TASK-03 | P0 | Vuoto verticale tra vista Elenco/Kanban e ricerca; patch precedente da verificare sul live | Da ricollaudare |
| TICKET-01 | P0 | Riquadro chiaro mini-dashboard Assistenza/Chiamate oltre il bordo padre | Aperto/da ricollaudare |
| DASH-01 | P0 | Toolbar destra stretta contro bordo alto | Aperto/da ricollaudare |
| CHART-01 | P0 | Legenda "Stato chiamate per mese" interferisce col grafico | Aperto/da ricollaudare |
| BUILD-01 | P0 | Versione e nome ZIP ancora 0.8.0; JS VERSION 0.7.0 | Aperto |
| STANDALONE-01 | P1 | Attività senza progetto non consentite da ProjectTask | Nuovo requisito; NON implementato |

Nota: il cliente segnala persistenza dei bug precedenti, ma non tutte le ultime schermate riprendono il dashboard; la causa precisa (CSS, JavaScript, caching, misure GridStack) richiede DevTools sul GLPI reale. Non descrivere ipotesi come diagnosi conclusa.

## Evidenze e cause da indagare

- public/css/settings.css: .mdo-setting-detail usa il token scuro --tblr-secondary-color e il testo ha contrasto insufficiente nell'immagine.
- front/tasks.php: Dropdown::show(Project::class) seleziona il progetto tramite GLPI; TaskBoard::canCreateInProject può rifiutare il redirect. Test doubles non dimostrano submit AJAX reale.
- GLPI 11.0.9 ProjectTask deriva da CommonDBChild: obbligo del Project parent; standalone richiede un tipo diverso. Esclusi i progetti fittizi.
- setup.php / front/config.php: versione 0.8.0; JS dashboard-overhaul.js: VERSION ancora 0.7.0; CI ZIP statico 0.8.0.
- public/css/ticket-mini-dashboard.css e pubblico JS/dashboard-overhaul.js hanno già fix mock-testati ma problemi segnalati persistono sul live.
- Diritti/ACL/e gruppi e ricerca nativa GLPI reali restano da verificare prima di merge.

## Piano verificabile

1. P0: tracciabilità build/versione/cache, contrasto Settings, creazione ProjectTask via form GLPI, layout Tasks, mini ticket, toolbar e grafico ECharts. CI verde + test live.
2. P1: nuova classe/tavola StandaloneTask dentro mdoverhaul con ACL, migrazione, CRUD e interfaccia unificata o sottoviste. Ripetere CI/test live.
3. PR Dev -> Main solo dopo revisione, test staging e approvazione utente; Main resta immutato.

## Registro versioni e lavoro (append-only)

| Data | Versione/build | Fonte | Test | Stato live | Note e prossimi passi |
| --- | --- | --- | --- | --- | --- |
| 2026-10-24 | 0.8.0 Dev | CI codice 7d22644, ZIP publisher a866f3a | CI #40074660688 PASS su mock | Regressioni osservate | Specifica 0.8.1; da attuare |
| 2026-10-24 | Handoff SOLO documentale | Commit docs da rilevare dopo pubblicazione | CI da verificare | Nessun bug chiuso | Codex implementi P0, poi P1 |

## Istruzioni per aggiornare questo file

A ogni candidato: versione e SHA codice esatti, build_id, ZIP/checksum, file modificati e fix, nuove/risolte regressioni con prove, test PASS/FAIL/NON ESEGUITI, migrazioni, rischio e rollback, prossimi passi. Aggiornare CHANGELOG, README, QA e docs/releases/<version>.md nello stesso commit; non cancellare le righe storiche. Non registrare dati privati.


## Candidata corrente: 0.8.1-dev.1 — P0, 2026-10-24

Base effettivamente aggiornata prima del lavoro: 45b4e968451813c579bb14b343f6cc3ad69fca8e.
IMPLEMENTATO: versione unica, metadata build e gate pubblicazione; CSS Settings,
controllo creazione ProjectTask conforme al core, messaggi e layout, misura mini
Ticket, offset toolbar e layout della sola legenda ticket_status.

Tutti gli ID SETTINGS-01, TASK-01/02/03, TICKET-01, DASH-01, CHART-01 restano
**APERTI — NON COLLAUDATO LIVE**: correzioni candidate e test isolati non chiudono
il feedback utente. BUILD-01 implementato e da verificare anche sugli asset HTTP
nello staging. STANDALONE-01 non ancora incluso: prossima candidata dev.2.

Il commit sorgente esatto, build ID, data UTC e SHA512 sono registrati da CI in
build-info.json e docs/releases/0.8.1-dev.1.build.json dopo test verdi. Non confondere
lo SHA del publisher con quello del codice. Vedere le note complete della versione.
Rollback P0: file del plugin precedenti, nessuna migrazione. Main invariato.
Staging non raggiungibile da Codex; l'utente eseguirà il collaudo dopo pubblicazione.

| 2026-10-24 | 0.8.1-dev.1 | Source SHA generato da CI | Test isolati, esiti in QA/CI | NON COLLAUDATO LIVE | P0 candidato; segue P1 |
