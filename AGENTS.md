# Repository guidance for Codex — MDO

**Read first for work requested 2026-10-24:** [MDO 0.8.1 Dev Codex specification](docs/specs/MDO_0_8_1_DEV_WORKPLAN.md), [Dev status](docs/dev/STATUS.md), [Dev version policy](docs/dev/VERSIONING_POLICY.md). Historical context: [Tasks P0/P1 specification](docs/specs/TASKS_P0_P1.md).
**Branch policy:** [Development and release workflow](.github/BRANCHES.md).

- Implement Tasks and Kanban features **inside** the existing GLPI 11 plugin `mdoverhaul`; do not create a second plugin or patch GLPI core.
- Work on `Dev`, not `Main`. `Main` is the reviewed publication/production branch.
- Re-fetch `origin/Dev` before working. Do not assume an old commit is current HEAD.
- Prioritize P0 then P1; preserve Dashboard/RSS/Settings and legacy RSS card IDs.
- Treat GLPI ACL/entity/project checks as required, never replace them with browser-only filtering.
- Do not store credentials, Notion data exports or private details in this public repository.
- Do not merge, deploy or generate a production release without review and approval.
- Report actual tests run, blockers, and unresolved data-model decisions.

If this repository does not contain the stated specification at that path, stop and report the missing file instead of guessing the scope.

## Required Dev documentation and gates

- Before coding, read the CURRENT 0.8.1 Dev specification, status and version policy; fetch the actual origin/Dev HEAD, not a memorized hash.
- TaskProject stays native ProjectTask/Project. True independent Tasks must not use a hidden Project or orphan ProjectTask. Implement a new, GLPI-integrated plugin model with ACL and safe schema migration.
- Each installable Dev candidate must have a visible prerelease version (starting at 0.8.1-dev.1) and identifiable tested source SHA, CI run ID, UTC build time. Never ship a 0.8.1 code base under a file labelled 0.8.0.
- **For each candidate in the same commit as the code**, update CHANGELOG.md, docs/releases/<version>.md, docs/dev/STATUS.md, README/QA and all impacted install/config/security/architecture docs. Record fixes, exact tests run, mocked vs LIVE GLPI status, code SHA, ZIP checksum, risks, rollback, outstanding work and next steps. Keep history intact.
- Mock/fixture success is not proof of the reported GLPI 11.0.9 UI regressions. Keep issues open until verified on actual staging. Never merge Main automatically.

- Current follow-up after user successfully installed MDO 0.8.1-dev.3: [Next sprint (staging/Tasks/UI)](docs/specs/MDO_NEXT_SPRINT.md). Do not assume every ProjectTask/StandaloneTask/ACL test passed merely because the installation works. Start with targeted live evidence, then implement minimal fixes on Dev (next code candidate 0.8.1-dev.4).

- As of version `0.8.1-dev.4` the current technical changes are the native StandaloneTask Search Status selector (return HTML, don't echo) and Dev publisher's documentation-only immutable archive gate; see `docs/releases/0.8.1-dev.4.md`. Keep Core GLPI/ProjectTask intact. Staging Search/ACL and dashboard visual issues remain pending.

## Aggiornamento 0.8.1-dev.5

Per MDO 0.8.1-dev.5: GLPI 11 DBmysqlIterator si aspetta ORDER come lista di stringhe contenenti colonna + direzione, es. ['table.id DESC']; la mappa associativa produce ORDER BY DESC e MySQL 1054 (registrato dal GLPI dell'utente). Non ripristinare il formato errato. I profili standalone partono senza permessi, anche SuperAdmin: READMY/READALL devono essere configurati, mai aggirare ACL. Stato e test nelle note dev.5; Main invariato.
