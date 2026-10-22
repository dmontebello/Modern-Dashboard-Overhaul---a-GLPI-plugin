# Modern Dashboard Overhaul

**Versione:** `0.7.0` (pilot interno) · **Autore:** Davide Montebello · **Licenza:** MIT  
**ID tecnico/cartella:** `mdoverhaul` · **Target:** GLPI `11.0.x` (`>=11.0.0`, `<11.1.0`)

Un plugin GLPI con **due moduli indipendenti**: migliora la leggibilità delle dashboard native e aggiunge widget che mostrano i feed RSS già gestiti da GLPI. Non sostituisce GLPI, non modifica i dati dei suoi widget nativi e non richiede servizi aggiuntivi.

> **Stato:** 0.7.0 in collaudo verso la 1.0.0. Non è ancora una release stabile, né è pubblicato su GitHub/Marketplace. Il funzionamento reale va verificato sull'installazione di destinazione.

## Cosa fa

| Modulo | Comportamento |
| --- | --- |
| **Dashboard Restyling** | Contatori più leggibili anche in schede piccole, tema chiaro/scuro comandato dal toggle nativo, grafici con testi migliorati, eliminazione dello spazio dei filtri vuoti e tab orizzontali delle dashboard. Mantiene il pulsante `+` originale e i permessi GLPI. **Non crea nuovi widget nativi.** |
| **Widget RSS** | Un widget per ogni feed GLPI attivo e visibile all'utente, con preset **5/10/15/20** articoli (10 predefiniti), data/ora se fornite dalla fonte, titoli e link sicuri. Non richiede un feed di sicurezza: funziona anche con RSS generici. |

Il plugin non introduce tabelle proprie, job cron, telemetria, password, download di font o scanner. Usa il parser/cache RSS di GLPI e salva le cinque opzioni amministrative nella configurazione nativa `glpi_configs`.

## Avvio rapido

1. **Installazione nuova:** estrarre lo ZIP con directory principale `mdoverhaul/` nella cartella plugin GLPI. Per Docker occorre un bind mount leggibile dal container. Vedi [Installazione](docs/INSTALLAZIONE.md).
2. **Aggiornamento:** salvare codice e backup GLPI, controllare SHA-512, estrarre in una directory temporanea e sostituire i file del plugin. Non disinstallare per aggiornare. Vedi [UPDATE](UPDATE.md).
3. In GLPI aprire **Configurazione → Plugin → Modern Dashboard Overhaul → Installa/Abilita**.
4. Da **Configura** (chiave inglese) gestire i due moduli e le sotto-opzioni. Vedi [Configurazione](docs/CONFIGURAZIONE.md).
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
