# Note di versione — Modern Dashboard Overhaul 0.8.1-dev.2

> **Corrente 0.8.1-dev.2:** vedere la sezione aggiornata in fondo; le descrizioni precedenti restano riferimento storico delle versioni indicate.

**Stato:** Pilot interno verso la 1.0.0 · **Autore:** Davide Montebello · **ID:** `mdoverhaul`.

## Cosa cambia rispetto alla 0.6.0

- **Meno CPU e meno timer nel browser:** retry grafici ECharts raggruppati per dashboard, observer su meno nodi e per meno attributi, niente osservazione globale del DOM nelle pagine GLPI senza dashboard, dimensioni contatori calcolate soltanto quando servono.
- **Grafici più fluidi quando li ridimensioni:** il testo si aggiorna per incrementi di 32 pixel, mantenendo invariati dati, palette delle serie e click nativi.
- **Correzione proporzioni mobile:** toolbar e tab non si sovrappongono alle schede su smartphone; oltre 40 tab restano scorrevoli col pulsante `+` originale sempre accessibile.
- **RSS più robusti:** titoli lunghi limitati; pubblicazioni distinte che citano la stessa CVE non sono fuse impropriamente. Chiavi `arensecurity_*` inalterate per i widget GLPI già salvati.
- **Sicurezza:** controllo dei privilegi, link/HTML non fidati e nuovo script per segnalare versioni PHP probabilmente esposte alle CVE critiche pubblicate il 21 ottobre 2026.
- **Test riproducibili:** fixture browser locale, unit test PHP isolati e benchmark DOM senza dati GLPI reali.

## Misure e limiti

Nel fixture con 62 grafici il numero di timer di retry pianificati è passato **da 186 a 3 (−98,4%)**. Questo numero riguarda *soltanto i timer aggiuntivi del plugin*, non la memoria totale, la rete o il tempo di caricamento di GLPI.

Sono passate **80 verifiche browser** su 4 risoluzioni e **38 verifiche PHP** con classi GLPI simulate. Non è stato possibile interagire con la tua istanza GLPI: creazione/eliminazione dashboard, ACL reali, CSRF e performance server devono essere convalidati prima della 1.0.

## Compatibilità

Rimane la directory `mdoverhaul`, GLPI 11.0.x. Le impostazioni 0.6.0 in `glpi_configs` rimangono valide. Nessuna migrazione DB, nuovi permessi, tabelle o attività in background.

**Importante:** le CVE PHP non si correggono aggiornando questo plugin. Verifica immediatamente la versione runtime del container con `tools/verifica-runtime-php.sh` e applica gli aggiornamenti della distribuzione/immagine GLPI secondo la documentazione vendor.

Vedi [CHANGELOG](../CHANGELOG.md), [QA](../QA.md) e [UPDATE](../UPDATE.md).

## 0.8.0 (branch di sviluppo)

Proposta integrazione Tasks Workspace nel plugin unico `mdoverhaul`: menu Assistance → Tasks, elenco ProjectTask nativo, board read-only e Skin Kanban nativo indipendente dai moduli dashboard/RSS. Opzioni disattivate per default. Nessuna migrazione task o modifica ai ticket. Test su GLPI reale ancora necessari prima dell'installazione.

**Distribuzione 0.8.0:** unico ZIP installabile con i moduli Dashboard, RSS, Tasks e Kanban e sette opzioni Configura. Include correzione sperimentale mini-dashboard Ticket; da confermare su GLPI reale. Scaricare ZIP + `.zip.sha512` dal branch GitHub, non dall'archivio sorgenti automatico.


## Aggiornamento 0.8.1-dev.1

Candidata P0: versione e provenienza, contrasto Settings, creazione/layout Tasks, misura mini Ticket, toolbar e legenda specifica. Le regressioni live restano aperte. Vedere docs/releases per note complete e stato.

Stato: NON COLLAUDATO LIVE. Note: [0.8.1-dev.1](releases/0.8.1-dev.1.md).

## Aggiornamento 0.8.1-dev.2

