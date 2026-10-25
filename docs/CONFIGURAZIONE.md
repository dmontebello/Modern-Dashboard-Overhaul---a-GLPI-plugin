# Configurazione — Modern Dashboard Overhaul 0.8.1-dev.2

> **Corrente 0.8.1-dev.2:** vedere la sezione aggiornata in fondo; le descrizioni precedenti restano riferimento storico delle versioni indicate.

## Dove si trova

**GLPI → Configurazione → Plugin → Modern Dashboard Overhaul → Configura** (chiave inglese). Richiede `config:UPDATE`; un profilo senza questo diritto non deve poter aprire o salvare la pagina.

## Le otto opzioni

| Opzione in GUI | Chiave interna | Predefinito | Effetto |
| --- | --- | --- | --- |
| **Dashboard Restyling** | `dashboard_restyling` | Attivo | Aspetto dei widget nativi, responsive e tema coerente con il toggle dashboard |
| Compatta i filtri vuoti | `compact_empty_filters` | Attivo | Libera lo spazio solo quando nessun filtro è presente e non si è in modifica; richiede Restyling |
| Dashboard affiancate | `dashboard_tabs` | Attivo | Tab al posto del dropdown, con scorrimento orizzontale; mantiene il `+` originale; richiede Restyling |
| Testi dei grafici | `chart_text` | Attivo | Contrasto/dimensioni dei testi dei grafici supportati; richiede Restyling |
| **Widget RSS** | `rss_widgets` | Attivo | Registra i widget dei feed RSS visibili, indipendentemente da Restyling |
| **Tasks Workspace** | `task_workspace` | Spento | Assistance → Tasks, ricerca ProjectTask nativa e board read-only |
| Attività indipendenti | `tasks_standalone` | Spento | Sotto-opzione Tasks Workspace; richiede schema e diritti dedicati |
| **Kanban Restyling** | `kanban_restyling` | Spento | Skin del Kanban Vue GLPI, indipendente dal workspace |

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
        ├── chart_text
        ├── task_workspace
        ├── tasks_standalone
        └── kanban_restyling
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

## 0.8.0 — Configurazione Tasks Workspace

Due interruttori indipendenti, spenti per default: `task_workspace` per aggiungere Assistance → Tasks (elenco nativo GLPI / Kanban read-only), `kanban_restyling` per il CSS del Kanban Vue nativo. Entrambi sono salvati nel contesto `plugin:mdoverhaul` in `glpi_configs`. Disabilitare Tasks rimuove la voce di menu e disabilita il controller; non elimina alcuna ProjectTask. Dopo il cambio, rigenerare la sessione/menu se necessario. Il modulo non altera ticket o request.

Nel workspace, **Nuova task** propone i progetti leggibili e modificabili e apre
il form GLPI con il progetto scelto. L'elenco conserva ricerca, colonne,
ordinamento e paginazione nativi. Il Kanban ha filtri propri per testo, progetto,
stato, completamento e scadenza, nel pannello richiudibile **Filtri e ordinamento**.
I filtri delle due viste restano separati; le preferenze della board viaggiano
nei link quando si cambia vista. Una board con progetto selezionato offre anche
il collegamento al suo **Kanban nativo**, che mantiene il drag & drop di GLPI.

Il raggruppamento predefinito usa lo **stato GLPI**. Quello per avanzamento mostra
**0%, 1–99%, 100%**: non è uno Stage manuale. Scadenza prevista e fine effettiva
sono due date native distinte. Le card aprono i form GLPI per ogni modifica.

La board verifica al massimo 1000 candidati recenti nelle entità attive prima
delle ACL individuali: se il campione è troncato, conteggi e filtri sono indicati
come **parziali**. Per trovare anche le attività più vecchie usare l'elenco nativo.
Le decisioni che richiedono dati aggiuntivi sono descritte in
[Tasks: decisioni aperte](specs/TASKS_OPEN_DECISIONS.md).


## Aggiornamento 0.8.1-dev.1

Settings mostra versione, canale Dev, commit sorgente testato, build ID e data UTC. I colori sono locali e riconoscono data-glpi-theme-dark. Sette flag invariati; provenienza assente mostrata come build non generata.

Stato: NON COLLAUDATO LIVE. Note: [0.8.1-dev.1](releases/0.8.1-dev.1.md).

## Aggiornamento 0.8.1-dev.2

Otto flag, i precedenti sette preservati. tasks_standalone è opt-in e sotto
il padre task_workspace; kanban_restyling resta indipendente. Schema e diritti
non vengono creati da Settings: prima upgrade amministrativo, poi profili.

Configurazione: Profili → profilo centrale → scheda MDO attività indipendenti:
READALL per tutte nelle entità attive, READMY per proprie/assegnate a utente o
gruppo, CREATE, UPDATE, DELETE. Nuovi diritti inizialmente zero per ogni profilo.
Dare READMY/READALL prima di usare il workspace; scritture solo nello scope letto.
Rifare login dopo modifiche ai diritti. Super-Admin non riceve un grant MDO
implicito. Attivare Tasks Workspace + Attività indipendenti; OFF conserva dati.

Nuova task propone tipo indipendente o progetto secondo i diritti. Indipendente
non richiede progetto; progetto usa esclusivamente form e ACL ProjectTask GLPI.
Elenco e Kanban separano i tipi e i relativi filtri. Nessun DnD globale di scrittura.

Stato: NON COLLAUDATO LIVE. [Note complete](releases/0.8.1-dev.2.md).

## Aggiornamento MDO 0.8.1-dev.3 (25 ottobre 2026)

La candidata 0.8.1-dev.3 conserva gli otto switch della dev.2: task_workspace controlla il menu Tasks, tasks_standalone abilita le autonome solo con migrazione e diritti profilo predisposti. Il fix del redirect delle ProjectTask non richiede ulteriori opzioni e non modifica le configurazioni salvate.

## Aggiornamento 0.8.1-dev.4 (25 ottobre 2026)

La candidata 0.8.1-dev.4 mantiene gli otto flag. StandaloneTask richiede task_workspace + tasks_standalone attivi, schema 1 pronto e diritti profilo (READMY/READALL per visualizzare, CREATE per creare e UPDATE/DELETE per modificare/cestinare). Lo Stato della ricerca usa il Dropdown nativo in modalità stringa.
