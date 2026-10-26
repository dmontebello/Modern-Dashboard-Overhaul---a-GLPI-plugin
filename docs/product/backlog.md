# MDO — Backlog unico: feature, fix, bug, UX e governance

**Fotografia documentale: 25 ottobre 2026.** Questa tabella è l'**unica fonte degli stati operativi per item**. In caso di nuovo feedback, cambiamento di implementazione, pipeline o collaudo, aggiornare qui lo stato nello stesso lavoro del codice e delle specifiche. La [roadmap](../roadmap.md) raggruppa per milestone senza ricopiare gli stati; [implementation-state](../implementation-state.md) conserva le prove tecniche; [current-focus](../project/current-focus.md) dà il prossimo passo.

Stati consentiti **esattamente**: `TODO`, `IN SVILUPPO`, `IN COLLAUDO OPERATIVO`, `CHIUSA`, `BLOCCATA`, `RINVIATA`. `IN COLLAUDO OPERATIVO` = implementazione candidata e CI superata, ma prove/accettazione operative incomplete; **CI verde NON equivale a CHIUSA**. Il ramo `Dev` ha lo ZIP `0.8.1-dev.6` pubblicato da [Actions #40225944788](https://github.com/dmontebello/Modern-Dashboard-Overhaul---a-GLPI-plugin/actions/runs/40225944788), source SHA `080532ab6c6e5774e5445ddd80dec0c938e43726`; staging live rimane da certificare. `Main` mantiene 0.8.0 Pilot. Verificare di nuovo HEAD e run prima di agire.

| ID | Tipo | Titolo e fonte specifica | Priorità / target | Stato operativo | Evidenza / prossima azione |
|---|---|---|---|---|---|
| MDO-GOV-001 | Documentazione | [Handoff CTO/Dev, fonti di verità e governance](../project/start-here.md) | P0 / subito | IN SVILUPPO | Pubblicare e verificare i nuovi documenti, links e CI dello SHA; non dire CHIUSA prima della verifica remota |
| MDO-GOV-002 | CI/documentazione | [Validator link relativi, ID, stati, riferimenti](../project/documentation-checks.md) | P1 / prossimo cambio tecnico | TODO | Gate `check_dev_docs.py` oggi minimale; estendere test CI quando il versioning permette modifica tooling/workflow con bump |
| MDO-GOV-003 | Supply chain/Git | [Protezione Main e verifiche release](../project/working-agreement.md) | P1 / pre-stabile | TODO | Main e Dev erano unprotected al controllo; definire branch rules coerenti con publisher bot senza bloccare Dev |
| MDO-TASK-001 | Feature/fix | [ProjectTask native, selezione Project, redirect e Salva](../specs/MDO_0_8_1_DEV_WORKPLAN.md) | P0 / 0.8.0→dev.3 | IN COLLAUDO OPERATIVO | CI fixture e log 302/form reali parziali; completare creazione e ACL con profili reali |
| MDO-TASK-002 | Feature | [StandaloneTask senza progetto; schema1/diritti](../specs/MDO_0_8_1_DEV_WORKPLAN.md) | P0 / dev.2 | IN COLLAUDO OPERATIVO | Codice, mock e alcuni screenshot di task creata; restano ACL READMY/READALL, entità, migrazione e restore |
| MDO-TASK-003 | UX/feature | [Vista Tutte, filtri, pagine e origine attiva](../specs/TASKS_UNIFIED_WORKSPACE_V2.md) | P0 / dev.6 | IN COLLAUDO OPERATIVO | CI dev.6 PASS, lista visibile su GLPI dell'utente; qualità visiva e ricerca >1000 da collaudare |
| MDO-TASK-004 | Feature | [Kanban tre colonne e DnD/POST autorizzato](../specs/TASKS_UNIFIED_WORKSPACE_V2.md) | P0 / dev.6 | IN COLLAUDO OPERATIVO | Feedback LIVE 26/10: primo drag OK, successivi KO finché non si ricarica; fix rotazione token IN SVILUPPO su dev.8; prova successiva LIVE aperta |
| MDO-TASK-005 | Feature | [Diario task cronologico, tutte le note](../specs/TASKS_CHECKLIST_CONVERSION_LINKS_ROADMAP.md) | P0 / dev.6 | IN COLLAUDO OPERATIVO | Codice/CI dev.6; provare 25 note, 'Mostra precedenti', autore/data, pagina e permessi |
| MDO-TASK-006 | Feature/sicurezza | [Allegati MDO privati e download](../specs/TASKS_UNIFIED_WORKSPACE_V2.md) | P0 / dev.6 | IN COLLAUDO OPERATIVO | CI mock; upload/download GLPI reali, ACL parent, storage privato/backup da provare |
| MDO-TASK-007 | Feature | [Nuova task con form unico, Project facoltativo](../specs/TASKS_UNIFIED_WORKSPACE_V2.md) | P1 / dev.7 | IN COLLAUDO OPERATIVO | Dev.7 CI PASS #40236015750, ZIP e SHA512 pubblicati su Dev; verificare creazione e ACL/CSRF/Select2 sul GLPI reale prima di CHIUSA |
| MDO-TASK-008 | Feature | [Checklist persistente spuntabile/riordinabile](../specs/TASKS_CHECKLIST_CONVERSION_LINKS_ROADMAP.md) | P1 / dopo stabilizzazione UX | TODO | Schema/ACL/audit/keyboard e test; non alterare percent_done né Stage in automatico |
| MDO-TASK-009 | Feature/dati | [Conversione Standalone→ProjectTask con audit](../specs/TASKS_CHECKLIST_CONVERSION_LINKS_ROADMAP.md) | P1 / dopo checklist | TODO | Conversione esplicita, idempotenza, allegati/diario/checklist preservati, rollback verificato |
| MDO-TASK-010 | Feature | [Relazioni bidirezionali task↔asset/licenze/contratti/task](../specs/TASKS_CHECKLIST_CONVERSION_LINKS_ROADMAP.md) | P1 / dopo checklist | TODO | Link e link inversi solo con READ sia su task sia su target |
| MDO-TASK-011 | Architettura | [Stage e Status indipendenti stile iTasks](open-decisions.md) | P2 / futuro | BLOCCATA | Decisione MDO-OD-001 su modello e persistenza; core percent_done non equivale a due assi |
| MDO-TASK-012 | Feature/architettura | [Sessioni Worklog e aggregati tempo](open-decisions.md) | P2 / futuro | BLOCCATA | MDO-OD-002 aperta, note cronologiche NON sono worklog |
| MDO-TASK-013 | Feature/integrazione | [Parent iRequest/iNiziativa/iProject](open-decisions.md) | P2 / futuro | RINVIATA | Modellazione MDO-OD-003 ancora aperta; nessun nuovo ticket involontario |
| MDO-PROJ-001 | Feature/UX | [Brief iProjects: Goals, In scope, Out of scope](../specs/TASKS_CHECKLIST_CONVERSION_LINKS_ROADMAP.md) | P1 / 0.8.2 | RINVIATA | Scope approvato, fuori dalle dev.7/8/9 tranne comando contestuale conversione dev.9 |
| MDO-UI-001 | UI/regressioni | [Dashboard/RSS/Kanban skin e compatibilità](../specs/MDO_0_8_1_DEV_WORKPLAN.md) | P0 / 0.8.0–Dev | IN COLLAUDO OPERATIVO | Pilot Main, fixture e CSS; verifica GLPI dashboard, RSS reali, mini ticket e Kanban core |
| MDO-UI-002 | Bug/UI | [Settings dark, Tasks layout, toolbar/legenda, mini Ticket](../specs/MDO_NEXT_SPRINT.md) | P0 / Dev | IN COLLAUDO OPERATIVO | Fix candidati; il vecchio feedback screenshot non prova tutte le regressioni chiuse |
| MDO-TECH-001 | Release | [Identità versione/commit/ZIP e asset cache](../dev/VERSIONING_POLICY.md) | P0 / dev.1–6 | IN COLLAUDO OPERATIVO | CI e ZIP verificate; controllare versione e CSS/JS realmente serviti dal GLPI di staging |
| MDO-TECH-002 | Bug backend | [RedirectException, SQL ORDER BY 1054 e filtro Status](../specs/MDO_NEXT_SPRINT.md) | P0 / dev.3–5 | IN COLLAUDO OPERATIVO | Fix PHP/CI registrati; provare Search AJAX e Kanban GLPI reale dopo upgrade |
| MDO-TECH-003 | Sicurezza/dati | [Schema2, ACL e rollback note/file](../releases/0.8.1-dev.6.md) | P0 / dev.6 | IN COLLAUDO OPERATIVO | Backup DB/plugin/`GLPI_DOC_DIR`; verificare upgrade 1→2, regressione e restore |
| MDO-TECH-004 | Release | [Gate 1.0.0 stabile](../TEST_MATRIX.md) | P0 / 1.0.0 | RINVIATA | Definire matrice release, evidenze staging, performance/security e backup prima di promozione |
| MDO-TECH-005 | Performance | [Paginazione completa autorizzata della vista Tutte](../specs/TASKS_UNIFIED_WORKSPACE_V2.md) | P1 / dopo dev.6 | TODO | Campione max 1000 per origine; evitare N+1, ACL leak e dati caricati senza limiti |
| MDO-TECH-006 | Review | [Review architettura, performance, cybersecurity](../project/review-playbook.md) | P1 / periodico | TODO | Eseguire review su SHA recente; findings come item con prove |
| MDO-TECH-007 | Performance | [Benchmark CPU/RAM/SQL/UI reale](../project/review-playbook.md) | P1 / prima della stabilizzazione | TODO | Misurare in staging reale, distinguere fixture e browser live; budget da definire |

| MDO-TASK-014 | Bug funzionale | [DnD si blocca dopo primo POST](../releases/0.8.1-dev.8.md) | P0 / dev.8 UX | IN SVILUPPO | Riproduzione LIVE utente; rotazione token consumato GLPI11 e regressione 3+ spostamenti da verificare su GLPI reale |
| MDO-UI-003 | UX | [Card segue mouse e drop evidenziato](../releases/0.8.1-dev.8.md) | P0 / dev.8 UX | IN SVILUPPO | Ghost HTML5 drag e CSS visivo su feature branch; collaudo viewport, tema e accessibilità |
| MDO-TASK-015 | Feature/sicurezza | [Eliminazione task da lista combinata](../releases/0.8.1-dev.8.md) | P1 / dev.8 UX | IN SVILUPPO | Soft-delete via GLPI API, check DELETE parent e CSRF; test senza purge/entità da completare |

## Come aggiornare una riga

1. **ID immutabile** e non duplicato; nuova esigenza separabile = nuovo ID. Il cambio versione NON cambia ID.
2. Aggiorna tipo, priorità, milestone, stato, link a specifica/decisione, commit sorgente, CI run sullo SHA, risultato staging e prossimo passo. Se il dettaglio richiede molte righe crea specifica in `docs/specs/`.
3. Passa a `IN COLLAUDO OPERATIVO` soltanto dopo codice pubblicato su Dev e CI positiva per quel candidato. Passa a `CHIUSA` solo per accettazione circoscritta e verificabile; registrare versione/profilo/casi nel [registro tecnico](../implementation-state.md).
4. Per un blocco aggiungi causa, dipendenza e condizione di sblocco; per rinvio indica il target. Non chiudere un bug con il generico «Funziona» relativo all'installazione.
5. Aggiorna contemporaneamente [current-focus](../project/current-focus.md), [implementation-state](../implementation-state.md), QA, specifiche e decisioni toccate. Vedi [developer workflow](../project/developer-workflow.md).

**Le vecchie note append-only in [STATUS](../dev/STATUS.md) e nei changelog restano storia, non un secondo registro degli stati.**
