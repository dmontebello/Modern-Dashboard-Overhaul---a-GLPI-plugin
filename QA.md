# QA — Modern Dashboard Overhaul 0.8.1-dev.5 (Dev)

Stato corrente: NON COLLAUDATO LIVE. Gli esiti e ambienti precedenti sotto restano storici.

**Ambiente:** codice della 0.6.0 Documented Pilot verificato con SHA-512; modifiche 0.7.0 analizzate ed eseguite offline. **Non è un test end-to-end della GLPI di produzione.**

| Controllo | Evidenza 0.7.0 |
| --- | --- |
| UI Chromium headless | **80 test superati** su 1440, 1024, 780 e 390 px, widget KPI, RSS, temi, filtri, oltre 40 tab, `+`, edit, ECharts con caricamento asincrono |
| PHP isolato | **38 test superati** su configurazioni, memoizzazione, hook, ACL con mock, sanitizer RSS, date, 4 preset e chiavi legacy |
| Performance | Benchmark locale 62 grafici: timer di retry **186 (0.6) → 3 (0.7)**; 250 mutazioni numero danno un singolo refresh in fixture |
| Controlli sintassi | `php -l` per tutti i PHP, `node --check` JavaScript, `bash -n` per gli script |
| Integrità | SHA-512 della 0.6 verificato prima dell'editing; manifest e ZIP 0.7 rigenerati e ricontrollati |
| Sicurezza | Test URL per schemi non sicuri/credenziali, escaping RSS, accesso feed in profili mock differenti, form allowlist con rifiuto input incompleti |
| Dipendenze | Nessun framework aggiuntivo, font esterno, telemetria, chiamata AJAX aggiuntiva o schema DB introdotto |

## Limiti delle verifiche

- Chromium usa una **fixture HTML che riproduce classi/markup principali di GLPI 11.0.9**, non GLPI reale. Screenshot nel processo di build non vengono inclusi nel plugin per evitare dati privati.
- Alcuni grafici della fixture sono simulati; ECharts viene testato tramite istanza mock. Grafici effettivi e GridStack su GLPI richiedono test visivo e funzionale sulla VM.
- Le ACL mock verificano che il codice controlli i permessi, **non dimostrano l'esattezza delle ACL di tutte le installazioni GLPI**.
- Il benchmark confronta la **quantità di retry pianificati** e non misura prestazioni browser in condizioni di carico reale né memoria Apache/PHP.
- **Le due CVE del runtime PHP 2026 non sono correggibili all'interno del plugin.** Verificare la patch effettivamente in esecuzione in Apache/PHP-FPM.

## Collaudo necessario sulla VM prima di accettare la 0.7

1. GLPI 11.0.9: admin → Configura, modifica e salva le sette opzioni; utente senza `config:UPDATE` deve essere respinto.
2. Verificare i feed RSS privati/pubblici con profili, entità e gruppi differenti (nessuna fuga dati).
3. Dashboard in entrambe le modalità, widget da piccoli a grandi, browser desktop/mobile reali, zoom 125/150%.
4. Creare, rinominare, clonare ed eliminare dashboard con tab e `+`; entrare/uscire da modifica, spostare widget GridStack.
5. Collaudare il caricamento RSS (5/10/15/20), feed vuoto/offline, contenuto ostile, date, scrolling, nessun collegamento JavaScript.
6. Testare modulo Restyling OFF + RSS ON e viceversa. **Non salvare dashboard in modifica con RSS disabilitato**.
7. Confrontare registri Apache/PHP/GLPI e tempo/carico con dashboard equivalenti su 0.6/0.7.
8. Ripristino versione 0.6 dalla copia, su sistema di prova. Confermare backup di database/volumi.

I criteri generali sono in [TEST_MATRIX](docs/TEST_MATRIX.md). **Non dichiarare stabile 1.0 senza questi test**.

## 0.8.0 — Verifiche preliminari

Modificati i test PHP isolati per sette impostazioni e il menu condizionale. Da eseguire su GLPI 11 reale: abilitazione/disabilitazione opzioni, cache sessione menu, lista nativa ProjectTask, restrizioni per entità e team, board di sola lettura, Kanban Vue drag/drop, dark/light, mobile. Nessun test end-to-end sulla tua installazione è stato ancora eseguito.

