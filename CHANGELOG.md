# Changelog — Modern Dashboard Overhaul

## 0.8.1-dev.4 — Tasks Search nativa e CI immutabile (2026-10-25)

- Il filtro Stato di StandaloneTask restituisce ora l'HTML del Dropdown nativo con display=false, anziché stamparlo fuori dalla ricerca.
- La pipeline mantiene immutabili ZIP versionate; salta soltanto i successivi commit esclusivamente documentali o rebuild del medesimo sorgente. Modificare codice senza bump è sempre bloccato.
- Regressioni PHP e Python dedicate. Nessuna modifica DB/ACL, nessun secondo plugin, nessun intervento su Main. **NON COLLAUDATO LIVE**.
- [Note della candidata](docs/releases/0.8.1-dev.4.md).

## 0.8.1-dev.3 — redirect GLPI nativo delle ProjectTask (2026-10-25)

- Corretto il catch(Throwable) che intercettava l'eccezione lanciata da Html::redirect() in GLPI 11 e faceva ricaricare la scelta progetto.
- Aggiunto test con double che lancia la stessa classe di errore semantico; nessuna modifica ai dati, schema, permessi o moduli Dashboard/RSS.
- **NON COLLAUDATO LIVE**: occorre verificare Form ProjectTask/Select2 in staging e il Salva successivo. Main invariato.
- [Note 0.8.1-dev.3](docs/releases/0.8.1-dev.3.md).

## 0.8.1-dev.2 — attività indipendenti (2026-10-24)

- Nuovo oggetto CommonDBTM nel plugin, schema versione 1 e upgrade idempotente.
- ACL di profilo/entità/proprietario/assegnatario/gruppo e hook Search pubblico.
- CRUD con middleware CSRF GLPI, nuova scelta tipo, sottoviste native separate,
  card con tipo/ID, nessuna percentuale inventata per standalone.
- Ottava sotto-opzione opt-in; dati conservati con OFF, cestino e uninstall.
- Backup/rollback, test negativi/CRUD/schema simulati e documentazione aggiornati.
- NON COLLAUDATO LIVE, inclusa la migrazione reale. Main e ZIP legacy invariati.

[Note complete](docs/releases/0.8.1-dev.2.md).

## 0.8.1-dev.1 — pianificata, NON ANCORA IMPLEMENTATA (2026-10-24)

- L'utente segnala su GLPI reale Settings illeggibili in dark mode, il pulsante Continua che non apre il form Task nativo, spaziature della scelta progetto, mini KPI Ticket, toolbar e legenda ECharts.
- Richiesti prerelease numerati 0.8.1-dev.N, metadata SHA e build ID visibili, ZIP versionati con alias Latest Dev e documentazione aggiornata ad ogni candidatura.
- Richiesta una successiva build (almeno 0.8.1-dev.2) con task realmente senza progetto. Serve un oggetto GLPI-integrato del plugin, ACL e migrazione; **nessuna tabella nuova e stata aggiunta con questa voce del Changelog**.
- [Specifica 0.8.1 Dev](docs/specs/MDO_0_8_1_DEV_WORKPLAN.md) e [stato](docs/dev/STATUS.md). CI 0.8.0 con mock verde, ma regressioni staging ancora APERTE.

Le versioni sono cumulative: i cambiamenti più recenti sono riportati per primi. **Un rebuild documentale della stessa versione non implica una modifica del software in esecuzione.**

## 0.8.1-dev.1 — candidata P0 (2026-10-24)

Versionamento e build verificabile, contrasto Settings, creazione ProjectTask
con ACL native e layout, contenimento mini Ticket, toolbar e legenda ticket_status.
Test isolati e gate CI/documentazione. NON COLLAUDATO LIVE: regressioni segnalate
ancora aperte. Nessuna migrazione o pubblicazione Main.
Dettagli: [release 0.8.1-dev.1](docs/releases/0.8.1-dev.1.md).

## Sviluppo P0/P1 — non pubblicato (2026-10-24)

- Corretto il modello degli stati GLPI 11 (`projectstates_id` / `glpi_projectstates`),
  il target della ricerca nativa e gli URL per installazioni in sottocartella.
- Rafforzate ACL su task/team/progetti, creazione con progetto esplicito e
  isolamento delle entità. Menu limitato agli utenti autorizzati.
- Filtri scadenza/completamento combinabili, ordinamento, date native distinte,
  stati vuoti/errore, navigazione da tastiera e layout responsive light/dark.
- Stato GLPI come raggruppamento iniziale; percentuali senza falsa equivalenza
  con uno Stage manuale. Collegamento al Kanban nativo del progetto.
- Test isolati PHP/UI ampliati e CI aggiornata; nessun bump della versione 0.8.0,
  modifica al core, nuova persistenza, release o deploy.

## 0.8.0 — Tasks Workspace configurabile (2026-10-23)

- Aggiunte le impostazioni indipendenti e opt-in `task_workspace` e `kanban_restyling` nel plugin principale.
- Nuova pagina Assistance → Tasks per le ProjectTask native: lista GLPI e Kanban read-only, permessi per elemento.
- CSS confinato al Kanban nativo; nessuna modifica core, dati o TicketTask.
- Stato: pilota su branch, test integrazione GLPI live da eseguire.

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
