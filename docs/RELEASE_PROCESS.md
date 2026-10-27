# Regole obbligatorie per ogni release — Modern Dashboard Overhaul 0.8.1-dev.2

> **Corrente 0.8.1-dev.2:** vedere la sezione aggiornata in fondo; le descrizioni precedenti restano riferimento storico delle versioni indicate.

**Obiettivo:** nessuna versione senza README e documentazione sincronizzati con il codice. Questo documento fa parte del pacchetto **e deve essere aggiornato con esso**. Tutto si svolge localmente finché non sarà validata la 1.0; pubblicare sul branch GitHub di sviluppo la ZIP pilota completa; il Marketplace resta escluso finché non sarà richiesto.

## 1. Prima di cambiare il codice

1. Prendere la **ZIP sorgente completa dell'ultima versione** e verificarne SHA-512 interno/esterno; non partire da appunti o ZIP più vecchi.
2. Identificare versione in `setup.php`, API GLPI di riferimento, moduli, identificativi dei widget salvati e test già effettuati.
3. Creare una directory di staging separata. Prima di modificare, registrare i file cambiati e il motivo.
4. Preservare ID tecnico `mdoverhaul`, namespace `GlpiPlugin\Mdoverhaul` e chiavi compatibili `arensecurity_feed_*` / `arensecurity_rss*`.

## 2. Aggiornare **insieme al codice** questi documenti

| File | Contenuto richiesto |
| --- | --- |
| `README.md` | Versione, funzioni attive, requisiti e limiti, indice della documentazione |
| `CHANGELOG.md` | Nuova sezione con data e novità, preservando tutte le versioni precedenti |
| `docs/RELEASE_NOTES_IT.md` | Sintesi operativa specifica della versione |
| `docs/ARCHITETTURA.md` | Moduli, file, hook, dati e flussi aggiornati al codice reale |
| `docs/CONFIGURAZIONE.md` | Flag, valori di default, permessi, persistenza e dipendenze |
| `docs/INSTALLAZIONE.md` / `UPDATE.md` | Installazione, upgrade dalla precedente, controllo SHA-512, rollback |
| `docs/MAPPA_CARTELLE.md` | Nuovi file/spostamenti, host/container/volumi |
| `docs/TEST_MATRIX.md` / `QA.md` | Casi richiesti ed esiti realmente verificati, incluso ciò che manca |
| `docs/SECURITY.md` / `docs/INTEGRATION.md` | Cambi nei confini di fiducia, API GLPI e compatibilità |
| `tools/README.md` | Nuovi comandi/controlli del pacchetto, se applicabili |

**Non sovrascrivere lo storico.** Una funzione dichiarata in README deve essere riscontrabile nel codice. Le prove di browser e di GLPI live restano `NON TESTATO` finché non vengono davvero eseguite.

## 3. Controlli prima di confezionare

- Verifica sintassi PHP/Bash/JavaScript (Node assente = test parziale, mai successo pieno).
- Verifica form/configurazione, permessi e modulo RSS indipendente.
- Verifica UI light/dark, card piccole, filtri, tab con molte dashboard e `+` nativo.
- Verifica feed vuoti, privati, non raggiungibili e collegamenti anomali.
- Nessuna password, `.env`, certificato privato o percorso interno nei documenti pubblicabili.
- Checksum **nuovi**, non copiati dalla release precedente; file extra e symlink rifiutati.

## 4. Pacchetto riproducibile

Il codice include **strumenti opzionali del manutentore** in `tools/maintainer/`; richiedono Python 3 **solo per creare release**, non per eseguire il plugin.

```bash
# Dalla directory PADRE che contiene mdoverhaul/
python3 -B mdoverhaul/tools/maintainer/package_mdo.py \
  ./mdoverhaul ./Modern_Dashboard_Overhaul_VERSIONE_Pilot.zip

python3 -B mdoverhaul/tools/maintainer/check_release.py \
  ./mdoverhaul --zip ./Modern_Dashboard_Overhaul_VERSIONE_Pilot.zip

sha512sum -c Modern_Dashboard_Overhaul_VERSIONE_Pilot.zip.sha512
unzip -t Modern_Dashboard_Overhaul_VERSIONE_Pilot.zip
```

`package_mdo.py` rigenera `SHA512SUMS`, valida documenti/versioni e crea ZIP deterministico con sidecar `.zip.sha512`. `check_release.py` confronta versione, link locali, manifest e byte della ZIP con i file sorgente. Le regole sono anche contenute nella competenza ChatGPT **MDO Release & Documentation**, distribuita separatamente dal plugin.

## 5. Criteri di uscita e versioni

- Usare SemVer (`0.6.0`, `0.7.0`, …, `1.0.0`). Incrementare la versione per codice/funzionalità; dichiarare esplicitamente gli eventuali **rebuild soltanto documentali** della stessa versione.
- Ogni ZIP contiene l'intera directory `mdoverhaul/`, non una patch di file sparsi.
- Includere ZIP, sidecar SHA-512, elenco cambiamenti, esito test e procedura rollback.
- **1.0 solo dopo** collaudo reale sulla dashboard operativa e installazione GLPI pulita, inclusi compatibilità, sicurezza, no regressioni e ripristino.

