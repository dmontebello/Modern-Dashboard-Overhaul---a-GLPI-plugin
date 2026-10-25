# MDO Dev — stato operativo e handoff

> **Corrente: 0.8.1-dev.5.** Search Status nativa e gate di pubblicazione Dev; **NON COLLAUDATO LIVE**.
> La cronologia seguente mantiene le valutazioni precedenti. Note correnti in
> [0.8.1-dev.5](../releases/0.8.1-dev.5.md), collaudo in [staging](STAGING_0_8_1.md).

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


## Registro 0.8.1-dev.1 — P0, 2026-10-24

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


## Registro 0.8.1-dev.2 — P1

Base P1: publisher 4f5ed1c della candidata P0. P0 codice esatto
7e3b93a62e965d03ccfdb7ecc91471e033eaec62; CI 40090042032 PASS, incluso publisher.
ZIP P0 SHA512: 8ce18180015b0382c7f8402eeb0527e81b0df077f1f1a05b8ef4a1298bc703208c738a8fe995508d4c0eff1e474b8bac51cb8a9e714812d2641f2794f3972865.

P1 IMPLEMENTATO: StandaloneTask, tabella dedicata versione schema 1, profili,
ACL e Search pubblica; form, CRUD/cestino/restore, sottoviste native e ottavo flag
opt-in. Nessun progetto fittizio o ProjectTask orfano. Migration solo da hook
amministrativo GLPI; nessun upgrade dal GET. Rights nuovi zero per tutti i profili.

STANDALONE-01: implementato/testato con doubles, **NON COLLAUDATO LIVE**, inclusi
DDL, upgrade e ripristino DB. SETTINGS-01/TASK-01/02/03/TICKET-01/DASH-01/CHART-01
rimangono aperti fino alle prove dell'utente. BUILD-01 verificato in CI; HTTP
native e OPcache dello staging da verificare. Source SHA, build ID e SHA512 P1
sono generati in build-info.json e docs/releases/0.8.1-dev.2.build.json dalla CI.

| 2026-10-24 | 0.8.1-dev.1 | codice 7e3b93a / CI 40090042032 / publisher 4f5ed1c | PASS automatico | NON COLLAUDATO LIVE | P0 pubblicato solo Dev |
| 2026-10-24 | 0.8.1-dev.2 | fonte esatta nel record CI generato | esiti in QA/CI | NON COLLAUDATO LIVE | P1 candidato, schema 1 opt-in |

Rollback funzionale: standalone OFF, nessuna cancellazione. Per rollback versione
usare snapshot coordinati DB/plugin/volumi, esportando prima le task nuove.
Main invariato 0dd4debe1b68088190132c0dfcca9c3bae068099. Nessuna PR/Main/deploy.

Verifiche locali P1 PASS: 26 PHP/7 Bash/2 JS sintassi; parser Python/YAML;
67 moduli, 63 Tasks, 59 standalone PHP; 8 packaging; 86 dashboard, 175 Tasks,
560 Settings e 108 standalone UI. Gate documenti e ZIP/manifest PASS.
Build locale dirty dichiarata; source/build/checksum definitivo dal publisher CI.

## Registro 0.8.1-dev.3 — 2026-10-25

TASK-01: causa precisa individuata nei sorgenti di GLPI 11.0.9: Html::redirect() lancia RedirectException, che il vecchio front/tasks.php intercettava con catch(Throwable). Il fix esegue la verifica permessi nel try e il redirect FUORI dal catch. Test double esteso per simulare il lancio dell'eccezione. CI non ancora confermata al momento del commit; **NON COLLAUDATO LIVE**. Dopo CI verde collaudare progetto valido -> Continua -> form nativo -> Salva -> elenco/Kanban. Gli altri bug UI e le ACL standalone restano aperti. Schema standalone 1 invariato, rollback con snapshot staging, Main invariato.

## Feedback utente 2026-10-25 — installazione dev.3 e prosecuzione

Dopo il download e la verifica ZIP della 0.8.1-dev.3, l'utente aveva eseguito soltanto `unzip -t` (che non estrae); ha poi seguito la procedura di sostituzione/aggiornamento del plugin e ha risposto **«Funziona, andiamo avanti con lo sviluppo»**. **Riscontro positivo sulla procedura / stato attuale**; non è specificato se tutti i flussi (Continua → form → Salva, StandaloneTask, ACL multi-entità e grafici) siano stati testati. Conservare `NON VERIFICATO LIVE` per i casi non esplicitamente confermati; non cambiare vecchi esiti senza evidenza.

| Data | Versione | Esito comunicato | Cosa NON è ancora certificato | Prossimo passo |
| --- | --- | --- | --- | --- |
| 2026-10-25 | 0.8.1-dev.3 | «Funziona» dopo corretta installazione/estrazione | ACL StandaloneTask e ProjectTask, migrazione database, tutte le anomalie dashboard, Search AJAX | [Sprint stabilizzazione e UX](../specs/MDO_NEXT_SPRINT.md) + [collaudo staging](STAGING_0_8_1.md) |

