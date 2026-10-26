# MDO — Workflow Developer / Codex: feedback, codice, documenti, CI

**Procedura vincolante** per il lavoro ordinario su `Dev`. Prima: [start-here](start-here.md), [AGENTS](../../AGENTS.md), [backlog](../product/backlog.md), [focus](current-focus.md), [branch policy](../../.github/BRANCHES.md) e [version policy](../dev/VERSIONING_POLICY.md).

## 1. Entrata e presa in carico

1. `git fetch origin`, verificare `origin/Dev`, `origin/Main`, PR/issue e CI su SHA preciso; evitare hash copiati dalle vecchie chat.
2. Individuare il primo item definito e non BLOCCATA nel backlog; aprire specifica, sorgenti e test. Per nuovo feedback classificare bug / fix / UX / feature / refactoring / performance / sicurezza / documentazione; assegnare un **ID stabile** MDO-TASK-### / MDO-UI-### / MDO-TECH-### / MDO-GOV-###.
3. Aggiornare backlog e specifica **prima o insieme** al codice. Descrivere comportamento normale, edge cases, ACL, criteri di accettazione, esclusioni, eventuali decisioni aperte. Non trasformare idee in funzionalità implementate.
4. Procedere autonomamente su `Dev` se scope e priorità sono sufficienti; chiedere decisione solo dove serve un nuovo comportamento di dominio/diritto o schema non approvato. Documentare il blocco e svolgere le parti indipendenti.

## 2. Esempio feedback durante collaudo

«Nel popup voglio ricerca e pulsanti più distanziati»: registrare versione installata, profilo, viewport e comportamento osservato senza PII; riusare ID della feature o crearne uno nuovo per il requisito separabile. Aggiornare la specifica con design e acceptance (ricerca, focus, no overflow, permessi), implementare e testare. **Mai lasciare feedback soltanto dentro la chat**; serve commit GitHub che aggiorni registro e codice o, se soltanto requisito, documenti e prossimo passo.

## 3. Regole tecniche GLPI

- Una sola cartella plugin `mdoverhaul`. ProjectTask/Project, RSS, ticket e Dashboard sono nativi GLPI; StandaloneTask è modello plugin. Nessun SQL diretto su tabelle core per aggirare API/ACL.
- Verificare permessi per ogni task, entità, READMY/READALL, owner/team/gruppi, endpoint GET/POST, file e Search, inclusi conteggi. POST con token CSRF e verifiche server; proteggere XSS/SQL e concorrenza.
- Nuove tabelle: migratore idempotente solo da install/upgrade, test schema e restore su staging. Backup coordinato DB, plugin, `GLPI_DOC_DIR/mdoverhaul`. Flag OFF non elimina dati.
- Incrementare `dev.N` per PHP/JS/CSS/test/tooling e mantenere ZIP versionato immutabile. Leggere [policy versioni](../dev/VERSIONING_POLICY.md), [release process](../RELEASE_PROCESS.md). Modifiche solo documentali non alterano l'alias del pacchetto già pubblicato.

## 4. Documentazione e test come parte del lavoro

Nel medesimo incarico del codice aggiornare: [backlog](../product/backlog.md), [current-focus](current-focus.md), [implementation-state](../implementation-state.md), specifiche e decisioni impattate, README, CHANGELOG, QA, `docs/releases/<version>.md`, e manuali schema/security/install se necessari. Riportare SHA, run, file, rischi, prove fixture vs LIVE, rollback e next steps.

Eseguire sintassi PHP/JS/Bash, test PHP con doubles, Playwright viewport/temi, packaging, checksum e CI. Verificare il run remoto sul commit **sorgente** esatto, distinguendolo dal publisher bot. Non disabilitare test falliti e non attribuire PASS a prove non fatte. In caso di test reali non accessibili, segnalare **NON COLLAUDATO LIVE** e fornire checklist.

## 5. Stati e consegna

- `TODO` → `IN SVILUPPO` quando si inizia davvero.
- `IN SVILUPPO` → `IN COLLAUDO OPERATIVO` soltanto con codice su `Dev`, CI verde sullo SHA e candidata disponibile.
- `IN COLLAUDO OPERATIVO` → `CHIUSA` solo con criteri specifici testati/accettati. `BLOCCATA` include motivo e dipendenza; `RINVIATA` include target.
- Nessuna promozione automatica di `Main` sulla sola CI.
- Consegna: ID, commit URL e branch, versione, build/sha512, CI + stato di tutti i job, file, risultati PASS/FAIL/NON ESEGUITI (mock vs live), documenti realmente pubblicati, blocker e prossimo passo.

Se accesso scrittura assente: dichiararlo e fornire patch; non affermare che GitHub è stato aggiornato.

## Regola di manutenzione 26 ottobre 2026
Ogni modifica Dev aggiorna nello stesso incarico backlog, current focus, implementation state, release note e QA con ID e prove. Una modifica PHP/JS/test/schema usa una nuova dev.N immutabile; la sola documentazione Markdown non avvia CI. Nessuna migrazione database (`migration:timestamps` inclusa) è automatica o autorizzata dal codice frontend.
