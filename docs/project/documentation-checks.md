# MDO — Controlli documentali, limiti attuali e piano CI

## Check esistenti (verificati nel repository)

`tools/maintainer/check_dev_docs.py` richiama `versioning.documentation_errors()`: controlla versione dichiarata in README/setup.php, presenza della versione in CHANGELOG, README, QA, STATUS, VERSIONING_POLICY e release note; rileva alcune etichette obbligatorie in `docs/releases/<version>.md`. Il workflow `.github/workflows/build-mdo.yml` esegue questo gate insieme a lint, PHP mock, Playwright fixture e packaging. Il publisher `publish_dev.py` conserva ZIP `dev.N` immutabile, accetta commit solo documentali su path Markdown autorizzati e richiede versione nuova per variazioni a JS/PHP/tooling/workflow.

**Limite:** il check attuale NON valida l'intero backlog, link relativi, ID univoci, stati ammessi o il nesso fra acceptance e CI. Non affermare che li validi automaticamente.

## Verifica manuale per questa riorganizzazione

- Verificare file essenziali: `docs/project/start-here.md`, `current-focus.md`, `working-agreement.md`, `developer-workflow.md`, `review-playbook.md`, `cto-handoff.md`, `developer-handoff.md`, `docs/product/backlog.md`, `decisions.md`, `docs/roadmap.md`, `docs/implementation-state.md` e `AGENTS.md`.
- Controllare che ogni link Markdown **relativo** senza `#anchor` corrisponda a un file reale nel medesimo branch. Non considerare automaticamente validi URL esterni o sezioni ancorate.
- Nel backlog gli ID devono essere unici; lo stato deve essere uno di `TODO`, `IN SVILUPPO`, `IN COLLAUDO OPERATIVO`, `CHIUSA`, `BLOCCATA`, `RINVIATA`; le specifiche e release note collegate devono esistere.
- Verificare che il README e AGENTS puntino all'ingresso e non a versioni inattuali come «candidato attuale». Non cancellare la cronologia versionata.
- Confermare su GitHub che una CI COMPLETA sia terminata con SUCCESS **sul commit esatto**; se non ci sono run o sono ancora in corso scriverlo, senza trasporre SUCCESS di altri SHA.

## Gate CI raccomandato (TODO MDO-GOV-002)

Alla **prossima modifica funzionale con incremento `dev.N`** aggiungere un piccolo `tools/maintainer/check_project_docs.py` e test Python stdlib su fixture, invocato da `build-mdo.yml` prima del packaging. Validare link relativi, stati, ID, file essenziali, sezioni di handoff e versioni; fallire con messaggi leggibili. Evitare parsing generico Markdown e nessun servizio esterno. Non disabilitare `check_dev_docs.py` o test preesistenti.

**Motivo della pianificazione:** toccare ora tooling o workflow con il numero immutabile `0.8.1-dev.6` farebbe fallire il publisher (cambiamento non classificato come solo-documentale). Non forzare il rilascio, non ricostruire la ZIP pubblicata, non usare una versione `dev.7` riservata alla feature form unico soltanto per uno script. Il controllo avanzato è deliberatamente TODO, non già automatizzato.

Non salvare nel repository report contenenti hostname, nomi utente, record, screenshot o credenziali dello staging GLPI.
