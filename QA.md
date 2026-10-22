# QA — Modern Dashboard Overhaul 0.7.0 (Pilot)

**Ambiente:** codice della 0.6.0 Documented Pilot verificato con SHA-512; modifiche 0.7.0 analizzate ed eseguite offline. **Non è un test end-to-end della GLPI di produzione.**

| Controllo | Evidenza 0.7.0 |
| --- | --- |
| UI Chromium headless | **80 test superati** su 1440, 1024, 780 e 390 px, widget KPI, RSS, temi, filtri, oltre 40 tab, `+`, edit, ECharts con caricamento asincrono |
| PHP isolato | **38 test superati** su configurazioni, memoizzazione, hook, ACL con mock, sanitizer RSS, date, 4 preset e chiavi legacy |
| Performance | Benchmark locale 62 grafici: timer di retry **186 (0.6) → 3 (0.7)**; 250 mutazioni numero danno un singolo refresh in fixture |
| Controlli sintassi | `php -l` per tutti i PHP, `node --check` JavaScript, `bash -n` per gli script |
| Integrità | SHA-512 della 0.6 verificato prima dell'editing; manifest e ZIP 0.7 rigenerati e ricontrollati |
| Sicurezza | Test URL per schemi non sicuri/credenziali, escaping RSS, accesso feed in profili mock differenti, form allowlist con rifiuto input incompleti |
| Dipendenze | Nessun framework aggiuntivo, font esterno, telemetria, chiamata AJAX aggiuntiva o schema DB introdotto |

## Limiti delle verifiche

- Chromium usa una **fixture HTML che riproduce classi/markup principali di GLPI 11.0.9**, non GLPI reale. Screenshot nel processo di build non vengono inclusi nel plugin per evitare dati privati.
- Alcuni grafici della fixture sono simulati; ECharts viene testato tramite istanza mock. Grafici effettivi e GridStack su GLPI richiedono test visivo e funzionale sulla VM.
- Le ACL mock verificano che il codice controlli i permessi, **non dimostrano l'esattezza delle ACL di tutte le installazioni GLPI**.
- Il benchmark confronta la **quantità di retry pianificati** e non misura prestazioni browser in condizioni di carico reale né memoria Apache/PHP.
- **Le due CVE del runtime PHP 2026 non sono correggibili all'interno del plugin.** Verificare la patch effettivamente in esecuzione in Apache/PHP-FPM.

## Collaudo necessario sulla VM prima di accettare la 0.7

1. GLPI 11.0.9: admin → Configura, modifica e salva le cinque opzioni; utente senza `config:UPDATE` deve essere respinto.
2. Verificare i feed RSS privati/pubblici con profili, entità e gruppi differenti (nessuna fuga dati).
3. Dashboard in entrambe le modalità, widget da piccoli a grandi, browser desktop/mobile reali, zoom 125/150%.
4. Creare, rinominare, clonare ed eliminare dashboard con tab e `+`; entrare/uscire da modifica, spostare widget GridStack.
5. Collaudare il caricamento RSS (5/10/15/20), feed vuoto/offline, contenuto ostile, date, scrolling, nessun collegamento JavaScript.
6. Testare modulo Restyling OFF + RSS ON e viceversa. **Non salvare dashboard in modifica con RSS disabilitato**.
7. Confrontare registri Apache/PHP/GLPI e tempo/carico con dashboard equivalenti su 0.6/0.7.
8. Ripristino versione 0.6 dalla copia, su sistema di prova. Confermare backup di database/volumi.

I criteri generali sono in [TEST_MATRIX](docs/TEST_MATRIX.md). **Non dichiarare stabile 1.0 senza questi test**.
