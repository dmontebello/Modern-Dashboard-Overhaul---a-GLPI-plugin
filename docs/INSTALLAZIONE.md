# Installazione e ripristino — Modern Dashboard Overhaul 0.8.1-dev.2

> **Corrente 0.8.1-dev.2:** vedere la sezione aggiornata in fondo; le descrizioni precedenti restano riferimento storico delle versioni indicate.

Questa procedura è generica per GLPI 11.0.x; per un ambiente particolare verificare sempre i percorsi prima di copiare comandi. **Non fare prove direttamente in produzione senza un backup recuperabile di DB e volumi GLPI.**

## Requisiti

- GLPI `>=11.0.0` e `<11.1.0` (integrazione verificata sul codice 11.0.9).
- Diritti di installazione plugin GLPI; diritto `config:UPDATE` per gestire le impostazioni.
- PHP, parser RSS e frontend già forniti da GLPI; **nessun server esterno** e nessun Node necessario per l'esecuzione.
- Cartella plugin leggibile dal runtime GLPI e **un solo plugin attivo** tra `mdoverhaul` e l'eventuale vecchio `arensecurity`.

## Nuova installazione GLPI non-Docker

1. Salva il database/GLPI e verifica che il file ZIP provenga da una fonte attendibile. Controlla il checksum esterno `.zip.sha512`:
   ```bash
   sha512sum -c Modern_Dashboard_Overhaul_0.8.0_Pilot.zip.sha512
   unzip -t Modern_Dashboard_Overhaul_0.8.0_Pilot.zip
   ```
2. Estrai **nella cartella padre dei plugin GLPI**: lo ZIP contiene già `mdoverhaul/`. Il percorso effettivo va verificato nel tuo deployment (es. `.../glpi/plugins/mdoverhaul/`). Non creare `mdoverhaul/mdoverhaul/`.
3. Controlla `setup.php`, `hook.php`, `front/config.php` e i permessi leggibili dal processo PHP/Apache.
4. In GLPI → **Configurazione → Plugin → Modern Dashboard Overhaul → Installa → Abilita**.
5. Apri **Configura** e verifica le opzioni; apri una dashboard di prova e un feed RSS consentito.

## Installazione nuova in Docker Compose (esempio)

Se GLPI viene gestito con progetto Compose `/opt/glpi` e plugin personalizzati montati individualmente:

```bash
# Da root sul server Docker; cambia NOMEPACCHETTO con lo ZIP verificato.
cd /opt/glpi/plugins
unzip -t NOMEPACCHETTO.zip
unzip -q NOMEPACCHETTO.zip -d /opt/glpi/plugins/
cd /opt/glpi/plugins/mdoverhaul
sha512sum -c SHA512SUMS
```

Nel servizio `glpi` del Compose/override, aggiungi **solo se assente**:

```yaml
    volumes:
      - ./plugins/mdoverhaul:/var/www/glpi/plugins/mdoverhaul:ro
```

**Non sostituire il file Compose con questo esempio**: conserva i mount, porte, image/build e certificati già presenti. Verifica che le directory host siano attraversabili e i file leggibili dal processo nel container (solitamente permessi 755 directory / 644 file, adattando ACL locali, senza `chmod 777`).

```bash
cd /opt/glpi
docker compose config --quiet
docker compose up -d --no-deps --no-build --pull never glpi
docker compose exec -T glpi test -r /var/www/glpi/plugins/mdoverhaul/setup.php
```

L'aggiunta di un mount può richiedere ricreazione del container GLPI e provocare breve interruzione della GUI. **Non riavviare MySQL e non usare `docker compose down -v`.** Terminare da WebGUI con **Installa → Abilita**.

## Aggiornamento dalla 0.5 o successiva

1. Effettua un backup completo DB + volumi GLPI, e **backup del codice plugin**:
   ```bash
   bash /opt/glpi/plugins/mdoverhaul/tools/backup-plugin.sh
   ```
2. Da GLPI → Plugin, **Disabilita** il plugin, **non Disinstalla**.
3. Copia il nuovo ZIP e `.sha512`, controlla con `sha512sum -c` e `unzip -t`; estrai lo ZIP in **staging temporaneo**, non direttamente nel codice attivo.
4. Da staging verifica il manifest `SHA512SUMS`; **solo se tutto OK** copia nella medesima directory `mdoverhaul` già montata (il mount non cambia). Conserva gli eventuali override locali non distribuiti.
5. Controlla con `tools/verifica-tutto.sh`: **1 = stop per errore**, **2 = controllo JavaScript incompleto**, **0 = controlli disponibili superati**.
6. Se usi Docker e i file sono stati sostituiti, esegui `docker compose restart glpi` per aggiornare il codice e OPcache; non toccare DB/Compose per un semplice aggiornamento del plugin.
7. In GLPI → Plugin, **Aggiorna** se richiesto, quindi **Abilita**, ricarica `Ctrl+F5` e verifica la matrice test.

I comandi dettagliati per il passaggio 0.5→0.6 sono nel file [UPDATE.md](../UPDATE.md). I salvataggi delle dashboard e gli RSS risiedono nel **database GLPI**, non nel codice del plugin.

## Rollback

**Percorso consigliato: rollback del codice, non del database.**

