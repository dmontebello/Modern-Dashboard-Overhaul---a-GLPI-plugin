# Sicurezza e dati — Modern Dashboard Overhaul 0.8.1-dev.2

> **Corrente 0.8.1-dev.2:** vedere la sezione aggiornata in fondo; le descrizioni precedenti restano riferimento storico delle versioni indicate.

## Modello di fiducia

| Elemento | Confine di sicurezza |
| --- | --- |
| Utente GLPI | La visualizzazione di dashboard/RSS eredita autenticazione e diritti GLPI; la pagina Configura richiede `config:UPDATE` |
| Feed RSS esterno | Testi, date, link e metadati sono **non fidati**: usare parser GLPI, normalizzazione, escaping HTML e schemi URL sicuri |
| Browser | JavaScript del restyling modifica soltanto presentazione/DOM della dashboard; non deve leggere credenziali né inviare telemetria |
| Container/plugin | Codice su bind mount **read-only**; PHP GLPI gestisce impostazioni con API native in `glpi_configs` |
| Backup ZIP | SHA-512 protegge dall'alterazione accidentale e aiuta il confronto, **non dimostra provenienza, affidabilità o assenza di malware** |

## Controlli presenti nella 0.7

- Token CSRF incluso nel form e protezione della richiesta demandata al router GLPI 11; `Settings::save` accetta soltanto i sette flag `0` o `1`, rifiutando input incompleto o non valido.
- `Session::checkRight('config', UPDATE)` per la pagina amministrativa; selezione RSS soggetta a diritti e visibilità GLPI.
- Titoli e attributi RSS passano attraverso escaping HTML; link valutati dal formatter e aperti con `rel="noopener noreferrer"`.
- Nessuna tabella propria, telemetria, archivio permanente delle CVE, password, processo in background o integrazione ad API di scoring.
- Moduli indipendenti: spegnere Restyling non deve cambiare l'accessibilità dei feed e viceversa.

## Rischi da collaudare

- Verificare in **GLPI reale** i permessi dei feed condivisi e privati con utenti/profili distinti.
- Provare link non-HTTP(S), markup HTML ostile, date anomale, redirect e feed malformati.
- Verificare che il salvataggio dei flag non possa essere effettuato da profili senza permessi o senza token valido.
- Testare il comportamento in caso di upgrade GLPI, perché hook/DOM/JS nativi possono cambiare.
- Verificare che disattivare il modulo RSS non porti alla perdita di schede quando un utente modifica una dashboard: **non salvarla durante tale fase**.

## Cosa non deve essere distribuito

Mai includere nel pacchetto: `.env` di Docker, certificati o chiavi private, password, backup MySQL, configurazioni `ui.local.php` contenenti personalizzazioni, log con dati personali, nomi interni di host/IP e screenshot con dettagli riservati.

## Segnalazioni di vulnerabilità

Il progetto è in **pilot interno**; non è ancora disponibile un canale pubblico del repository. Segnalare problemi al manutentore attraverso il canale privato concordato per il progetto. Non pubblicare token, password, dati RSS privati o log sensibili.

## CVE critiche del runtime PHP (21 ottobre 2026)

Le vulnerabilità **CVE-2026-109618 e CVE-2026-109619** (CVSS v4 9.2) interessano il runtime PHP, possono consentire RCE non autenticata e non sono risolvibili con un aggiornamento di questo plugin. Il comando `tools/verifica-runtime-php.sh` indica il confronto con le versioni upstream corrette e segnala quando serve verificare backport del distributore. Per dettagli, impatto e patch vedere [SECURITY_PHP_2026.md](SECURITY_PHP_2026.md). L'aggiornamento del container web e il riavvio dei worker vanno gestiti separatamente con backup GLPI e immagini aggiornate.

## 0.8.0 — Tasks Workspace

`TaskMenu` controlla `ProjectTask::canView()`; `front/tasks.php` verifica l'accesso e `TaskBoard` usa `ProjectTask::canViewItem()` per ogni record, con soli metodi di lettura. Titoli e descrizioni vengono renderizzati con escaping HTML; i link puntano alle form GLPI. Il CSS Kanban non sovrascrive i comportamenti Vue. Nessun token esterno, nessuna tabella o servizio nuovo. Le impostazioni OFF non cancellano task.



## Aggiornamento 0.8.1-dev.1

La creazione ProjectTask usa ProjectTask::can(-1, CREATE, input) con progetto esistente non cancellato/template; non aggiunge Project READ al diritto core. runtime.php richiede login e plugin attivo, espone solo provenienza pubblica. Test reali ACL e CSRF restano necessari.

