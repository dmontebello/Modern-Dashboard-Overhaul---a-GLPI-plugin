# MDO — Handoff nuova chat Developer / Codex

**Prima di fare modifiche:** [start-here](start-here.md), [AGENTS](../../AGENTS.md), [workflow](developer-workflow.md), [focus](current-focus.md), [backlog](../product/backlog.md), [versioning](../dev/VERSIONING_POLICY.md).

```bash
git fetch origin
git switch Dev
git pull --ff-only origin Dev
git rev-parse HEAD
```

Verifica il confronto con `origin/Dev`, `origin/Main`, PR/issue e GitHub Actions sul commit esatto. Non partire dalla default `Main` per lo sviluppo e non assumere che una vecchia ZIP sia HEAD.

## Baseline solo storica (25/10/2026)

`0.8.1-dev.6` è stata pubblicata su `Dev`, source SHA `080532ab6c6e5774e5445ddd80dec0c938e43726`, [CI PASS](https://github.com/dmontebello/Modern-Dashboard-Overhaul---a-GLPI-plugin/actions/runs/40225944788), [record build](../releases/0.8.1-dev.6.build.json). `Main` resta 0.8.0 Pilot. La dev.6 presenta un'area di lavoro combinata con DnD, diario, allegati MDO e schema2, **NON COLLAUDATA LIVE**; leggi [staging](../dev/STAGING_0_8_1.md). La prossima candidata funzionale pianificata è dev.7 (form unico, Project opzionale) ma non saltare le regressioni P0.

## Ciclo da ripetere

1. Scegli un ID nel backlog, apri specifica e codice, verifica se manca decisione sostanziale. Per un feedback nuovo registralo nel backlog e definisci acceptance prima di codificare.
2. Su `Dev` implementa in incrementi piccoli, preserva ACL per record/entità e dati; una feature tecnica definita non richiede nuova approvazione CTO.
3. Aggiorna test e documentazione **nello stesso lavoro**: backlog, implementation-state, current-focus, CHANGELOG, README, QA, release notes e manuali impattati.
4. Per codice cambia versione prerelease, esegui suite e valida Actions, package SHA512 e provenienza; CI fixture non equivale al server reale.
5. Riporta branch/SHA, CI, ZIP, rischi, PASS/FAIL/NON ESEGUITO, migrazioni e prossimo passo. Nessuna promozione su `Main` automatica.

Per passare a un'altra chat usare il [modello di handoff](handoff-template.md). Se non puoi scrivere su GitHub, fornisci patch senza dichiarare pubblicazione.