## 0.8.0 — Pacchetto unico

MDO 0.8.0 contiene dashboard/RSS precedenti e task/kanban nella stessa directory installabile. Verifiche richieste: sintassi PHP/JS, test unit isolati, SHA512SUMS di sorgenti e ZIP, corretta radice `mdoverhaul/`, assenza di patch/vecchi ZIP annidati. **Il collaudo sul GLPI operativo è ancora da effettuare**; i risultati storici 0.7 non sono prova end-to-end della 0.8.

## Tasks P0/P1 — verifica locale del 2026-10-24, non pubblicata

Baseline `Dev`: `dd275eb`. Runtime PHP 8.4.27, Git 2.52.0, Node e Chromium
locali, Playwright 1.62.0. Nessuna istanza GLPI o database applicativo collegato.

| Verifica eseguita | Esito e limite |
| --- | --- |
| `php tests/unit_php.php` | PASS, 67 controlli moduli/RSS e matrice flag/diritti |
| `php tests/tasks_php.php` | PASS, 59 controlli Tasks; doubles GLPI isolati |
| `bash tools/verifica-sintassi.sh --local` | PASS, 15 PHP / 6 Bash / 2 JS |
| Python modificati e YAML Actions | Parsing sintattico locale |
| `python3 tests/ui_playwright.py` | PASS, 80 controlli della dashboard su DOM simulato |
| `python3 tests/tasks_ui_playwright.py` | PASS, 167 controlli del controller reale con doubles e CSS GLPI rappresentativo |
| Responsive Tasks | 1440/1024/768/390 px, light/dark, 0/100 task, titoli lunghi, keyboard e form GET |
| Skin Kanban | Scope/geometria e attributi nativi su fixture; Vue/DnD reale NON TESTATO |
| Build locale `package_from_repo.py --output …` | Unico ZIP completo `mdoverhaul/`, versione 0.8.0; checksum/integrità verificati per la consegna |
| GitHub Actions remota | NON ESEGUITA per questi commit locali; workflow aggiornata |

Gli screenshot locali desktop/mobile del controller sono stati ispezionati.
Il primo avvio Chromium nella sandbox è stato impedito dai socket; lo stesso
test è stato poi eseguito con successo nel contesto di esecuzione autorizzato.
Anche il fetch iniziale richiedeva quel contesto; il vero HEAD Dev è stato letto.

Il pacchetto è un artefatto locale di revisione: non sostituisce lo ZIP 0.8.0
pubblicato. Nessun push, merge, release o deployment. I collaudi GLPI reali e le
decisioni strutturali restano elencati nel
[rapporto Tasks](docs/specs/TASKS_IMPLEMENTATION.md) e nelle
[decisioni aperte](docs/specs/TASKS_OPEN_DECISIONS.md).



## Feedback reale GLPI — 2026-10-24 (non superato)

Il cliente osserva: contrasto scuro dei dettagli nelle opzioni Settings; selettore Project di Nuova task e pulsanti adiacenti; "Continua" che non raggiunge il form nativo; persistenti problemi di mini KPI Ticket, toolbar e legenda ECharts. Richieste nuove Task indipendenti e versione Dev visibile.

La pipeline #40074660688 e verde: 67 PHP, 61 Tasks PHP, 84 UI Dashboard, 167 UI Tasks, soltanto **doubles e DOM fixture**. Non prova il flusso di creazione con Ajax GLPI o i layout reali. Nessuno dei bug sopra puo essere marcato risolto sullo staging. Riferimenti: [specifica 0.8.1](docs/specs/MDO_0_8_1_DEV_WORKPLAN.md), [registro stato](docs/dev/STATUS.md).


## Candidata 0.8.1-dev.1

IMPLEMENTATO / test automatici: 67 PHP moduli, 63 Tasks, 8 packaging, 86 UI dashboard, 175 UI Tasks, 512 UI Settings; sintassi 18 PHP/6 Bash/2 JS e parsing Python/YAML. Build ZIP e manifest verificati. NON COLLAUDATO LIVE:
nessun accesso allo staging GLPI 11.0.9, confermato dall'utente. I bug segnalati
non sono dichiarati risolti sul GLPI reale. Comandi e limiti nelle
[note di versione](docs/releases/0.8.1-dev.1.md); risultati CI del preciso SHA
codice nel record generato docs/releases/0.8.1-dev.1.build.json dopo pubblicazione.


