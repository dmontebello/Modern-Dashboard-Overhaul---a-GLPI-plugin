# MDO — Roadmap prodotto per milestone

**Snapshot 25/10/2026.** La [fonte unica dello stato di ciascun ID](product/backlog.md) è il backlog; qui soltanto la sequenza. [Prove tecniche](implementation-state.md) e [prossimo lavoro](project/current-focus.md).

| Fase | Scope | Riferimenti backlog |
|---|---|---|
| 0.8.0 Pilot (`Main`) | Dashboard, RSS, Tasks ProjectTask, skin Kanban | MDO-TASK-001, MDO-UI-001 |
| 0.8.1-dev.1–dev.5 (`Dev`) | Provenienza build, redirect, fix SQL/Search, StandaloneTask e ACL/schema1 | MDO-TASK-002, MDO-TECH-001, MDO-TECH-002, MDO-UI-002 |
| 0.8.1-dev.6 (`Dev`, CI PASS, LIVE aperto) | Vista Tutte, Kanban DnD, diary note storiche, allegati MDO privati, schema2 | MDO-TASK-003, MDO-TASK-004, MDO-TASK-005, MDO-TASK-006, MDO-TECH-003 |
| 0.8.1-dev.7 | Creazione task unica, progetto facoltativo | MDO-TASK-007 |
| 0.8.1-dev.8 | Checklist persistente e riordinabile nella task | MDO-TASK-008 |
| 0.8.1-dev.9 | Conversione Standalone→ProjectTask + relazioni bidirezionali asset/contract/license/task | MDO-TASK-009, MDO-TASK-010 |
| 0.8.2 | Brief di progetto strutturato (Goals/In scope/Out of scope), poi evoluzione iProjects | MDO-PROJ-001 |
| 1.0.0 | Stabilizzazione, ACL, collaudi reali, rollback, performance, documentation gates | MDO-TECH-004, MDO-TECH-005, MDO-GOV-002 |
| Futuro da decidere | Stage/Status indipendenti, worklog, parent Request/Iniziativa | MDO-TASK-011, MDO-TASK-012, MDO-TASK-013 |

[Specifica workspace](specs/TASKS_UNIFIED_WORKSPACE_V2.md) · [Roadmap dettagliata dev.8/9/0.8.2](specs/TASKS_CHECKLIST_CONVERSION_LINKS_ROADMAP.md). Non attribuire una milestone a Main automaticamente: Dev→Main richiede gate e approvazione release applicabile.
