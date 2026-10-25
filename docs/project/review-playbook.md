# MDO — Playbook review architettura, prestazioni e cybersecurity

Le review indipendenti sono **periodiche e mirate**, non approvazioni umane obbligatorie a ogni commit. Eseguirle a richiesta, dopo modifiche significative, prima di stabilizzazione o dopo regressioni.

## Avvio reviewer

Leggere [start-here](start-here.md), [focus](current-focus.md), [backlog](../product/backlog.md), [stato tecnico](../implementation-state.md) e codice su HEAD reale. Verificare diff, PR e Actions sullo SHA esatto, dichiarare il candidato e i limiti. Se non disponibile staging, non attribuire prove live.

### Architettura e manutenibilità

Modello nativo `ProjectTask` e plugin `StandaloneTask`, hooks GLPI, responsabilità PHP/controller/view, code duplication, N+1, mix Search nativa e vista custom, separazione Stage/Status, codice morto, compatibilità, migrazioni, coerenza con documentazione e testabilità. Minimizzare complessità superflua.

### Prestazioni e risorse

Misurare query MariaDB, indici, scansione bounded 1000 candidate per origine, ACL per item, costo PHP/RAM, numero richieste, p50/p95, caricamento HTML/CSS/JS, resize e observer ECharts, rendering Kanban e responsive. Documentare dataset, carico, ambiente, prima/dopo; non chiamare «CPU risparmiata» la sola riduzione dei timer JS.

### Cybersecurity

ACL e scope per item/entità, READMY/READALL, CSRF e sessioni, XSS/SQL injection, links/download privati `GLPI_DOC_DIR`, MIME e dimensioni upload, ID tampering, concorrenza, audit, backup/restore, patch PHP, supply chain, GitHub Actions e differenza fra hash d'integrità e autenticità.

## Consegna e tracciabilità

Creare `docs/reviews/YYYY-MM-DD-<area>.md` oppure issue con SHA e scope, test, findings ID, gravità CRITICA/ALTA/MEDIA/BASSA, riproduzione, file/linea, fix minimo, regressioni proposte, stato e owner/prossimo passo. Per ogni finding azionabile aggiungere/collegare [item backlog](../product/backlog.md). Il developer può risolverlo senza nuovo GO CTO se definito, con codice+test+docs. Vulnerabilità critiche aperte impediscono release stabile. Niente scansioni invasive del GLPI aziendale senza autorizzazione o dati reali nel repo pubblico.
