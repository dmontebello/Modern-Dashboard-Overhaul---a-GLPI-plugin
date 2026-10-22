# Avviso di sicurezza — PHP, 21 ottobre 2026 (MDO 0.7.0)

**Ambito:** runtime PHP usato da GLPI / Apache / PHP-FPM; non una vulnerabilità individuata nel codice specifico di Modern Dashboard Overhaul.

Il progetto PHP e CERT-AGID hanno comunicato il 21/10/2026 due vulnerabilità critiche, **CVE-2026-109618** e **CVE-2026-109619**, CVSS v4 **9.2/10**, che possono consentire esecuzione di codice da remoto prima dell'esecuzione dell'applicazione. Il rischio è particolarmente elevato per Apache `mod_php`; riguarda anche altre SAPI. **PHP/GLPI non diventano immuni utilizzando CSS, JavaScript o TypeScript nel plugin.**

| Ramo | Patch upstream da applicare almeno |
| --- | --- |
| 8.5 | **8.5.12** |
| 8.4 | **8.4.27** |
| 8.3 | **8.3.36** |
| 8.2 | **8.2.35** |

I rami più vecchi possono essere fuori supporto. I distributori Linux applicano a volte **backport**: verificare security bulletin e changelog del *pacchetto effettivamente installato* anziché basarsi esclusivamente su `php -v`.

## Procedura di verifica non invasiva

```bash
cd /opt/glpi
bash /opt/glpi/plugins/mdoverhaul/tools/verifica-runtime-php.sh

docker compose exec -T glpi php -v
docker compose exec -T glpi sh -c 'apache2ctl -M 2>/dev/null | grep -i php || true'
docker compose exec -T glpi sh -c 'dpkg-query -W "php*" 2>/dev/null | head -30'
```

Il controllo dello script restituisce `0` quando la versione **CLI** upstream soddisfa il minimo, `2` in caso di versione antecedente/non verificabile, `1` quando il controllo non può essere eseguito. **Non certifica la SAPI web.** Nessuno di questi comandi aggiorna o riavvia servizi.

## Aggiornamento del runtime

1. Pianificare backup completo di database/volumi GLPI, backup del Compose e dell'immagine, nonché un test in ambiente separato.
2. Stabilire la base dell'immagine personalizzata GLPI e il metodo di installazione di PHP; cercare pacchetti o immagini con le patch delle CVE.
3. Aggiornare la base runtime/container secondo le istruzioni ufficiali del venditore; **non** installare una seconda copia di PHP in parallelo senza piano.
4. Ricostruire e avviare **solo il servizio GLPI** in finestra di manutenzione e verificare PHP in uso dai processi web, log e compatibilità.
5. Se l'aggiornamento non è immediato, limitare l'esposizione alle sole reti/utenti necessari; mitigazioni parziali non sostituiscono le patch.

**Non usare** questo documento come giustificazione per `memory_limit=-1` o altre mitigazioni che possono peggiorare la disponibilità di GLPI senza una valutazione specifica; il fix è applicare le patch ufficiali.

## Fonti primarie

- [PHP Security Advisory 21/10/2026](https://www.php.net/archive/2026.php#2026-10-21-5)
- [CERT-AGID, bollettino PHP del 21/10/2026](https://cert-agid.gov.it/news/due-vulnerabilita-critiche-in-php-consentono-lesecuzione-di-codice-da-remoto-rilasciati-aggiornamenti-di-sicurezza/)
- [Ubuntu USN-8948-1](https://ubuntu.com/security/notices/USN-8948-1)
