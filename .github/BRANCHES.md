# Flusso GitHub di MDO

Questo repository adotta **due soli branch remoti permanenti**, con nomi esatti:

- `Dev`: sviluppo, test e attività Codex. Tutti i cambiamenti P0/P1 partono dal HEAD corrente di `Dev`.
- `Main`: pubblicazione/produzione. Non modificarlo per il lavoro quotidiano; portare gli aggiornamenti da `Dev` a `Main` soltanto dopo revisione, test e approvazione.

## Codex

In una nuova attività Codex: `git fetch origin`, `git switch Dev`, `git pull --ff-only origin Dev`, quindi `git rev-parse HEAD`. Verificare che il commit corrisponda al HEAD di `origin/Dev` prima di implementare P0/P1. Evitare nuovi branch remoti di funzionalità: i cambiamenti vanno su `Dev`; per pubblicarli si apre una PR **`Dev` → `Main`**. Se Codex richiede un proprio branch temporaneo, eliminarlo dopo il merge: il regime ordinario deve rimanere di due branch.

Conservare il plugin unico `mdoverhaul` (Dashboard, RSS, Tasks, Kanban, Settings), nessun core GLPI modificato e nessun plugin secondario. Prima della PR: sintassi PHP/JS, test, controllo di versione e build dello ZIP unico installabile.

## Versione 0.8.0 (Pilot)

`Main` contiene `Modern_Dashboard_Overhaul_0.8.0_Pilot.zip` e il sidecar SHA512. Per scaricare la versione di produzione/pubblicata utilizzare:

```bash
curl -fL -o Modern_Dashboard_Overhaul_0.8.0_Pilot.zip \
 "https://raw.githubusercontent.com/dmontebello/Modern-Dashboard-Overhaul---a-GLPI-plugin/Main/Modern_Dashboard_Overhaul_0.8.0_Pilot.zip"
```

Il vecchio branch `feature/tasks-workspace-settings` era già stato integrato e si può eliminare senza perdere il codice. Il vecchio `main` minuscolo va eliminato **solo dopo** aver impostato `Main` come branch predefinito. Prima di cancellare `feature/tasks-workspace-settings`, aggiornare nel README/UPDATE i link raw ancora diretti al vecchio branch: i raw URL non usufruiscono dei redirect di rinomina GitHub.

GitHub Actions convalida la ZIP su push a `Dev` e `Main` e sulle PR destinate a `Main`, ma **non esegue deployment automatici in produzione**.
