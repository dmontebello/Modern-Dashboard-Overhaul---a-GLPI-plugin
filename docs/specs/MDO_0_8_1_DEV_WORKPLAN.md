# MDO 0.8.1 Dev — specifica esecutiva per Codex

Stato: SPECIFICA, NON IMPLEMENTATA. Feedback reale del 24 ottobre 2026.
Repository: dmontebello/Modern-Dashboard-Overhaul---a-GLPI-plugin.
Sviluppo esclusivamente su branch Dev; Main resta produzione e non va modificato.
Plugin unico: mdoverhaul; riferimento GLPI 11.0.9.
Snapshot verificato prima di redigere: Dev a866f3aa64174fc99e2462f373a145d39a2efca1, Main 0dd4debe1b68088190132c0dfcca9c3bae068099. Codex deve rileggere il vero HEAD prima di lavorare, perché questi SHA diventeranno storici.

Documenti collegati: [Stato Dev](../dev/STATUS.md), [Versioning](../dev/VERSIONING_POLICY.md), [decisioni storiche Tasks](TASKS_OPEN_DECISIONS.md), [policy branch](../../.github/BRANCHES.md).

## Vincoli non negoziabili

1. Solo Dev; niente force-push, nuovo plugin, patch core, merge/Main, release produzione o deploy autonomo.
2. ZIP unico con radice mdoverhaul/ e tutti i moduli (Dashboard, RSS, Tasks, Kanban, Settings).
3. Native-first per ProjectTask: modello Project, diritti e form GLPI, Search GLPI, permessi per item/team/entità e sessione. Per attività davvero indipendenti dal progetto NON creare un progetto nascosto e NON salvare ProjectTask con projects_id=0.
4. Preservare i widget RSS salvati, gli ID dei tipi RSS legacy, i dati native dashboard, i drag/drop e permessi Vue GLPI. Evitare alterazioni serie, dati, palette e click ECharts.
5. Test isolati NON equivalgono a un test reale di GLPI. I bug riportati sono APERTI finché non verificati su staging GLPI 11.0.9.
6. Migrazioni solo con backup DB e volumi, upgrade idempotente, test staging, rollback. Non eliminare dati quando un modulo viene disattivato.

## P0-A — Versioning prerelease e build verificabile (prima degli altri fix)

- Prima versione con fix: 0.8.1-dev.1. Incrementare a 0.8.1-dev.2, dev.3 ecc. a ogni nuovo candidato funzionale installabile. Mai chiamare una build con codice 0.8.1 usando il nome ZIP 0.8.0.
- Versione applicativa da PLUGIN_MDOVERHAUL_VERSION in setup.php (o unica sorgente equivalente), senza stringa fissa "0.8.0" in front/config.php. Aggiornare versione JavaScript rimasta a 0.7.0; distinguere commenti storici da metadata eseguiti.
- Mostrare in Settings: versione, canale Dev, SHA del COMMIT SORGENTE TESTATO, GitHub Actions run/build ID e built_at UTC. Valore da metadata generati e inclusi nello ZIP (es. build-info.json), senza credenziali. Il commit successivo di pubblicazione del bot non è lo SHA del codice testato.
- Adeguare tooling package_from_repo.py, package_mdo.py, check_release.py, workflow build-mdo.yml, regex SemVer prerelease, SHA512SUMS e manifest per metadata generati. I check devono poter distinguere file generati e sorgenti senza bypassare verifiche crittografiche.
- Pubblicare su Dev un pacchetto versionato immutabile e un alias STABILE MDO_Dev_Latest.zip con relativo sidecar SHA512. Dopo CI verde e verifica HEAD del commit testato, aggiornare alias solo su Dev; se CI fallisce lasciare l'ultimo valido. Non sovrascrivere il nome legacy 0.8.0 con codice più recente. Main intatto.
- Verificare la versione effettivamente servita negli asset CSS/JS e cache-busting browser/GLPI, non ipotizzare che il client abbia caricato il CSS nuovo perché lo ZIP è aggiornato.
- Coerenza obbligatoria tra GLPI plugin manager, Settings, setup.php, ZIP, SHA512SUMS, source SHA, build ID e release notes.

