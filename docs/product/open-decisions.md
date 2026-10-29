# MDO — Decisioni aperte, non inventare regole di business

| ID | Questione | Stato e condizione |
|---|---|---|
| MDO-OD-001 | Stage e Status davvero indipendenti stile Notion | APERTA; oggi la board ProjectTask deriva Stage da percent_done e StandaloneTask usa status 1/2/3. Definire UX, memoria, ACL e migrazione prima di modificare il modello |
| MDO-OD-002 | Sessioni worklog e rendicontazione tempo | APERTA; note/diario ≠ ore. Confrontare API GLPI e pianificare data model/ACL |
| MDO-OD-003 | Parent unico Request/Iniziativa/Project | APERTA; ProjectTask richiede Project, evitare ticket fittizi o relazioni non native arbitrarie |
| MDO-OD-004 | Paginazione autorizzata completa vista Tutte | APERTA tecnica; limite 1000 candidate/origine e conteggi parziali da risolvere con query/ACL misurate |
| MDO-OD-005 | Integrazione Note/Documenti ProjectTask native con diario MDO | APERTA; evitare duplicazioni e fughe dati, rispettare autore/date |
| MDO-OD-006 | Criteri 1.0.0 stabili | APERTA; compatibilità, livelli performance, collaudo GLPI reale e rollback |

Queste NON sono già funzionalità in roadmap implementate o decisioni autorizzate implicitamente. Per sbloccare registrare [decisione](decisions.md) e aggiornare [backlog](backlog.md) e [current-focus](../project/current-focus.md). La sequenza dev.7/8/9/0.8.2 è già decisa; i dettagli non equivalenti a GLPI restano da definire.

## 29 ottobre 2026
La decisione MDO-D-009 supera l'uso di Stage/Status per le task indipendenti. Un eventuale recupero/esportazione delle righe legacy source=standalone rimane una decisione separata da approvare: non è autorizzata alcuna conversione automatica o cancellazione.
