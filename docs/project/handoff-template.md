# MDO — Modello riutilizzabile di incarico/handoff

Non è un gate di approvazione CTO; serve a preservare contesto fra chat. Compilare dal GitHub corrente senza dati sensibili.

- **ID e tipo:** MDO-TASK-### / MDO-UI-### / MDO-TECH-### / MDO-GOV-###, feature/bug/fix/UX/tech/security/docs.
- **Origine:** feedback collaudo, review, requisito, CI, codice; data.
- **Stato operativo:** TODO / IN SVILUPPO / IN COLLAUDO OPERATIVO / CHIUSA / BLOCCATA / RINVIATA.
- **Priorità/milestone/dipendenze:** P0/P1/P2; dev.6/dev.7/dev.8/dev.9/0.8.2/1.0.0.
- **Specifica/decisioni:** link e acceptance; comportamento normale, errori, condizioni limite, fuori scope.
- **Ruoli/ACL e dati:** ProjectTask/StandaloneTask, owner/team/gruppi, entità, READMY/READALL, schema, CSRF, note/allegati, migrazioni.
- **UX:** light/dark, viewport 390/768/1024/1440, tastiera e mobile, no overflow.
- **Sorgenti:** HEAD `Dev` e `Main` verificati; commit/PR/Issue, file toccati.
- **CI:** run sul source SHA, esiti per job, versione/ZIP/checksum/publisher SHA distinti.
- **Staging:** versione GLPI, candidato installato, profili/entità anonimizzati, PASS/FAIL/NON ESEGUITO; tenere evidenze sensibili fuori GitHub.
- **Backup/rollback:** DB+plugin+volumi (incluso GLPI_DOC_DIR quando applicabile), test restore.
- **Documenti sincronizzati:** backlog, specifiche, decisions, QA, implementation-state, current-focus, release note.
- **Prossimo passo:** owner, data se reale, blocker, prova o decisione richiesta.

Nessun «GitHub aggiornato» senza commit remoto. Il developer procede autonomamente su item definiti; per decisioni di dominio non risolte apre [decisioni aperte](../product/open-decisions.md).