## P0-B — Settings: descrizioni invisibili in dark mode

File front/config.php, public/css/settings.css, src/Settings.php.

Screenshot reale: .mdo-setting-detail e .mdo-settings-intro diventano scurissimi su sfondo scuro usando --tblr-secondary-color. Creare token CSS LOCALI alla pagina per testi, secondari, separatori, sfondo e focus, per le varianti GLPI light/dark. Evitare regole globali body/card/text-secondary. Contrast ratio calcolato >=4.5:1 per testo normale e >=3:1 per controlli; verificare tutti i 7 switch, label, descrizione, tooltip, versione, messaggi e bottone Salva. Valutare 200% zoom, tastiera e mobile. Nessuna variazione ai flag persistenti.

## P0-C — Nuova TASK DI PROGETTO: "Continua" rimane sulla stessa pagina

File front/tasks.php, src/TaskBoard.php e test correlati.

Percorso atteso: Assistenza > Tasks > Nuova task > Task di progetto > selezione PROGETTO-TEST tramite dropdown GLPI > Continua => apertura vera /front/projecttask.form.php?projects_id=ID. Non si deve creare una task vuota in questo step; il POST Salva del form nativo GLPI crea la task. Poi la task compare nel progetto e in Tasks elenco/Kanban se ACL lo consentono.

Verificare su GLPI reale il valore HTML name=project, input Ajax Select2 (id numerico effettivamente inviato), query GET e router, root_doc, risposta redirect e stato della sessione. Oggi Dropdown::show(Project::class) mostra il selettore, ma i mock non provano la selezione reale. TaskBoard::canCreateInProject impone Project::can(READ/UPDATE) + ProjectTask::can(-1,CREATE,input): confrontare ciascun controllo con GLPI 11.0.9 e CommonDBChild::canCreateItem. Non inventare diritti più restrittivi del core né saltare ACL; attenzione a profili READMY, team e creatore del progetto.

Se scelta mancante/0, ID manipolato, progetto cancellato, altra entità, non aggiornabile o diritto insufficiente: avviso esplicito e sicuro, mai ricarica silenziosa né fuga di informazioni. Controlli server-side sempre presenti. Link fallback al progetto nativo sicuri e validi con root_doc. Log errors a livello GLPI evitando PII e segreti. Test end-to-end con GET e salvataggio reale.

## P0-D — Layout della scelta progetto e della lista

Screenshot: dropdown largo tutta la pagina e pulsanti adiacenti senza gap. Form in una zona con max-width 680–820px, label separata, Select2 max-width 100%, spazio verticale >=16px dopo il picker, riga bottoni gap 12–16px, margin-top 20px; su mobile azioni impilate con focus/hover visibili. Nessun overflow 390/768/1024/1440px, zoom 200%.

Elenco/Kanban: rimuovere realmente il vuoto fra tab e risultati, non mascherarlo con margin-top negativo. Integrare Search GLPI senza perdere AJAX, paginazione, export, filtri, preferenze e permessi. Dopo creazione mostrare messaggio o accesso alla task senza falsificare la lista.

## P0-E — Mini Ticket dashboard: rettangolo non contiene le KPI

File public/css/ticket-mini-dashboard.css, public/js/ticket-mini-dashboard.js.

GLPI mini usa GridStack e storicamente margin-bottom:-45px. La riga delle sette card nella pagina Assistenza/Chiamate produce un pannello chiaro che supera il bordo padre. Misurare in browser vero getBoundingClientRect() di .dashboard-card, .card-body, .dashboard.mini, .grid-stack, .grid-stack-item e .card, insieme a computed CSS. Tutti i bordi delle KPI, numeri e background devono restare nel contenitore, senza overlap con search toolbar e senza taglio delle card. Gestire resize dinamico, 390/768/1024/1440, temi chiaro/scuro, zoom, dataset vuoto e pieno. Non cambiare posizioni persistite del grid, API Vue o core GLPI. Verificare asset realmente caricati e screenshot prima/dopo.

