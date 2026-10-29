# MDO — START HERE: ripresa autonoma CTO, Developer, Codex e Reviewer

**Punto d'ingresso obbligatorio per ogni nuova chat.** Repository [Modern Dashboard Overhaul](https://github.com/dmontebello/Modern-Dashboard-Overhaul---a-GLPI-plugin): plugin GLPI 11.0.x `mdoverhaul` in PHP, JavaScript/CSS e database GLPI MariaDB/MySQL, distribuito su VM Docker. Include restyling Dashboard, widget RSS, Tasks Workspace, Kanban e Settings. Il repository è **pubblico**: niente dati reali, credenziali o screenshot sensibili.

> Questa pagina è una procedura, NON una fonte live dello stato. GitHub deve essere ricontrollato all'inizio di ogni incarico. Il default branch `Main` non è il branch di sviluppo `Dev`.

## Procedura di avvio

1. Verificare su GitHub **HEAD di `Dev`/`Main`, ultimi commit, issue/PR e GitHub Actions sullo SHA esatto**. Controllare `docs/releases/<version>.build.json` e lo ZIP corrispondente; lo SHA di pubblicazione bot NON coincide necessariamente con lo SHA del sorgente testato.
2. Leggere [working agreement](working-agreement.md), [AGENTS](../../AGENTS.md) e [current focus](current-focus.md). Se un'intestazione storica contraddice codice o CI correnti, chiarire la contraddizione e aggiornare il documento.
3. Leggere [backlog (stati per item)](../product/backlog.md), [roadmap](../roadmap.md), [decisioni](../product/decisions.md), [scelte aperte](../product/open-decisions.md) e [stato tecnico verificato](../implementation-state.md).
4. Prima di sviluppare, aprire la specifica linkata nel backlog, sorgenti e test, [matrice collaudo](../dev/STAGING_0_8_1.md), [version policy](../dev/VERSIONING_POLICY.md), [release process](../RELEASE_PROCESS.md) e [branch policy](../../.github/BRANCHES.md).
5. Definire il prossimo item non bloccato, verificare eventuali PR concorrenti e seguire il workflow del proprio ruolo. Non chiamare CHIUSA una funzione solo perché compare nel codice o ha CI PASS.

## Guide per ruolo

- **CTO / PM:** [cto-handoff](cto-handoff.md) per scelte, priorità, dipendenze, rischi e handoff. Niente approvazioni CTO per ogni fix.
- **Developer / Codex:** [developer-handoff](developer-handoff.md), [developer-workflow](developer-workflow.md), specifica dell'ID e test. Aggiornare documenti e codice nello stesso incarico.
- **Reviewer:** [review-playbook](review-playbook.md) per architettura, efficienza o cybersecurity, finding tracciati con prova.
- **Feedback utente:** trasformarlo in item con ID e specifica, aggiornare backlog e stato nello stesso intervento, prima/con il codice.

## Una responsabilità per fonte

| Documento | Risponde a |
|---|---|
| [current-focus](current-focus.md) | Lavoro e blocchi immediati, snapshot datato |
| [backlog](../product/backlog.md) | **Unico registro dello stato operativo** per ogni feature/fix/bug |
| [roadmap](../roadmap.md) | Fasi e milestone, senza duplicare stati item |
| [specifiche in docs/specs](../specs/TASKS_UNIFIED_WORKSPACE_V2.md) | Requisiti e criteri di accettazione |
| [implementation-state](../implementation-state.md) | Codice, commit, CI, ZIP e collaudo effettivamente verificati |
| [decisions](../product/decisions.md) | Decisioni approvate/superate, tracciate |
| [STATUS cronologico](../dev/STATUS.md) | Evidenze storiche dettagliate e debugging |
| [QA](../../QA.md) | Test, fixture, limitazioni e QA di release |

## Regole permanenti

Un solo plugin, nessuna modifica al core GLPI; permessi per item/entità sempre lato server; ProjectTask nativa, StandaloneTask separata. Backup coordinato DB/plugin/storage prima di migrazioni. `Dev` sviluppo e ZIP candidate, `Main` pubblicazione controllata; non force push, non deploy automatici. CI fixture ≠ GLPI reale. Le chat **non** aggiornano GitHub per magia: bisogna effettuare una scrittura riuscita e verificarne lo SHA; in assenza di permessi fornire patch.

## Regola attuale 29/10/2026
MDO-D-009: il plugin offre soltanto ProjectTask nativa collegata a un progetto GLPI. Lo Standalone descritto nelle precedenti versioni è ritirato, ma i dati storici sono conservati senza CRUD. Vedi docs/releases/0.8.1-dev.12.md e MDO-TASK-022.