## 6. Checklist del manutentore

- [ ] Versione coerente tra PHP, README, Changelog, QA e Release Notes
- [ ] Nuove/alterate funzioni descritte in Architettura e Configurazione
- [ ] Installazione/upgrade/rollback aggiornati
- [ ] Test effettivi riportati; non verificati dichiarati
- [ ] Nessun dato privato nel pacchetto
- [ ] SHA512SUMS rigenerato, ZIP confrontata ai sorgenti, sidecar verificato
- [ ] Test su GLPI quando disponibili (non sostituibili con il solo lint)

## 0.8.0 — Distribuzione GitHub completa

Sul branch di pubblicazione `Main` il file `Modern_Dashboard_Overhaul_0.8.0_Pilot.zip` e il sidecar `.zip.sha512` sono artefatti della release, non archivi automatici dei sorgenti. Genera il pacchetto dalla checkout con `python3 -B tools/maintainer/package_from_repo.py`, che esclude `.git/`, `.github/` e gli ZIP vecchi, sincronizza `SHA512SUMS` e crea la struttura `mdoverhaul/`. Pubblica sorgenti, checksum e ZIP insieme. Una verifica GitHub Actions ricostruisce l'archivio, ma l'installazione reale necessita comunque un collaudo separato.


## Distribuzione continua del branch Dev

Il workflow `.github/workflows/build-mdo.yml` genera lo ZIP dall'HEAD testato e lo allega come Artifact. Dopo il successo completo dei job, `publish_dev_zip` (permesso GitHub Actions `contents: write` limitato al job) aggiorna **solo su `Dev`** `Modern_Dashboard_Overhaul_0.8.0_Pilot.zip`, il `.zip.sha512` e `SHA512SUMS`. Prima del commit, confronta `origin/Dev` con il commit testato: non pubblica un bundle obsoleto se nel frattempo è arrivato un nuovo push. Il commit automatico GitHub Actions non attiva un nuovo workflow. Nessuna pubblicazione automatica su `Main`: quel branch riceve soltanto le versioni approvate dopo collaudo e PR.


## Aggiornamento operativo Dev 0.8.1 — 24 ottobre 2026

Il contenuto storico di questo documento regola la 0.8.0. Per il prossimo sviluppo valgono anche [specifica 0.8.1](specs/MDO_0_8_1_DEV_WORKPLAN.md), [policy versioni](dev/VERSIONING_POLICY.md) e [stato](dev/STATUS.md).

- Nuove candidate Dev con suffisso 0.8.1-dev.N, build info e source SHA verificabili nell'interfaccia GLPI. L'implementazione del versioning e ancora P0 da sviluppare.
- Non sovrascrivere un archivio denominato 0.8.0 con codice di 0.8.1; versione nominale e byte devono concordare. Mantenere alias MDO_Dev_Latest.zip solo dopo CI positiva.
- Codex deve creare docs/releases/<version>.md e aggiornare CHANGELOG, docs/dev/STATUS.md, README, QA e documentazione d'impatto INSIEME al commit candidato: fix, prove reali e mock distinti, checksum, installazione, rollback, rischi, prossimi passi.
- Task standalone in P1 introducono schema plugin separato: il vecchio vincolo "nessuna tabella" descrive 0.8.0, non autorizza a rappresentare falsamente una task senza Project. Le nuove tabelle richiedono migrazione idempotente e backup database/volumi.
- Branch Main resta immutato fino al collaudo dell'utente e PR esplicitamente approvata.


## Aggiornamento 0.8.1-dev.1

La pipeline della candidata testa prima di aggiornare Latest. Verifica HEAD remoto esatto, source SHA, run ID, manifest, checksum, file e prerelease; un branch avanzato conserva il precedente alias. Versionati immutabili, nessuna release/Main automatica.

Stato: NON COLLAUDATO LIVE. Note: [0.8.1-dev.1](releases/0.8.1-dev.1.md).

## Aggiornamento 0.8.1-dev.2

0.8.1-dev.1 pubblicata dal source 7e3b93a dopo CI 40090042032 PASS; il publisher
4f5ed1c è distinto dal commit testato. 0.8.1-dev.2 segue lo stesso gate con
release notes/code nello stesso commit, poi record source/build/UTC/SHA512
generato dalla CI. Nome immutabile nuovo, alias solo dopo verde + controllo HEAD.
Nessun overwrite del vecchio 0.8.0, merge Main, PR o deploy automatico.

Anche con CI verde la migrazione e i bug UI restano NON COLLAUDATO LIVE finché
l'utente non collauda staging e approva. Il nuovo schema richiede backup prima
che l'operatore esegua l'upgrade GLPI. Candidato Dev non equivale a release stabile.

