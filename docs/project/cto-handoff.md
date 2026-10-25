# MDO — Handoff nuova chat CTO / Project Manager

**Nessuna cronologia chat necessaria.** Aprire [start-here](start-here.md), [focus](current-focus.md), [working agreement](working-agreement.md), [backlog](../product/backlog.md), [roadmap](../roadmap.md), [decisioni](../product/decisions.md) e [stato tecnico](../implementation-state.md).

## Primi 10 minuti

1. Verificare GitHub `Dev`/`Main`, commit/PR/issue, run Actions e stato della ZIP dal source SHA esatto. La default branch `Main` non contiene automaticamente l'ultimo lavoro su `Dev`.
2. Al 25/10/2026 `Dev` ha candidata `0.8.1-dev.6` con [CI PASS](https://github.com/dmontebello/Modern-Dashboard-Overhaul---a-GLPI-plugin/actions/runs/40225944788); `Main` è 0.8.0 Pilot. Dev.6 non è collaudata live: schema2, file, multi-entità/ACL, POST, DnD, concorrenza e rollback richiedono test GLPI 11.0.9.
3. Evidenziare differenza fra implementato/fixture/staging/release; non dedurre chiusura da PR integrata o «Funziona» generico.
4. Priorità: collaudo dev.6 e regressioni prima di nuovi dati/migrazioni; poi dev.7 form unico, dev.8 checklist, dev.9 conversione/links e 0.8.2 brief progetti.
5. Dirigere strategia, dipendenze e decisioni importanti, non bloccare ogni fix con una firma CTO. Review indipendenti periodiche e findings tracciati. Nessun merge `Main` implicito.

## Prima di terminare il turno

Aggiornare realmente su GitHub [backlog](../product/backlog.md), [current-focus](current-focus.md), [implementation-state](../implementation-state.md), specifiche e decisioni. Riportare SHA remoto, run CI e prossimo passo; se non esiste accesso scrittura, produrre patch recuperabile. Non eseguire deploy e non modificare il core.