Stato: NON COLLAUDATO LIVE. Note: [0.8.1-dev.1](releases/0.8.1-dev.1.md).

## Aggiornamento 0.8.1-dev.2

StandaloneTask ha una ACL distinta da ProjectTask. Richiede login, plugin e
padre/sotto-opzione attivi, schema pronto, bit di profilo e scope di entità.
READMY significa owner/assignee/gruppo corrente; READALL non salta le entità.
L'hook Search e l'interfaccia API producono gli stessi criteri; la board applica
anche can(id,READ)/ITEM_CAN prima di mostrare dati o conteggi.

Owner e entità sono fissati alla creazione e non trasferibili via update. Una
nuova assegnazione richiede User/Group leggibile e appartenente all'entità o
gruppo antenato ricorsivo. Campi scrivibili allowlist, ID/date/stato validati;
contenuti testo escaped. Login zero, parametri ostili e riferimenti cross-entity
negati. Cestino e restore richiedono DELETE + scope; purge vietato anche dal modello.

Le scritture passano dai controller GLPI 11 e CheckCsrfListener nativo. Non
ripetere la validazione di un token già consumato. Le negative CSRF nei test
sono un middleware double: serve collaudo reale. Profili usano la matrice e il
POST Profile nativi, nessun endpoint custom per concedersi diritti. Snapshot e
dump contengono dati privati: mantenerli fuori da plugin/repository/pubblicazioni.

Stato: NON COLLAUDATO LIVE. [Note complete](releases/0.8.1-dev.2.md).

## Aggiornamento MDO 0.8.1-dev.3 (25 ottobre 2026)

In 0.8.1-dev.3 la verifica ProjectTask::can(-1,CREATE, input) continua ad applicare i diritti nativi sul Project selezionato. Html::redirect viene spostato fuori dal catch(Throwable) perché il router GLPI 11 segnala tramite RedirectException un redirect valido; questo non rimuove né aggira alcun controllo ACL. Cross-entity e READMY/READALL richiedono collaudo live.

## Aggiornamento 0.8.1-dev.4 (25 ottobre 2026)

0.8.1-dev.4: nessun cambiamento ai permessi. Lo status filter di StandaloneTask usa display=false per evitare output diretto fuori dal controllo nativo. La whitelist del publisher accetta solo modifiche documentali specifiche e output generati; non può mascherare modifiche di codice senza aumento versione. ACL live ancora non collaudate.

## Aggiornamento 0.8.1-dev.5

Dev.5 mantiene gli ACL GLPI. StandaloneTask::canView richiede READMY o READALL anche per Super-Admin, con schema e flags attivi. L'utente senza diritti riceve ancora la pagina 403. Soltanto chi possiede config:UPDATE vede un avviso non sensibile nel workspace di progetto; non sono introdotti grant automatici o informazioni di task non autorizzate. Le tre ORDER sono letterali e non usano input utente.


## 0.8.1-dev.6 — Sicurezza Tasks

Endpoint stage: ACL READ+UPDATE per item, entità, CSRF middleware GLPI e date_mod anti-stale. Diario/allegati: READ/UPDATE del parent su ogni GET/POST/download, storage privato sotto GLPI_DOC_DIR, token file casuale, download forzato e upload limitato a 15MiB. Rischi da testare: concorrenza non atomica, antivirus esterno, middleware CSRF, isolamento entità e download reali. NON COLLAUDATO LIVE.

## 0.8.1-dev.7 — allineamento

Il servizio dev.7 consente solo campi allowlist. Progetto ID non valido/non autorizzato causa errore senza ricadere su Standalone; POST verificato da middleware CSRF GLPI, `check(-1,CREATE)` nativo prima di `add`, redirect fuori catch GLPI11. Nessun bypass entità/profili. NON COLLAUDATO LIVE.

## Dev.8 UX — eliminazione e rotazione token

POST `front/task-delete.php`: centrale e plugin attivo, itemtype allowlist, ID positivo, conferma server, parent can READ e DELETE specifico, `maybeDeleted`, soft-delete GLPI senza purge e filtro entità. Middleware GLPI11 verifica CSRF prima del controller; token ruotato per AJAX quando il middleware ha accettato il POST, anche su 4xx. La risposta 403 non espone item sensibili. JS aggiorna il token in board e lista. Resta necessaria una prova live dei permessi e del comportamento della cancellazione con sottotask native.
