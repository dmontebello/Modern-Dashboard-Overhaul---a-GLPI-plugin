# MDO — Stato tecnico verificato (distinto dallo stato operativo)

**Snapshot 25 ottobre 2026 prima della governance.** [Backlog con stati](product/backlog.md) è la fonte delle fasi operative; questo file conserva livelli verificabili: PROGETTATO / IMPLEMENTATO IN CODICE / TEST AUTOMATICI PASS / ZIP PUBBLICATO DEV / COLLAUDO LIVE / PUBBLICATO MAIN.

## GitHub e provenienza

- `Main` a `0dd4debe1b68088190132c0dfcca9c3bae068099` — codice ZIP 0.8.0 Pilot.
- `Dev` prima della governance a `3f1a5b398d92a2b064ddcb6cc15c44eb2ae85889`; la testata del branch include commit di publisher e documentazione, NON è la provenienza della ZIP.
- Sorgente verificato `0.8.1-dev.6`: `080532ab6c6e5774e5445ddd80dec0c938e43726`, [GitHub Actions #40225944788 SUCCESS](https://github.com/dmontebello/Modern-Dashboard-Overhaul---a-GLPI-plugin/actions/runs/40225944788). [Build provenance e SHA512](releases/0.8.1-dev.6.build.json), [release](releases/0.8.1-dev.6.md). Verifiche PHP/Playwright sono fixture e doubles.
- **NON COLLAUDATO LIVE:** schema1→2 e MariaDB GLPI, ACL team/entità, POST Stage/CSRF/concorrenza, Kanban DnD, note storiche e download/upload, storage privato, backup/rollback. Nessuna promozione dev.6 su Main.

| Area | Codice | Automatici | GLPI LIVE | Canale Main |
|---|---|---|---|---|
| Dashboard/RSS/Settings | Main 0.8.0 e Dev | Test PHP/browser fixture | Regressioni UI e browser reale da confermare | 0.8.0 Pilot |
| ProjectTask nativa / Kanban iniziale | Main 0.8.0 | Test fixture | Creazione, salvataggio e ACL completi da confermare | 0.8.0 Pilot |
| StandaloneTask, schema1 e diritti | Dev da dev.2 | Test PHP/fixture PASS nelle candidate | Creazione parzialmente vista; ACL, migrazione/restore aperte | Assente |
| Fix RedirectException, SQL ORDER, Search Status | Dev dev.3–5 | Regressioni automatiche | Test UX/SQL post install da ripetere | Assente |
| Vista unificata, drag&drop, aggiornamento Stage | Dev dev.6 | CI #40225944788 PASS | Non collaudata live | Assente |
| Diario storico + allegati privati/schema2 | Dev dev.6 | CI #40225944788 PASS | Non collaudata live | Assente |
| Dev.7 form, Dev.8 checklist, Dev.9 conversione/link, 0.8.2 iProjects | Specifiche soltanto | Non applicabile | Non implementate | Assente |

**Rischi:** vista Tutte bounded 1000 candidate per origine, nessuna performance CPU/RAM live certificata; MDO diario e Note native separate; upgrade schema2 richiede backup coordinato DB+plugin+`GLPI_DOC_DIR/mdoverhaul`. Il commento «funziona» relativo a una installazione dev.3 NON certifica tutte le funzioni.

## Aggiornamento dopo ogni lavoro

Aggiungi prove datate con SHA del **sorgente testato**, ID CI run/esiti per job, versione ZIP/hash, ambiente GLPI, risultati PASS/FAIL/NON ESEGUITI, rischi e next steps. Aggiorna nello stesso commit [backlog](product/backlog.md), [focus](project/current-focus.md) e QA/release notes interessate. Non cancellare i vecchi snapshot: [registro storico](dev/STATUS.md).