## Candidata 0.8.1-dev.2

IMPLEMENTATO e TESTATO CON DOUBLES. NON COLLAUDATO LIVE: nessuna istanza GLPI
11.0.9 raggiungibile, nessuna migrazione DB reale eseguita.

| Verifica locale eseguita | Esito |
| --- | --- |
| Sintassi PHP/Bash/JS | PASS: 26 PHP, 7 Bash, 2 JS |
| Parser Python e workflow YAML | PASS |
| Moduli/RSS PHP | PASS: 67 |
| Tasks/ProjectTask PHP | PASS: 63 |
| Standalone PHP | PASS: 59 |
| Packaging/provenienza/tamper | PASS: 8 |
| Browser dashboard | PASS: 86, DOM/ECharts fixture |
| Browser Tasks | PASS: 175, controller e doubles |
| Browser Settings | PASS: 560, otto switch, contrasto AA e zoom |
| Browser standalone | PASS: 108, CRUD HTTP e persistenza simulata, form responsive/zoom |
| Gate documenti e build ZIP/manifest | PASS, build locale marcata dirty senza SHA fittizio |
| DB migration/backup/restore e GLPI live | NON ESEGUITO |

Comandi nelle [note](docs/releases/0.8.1-dev.2.md). Dopo commit, la CI ripete
le prove sullo SHA esatto e genera il pacchetto pulito; record definitivo in
`docs/releases/0.8.1-dev.2.build.json`. Nessun risultato locale simula una CI verde.

Prove aggiunte: ACL owner/assignee/gruppi/entità/ricorsività/READMY/READALL,
parametri ostili e XSS, CRUD, cestino/restore/purge vietato, CSRF tramite middleware
double, modulo/plugin OFF, diritti iniziali zero, install/reinstall idempotente
con schema simulato e nessun INSERT Project/ProjectTask. I test UI usano gli
stessi controller con stato persistente simulato, non il database/Select2 GLPI.

Da collaudare: migrazione e backup/restore veri; scheda diritti Profile, login,
Search/AJAX/export/API, User/Group dropdown, diritti effettivi, geometria GridStack,
ECharts ticket_status, cache HTTP, DnD nativo e temi reali. Nessun bug live chiuso.

## 0.8.1-dev.3 — diagnosi 25 ottobre 2026

Bug ProjectTask: GLPI 11.0.9 implementa Html::redirect() lanciando RedirectException. MDO dev.2 catturava l'eccezione con catch(Throwable) e restava sulla schermata di scelta progetto. Fix: redirect fuori dal catch; nuova regressione nei test PHP che simula l'eccezione reale. CI e checksum da verificare dopo commit. **NON COLLAUDATO LIVE**: Select2, diritti, form e Salva richiedono staging. Le altre regressioni UI e migrazione/ACL standalone sono ancora da collaudare.

## Aggiornamento 0.8.1-dev.4 (25 ottobre 2026)

0.8.1-dev.4: test previsto per Dropdown::showFromArray(display=false), restituzione HTML e selezione Stato; regressioni su publisher solo documenti rispetto a modifiche sorgente senza bump; CI da verificare su SHA effettivo. NON COLLAUDATO LIVE: ricerca AJAX, Search/ACL e CRUD standalone, mini Ticket, toolbar, legenda e Settings sul GLPI reale.

## Aggiornamento 0.8.1-dev.5

Bug verificato prima del fix: log GLPI 11.0.9 con MySQL 1054 ORDER BY DESC sul Kanban ProjectTask e screenshot di accesso Standalone negato. Nella dev.5 sono corretti tre ORDER e aggiunti test sulla sintassi DB e sul testo amministrativo. Permessi non modificati: READMY/READALL vanno conferiti dal profilo. I risultati della CI non sono anticipati come PASS; il collaudo GLPI reale dopo il fix rimane NON ESEGUITO.