1. Disabilita il plugin da WebGUI, se accessibile. Conserva i log dell'errore e il backup del codice della versione funzionante.
2. Con breve fermo programmato del **solo** container GLPI, ripristina nella directory `mdoverhaul` i file della versione precedente dal backup verificato. Se si sostituisce fisicamente **la directory** montata (anziché sovrascrivere i suoi file), occorre **ricreare** il container affinché il bind mount punti alla nuova directory.
3. Verifica `SHA512SUMS`/sintassi della versione ripristinata, riavvia o ricrea il solo container GLPI e riabilita la versione corretta dalla WebGUI.
4. Verifica i widget RSS salvati e la dashboard prima di modificare ulteriormente i dati. Se è stato aggiornato anche uno schema DB in una futura release, **non ripristinare solo PHP** senza una procedura di rollback DB specifica.

**Nella 0.6 non esistono tabelle del plugin.** Le opzioni `glpi_configs` aggiunte dalla 0.6 restano nel DB anche ripristinando la 0.5, che non le legge. Evita cancellazioni manuali di record MySQL.

## Disattivazione e rimozione definitiva

- **Disattivare un modulo:** usare **Configura**; non disinstallare per questo.
- **Disabilitare il plugin:** GLPI → Configurazione → Plugin → Disabilita. La dashboard torna alla presentazione nativa, i dati restano GLPI.
- **Disinstallazione:** fare prima un backup di DB e plugin; la rimozione può cambiare registrazioni/configurazioni del plugin. Nella 0.6 `hook.php` non elimina tabelle proprie, ma la rimozione va comunque verificata.
- **Rimozione cartella da Docker:** prima disabilitare/disinstallare dalla GUI, poi rimuovere il bind mount dal Compose, verificarlo, ricreare il container GLPI e infine archiviare o rimuovere la cartella host. Non cancellare la cartella mentre il mount è ancora richiesto dal Compose.

## Diagnosi rapida

```bash
cd /opt/glpi
docker compose ps
docker compose logs glpi --tail 100
docker compose exec -T glpi ls -la /var/www/glpi/plugins/mdoverhaul
bash /opt/glpi/plugins/mdoverhaul/tools/verifica-tutto.sh
```

Se un file è montato ma non leggibile, verificare **percorso e permessi** prima di riavviare o cambiare tutti i permessi di `/opt/glpi`.

## 0.8.0 — Archivi completi

Per 0.8.0 usa soltanto lo ZIP pubblicato nel branch `Main`, non "Download ZIP" dei sorgenti della repository. La ZIP contiene `mdoverhaul/` completa e non richiede di installare prima la 0.7.0. Scarica anche `.zip.sha512`, verifica il checksum e fai il test `unzip -t`. L'aggiornamento da 0.6/0.7 è documentato in [UPDATE](../UPDATE.md). Nessuna disinstallazione, nessuna nuova tabella.



## Aggiornamento 0.8.1-dev.1

Usare il pacchetto versionato Dev con sidecar SHA512 e build-info.json; verificare anche il manifest interno. Effettuare upgrade file/plugin in staging e controllare versione/asset HTTP. Non installare il vecchio ZIP 0.8.0 per testare questa candidata.

Stato: NON COLLAUDATO LIVE. Note: [0.8.1-dev.1](releases/0.8.1-dev.1.md).

## Aggiornamento 0.8.1-dev.2

Procedura STAGING: 1) backup coerente DB, directory plugin e volumi; provare il
ripristino; 2) verificare ZIP/sidecar e build-info del commit testato; 3) sostituire
la sola cartella mdoverhaul; 4) eseguire l'upgrade dalla gestione plugin GLPI,
non da una pagina Tasks; 5) controllare creazione schema 1 e tabella dedicata;
6) configurare i profili con i nuovi diritti; 7) attivare l'ottava opzione,
rifare login e collaudare. Nessuna migrazione DB reale eseguita da Codex.

Hook install/upgrade idempotente: completa campi/indici senza duplicare righe,
non azzera grant esistenti e registra task_schema_version=1 solo a fine upgrade.
Se l'upgrade è incompleto la feature non è disponibile; non forzare il flag.
Gli altri moduli mantengono dati e impostazioni. Nessun deploy Main automatico.

Stato: NON COLLAUDATO LIVE. [Note complete](releases/0.8.1-dev.2.md).

## Aggiornamento MDO 0.8.1-dev.3 (25 ottobre 2026)

Collaudo 0.8.1-dev.3: usare solo ZIP Dev versionato dopo GitHub Actions verde; verificare SHA512, build-info.json e versione Settings; backup prima dell'upgrade su GLPI 11.0.9 staging. Provare Nuova task -> Task di progetto -> selezione -> Continua -> form nativo -> Salva. Non testata su produzione.

## Aggiornamento 0.8.1-dev.4 (25 ottobre 2026)

0.8.1-dev.4: prelevare ZIP specifica da Dev soltanto dopo gate verde, verificare .sha512, estrarre (unzip -t fa solo il test, non installa), mantenere backup e upgrade normale del plugin sullo staging. Verificare versione, source SHA e Search filtro Stato. Nessun upgrade schema rispetto alla dev.3.
