# QA — Modern Dashboard Overhaul 0.8.0 (Pilot)

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