## P0-F — Dashboard Assistenza: toolbar e legenda grafici

File public/css/dashboard-overhaul.css, public/css/dashboard-tabs.css, public/js/dashboard-overhaul.js, public/css/chart-text.css.

Toolbar comandi a destra (refresh, dark mode, clone/share/delete/edit/fullscreen) schiacciata sul bordo top. Dare padding/offset misurabile e coesistenza con tab sinistra, anche in edit, resize, RTL, dark/light, fullscreen e viewport stretto.

Widget "Stato chiamate per mese": legenda lunga su più righe si sovrappone alla grafica. Individuare l'istanza ECharts EFFETTIVA e le opzioni native (legend, grid, title, tooltip, axes, card size); riservare spazio distinto per legenda orizzontale leggibile con scroll/pager quando occorre, plot ed etichette assi. Valutare container small height e nessun overflow; non intervenire indiscriminatamente su pie/donut. Mantenere serie, dati, palette, tooltip, click e refresh originali. Nessun loop di ResizeObserver/MutationObserver. I test fixture passati in passato non dimostrano la risoluzione sul GLPI vero.

## P1 — Task davvero INDIPENDENTI dal progetto

Richiesta utente del 24/10/2026. GLPI 11.0.9 ProjectTask estende CommonDBChild e il parent Project è obbligatorio; projects_id=0, progetto "Inbox" nascosto o Reminder travestito non soddisfano il requisito.

Architettura: nello STESSO mdoverhaul introdurre per esempio GlpiPlugin\Mdoverhaul\StandaloneTask, oggetto GLPI CommonDBTM integrato nel plugin, tabella glpi_plugin_mdoverhaul_tasks. Verificare naming e schema GLPI in sorgenti 11.0.9 prima di realizzare. Nessuna modifica a tabelle core. Campi minimali: id, entities_id, is_recursive solo se appropriato, name, content, owner, assignee opzionale, status minimo (todo/doing/done), due date, date_creation, date_mod, is_deleted. Indici per entità/owner/assignee/stato/scadenza; nessuna FK Project obbligatoria.

Diritti configurabili via profili GLPI: READMY, READALL, CREATE, UPDATE, DELETE o modello equivalente, con controlli sul server per ogni item, owner/team, sessione/entità e CSRF per scritture. Separare chiaramente le ACL dalle ProjectTask. Menu Assistance > Tasks visibile se task_workspace attivo e utente può vedere uno dei due tipi. Chi può usare solo StandaloneTask non deve dipendere da ProjectTask::canView. Niente leakage via contatori, ricerche, API e tooltip.

CTA "Nuova task" deve consentire scelta semplice fra "Task indipendente" e "Task di progetto". Per ProjectTask usare SOLO form GLPI nativo, per StandaloneTask usare nuovo form GLPI-integrato del plugin e CRUD sicuro; nessun progetto obbligatorio per standalone.

Elenco e Kanban consentono di trovare e modificare le task indipendenti. Se Search nativo non può fare una UNION autorizzata di due itemtype, usare sottoviste separate Tutte/Progetto/Indipendenti con filtri chiaramente indicati, non simulare paginazione/ACL con query pericolose. Ogni card distingue tipo/ID per evitare collisioni; non alterare ProjectTask. Disattivare opzione non cancella dati.

