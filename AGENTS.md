# Repository guidance for Codex — MDO

**Read first:** [Tasks P0/P1 implementation specification](docs/specs/TASKS_P0_P1.md).
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
