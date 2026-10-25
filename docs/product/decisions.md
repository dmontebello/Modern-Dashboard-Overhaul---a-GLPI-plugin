# MDO — Decisioni di prodotto e governance (append-only)

**Fonte:** decisioni utente del 23–25 ottobre 2026 e specifiche GitHub. Non copiare le decisioni D-014/D-015 di N3RA come regole di dominio: sono solo un precedente metodologico. Per decisioni non concluse: [open decisions](open-decisions.md).

### MDO-D-001 — 23/10/2026 · APPROVATA · Plugin unico, native-first
Sviluppare dentro `mdoverhaul` (Dashboard, RSS, Tasks, Kanban, Settings), senza patch al core GLPI o plugin duplicati; ProjectTask e Project rimangono nativi, conservando le ACL e i widget RSS persistiti. [Specifica](../specs/MDO_0_8_1_DEV_WORKPLAN.md).

### MDO-D-002 — 23/10/2026 · APPROVATA · Branch Dev e Main
`Dev` è sviluppo; `Main` è canale pubblicato. CI e build verificata prima delle candidate; PR Dev→Main solo dopo revisione, collaudo e condizioni di release, senza deploy impliciti. [Branch policy](../../.github/BRANCHES.md).

### MDO-D-003 — 24/10/2026 · APPROVATA · StandaloneTask
Task senza progetto = vero tipo `StandaloneTask` con schema e diritti propri; no Project fittizi e no ProjectTask senza parent. ACL e profili explicit, nessun bypass automatico per admin. Implementata nel codice da dev.2, collaudo reale ancora aperto. [Piano](../specs/MDO_0_8_1_DEV_WORKPLAN.md).

### MDO-D-004 — 25/10/2026 · APPROVATA · Perimetro dev.6
Incorporare la vecchia richiesta del Kanban modificabile (un tempo dev.8) nella dev.6, insieme a vista Tutte, diario completo, allegati, navigazione e layout. La nuova dev.8 riguarda la checklist. [V2](../specs/TASKS_UNIFIED_WORKSPACE_V2.md).

### MDO-D-005 — 25/10/2026 · APPROVATA · Roadmap future Tasks
Dev.7 form unico con Project facoltativo, dev.8 checklist, dev.9 conversione Standalone→ProjectTask anche da scheda progetto e relazioni bidirezionali task↔asset/contratti/licenze/altre task, 0.8.2 brief Projects Goals/In scope/Out of scope. Sono **obiettivi**, non funzionalità già implementate. [Roadmap specifica](../specs/TASKS_CHECKLIST_CONVERSION_LINKS_ROADMAP.md).

### MDO-D-006 — 25/10/2026 · APPROVATA · Autonomia Developer e documentazione
Developer/Codex prosegue su Dev autonomamente per scope chiaro senza GO CTO su ogni fix/feature/PR. Ogni feedback aggiorna backlog, specifica, prove, current-focus nello stesso intervento, con commit GitHub reale. Non introdurre regole aziendali non approvate. [Workflow](../project/developer-workflow.md).

### MDO-D-007 — 25/10/2026 · APPROVATA · Code review indipendenti periodiche
Analisi manutenibilità, prestazioni e cybersecurity secondo [playbook](../project/review-playbook.md), findings tracciati; nessuna review umana obbligatoria a ogni commit, vulnerabilità critiche aperte bloccano release stabile.

### MDO-D-008 — 25/10/2026 · APPROVATA · Nessuna produzione automatica
Questo incarico autorizza sola riorganizzazione dei documenti del ramo Dev, non feature, deploy, cambi a Main o N3RA. Qualità, collaudo e rollback rimangono necessari per la promozione.

**Nuova decisione:** `MDO-D-### — data — APPROVATA/PROPOSTA/SUPERATA — alternativa/scelta/motivazione/prove/link`. Per cambiare: aggiungere una nuova voce che dica **SUPERSEDE MDO-D-###**, mai cancellare lo storico.