Settings: task_workspace padre, nuova sotto-opzione tasks_standalone (opt-in fino al completamento dell'upgrade/migrazione), kanban_restyling indipendente. Aggiornare testo "nessuna nuova tabella" SOLO quando la nuova persistenza viene davvero introdotta, nelle sezioni config/readme/architettura/security.

Installazione/update: migrazione schema idempotente con controllo esistenza e versione, upgrade dalla 0.8.0, backup DB e volumi, re-install, rollback e uninstall documentati. Vietata cancellazione automatica dei dati con disattivazione del modulo. Non eseguire una migrazione non verificata in produzione.

Fuori scope P1: Stage e Status indipendenti stile Notion, parent iRequest, conversione automatica in ProjectTask, sync Notion, worklog, DnD con scrittura. Documentarli come future decisioni.

## Matrice collaudo e condizioni di accettazione

Automazione:
- syntax/lint PHP/JS/Bash/Python/YAML, unit PHP, test Tasks/Settings/RSS, Playwright viewport 390/768/1024/1440px e tema dark/light, focus/zoom.
- Mock ACL: superadmin, READMY, READALL, entità ricorsive, proprietario non membro team, solo ProjectTask, solo StandaloneTask, senza diritti, accesso cross-entity, ID manipolati, CSRF/XSS, cancellato, modulo OFF.
- Verifica a geometria DOM: bounding box mini card contenuto nel padre, no toolbar/tab overlap, grafico riserva spazio a legenda e assi, niente scroll inutile, nessun loop reflow.
- PHP version compare 0.8.1-dev.N; coerenza Settings/plugin/JS/build-info/ZIP, checksum esterno e manifest interno, una sola root mdoverhaul/, nessun file confidenziale né ZIP annidato.

STAGING LIVE GLPI 11.0.9 obbligatorio prima di considerare il bug CHIUSO:
1. Versione 0.8.1-dev.1 + SHA build leggibili in Settings, asset CSS/JS nuovi davvero serviti; contrasto delle sette descrizioni >=4.5:1.
2. Progetto PROGETTO-TEST > selezione > Continua: URL/form nativo corretto > Salva > task leggibile nel progetto e in MDO elenco/Kanban con diritti.
3. Nuova task indipendente senza scegliere Project > Salva > filtro/elenco/Kanban/edit/completamento/delete, e verificare assenza di insert in glpi_projects.
4. Testare profili/entità non SuperAdmin, gruppi/team, visibilità privata, disable/enable flag, isolamento autorizzazioni.
5. Screenshot Assistenza/Chiamate: KPI completamente dentro il bordo; dashboard Assistenza: toolbar distanziata, grafico "Stato chiamate per mese" senza collisioni, anche mobile/dark/zoom.
6. AJAX Search, paginazione, export, menu dopo logout/login, DnD Vue su progetto, dashboard/RSS regressioni assenti.
7. Migrazione 0.8.0 -> 0.8.1 Dev + rollback in staging con backup; mai in produzione senza approvazione.

Se Codex non può raggiungere il GLPI reale, completare codice, test e documentazione, poi marcare precisamente "NON COLLAUDATO LIVE"; fornire comandi, prove e checklist all'utente. Non usare test mock per dichiarare "risolto su GLPI".

## Sequenza e consegna finale a Codex

1. Leggere AGENTS, questa specifica e i documenti di Stato/Versioni; git fetch origin e verifica reale di origin/Dev HEAD prima di scrivere. Non assumere vecchi SHA.
2. Correggere P0, alzare 0.8.1-dev.1, testare e pubblicare in Dev soltanto dopo pipeline verde. Aggiornare in uno stesso commit file di codice e documentazione di versione; non toccare Main.
3. Implementare P1 con migrazione e ACL, alzare almeno a 0.8.1-dev.2, ripetere CI/staging/registrazioni. Nessun deploy/merge in Main.
4. Prima di ogni candidato aggiornare CHANGELOG, docs/releases/<version>.md, docs/dev/STATUS.md, README, QA e ogni documento interessato (installazione, configurazione, architettura, sicurezza, test). Riportare fix, file, commit, test effettivi, SHA512, rollback, blocchi e next steps, senza cancellare storico.
5. Consegna: SHA commit, elenco file e motivazioni, differenze DB e sicurezza, PASS/FAIL/NON ESEGUITO per ogni test, URL ZIP versionato/Latest, checksum, procedura staging e rischi aperti.
