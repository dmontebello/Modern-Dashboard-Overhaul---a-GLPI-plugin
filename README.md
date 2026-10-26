# Modern Dashboard Overhaul

> **NUOVE CHAT CTO / DEVELOPER / CODEX:** punto di ingresso operativo **[START HERE — MDO](docs/project/start-here.md)**. Consultare [focus corrente](docs/project/current-focus.md), [backlog con stati](docs/product/backlog.md), [roadmap](docs/roadmap.md), [decisioni](docs/product/decisions.md) e [stato implementato](docs/implementation-state.md). Lo sviluppo è su `Dev`; `Main` rimane la release pubblicata. Verificare sempre GitHub e CI sullo SHA attuale.


 > **Candidata Dev 0.8.1-dev.9:** [Note](docs/releases/0.8.1-dev.8.md) · [Stato/handoff](docs/dev/STATUS.md) · [Staging](docs/dev/STAGING_0_8_1.md). **NON COLLAUDATO LIVE**: i bug segnalati attendono il collaudo dell'utente.

**Versione:** `0.8.1-dev.9` (Dev) · **Autore:** Davide Montebello · **Licenza:** MIT
**ID tecnico/cartella:** `mdoverhaul` · **Target:** GLPI `11.0.x` (`>=11.0.0`, `<11.1.0`)

Un unico plugin GLPI con moduli indipendenti, configurabili da **Configura**: restyling dashboard, widget RSS, Tasks Workspace e restyling Kanban nativo. Non sostituisce GLPI, non modifica i dati dei suoi widget nativi e non richiede servizi aggiuntivi.

> **Stato:** 0.8.0 Pilot distribuita dal branch `Main`; lo sviluppo continua su `Dev`. Lo ZIP completo è pubblicato su GitHub, ma richiede collaudo su GLPI reale e non è ancora una release stabile.

## Cosa fa

| Modulo | Comportamento |
| --- | --- |
| **Dashboard Restyling** | Contatori più leggibili anche in schede piccole, tema chiaro/scuro comandato dal toggle nativo, grafici con testi migliorati, eliminazione dello spazio dei filtri vuoti e tab orizzontali delle dashboard. Mantiene il pulsante `+` originale e i permessi GLPI. **Non crea nuovi widget nativi.** |
| **Widget RSS** | Un widget per ogni feed GLPI attivo e visibile all'utente, con preset **5/10/15/20** articoli (10 predefiniti), data/ora se fornite dalla fonte, titoli e link sicuri. Non richiede un feed di sicurezza: funziona anche con RSS generici. |

La release Main 0.8.0 non introduce tabelle proprie. Le candidate Dev da 0.8.1-dev.2 aggiungono la sola tabella `glpi_plugin_mdoverhaul_tasks` per attività indipendenti (migrazione amministrativa GLPI), con **otto** opzioni nella configurazione nativa `glpi_configs`; niente cron, telemetria o servizi aggiuntivi.

### Dev: ZIP completo aggiornato automaticamente