**Prossima candidata funzionale:** 0.8.1-dev.4 soltanto quando sono presenti modifiche di codice con test e documentazione coerenti. La versione attuale resta 0.8.1-dev.3; Main resta 0.8.0.

## Registro 0.8.1-dev.4 — 25 ottobre 2026

**Scope:** hardening della Search nativa di StandaloneTask e publisher immutabile Dev. Nessuna nuova tabella, modifica ACL, aggiornamento core GLPI o alterazione di Dashboard/RSS.

| ID | Fix candidato | Test previsti/effettivi | Live GLPI | Next steps |
| --- | --- | --- | --- | --- |
| STANDALONE-SEARCH-01 | `getSpecificValueToSelect('status')`: `Dropdown::showFromArray(display=false)` restituisce HTML invece di eseguirne echo immediato | Regression PHP + suite completa in CI; risultati da leggere nel run | **NON COLLAUDATO LIVE** | Search avanzata Status, salvataggio ricerche, AJAX/export, ACL READMY/READALL |
| DEV-PUBLISH-01 | Commit solo documentali e publisher-generated conservano versione ZIP immutable senza job FAIL. Il codice senza bump resta bloccato | Test Python con repo Git fittizia, pipeline; risultati da leggere nel run | Non applicabile | Verificare ZIP dev.4, checksum, run, SHA sorgente |
| TASKS-PROJECT | `Continua` della dev.3 invariato e nativo ProjectTask | Test preesistenti | L'utente ha detto «Funziona» dopo upgrade; non esiste una matrice completa | Confermare su GLPI form, salvataggio, utenti/team/entità |
| TASKS-STANDALONE | Stessa tabella/schema 1, flags e ACL della dev.2 | Tests doubles preesistenti | **NON COLLAUDATO LIVE** | CRUD e restore, due entità e profili |
| UI-DASHBOARD | Nessun nuovo CSS/JS in dev.4 | Fixture precedenti | **NON COLLAUDATO LIVE** | Screenshot riquadro mini Ticket, toolbar, chart legenda, dark settings |

**Stato consegna:** commit su Dev eseguiti, CI e pubblicazione candidate da verificare; dati/versione installata dall'utente restano `0.8.1-dev.3` finché non esegue upgrade della nuova ZIP. Changelog e note [dev.4](../releases/0.8.1-dev.4.md) documentano fix, file, test, SHA512, Rollback e Prossimi. Main `0.8.0` intatto.

## Aggiornamento 0.8.1-dev.5

Live 25 ottobre 2026: Kanban ProjectTask in errore con MySQL 1054, query generata ORDER BY `DESC`. Fix dev.5 nel formato ORDER di tre query read-only. La route standalone restituisce accesso negato: in MDO READMY/READALL sono diritti espliciti del profilo, inizialmente non assegnati anche a Super-Admin. Nessun bypass automatico; aggiunto avviso solo per admin. Il log dimostra invece che Continua del progetto 1 ha fatto HTTP 302 e aperto il form ProjectTask con 200. Salvare una task non risulta ancora provato. Correzioni dev.5: NON COLLAUDATO LIVE. Test CI e pubblicazione ancora da verificare; Main invariato. Next: installazione staging, board, profili, ricerca, UI dashboard.

**CI #40189803646 (primo run dev.5): FAIL del test Tasks sulla board vuota in inglese per fixture reinizializzata dai nuovi casi ACL; gli assert SQL e ACL aggiunti risultavano già PASS. Il test è stato corretto ripristinando l'insieme vuoto subito prima dell'assert; secondo run ancora da verificare. Nessuna ZIP dev.5 pubblicata dal run fallito.**

## Consuntivo pubblicazione verificato 0.8.1-dev.5 — 25 ottobre 2026

CI GitHub Actions #40189878021: **PASS completo** (bundle e publish). Commit sorgente testato `60fb656aad0400a31788bf68760edf390961f166`; commit publisher `bda3d9352d3c8fef8c7131df8a9ce9280db3658b`. ZIP completo `Modern_Dashboard_Overhaul_0.8.1-dev.5.zip`, stesso contenuto dell'alias `MDO_Dev_Latest.zip`. SHA512: `f12a4c5b7a0c13daadc6ccff60a85b83a2e3a2dfa8571d26a6d9265d9a875b206eff10577fc3674e9e57abe24176bbe7ae1e0b4624d623ec466e7a3b97475ef5`.

Risultati: 67 test PHP moduli/RSS, 69 Tasks PHP, 62 Standalone PHP, 9 packaging, 86 UI Dashboard, 175 UI Tasks, 560 UI Settings, 108 UI Standalone. Sintassi/documentazione/manifest/provenienza verificati. Tutti gli esiti browser e PHP sono in ambiente isolato, NON equivalgono al collaudo del server GLPI reale. Il primo run #40189803646 era fallito per fixture vuota ripristinata erroneamente nei test; correzione nel sorgente corrente e secondo run PASS. **Kanban dopo installazione dev.5: NON COLLAUDATO LIVE; ACL Standalone profili/entità: NON COLLAUDATO LIVE.** Main invariato.
