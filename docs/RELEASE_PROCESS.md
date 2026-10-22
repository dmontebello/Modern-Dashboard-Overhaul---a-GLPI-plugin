# Regole obbligatorie per ogni release — Modern Dashboard Overhaul 0.7.0

**Obiettivo:** nessuna versione senza README e documentazione sincronizzati con il codice. Questo documento fa parte del pacchetto **e deve essere aggiornato con esso**. Tutto si svolge localmente finché non sarà validata la 1.0; **non pubblicare su GitHub/Marketplace** prima di una richiesta esplicita.

## 1. Prima di cambiare il codice

1. Prendere la **ZIP sorgente completa dell'ultima versione** e verificarne SHA-512 interno/esterno; non partire da appunti o ZIP più vecchi.
2. Identificare versione in `setup.php`, API GLPI di riferimento, moduli, identificativi dei widget salvati e test già effettuati.
3. Creare una directory di staging separata. Prima di modificare, registrare i file cambiati e il motivo.
4. Preservare ID tecnico `mdoverhaul`, namespace `GlpiPlugin\Mdoverhaul` e chiavi compatibili `arensecurity_feed_*` / `arensecurity_rss*`.

## 2. Aggiornare **insieme al codice** questi documenti

| File | Contenuto richiesto |
| --- | --- |
| `README.md` | Versione, funzioni attive, requisiti e limiti, indice della documentazione |
| `CHANGELOG.md` | Nuova sezione con data e novità, preservando tutte le versioni precedenti |
| `docs/RELEASE_NOTES_IT.md` | Sintesi operativa specifica della versione |
| `docs/ARCHITETTURA.md` | Moduli, file, hook, dati e flussi aggiornati al codice reale |
| `docs/CONFIGURAZIONE.md` | Flag, valori di default, permessi, persistenza e dipendenze |
| `docs/INSTALLAZIONE.md` / `UPDATE.md` | Installazione, upgrade dalla precedente, controllo SHA-512, rollback |
| `docs/MAPPA_CARTELLE.md` | Nuovi file/spostamenti, host/container/volumi |
| `docs/TEST_MATRIX.md` / `QA.md` | Casi richiesti ed esiti realmente verificati, incluso ciò che manca |
| `docs/SECURITY.md` / `docs/INTEGRATION.md` | Cambi nei confini di fiducia, API GLPI e compatibilità |
| `tools/README.md` | Nuovi comandi/controlli del pacchetto, se applicabili |

**Non sovrascrivere lo storico.** Una funzione dichiarata in README deve essere riscontrabile nel codice. Le prove di browser e di GLPI live restano `NON TESTATO` finché non vengono davvero eseguite.

## 3. Controlli prima di confezionare

- Verifica sintassi PHP/Bash/JavaScript (Node assente = test parziale, mai successo pieno).
- Verifica form/configurazione, permessi e modulo RSS indipendente.
- Verifica UI light/dark, card piccole, filtri, tab con molte dashboard e `+` nativo.
- Verifica feed vuoti, privati, non raggiungibili e collegamenti anomali.
- Nessuna password, `.env`, certificato privato o percorso interno nei documenti pubblicabili.
- Checksum **nuovi**, non copiati dalla release precedente; file extra e symlink rifiutati.

## 4. Pacchetto riproducibile

Il codice include **strumenti opzionali del manutentore** in `tools/maintainer/`; richiedono Python 3 **solo per creare release**, non per eseguire il plugin.

```bash
# Dalla directory PADRE che contiene mdoverhaul/
python3 -B mdoverhaul/tools/maintainer/package_mdo.py \
  ./mdoverhaul ./Modern_Dashboard_Overhaul_VERSIONE_Pilot.zip

python3 -B mdoverhaul/tools/maintainer/check_release.py \
  ./mdoverhaul --zip ./Modern_Dashboard_Overhaul_VERSIONE_Pilot.zip

sha512sum -c Modern_Dashboard_Overhaul_VERSIONE_Pilot.zip.sha512
unzip -t Modern_Dashboard_Overhaul_VERSIONE_Pilot.zip
```

`package_mdo.py` rigenera `SHA512SUMS`, valida documenti/versioni e crea ZIP deterministico con sidecar `.zip.sha512`. `check_release.py` confronta versione, link locali, manifest e byte della ZIP con i file sorgente. Le regole sono anche contenute nella competenza ChatGPT **MDO Release & Documentation**, distribuita separatamente dal plugin.

## 5. Criteri di uscita e versioni

- Usare SemVer (`0.6.0`, `0.7.0`, …, `1.0.0`). Incrementare la versione per codice/funzionalità; dichiarare esplicitamente gli eventuali **rebuild soltanto documentali** della stessa versione.
- Ogni ZIP contiene l'intera directory `mdoverhaul/`, non una patch di file sparsi.
- Includere ZIP, sidecar SHA-512, elenco cambiamenti, esito test e procedura rollback.
- **1.0 solo dopo** collaudo reale sulla dashboard operativa e installazione GLPI pulita, inclusi compatibilità, sicurezza, no regressioni e ripristino.

## 6. Checklist del manutentore

- [ ] Versione coerente tra PHP, README, Changelog, QA e Release Notes
- [ ] Nuove/alterate funzioni descritte in Architettura e Configurazione
- [ ] Installazione/upgrade/rollback aggiornati
- [ ] Test effettivi riportati; non verificati dichiarati
- [ ] Nessun dato privato nel pacchetto
- [ ] SHA512SUMS rigenerato, ZIP confrontata ai sorgenti, sidecar verificato
- [ ] Test su GLPI quando disponibili (non sostituibili con il solo lint)
