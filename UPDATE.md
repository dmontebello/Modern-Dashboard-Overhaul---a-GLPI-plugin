# Aggiornare Modern Dashboard Overhaul a 0.8.0 Pilot

**Un solo archivio installabile:** `Modern_Dashboard_Overhaul_0.8.0_Pilot.zip`. Contiene Dashboard, RSS, Tasks Workspace, Kanban e Settings nella directory `mdoverhaul/`. Non è un kit patch né uno ZIP automatico dei sorgenti GitHub; non serve installare prima la 0.7.0. Compatibilità dichiarata GLPI 11.0.x, test GLPI live ancora necessario.

## 1. Scarica e verifica

```bash
cd /opt/glpi/plugins
curl -fL --retry 3 -o Modern_Dashboard_Overhaul_0.8.0_Pilot.zip \
  "https://raw.githubusercontent.com/dmontebello/Modern-Dashboard-Overhaul---a-GLPI-plugin/feature/tasks-workspace-settings/Modern_Dashboard_Overhaul_0.8.0_Pilot.zip"
curl -fL --retry 3 -o Modern_Dashboard_Overhaul_0.8.0_Pilot.zip.sha512 \
  "https://raw.githubusercontent.com/dmontebello/Modern-Dashboard-Overhaul---a-GLPI-plugin/feature/tasks-workspace-settings/Modern_Dashboard_Overhaul_0.8.0_Pilot.zip.sha512"
sha512sum -c Modern_Dashboard_Overhaul_0.8.0_Pilot.zip.sha512
unzip -t Modern_Dashboard_Overhaul_0.8.0_Pilot.zip
```

## 2. Prepara staging

```bash
STAGE="$(mktemp -d /tmp/mdo080.XXXXXX)"
unzip -q /opt/glpi/plugins/Modern_Dashboard_Overhaul_0.8.0_Pilot.zip -d "$STAGE"
test -f "$STAGE/mdoverhaul/setup.php"
grep -F "PLUGIN_MDOVERHAUL_VERSION', '0.8.0'" "$STAGE/mdoverhaul/setup.php"
(cd "$STAGE/mdoverhaul" && sha512sum -c SHA512SUMS)
```

## 3. Backup e aggiornamento senza disinstallazione

Esegui **prima** backup ripristinabili di database, volumi e cartella plugin GLPI. Assicurati che `/opt/glpi/plugins/` sia effettivamente la directory host del tuo GLPI/Docker; la cartella potrebbe differire.

```bash
cd /opt/glpi/plugins
test -d mdoverhaul
cp -a mdoverhaul "mdoverhaul.backup-$(date +%Y%m%d-%H%M%S)"
cp -a "$STAGE/mdoverhaul/." ./mdoverhaul/
(cd mdoverhaul && sha512sum -c SHA512SUMS)
```

Non cancellare `config/ui.local.php` se presente (file locale non distribuito), non disinstallare MDO e non cambiare i bind mount. Se necessario riavvia il solo servizio GLPI con la procedura prevista dal tuo Compose. In **Configurazione → Plugin → Modern Dashboard Overhaul**, aggiorna se richiesto; in **Configura** abilita Tasks Workspace e/o Kanban Restyling (OFF per default). Dopo il cambio menu può essere necessario rifare l'accesso.

## 4. Verifiche e rollback

Prova dashboard, widget RSS già salvati, pagina Ticket con sette contatori, Tasks, diritti e Kanban. Se la nuova versione causa problemi, ripristina la precedente cartella `mdoverhaul` dal backup e riavvia solo il runtime GLPI se necessario. Mantieni il backup DB. Non usare "Disinstalla" per il rollback.

## 5. Limiti

- Nessuna task da Notion importata automaticamente e nessun ticket creato.
- Tasks Workspace è basato sulle `ProjectTask` native, obbligatoriamente associate a un Project GLPI. La board alternativa è in sola lettura, con 1000 candidati recenti massimi; l'elenco nativo non ha questa limitazione.
- Il CSS/JS della mini-dashboard Ticket è da collaudare sulla tua installazione specifica.
- Aggiornare il plugin non aggiorna PHP e non corregge vulnerabilità del runtime.
