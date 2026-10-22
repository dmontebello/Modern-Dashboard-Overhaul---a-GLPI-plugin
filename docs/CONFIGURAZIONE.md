# Configurazione — Modern Dashboard Overhaul 0.7.0

## Dove si trova

**GLPI → Configurazione → Plugin → Modern Dashboard Overhaul → Configura** (chiave inglese). Richiede `config:UPDATE`; un profilo senza questo diritto non deve poter aprire o salvare la pagina.

## Le cinque opzioni

| Opzione in GUI | Chiave interna | Predefinito | Effetto |
| --- | --- | --- | --- |
| **Dashboard Restyling** | `dashboard_restyling` | Attivo | Aspetto dei widget nativi, responsive e tema coerente con il toggle dashboard |
| Compatta i filtri vuoti | `compact_empty_filters` | Attivo | Libera lo spazio solo quando nessun filtro è presente e non si è in modifica; richiede Restyling |
| Dashboard affiancate | `dashboard_tabs` | Attivo | Tab al posto del dropdown, con scorrimento orizzontale; mantiene il `+` originale; richiede Restyling |
| Testi dei grafici | `chart_text` | Attivo | Contrasto/dimensioni dei testi dei grafici supportati; richiede Restyling |
| **Widget RSS** | `rss_widgets` | Attivo | Registra i widget dei feed RSS visibili, indipendentemente da Restyling |

Le tre opzioni di dettaglio hanno effetto **solo quando Dashboard Restyling è attivo**. La schermata può restare accessibile anche se entrambi i moduli sono spenti.

## Procedura pratica

1. Apri **Configura** da un account autorizzato.
2. Attiva/disattiva i flag desiderati e premi **Salva impostazioni**.
3. Ricarica la dashboard (se il browser mantiene vecchi CSS/JS, `Ctrl+F5`).
4. Non serve riavviare Docker dopo un semplice salvataggio delle impostazioni.

### Risultato quando si disattiva un modulo

- **Solo Restyling OFF:** tornano aspetto e selettore originali GLPI; widget RSS ancora disponibili.
- **Solo RSS OFF:** la dashboard mantiene il restyling, ma i widget RSS possono risultare temporaneamente non disponibili. **Non entrare in modifica e non salvare la dashboard con widget RSS mancanti**. Riabilitare RSS prima di modificarla.
- **Entrambi OFF:** plugin ancora configurabile ma senza funzioni di visualizzazione attive. Non serve disinstallare.

## Dove vengono salvate

```text
Database MySQL GLPI
└── glpi_configs (tabella nativa GLPI)
    └── context = plugin:mdoverhaul
        ├── dashboard_restyling
        ├── rss_widgets
        ├── compact_empty_filters
        ├── dashboard_tabs
        └── chart_text
```

L'implementazione usa le API GLPI `Config::getConfigurationValues` e `setConfigurationValues`. Il form valida valori booleani `0`/`1` e include il token CSRF. Non crea tabelle proprie e **non scrive nella directory del plugin**. Il mount Docker `:ro` è pertanto compatibile.

Per installazioni precedenti alla 0.6, i valori `config/ui.php` e un eventuale `config/ui.local.php` sono usati **soltanto finché** non esiste il corrispondente valore nativo salvato. Non modificare i file distribuiti per personalizzare le opzioni: il manifest SHA-512 li controlla.

## RSS: configurazione del contenuto

I feed **si creano e si gestiscono in GLPI**, non nella pagina di configurazione del plugin: **Strumenti → Feed RSS/RSS Feed**.

- Feed attivo e visibile all'utente → scheda disponibile nell'editor dashboard.
- Per cambiare quanti elementi si visualizzano, scegliere il tipo RSS **5/10/15/20** nell'editor del widget. Il default è **10**.
- Un feed può fornire meno articoli del limite selezionato. La data/ora compare solo se fornita o desumibile correttamente dai campi RSS/Atom.
- Colore/accento e dimensioni della scheda dipendono dalle normali opzioni dashboard e dal tema; non è un indicatore di severità.

## Interventi che richiedono invece riavvio

La semplice **configurazione GUI** non richiede restart. Dopo la **sostituzione fisica dei file PHP/CSS/JS** della release, seguire [INSTALLAZIONE](INSTALLAZIONE.md) o [UPDATE](../UPDATE.md), compreso riavvio del container applicativo se necessario per OPcache.