Task indipendenti senza progetto con CommonDBTM, ACL e persistenza dedicate,
ottavo flag opt-in, sottoviste native, CRUD/cestino/restore e retention. Backend,
UI e idempotenza sono testati con doubles, NON COLLAUDATO LIVE. I fix P0 non
sono certificati risolti sullo staging dell'utente. Installazione/schema e rischi
sono descritti nelle note complete della candidata corrente.

Stato: NON COLLAUDATO LIVE. [Note complete](releases/0.8.1-dev.2.md).

## Aggiornamento MDO 0.8.1-dev.3 (25 ottobre 2026)

Nota 0.8.1-dev.3 (Dev): corretta la ricarica della schermata di scelta progetto dopo Continua, causata dal catch(Throwable) della RedirectException emessa da Html::redirect in GLPI 11. Test PHP aggiunto per riprodurre l'eccezione. NON COLLAUDATO LIVE: il flusso Select2->form->Salva deve essere riprovato su staging.

## Aggiornamento 0.8.1-dev.4 (25 ottobre 2026)

0.8.1-dev.4 Dev: corretta compatibilità del filtro Stato delle attività indipendenti con ricerca GLPI, con test contro output prematuro. Sistemata la pipeline Dev per commit soltanto documentali, mantenendo ZIP versionate immutabili. Nessun DB/ACL change. NON COLLAUDATO LIVE: ricerca Stato, profili ed entità.

## Aggiornamento 0.8.1-dev.5

Candidata Dev 0.8.1-dev.5: fix dell'ORDER nelle query ProjectTask, Project e StandaloneTask. Il precedente formato associativo causava MySQL 1054 (ORDER BY DESC) in GLPI 11. Aggiunto un avviso per l'admin con Tasks indipendenti attive ma prive di READMY/READALL sul profilo corrente. Nessun diritto viene concesso automaticamente e la 403 nativa resta valida. Nessuna migrazione DB; staging ancora da collaudare.


## 0.8.1-dev.6 — Aggiornamento documentale

MDO Tasks Workspace: vista unificata ProjectTask/StandaloneTask, Kanban con aggiornamento ACL, storico completo delle annotazioni e allegati MDO privati; schema 2 e relative verifiche di sicurezza. Vedere docs/releases/0.8.1-dev.6.md, QA.md e docs/dev/STATUS.md per test, rischi, rollback e prossimi passi. NON COLLAUDATO LIVE.

## 0.8.1-dev.7 — allineamento

Dev.7 introduce una nuova Task da un solo form. Con progetto valido crea ProjectTask nativa; senza progetto StandaloneTask; la scadenza viene mappata secondo il modello. Sicurezza e regressioni in docs/releases/0.8.1-dev.7.md. NON COLLAUDATO LIVE.


## 0.8.1-dev.8 — stabilizzazione Tasks UX

Questa candidata corregge la ripetizione dei trascinamenti del Kanban con token CSRF GLPI11 rinnovato, aggiunge la card ghost e la colonna di drop evidenziata, e consente il soft-delete da lista combinata soltanto se l'item ha il diritto DELETE. Modulo StandaloneTask conservato, senza nuove tabelle o migrazioni. CI automatica non sostituisce test sul GLPI reale; Main invariato. Dettagli e rollback in `docs/releases/0.8.1-dev.8.md`.


## 0.8.1-dev.9 — Kanban, stati e contatori

Nuova candidata di correzione del collaudo: catalogo completo ProjectState GLPI, comprese colonne a zero task; per ProjectTask spostamento autorizzato su stato nativo, nella vista All stage To do/Doing/Done per entrambi i tipi; aggiornamento immediato dei contatori sulla risposta POST senza reload. Nessuna migrazione, Main invariato. Backup coordinato database/plugin/config/storage privato prima della verifica su GLPI reale. **NON COLLAUDATO LIVE.** Vedere `docs/releases/0.8.1-dev.9.md` e backlog MDO-TASK-016/017/018.


## 0.8.1-dev.10 — Project Kanban native stage/defaults

Candidato UX: Project raggruppa per stati GLPI (non %), All Kanban apre come default, mapping nativo GLPI core ProjectState per drag di ProjectTask con avanzamento automatico, senza toccare percent_done. Card compatte, dettagli in form e diario. Non introduce nuove tabelle. **NON COLLAUDATO LIVE**. Test e rollback in `docs/releases/0.8.1-dev.10.md`.
