# Changelog — Modern Dashboard Overhaul

Le versioni sono cumulative: i cambiamenti più recenti sono riportati per primi. **Un rebuild documentale della stessa versione non implica una modifica del software in esecuzione.**

## 0.7.0 — Robustezza, sicurezza e prestazioni (2026-10-22)

### Prestazioni
- Coalescenza dei retry per grafici ECharts: **un gruppo di tre timer per dashboard** invece di tre per ciascun grafico; benchmark isolato con 62 grafici: **186 → 3 timer** (−98,4% delle schedulazioni di retry in quel caso). Non è una misura del tempo totale di GLPI.
- Observer DOM ridotto: niente osservazione globale sulle pagine senza dashboard, meno attributi monitorati e ResizeObserver soltanto sulle schede che richiedono adattamenti.
- Cache per richiesta delle impostazioni GLPI (`Settings::get()`), invalidata al salvataggio; contatori ricalcolati soltanto quando cambiano dimensioni o cifre.
- Dimensioni dei testi ECharts aggiornate per fasce di 32 px durante i ridimensionamenti, evitando riscritture a ogni pixel.

### Sicurezza e stabilità
- Duplicati RSS identificati da GUID, URL o titolo, **non più soltanto dall'eventuale CVE citata**: notizie differenti sulla stessa CVE restano visibili.
- Limitata la lunghezza dei titoli RSS normalizzati a 1024 caratteri Unicode; confermati URL HTTP(S) validi, escaping HTML e privilegi GLPI.
- Aggiunto controllo non invasivo delle versioni PHP corrette dopo le CVE critiche **CVE-2026-109618 / CVE-2026-109619**. Le correzioni del runtime devono essere applicate separatamente all'immagine GLPI.
- Corretto il conflitto di specificità CSS che faceva sovrapporre tab e widget sui viewport più stretti; RSS più coerenti con il toggle dashboard light/dark.
- Nessun cambiamento ai dati nativi, agli ID RSS persistenti o allo schema database.

### Collaudo
- **79** verifiche UI in Chromium headless su fixture locale a 1440/1024/780/390 px; **38** test PHP isolati con mock di GLPI.
- Prova di stress JS comparativa 0.6 → 0.7, test di sintassi, manifest SHA-512 e verifica documentale.
- **Non testata in un'istanza GLPI reale in questo ambiente**: resta Pilot, non stabile.

## 0.6.0 — Pilot interno (2026-10-21)

### Funzioni
- Aggiunta la pagina **Configura** nell'elenco plugin GLPI, accessibile con diritto `config:UPDATE`.
- Separati i moduli **Dashboard Restyling** e **Widget RSS**, con tre sotto-opzioni Restyling (filtri vuoti, dashboard affiancate, testo grafici).
- Persistenza di cinque flag in `glpi_configs`, contesto `plugin:mdoverhaul`, senza tabelle proprie e senza scritture nella cartella plugin.
- Retrocompatibilità con i valori 0.5 in `config/ui.php` ed eventuale `config/ui.local.php`, usati come fallback finché l'opzione non viene salvata dalla GUI.

### Correzioni
- Migliorato lo stato vuoto dei feed RSS e dei grafici senza dati.
- Usato il fuso orario utente/GLPI, senza imposizione di una località specifica.
- Nessuno script di manipolazione dashboard registrato quando Restyling è disattivato; RSS rimane indipendente.
- Conservati ID tecnico `mdoverhaul` e identificativi storici delle schede RSS.

### Rebuild SOLO documentale — `0.6.0_Documented_Pilot` (2026-10-21)
- Riscritti README e manuali, inclusi architettura, installazione/rollback, mappa, configurazione, sicurezza, matrice test e processo release.
- Inclusi script facoltativi **per il manutentore** che verificano documentazione/versione e generano ZIP + SHA-512.
- **Nessuna modifica al PHP/CSS/JavaScript runtime della precedente 0.6.0.** Non è necessario reinstallare se la 0.6.0 funziona già.

## 0.5.0 — Pilot interno
- Migrazione dal vecchio plugin `arensecurity` al plugin tecnico `mdoverhaul`, autore Davide Montebello.
- Correzione centratura dei KPI piccoli e rimozione della scrollbar vicino ai numeri.
- Mantenimento delle vecchie chiavi dei widget RSS salvati.
- Aggiunti SHA-512 e script di controllo sintassi con esiti a colori.

## 0.4.0 — Pilot interno
- Tipografia dei KPI adattiva, palette chiara/scura e dashboard affiancate con scorrimento orizzontale.
- Generalizzazione del modulo RSS, senza dipendenza dai feed di sicurezza.

## 0.3.0 — Pilot interno
- Primo restyling dashboard GLPI 11, compattazione filtri vuoti e CSS modulare.
