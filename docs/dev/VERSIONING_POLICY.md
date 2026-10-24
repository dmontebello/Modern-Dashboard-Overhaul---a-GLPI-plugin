# MDO: prerelease, build ID, ZIP e documentazione per ogni Dev

Valutazione iniziale 2026-10-24 (storica): REQUISITO / NON ANCORA IMPLEMENTATO NEL CODICE.
Riferimento: [specifica tecnica](../specs/MDO_0_8_1_DEV_WORKPLAN.md) e [stato corrente](STATUS.md).

## Due branch permanenti

- Dev: sviluppo e stage, versione prerelease e ZIP solo dopo CI positiva.
- Main: produzione/rilascio approvato, merge Dev -> Main manuale con PR dopo collaudo.
- Plugin folder e ID sempre mdoverhaul; due build non sono due plugin separati.

## Versioni e provenienza

- Primo candidato P0: 0.8.1-dev.1. Successivi: 0.8.1-dev.2, 0.8.1-dev.3, ecc. (non riutilizzare stesso prerelease per codice funzionale differente).
- Prima release stabile eventuale: 0.8.1 senza suffix, solo dopo stage e approvazione.
- Identificare ogni rebuild, anche solo documentale, con source commit, Actions run/build ID e built_at UTC visibili nei Settings anche quando il numero SemVer è lo stesso.
- Versione unica setup.php -> GLPI plugin manager -> Settings; nessuna stringa fissa 0.8.0 o VERSION JS 0.7.0. Il commit del bot publisher è diverso dal commit sorgente realmente testato; distinguerli.
- Build-info.json interno alla ZIP con versione, canale Dev/Main, source SHA, run ID e data UTC; nessun segreto. Manifest e checksum verificati anche per metadata generati. Adeguare regex e script Python e YAML CI a versioni prerelease.
- Invalidare cache browser per nuovi file CSS/JS e verificare asset HTTP consegnati da GLPI. Non usare query string cache-bust fittizie che GLPI ignora.

## Pacchetti e link

Su Dev creare un archivio nominato con versione esatta, ad esempio Modern_Dashboard_Overhaul_0.8.1-dev.1.zip (con .zip.sha512), e un alias stabile MDO_Dev_Latest.zip (con .zip.sha512). Quest'ultimo sarà scaricabile da:

    https://raw.githubusercontent.com/dmontebello/Modern-Dashboard-Overhaul---a-GLPI-plugin/Dev/MDO_Dev_Latest.zip

**Questo nuovo URL è PROPOSTO e non esiste ancora.** Ad oggi il link reale punta a Modern_Dashboard_Overhaul_0.8.0_Pilot.zip. Non sovrascrivere quel file per fingere che sia 0.8.1.

Gate: test PHP/JS/UI/documentazione -> build un plugin mdoverhaul/ -> manifest e sha512sum -c -> confronta HEAD remoto con SHA testato -> pubblica ZIP versionato, checksum e Latest su DEV soltanto. Se test fallisce o branch si sposta, non pubblicare build obsoleta. Main invariato. Non generare trigger CI ricorsivi col commit del bot.

## Documentazione per ogni versione e ripresa del lavoro

Nel commit con ogni candidato funzionale Codex deve aggiornare almeno:
- CHANGELOG.md (append history); docs/releases/<version>.md (fix, test, limiti, checksum, rollback, next steps);
- docs/dev/STATUS.md (tabella issue per stato, current HEAD, CI e azioni successive);
- README.md, QA.md, docs/TEST_MATRIX.md per prove reali e non eseguite;
- docs/ARCHITETTURA.md, docs/CONFIGURAZIONE.md, docs/INSTALLAZIONE.md, UPDATE.md, docs/SECURITY.md e docs/INTEGRATION.md se cambia schema, ACL, asset o flusso.

Evidenziare sempre: IMPLEMENTATO, TESTATO CON DOUBLES, TESTATO SU STAGING GLPI, NON TESTATO, BLOCCATO, PROPOSTO. Nessuna affermazione falsa di test reali. L'handoff non deve dipendere dalla memoria della chat. Lo stato attuale 0.8.0 resta storico anche dopo l'introduzione della 0.8.1.


## Implementazione da 0.8.1-dev.1

Il tooling ora gestisce SemVer prerelease e build-info.json generato: la versione
rimane unica in setup.php. La SHA512SUMS del checkout copre i sorgenti; quella
interna alla ZIP copre anche metadata generati, senza saltarne gli hash.
La workflow testa il codice/documentazione, poi costruisce e verifica il pacchetto
con source SHA/build ID e pubblica solo se origin/Dev è ancora lo SHA testato.
Il nome versionato non viene sovrascritto; Latest cambia solo dopo questi gate.
Il publisher registra checksum e provenienza in docs/releases/<version>.build.json
con un commit bot [skip ci]. Questi record generati non sono contenuto sorgente
dei pacchetti successivi. I documenti storici sopra restano tali.


## Corrente: 0.8.1-dev.2

Il meccanismo P0 ha già pubblicato 0.8.1-dev.1 dopo la CI verde del source SHA
7e3b93a. P1 incrementa a 0.8.1-dev.2, con proprie note e registro generato.
Il gate controlla anche la dichiarazione corrente di README contro setup.php;
la presenza di una versione soltanto in una sezione storica non è sufficiente.
Pacchetti versionati immutabili e vecchio 0.8.0 conservati, alias aggiornata solo
dopo test e verifica HEAD; non confondere source commit e commit del publisher.