Stato: NON COLLAUDATO LIVE. [Note complete](releases/0.8.1-dev.2.md).

## Aggiornamento MDO 0.8.1-dev.3 (25 ottobre 2026)

Per Dev 0.8.1-dev.3: bump prerelease in setup.php, nuova regressione redirect, note di versione e documenti contestuali aggiornati nello stesso ciclo. Il publisher GitHub Actions deve pubblicare Modern_Dashboard_Overhaul_0.8.1-dev.3.zip e MDO_Dev_Latest.zip solo dopo test verdi, con SHA512 e build-info.json. Main resta invariato.

## Aggiornamento 0.8.1-dev.4 (25 ottobre 2026)

0.8.1-dev.4 conserva ZIP dev.N immutabili. Se successivamente cambia solo la documentazione/manifest generato dal publisher, il job GitHub Actions completa con PASS e lascia ZIP/Latest intatti; altrimenti, se sorgenti PHP/CSS/JS/tool cambiano senza incremento prerelease, la pubblicazione resta bloccata. Main non si aggiorna automaticamente.

## Aggiornamento 0.8.1-dev.5

Nuova versione 0.8.1-dev.5 per modifiche PHP al Kanban. Le prove prima del fix comprendono un vero MySQL 1054 nel GLPI dell'utente e un Access Denied Standalone. La CI verifica sintassi, regressioni, note e ZIP unica; il publisher crea checksum e metadata solo dopo esito positivo. Dopo l'installazione la board deve essere collaudata di nuovo. Non promuovere a Main.


## 0.8.1-dev.6 — Aggiornamento documentale

MDO Tasks Workspace: vista unificata ProjectTask/StandaloneTask, Kanban con aggiornamento ACL, storico completo delle annotazioni e allegati MDO privati; schema 2 e relative verifiche di sicurezza. Vedere docs/releases/0.8.1-dev.6.md, QA.md e docs/dev/STATUS.md per test, rischi, rollback e prossimi passi. NON COLLAUDATO LIVE.

## 0.8.1-dev.7 — allineamento

Per dev.7 la ZIP include form unico e test: verificare version 0.8.1-dev.7, changelog, QA, note, release manifest/build-info e checksum; la pubblicazione su Dev è condizionata a PHP/Playwright e ai gate esistenti, Main invariato.


## 0.8.1-dev.8 — stabilizzazione Tasks UX

Questa candidata corregge la ripetizione dei trascinamenti del Kanban con token CSRF GLPI11 rinnovato, aggiunge la card ghost e la colonna di drop evidenziata, e consente il soft-delete da lista combinata soltanto se l'item ha il diritto DELETE. Modulo StandaloneTask conservato, senza nuove tabelle o migrazioni. CI automatica non sostituisce test sul GLPI reale; Main invariato. Dettagli e rollback in `docs/releases/0.8.1-dev.8.md`.


## 0.8.1-dev.9 — Kanban, stati e contatori

Nuova candidata di correzione del collaudo: catalogo completo ProjectState GLPI, comprese colonne a zero task; per ProjectTask spostamento autorizzato su stato nativo, nella vista All stage To do/Doing/Done per entrambi i tipi; aggiornamento immediato dei contatori sulla risposta POST senza reload. Nessuna migrazione, Main invariato. Backup coordinato database/plugin/config/storage privato prima della verifica su GLPI reale. **NON COLLAUDATO LIVE.** Vedere `docs/releases/0.8.1-dev.9.md` e backlog MDO-TASK-016/017/018.


## 0.8.1-dev.10 — Project Kanban native stage/defaults

Candidato UX: Project raggruppa per stati GLPI (non %), All Kanban apre come default, mapping nativo GLPI core ProjectState per drag di ProjectTask con avanzamento automatico, senza toccare percent_done. Card compatte, dettagli in form e diario. Non introduce nuove tabelle. **NON COLLAUDATO LIVE**. Test e rollback in `docs/releases/0.8.1-dev.10.md`.

## 0.8.1-dev.11 — allineamento release e CI (27 ottobre 2026)

La candidata di sviluppo 0.8.1-dev.11 corregge la vista Kanban Project: le colonne seguono gli stati GLPI e non la percentuale di completamento, anche con URL legacy `group=progress`. Nella vista All, se non esiste una mappatura nativa univoca per le tre fasi condivise, le attività Project utilizzano colonne ProjectState proprie e quelle Independent conservano le loro fasi; i movimenti restano vincolati al tipo e ai permessi GLPI. Il codice delle nuove tabelle diario/allegati usa `TIMESTAMP` per `date_creation`; gli impianti esistenti richiedono verifica amministrativa e migrazione GLPI separata, dopo backup coordinato DB, plugin e storage. **NON COLLAUDATO LIVE**: i test fixture non certificano il server reale. ZIP immutabile e SHA512 soltanto dopo CI completa e pubblicazione verificata; Main invariata. Per dettagli, prove e rollback vedere `docs/releases/0.8.1-dev.11.md`.
