# MDO — Working agreement

**Decisioni MDO approvate dall'utente il 25/10/2026**; N3RA ha fornito soltanto un riferimento metodologico in lettura. Le regole di MDO sono in [decisions](../product/decisions.md); l'avvio in [start-here](start-here.md).

## Fonte condivisa e ruoli

GitHub è la memoria operativa, non le chat. `docs/product/backlog.md` è l'unica fonte degli stati per ID. `docs/roadmap.md` aggrega milestone; `docs/implementation-state.md` distingue sorgente, CI, pacchetti, prove operative e release; `docs/project/current-focus.md` è lo snapshot datato del prossimo lavoro.

| Ruolo | Compito |
|---|---|
| CTO / PM | Priorità, visione, dipendenze, rischi e decisioni di dominio; non firma ogni commit |
| Developer / Codex | Recupera `Dev` live, sviluppa item definito, mantiene test e documentazione nello stesso intervento |
| Reviewer | Architettura/manutenibilità, efficienza/performance, sicurezza; review periodiche con finding tracciati |
| Collaudatore | Valida candidate sul GLPI autorizzato e comunica accettazione per item, non per intera release genericamente |

**Autonomia:** un item con scope sufficiente e priorità nota può essere implementato e corretto su `Dev` senza nuovo GO CTO per ogni feature/fix/PR. Per decisioni di business sostanziali o policy permessi non approvate usare [open decisions](../product/open-decisions.md); le parti indipendenti e sicure possono avanzare.

## Standard di lavoro

- `Dev` sviluppo e CI; `Main` release approvate, nessuna promozione/deploy automatico, nessun force push o perdita di storico. Prima di agire confrontare HEAD remoto e PR concorrenti.
- Classificare ogni feedback (bug, UX, feature, refactor, sicurezza, performance), assegnare ID e aggiornare [backlog](../product/backlog.md), specifica, decisioni, [stato tecnico](../implementation-state.md) e [focus](current-focus.md) **nello stesso incarico del codice**.
- Stati: TODO, IN SVILUPPO, IN COLLAUDO OPERATIVO, CHIUSA, BLOCCATA, RINVIATA. CI verde su fixture non certifica GLPI live. CHIUSA soltanto con acceptance applicabile e prova specifica; promozione `Main` richiede anche risk/rollback e release gate.
- Modifiche funzionali richiedono nuova prerelease `dev.N`: pacchetti versionati e SHA512 sono immutabili. Documentazione-only su `Dev` non autorizza sovrascrittura di ZIP esistente.
- Review umane periodiche, non obbligatorie per ogni commit. Sicurezza automatica e test restano gate; vulnerabilità critiche aperte impediscono release stabile.

## Limiti MDO

Plugin unico `mdoverhaul`, GLPI 11, ProjectTask nativa con progetto obbligatorio, StandaloneTask custom con ACL/entità. Non modificare core, non aggirare permessi, non rompere Dashboard/RSS o ID persistiti. Migrazioni solo via upgrade amministrativo e con backup DB + plugin + volume privato GLPI; flag OFF non cancella dati. Repository pubblico: no dati personali, dump, credenziali o screenshot operativi.

Vedi [developer workflow](developer-workflow.md) e [review playbook](review-playbook.md).
