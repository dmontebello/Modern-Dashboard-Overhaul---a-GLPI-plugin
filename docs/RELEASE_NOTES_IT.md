# Note di versione — Modern Dashboard Overhaul 0.7.0

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
