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