La versione **di sviluppo** è scaricabile direttamente dal branch [`Dev`](https://github.com/dmontebello/Modern-Dashboard-Overhaul---a-GLPI-plugin/tree/Dev), senza aprire la sezione Artifacts. A ogni push di codice su `Dev`, GitHub Actions esegue i test e, **solo se passano**, aggiorna nella radice del branch lo ZIP completo, il file `.zip.sha512` e il manifesto `SHA512SUMS`. Un commit automatico dedicato modifica solo quei tre file. In caso di test falliti, lo ZIP rimane all'ultima build riuscita: verificare il run e il commit prima di installare.

```bash
mkdir -p /tmp/mdo-dev && cd /tmp/mdo-dev
curl -fL --retry 3 -O "https://raw.githubusercontent.com/dmontebello/Modern-Dashboard-Overhaul---a-GLPI-plugin/Dev/Modern_Dashboard_Overhaul_0.8.1-dev.5.zip"
curl -fL --retry 3 -O "https://raw.githubusercontent.com/dmontebello/Modern-Dashboard-Overhaul---a-GLPI-plugin/Dev/Modern_Dashboard_Overhaul_0.8.1-dev.5.zip.sha512"
sha512sum -c Modern_Dashboard_Overhaul_0.8.1-dev.5.zip.sha512
unzip -t Modern_Dashboard_Overhaul_0.8.1-dev.5.zip
```

**Non installare insieme** le varianti Dev e Main nella medesima istanza GLPI: sono entrambe il singolo plugin `mdoverhaul`. Usare Dev soltanto su staging, lasciando `Main` invariato fino ad approvazione, PR e merge.

### Main: 0.8.0 pubblicata (non il sorgente GitHub)

Il file `Modern_Dashboard_Overhaul_0.8.0_Pilot.zip` contiene **una cartella principale `mdoverhaul/`** con tutti i moduli (Dashboard, RSS, Tasks, Kanban) e Settings, e non richiede l'installazione intermedia 0.7.

```bash
cd /opt/glpi/plugins
curl -fL --retry 3 -o Modern_Dashboard_Overhaul_0.8.0_Pilot.zip \
  "https://raw.githubusercontent.com/dmontebello/Modern-Dashboard-Overhaul---a-GLPI-plugin/Main/Modern_Dashboard_Overhaul_0.8.0_Pilot.zip"
curl -fL --retry 3 -o Modern_Dashboard_Overhaul_0.8.0_Pilot.zip.sha512 \
  "https://raw.githubusercontent.com/dmontebello/Modern-Dashboard-Overhaul---a-GLPI-plugin/Main/Modern_Dashboard_Overhaul_0.8.0_Pilot.zip.sha512"
sha512sum -c Modern_Dashboard_Overhaul_0.8.0_Pilot.zip.sha512
unzip -t Modern_Dashboard_Overhaul_0.8.0_Pilot.zip
```

Per aggiornare l'istanza attiva con backup e rollback, vedi [UPDATE](UPDATE.md). Per rigenerare la release dalla checkout GitHub: `python3 -B tools/maintainer/package_from_repo.py`.

## Avvio rapido

1. **Installazione nuova:** estrarre lo ZIP con directory principale `mdoverhaul/` nella cartella plugin GLPI. Per Docker occorre un bind mount leggibile dal container. Vedi [Installazione](docs/INSTALLAZIONE.md).
2. **Aggiornamento:** salvare codice e backup GLPI, controllare SHA-512, estrarre in una directory temporanea e sostituire i file del plugin. Non disinstallare per aggiornare. Vedi [UPDATE](UPDATE.md).
3. In GLPI aprire **Configurazione → Plugin → Modern Dashboard Overhaul → Installa/Abilita**.
4. Da **Configura** (chiave inglese) gestire i moduli e le sotto-opzioni. Vedi [Configurazione](docs/CONFIGURAZIONE.md).
5. Verificare dashboard, switch chiaro/scuro e widget RSS. Seguire la [matrice test](docs/TEST_MATRIX.md).

**Verifica locale (sul server Docker, dopo l'estrazione):**

```bash
bash /opt/glpi/plugins/mdoverhaul/tools/verifica-tutto.sh
```

`[OK]` verde = superato, `[ERRORE]` rosso = arrestare l'installazione, `[AVVISO]` giallo = controlli incompleti (es. Node.js non disponibile). Sintassi e checksum non sostituiscono il test in GLPI.

**Sicurezza urgente (21 ottobre 2026):** sono disponibili patch per CVE-2026-109618 e CVE-2026-109619 nel runtime PHP. **Aggiornare il PHP di GLPI e verificare il SAPI web è indipendente dal plugin.** Eseguire il controllo non invasivo `bash tools/verifica-runtime-php.sh` sul server Docker e consultare [Sicurezza PHP](docs/SECURITY_PHP_2026.md). Il controllo non modifica container o database.

**Novità 0.7.0:** riduzione dei timer JavaScript per i grafici, osservatori DOM meno invasivi, cache PHP per richiesta, miglioramento del layout dei tab sugli schermi piccoli, maggiore robustezza della deduplicazione RSS e test offline automatizzati. Vedi [Note 0.7.0](docs/RELEASE_NOTES_IT.md) e [QA](QA.md).

**Attenzione:** se disabiliti **Widget RSS**, le schede RSS già salvate possono risultare non disponibili. **Non aprire/salvare in modifica quelle dashboard finché il modulo è disabilitato.** Riabilitarlo non richiede di ricreare i feed.

## Documentazione inclusa in ogni ZIP

| Documento | Dove guardare |
| --- | --- |
| [Architettura](docs/ARCHITETTURA.md) | Componenti PHP/JS/CSS, flussi e dipendenze |
| [Installazione](docs/INSTALLAZIONE.md) | Nuova installazione, Docker, upgrade, rollback, rimozione |
| [Configurazione](docs/CONFIGURAZIONE.md) | Cinque opzioni, default, permessi e persistenza |
| [Mappa cartelle](docs/MAPPA_CARTELLE.md) | ZIP, filesystem host, bind mount, container e volumi |
| [Matrice collaudi](docs/TEST_MATRIX.md) | Test minimi prima di dichiarare una versione stabile |
| [Sicurezza](docs/SECURITY.md) · [Avviso PHP](docs/SECURITY_PHP_2026.md) | Permessi, dati RSS non fidati, CSRF e CVE del runtime PHP |
| [Integrazione GLPI](docs/INTEGRATION.md) | Hook GLPI e identificativi persistenti dei widget |
| [Processo release](docs/RELEASE_PROCESS.md) | Cosa aggiornare obbligatoriamente a ogni versione |
| [Note versione](docs/RELEASE_NOTES_IT.md) | Modifiche concrete della 0.7.0 |
| [Changelog](CHANGELOG.md) · [QA](QA.md) · [UPDATE](UPDATE.md) | Storico, esiti verificati, aggiornamento operativo |

## Compatibilità e limiti

- **GLPI 11.0.x:** dichiarato; integrazione verificata su sorgente **11.0.9**. Altre versioni e installazioni pulite richiedono collaudo.
- Le dashboard e i feed RSS persistono nel database **GLPI**: non nello ZIP o nella cartella montata.
- Gli ID `arensecurity_rss*` e `arensecurity_feed_<id>` restano **intenzionalmente** nei dati dei widget per compatibilità: il plugin attivo è soltanto `mdoverhaul`; non va riattivato il precedente `arensecurity`.
- **Fuori perimetro 1.0:** CVSS, associazione feed↔asset, creazione task da CVE, scansioni vulnerabilità.
- In caso di problemi, disabilitare il modulo Restyling o il plugin e usare il backup del codice; vedere [Rollback](docs/INSTALLAZIONE.md#rollback).

**Regola per chi mantiene il progetto:** ogni nuova versione aggiorna **README, CHANGELOG, RELEASE_NOTES, QA, UPDATE e documentazione tecnica** nello stesso ZIP, quindi rigenera i checksum. La procedura è descritta in [Release process](docs/RELEASE_PROCESS.md).


## 0.8.0 — distribuzione Main / sviluppo Dev

Due opzioni **disabilitate per impostazione predefinita** nella Configura del plugin `mdoverhaul`: `task_workspace` abilita **Assistance → Tasks** con la ricerca nativa `ProjectTask` e una board in sola lettura; `kanban_restyling` migliora l'aspetto del Kanban Vue nativo mantenendone dati e drag & drop. Le task restano native GLPI e devono appartenere a un progetto. Il workspace offre selezione progetto prima della creazione, filtri combinabili, ordinamento e collegamenti ai form nativi. La board verifica al massimo 1000 candidati recenti nelle entità attive prima dei permessi individuali: conteggi e filtri sono dichiarati parziali se troncati; la ricerca nativa non ha questo limite. Dettagli in [configurazione](docs/CONFIGURAZIONE.md) e [rapporto P0/P1](docs/specs/TASKS_IMPLEMENTATION.md). Il codice su Dev richiede ancora collaudo su GLPI live prima della pubblicazione.

## 0.8.0 — moduli addizionali

- **Tasks Workspace:** abilita/disabilita `Assistance → Tasks` in Configura; elenco nativo `ProjectTask` e Kanban alternativo in sola lettura.
- **Kanban Restyling:** migliora le board Vue native GLPI senza cambiarne la logica.
- **Mini Ticket:** quando Dashboard Restyling è attivo, CSS/JS dedicato evita che le KPI sforino il riquadro. Da verificare su GLPI reale.
- Tutte le sette opzioni restano nel plugin padre `mdoverhaul`, senza plugin aggiuntivi, ticket o DB autonomo.



## Candidata Dev 0.8.1-dev.1

[Note di versione](docs/releases/0.8.1-dev.1.md) e [stato operativo](docs/dev/STATUS.md).
Dopo CI verde: archivio completo Modern_Dashboard_Overhaul_0.8.1-dev.1.zip e
alias [MDO_Dev_Latest.zip](https://raw.githubusercontent.com/dmontebello/Modern-Dashboard-Overhaul---a-GLPI-plugin/Dev/MDO_Dev_Latest.zip), entrambi con SHA512.
L'alias è aggiornato solo se il commit testato è ancora HEAD Dev. Il manager GLPI
e Settings mostrano la versione unica; lo ZIP contiene provenienza e data UTC.
NON COLLAUDATO LIVE. Lo ZIP 0.8.0 su Main resta la pubblicazione precedente.


## Attività indipendenti — 0.8.1-dev.2

Dentro lo stesso plugin, `StandaloneTask` usa una tabella dedicata e diritti di
profilo propri. Non crea Project né ProjectTask senza parent. Attivazione opt-in:
backup, upgrade nativo del plugin, diritti nella scheda MDO del profilo, quindi
Tasks Workspace + Attività indipendenti in Settings e nuovo login.
Il workspace separa le ricerche native Progetto/Indipendenti e offre elenco,
Kanban read-only, creazione, modifica, completamento, cestino/ripristino.
Disabilitare conserva dati; disinstallare non esegue DROP.

Pacchetti Dev completi dopo CI positiva:
[0.8.1-dev.1](https://raw.githubusercontent.com/dmontebello/Modern-Dashboard-Overhaul---a-GLPI-plugin/Dev/Modern_Dashboard_Overhaul_0.8.1-dev.1.zip),
[0.8.1-dev.2](https://raw.githubusercontent.com/dmontebello/Modern-Dashboard-Overhaul---a-GLPI-plugin/Dev/Modern_Dashboard_Overhaul_0.8.1-dev.2.zip),
[Latest](https://raw.githubusercontent.com/dmontebello/Modern-Dashboard-Overhaul---a-GLPI-plugin/Dev/MDO_Dev_Latest.zip).
Ogni ZIP ha `.sha512`; SHA sorgente, build e hash sono anche nei record
`docs/releases/<version>.build.json`. Lo ZIP 0.8.0 e Main restano invariati.


## 0.8.1-dev.6 — Nuovo workspace Tasks

Vista Tutte per attività di progetto e indipendenti, Kanban To do/Doing/Done modificabile con drag and drop o selettore tastiera, caricamento parziale delle viste del plugin, diario di aggiornamenti storico e allegati privati, layout del form standalone migliorato. I dati di ProjectTask restano nativi; i nuovi contenuti del diario MDO sono separati dalla scheda Note nativa. Richiede upgrade amministrativo schema 1→2 e backup DB+volumi. Test su GLPI reale **NON COLLAUDATO LIVE**: vedere [QA](QA.md), [release note](docs/releases/0.8.1-dev.6.md) e [stato](docs/dev/STATUS.md).


## Dev.7 — Nuova task unificata

Il comando **Nuova task** apre un solo form. Con progetto autorizzato salva una ProjectTask nativa, senza progetto una StandaloneTask; campi comuni nome, descrizione e scadenza, con mapping consentito da GLPI. La scheda nativa rimane disponibile per team/parent avanzati; diario e allegati si aprono dopo il salvataggio. NON COLLAUDATO LIVE. [Release notes](docs/releases/0.8.1-dev.7.md).


## Dev.8 — UX Kanban e Tasks

Fix trascinamento successivo al primo POST con rotazione CSRF, anteprima della card e colonna drop evidenziata. Vista Tutte > Elenco: eliminazione con permessi e cestino nativo, nessun purge. Indipendenti conservate e opzionali. **NON COLLAUDATO LIVE**. [Note](docs/releases/0.8.1-dev.8.md).


## Dev.9 — correzioni Kanban in collaudo

Stati ProjectTask GLPI sempre visibili, trascinamento nativo fra stati, vista All con fasi comuni e contatori aggiornati subito. **Non collaudata live.** [Note](docs/releases/0.8.1-dev.9.md).
