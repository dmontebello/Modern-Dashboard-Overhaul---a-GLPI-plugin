# MDO — Istruzioni permanenti per chat Developer/Codex

**Prima di ogni lavoro** apri [START HERE](docs/project/start-here.md), [focus](docs/project/current-focus.md), [accordo](docs/project/working-agreement.md), [backlog](docs/product/backlog.md), [roadmap](docs/roadmap.md), [decisioni](docs/product/decisions.md), [stato tecnico](docs/implementation-state.md), [workflow](docs/project/developer-workflow.md) e la specifica della voce corrente. **Controlla live `origin/Dev`, `origin/Main`, PR/issue e CI sullo SHA esatto**: nessun vecchio SHA o numero di candidata qui è «corrente» per definizione.

## Autonomia e responsabilità

- Su `Dev` puoi portare avanti senza GO CTO rituale feature/fix/UX definiti, prioritizzati e coerenti con le decisioni; non inventare regole aziendali, livelli di autorizzazione, policy di conservazione o semantiche di Stage/Status.
- Ogni feedback del collaudo crea/aggiorna un item **MDO-...** nel [backlog unico](docs/product/backlog.md) e, dove serve, una specifica/decisione. Codice, test, documenti e stato avanzano **nello stesso incarico** e vengono realmente pubblicati su GitHub. Una chat senza accesso scrittura fornisce patch, non dichiara aggiornamenti inesistenti.
- Stati solo nel backlog: TODO / IN SVILUPPO / IN COLLAUDO OPERATIVO / CHIUSA / BLOCCATA / RINVIATA. Codice/CI/pacchetto/staging/produzione sono livelli di **evidenza tecnica separati**. PASS fixture non equivale a LIVE; installazione «funziona» non certifica tutti i flussi.
- Revisioni indipendenti architettura, prestazioni e cybersecurity [periodiche](docs/project/review-playbook.md), non gate umano a ogni commit. Vulnerabilità critiche aperte bloccano la promozione.
- Nuove decisioni sostanziali → [open decisions](docs/product/open-decisions.md), con opzioni e dipendenze. Parte tecnica indipendente può avanzare.

## Vincoli GLPI/MDO

- Un solo plugin `mdoverhaul`: Dashboard, RSS, Tasks, Kanban, Settings. Target GLPI 11.0.x. Nessuna modifica core, nessuna installazione di secondo plugin, nessun progetto GLPI nascosto per task senza progetto.
- `ProjectTask` rimane nativa GLPI; `StandaloneTask` è modello plugin con schema e ACL autonome. Per ciascun item e risposta: GLPI sessione, READMY/READALL, team/gruppi, entità, CSRF per scritture; evitare leak in board, paginazione, file e contatori.
- Preservare configurazioni e identificativi RSS `arensecurity_*`, dashboard/serie/filtri native. Nessuna tabella o regola di business nuova senza specifica, migrazione idempotente, test e backup/rollback.
- Gli allegati MDO stanno nel volume privato `GLPI_DOC_DIR/mdoverhaul`; snapshot coordinati DB+plugin+storage prima di upgrade. Non scrivere dati utente nel repository pubblico.
- Sviluppo su `Dev`, `Main` canale di pubblicazione. Non fare force push, non modificare `Main` nel lavoro ordinario, non eseguire deploy. `dev.N` immutabili, build source SHA, run CI, sidecar SHA512; incrementare prerelease per codice/tooling, non forzare una ZIP esistente. Seguire [versioning](docs/dev/VERSIONING_POLICY.md), [release process](docs/RELEASE_PROCESS.md) e [branch policy](.github/BRANCHES.md).

## Consegna obbligatoria

Prima del push riallinea `CHANGELOG.md`, `QA.md`, `README.md`, note `docs/releases/`, documenti d'impatto, `docs/product/backlog.md`, `docs/implementation-state.md` e `docs/project/current-focus.md`. Esegui lint PHP/JS/Bash/Python, suite e Playwright applicabili, packaging e verifica SHA512; ricontrolla CI remota *per ogni job* sul commit esatto e documenta FAIL/NON ESEGUITO. Per migrazioni e browser usa staging GLPI e registrane esito concreto. Nessuna promozione a `Main` soltanto perché CI è verde.

**Niente segreti, dati personali, URL interni o screenshot di GLPI reale nel repository pubblico.** Se HEAD remoto cambia, preserva il lavoro altrui, no force push; riconcilia prima di scrivere.

## Decisione corrente 29 ottobre 2026
MDO-D-009 SUPERSEDE le indicazioni storiche su StandaloneTask: solo ProjectTask nativa con progetto obbligatorio. Non ripristinare All/Independent o i vecchi endpoint. Conservare intatti i dati storici, anche gli allegati e il volume privato. Test e documentazione sempre nello stesso commit. Vedi dev.12.
