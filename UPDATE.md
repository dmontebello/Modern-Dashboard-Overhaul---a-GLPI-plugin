# Aggiornare Modern Dashboard Overhaul a 0.7.0

Questa guida descrive l'upgrade **0.6.0 → 0.7.0**. **Nessuna migrazione database:** restano le cinque impostazioni in `glpi_configs`, le dashboard e i widget RSS salvati. Non disinstallare la 0.6 per aggiornare. Verificare prima la patch PHP relativa alle CVE critiche del 21/10/2026: aggiornare il plugin non corregge il runtime.

## Prima: identificare i percorsi

Esempio di distribuzione Docker già montata:

```text
Host:      /opt/glpi/plugins/mdoverhaul/
Container: /var/www/glpi/plugins/mdoverhaul/
Mount:     ./plugins/mdoverhaul:/var/www/glpi/plugins/mdoverhaul:ro
```

Verificare i propri percorsi prima di eseguire comandi. **Non cambiare il Compose** se il bind mount esiste già. Il pacchetto include sempre la directory `mdoverhaul/`; estrarre nella **cartella padre**.

## Passaggi di aggiornamento dalla 0.6

1. Verifica backup completo **database + volumi GLPI**, non solo il codice. Da **Configurazione → Plugin** disabilita `mdoverhaul`; **non disinstallare**.
2. Scarica ZIP e checksum dallo stesso canale attendibile. Verifica ZIP prima di estrarre/eseguire script:

   ```bash
   cd /opt/glpi/plugins
   # Prima copia qui i due file: *.zip e *.zip.sha512
   sha512sum -c Modern_Dashboard_Overhaul_0.7.0_Pilot.zip.sha512
   unzip -t Modern_Dashboard_Overhaul_0.7.0_Pilot.zip
   ```

3. Salva il codice corrente e annota il percorso prodotto:

   ```bash
   bash /opt/glpi/plugins/mdoverhaul/tools/backup-plugin.sh
   ```

4. Estrai il nuovo pacchetto **in staging** e controllalo:

   ```bash
   STAGE=$(mktemp -d /root/mdo-release.XXXXXXXX)
   unzip -q /opt/glpi/plugins/Modern_Dashboard_Overhaul_0.7.0_Pilot.zip -d "$STAGE"
   (cd "$STAGE/mdoverhaul" && sha512sum -c SHA512SUMS)
   ```

5. Solo dopo esito **OK per tutti i file**, aggiorna la cartella già montata:

   ```bash
   cp -a "$STAGE/mdoverhaul/." /opt/glpi/plugins/mdoverhaul/
   chown -R root:root /opt/glpi/plugins/mdoverhaul
   find /opt/glpi/plugins/mdoverhaul -type d -exec chmod 755 {} \;
   find /opt/glpi/plugins/mdoverhaul -type f -exec chmod 644 {} \;
   bash /opt/glpi/plugins/mdoverhaul/tools/verifica-tutto.sh
   ```

   **Non usare `chmod 777`.** Se sono presenti ACL particolari, adeguare in modo mirato. `cp` aggiorna i file, ma **non rimuove i residui**: lo script SHA-512 segnala file extra. Un file locale `config/ui.local.php` è l'unica eccezione prevista; non deve essere distribuito dentro gli ZIP.

6. Se lo script non riporta errori rossi, riavvia **solo GLPI** per aggiornare PHP/OPcache (breve indisponibilità):

   ```bash
   cd /opt/glpi
   docker compose restart glpi
   docker compose ps
   ```

   Da GLPI → Configurazione → Plugin → **Aggiorna** se richiesto, quindi **Abilita**. Verifica **0.7.0**, apri **Configura**, controlla cinque flag e salva.

7. `Ctrl+F5` sulla dashboard. Verifica widget nativi, due temi, tab e `+` originale, feed RSS, due moduli indipendenti. Per il collaudo seguire [TEST_MATRIX](docs/TEST_MATRIX.md).

### Verifica separata della sicurezza PHP

Il pacchetto comprende anche un controllo di sola lettura (non richiesto da `verifica-tutto.sh`):

```bash
bash /opt/glpi/plugins/mdoverhaul/tools/verifica-runtime-php.sh
```

Confronta la versione PHP CLI con le versioni correttive pubblicate il **21/10/2026**. **ATTENZIONE:** la versione PHP CLI può differire dal runtime web e i pacchetti Debian/Ubuntu possono contenere backport senza cambiare il numero upstream. Un avviso non sostituisce la verifica del bollettino del distributore, e un OK non certifica Apache/PHP-FPM.

## Con quali verifiche procedere?

| Risultato | Decisione |
| --- | --- |
| `[OK]` verde | Controllo specifico superato |
| `[ERRORE]` rosso | **STOP**: non riattivare |
| `[AVVISO]` giallo per Node assente (exit 2) | Controllo JS incompleto: eseguirlo altrove prima di qualificare il collaudo |

Gli script non modificano database, plugin o Docker Compose. La verifica SHA-512 serve a confrontare i file ricevuti; non è una firma digitale.

## Rollback

Disabilita la 0.7 dalla WebGUI. Ripristina dal backup verificato il **codice della 0.6** nella directory `mdoverhaul`, seguendo [la procedura di rollback](docs/INSTALLAZIONE.md#rollback). Se sostituisci la cartella montata, **ricrea il container GLPI**. La 0.6 legge le stesse opzioni `glpi_configs`: nessuna conversione necessaria.

**Non rimuovere i feed, i widget RSS, le dashboard o i record MySQL per fare rollback.** In caso di problemi GLPI, conserva i log e usa il backup completo se necessario.
