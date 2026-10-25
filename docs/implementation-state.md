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


## Dev.7 — checkpoint implementazione

Form unico e servizio di creazione in sviluppo sul branch `feature/mdo-0.8.1-dev.7`, include validazione server-side, selezione itemtype e fallback non consentito, link nativo dopo salvataggio. Test isolati in CI feature branch; valore di prova sul GLPI live **NON ESEGUITO**. Nessuna nuova tabella rispetto alla dev.6. Una volta completato il publisher distinguere source SHA, publish SHA e build checksum. Main invariato.


## Consuntivo 0.8.1-dev.7 — pubblicazione verificata, 25 ottobre 2026

**MDO-TASK-007 IN COLLAUDO OPERATIVO; NON COLLAUDATO LIVE.** Build GitHub Actions [#40236015750](https://github.com/dmontebello/Modern-Dashboard-Overhaul---a-GLPI-plugin/actions/runs/40236015750): bundle PASS, publish_dev_zip PASS; source SHA `6aa2654fa81d6e65be4184256d06a81d66603753`, compilazione UTC 2026-10-25T21:34:49Z; archivio immutabile `Modern_Dashboard_Overhaul_0.8.1-dev.7.zip`; SHA512 `4d45752f6c40907adeea3ee40ee2e8c631b965231717aefce0945eba821fd8def701ad0786ffc3897be4d630c24160f05651f3a211e5e9e664081d19c3e2b2c3`. Metadati in [0.8.1-dev.7.build.json](releases/0.8.1-dev.7.build.json). Test automatici: 67 unit PHP, 69 Tasks, 62 Standalone, 44 Task Activity, 26 creazione unificata; Chromium 86 Dashboard, 175 Tasks, 72 nuova task, 560 Settings, 108 Standalone. 9 test packaging. **Prima pipeline ufficiale fallita solo sul gate documentale UPDATE.md privo della versione dev.7; aggiunta nota, nuovo test di non-scrittura su ACL negato e CI finale verde.** Non confondere mock con salvataggio o permessi reali GLPI 11.0.9. Next steps: backup DB+plugin+GLPI_DOC_DIR, staging test nuovo form con progetto/indipendente, READMY/entità, CSRF, Select2, scadenza, verifica unicità inserimento. `Main` e produzione invariati; prossima feature prevista dev.8 checklist.
