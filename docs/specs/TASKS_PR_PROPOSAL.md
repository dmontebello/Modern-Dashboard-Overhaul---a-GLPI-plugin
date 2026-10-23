# Proposta PR Dev → Main — non aperta

Titolo: **Tasks P0/P1: compatibilità GLPI 11, ACL e workspace native-first**

Base: `Main`. Head: `Dev`. Nessun push, PR remota, merge, release o deploy eseguito.

## Descrizione proposta

Il workspace Tasks 0.8.0 leggeva uno stato inesistente per ProjectTask e delegava
la ricerca a un URL privo di controller nativo. Corregge campo/tabella degli
stati, target della ricerca e URL con sottocartella; preserva le ACL native
di task/team/entità, nasconde i dati dei progetti non leggibili e guida alla
creazione nel form GLPI con un progetto esplicito autorizzato.

Migliora la board read-only con filtri combinabili, ordinamento, date native
distinte, avvisi per campioni parziali, stati vuoti/errore e layout accessibile
responsive light/dark. Riutilizza ricerca, dropdown, form, configurazione e
permessi GLPI. Il Kanban Vue e il suo drag & drop restano nativi, raggiungibili
dal progetto; l'adattatore globale di sole task resta giustificato dai limiti
del Kanban globale del core. Nessuna nuova tabella, versione o modifica al core.

Validazione locale: 67 test PHP moduli/RSS, 59 Tasks, 80 UI dashboard, 167 UI
Tasks; lint PHP/Bash/JS. Build unica con checksum verificabile. Tutti i test
funzionali usano doubles/DOM simulato: nessuna istanza GLPI disponibile.

Prima di approvare il passaggio a Main, collaudare su GLPI 11.0.9 di staging:
menu/configurazione dopo nuovo login, ricerca AJAX/paginazione/export, creazione
e modifica, almeno due profili e due entità con gruppi/ricorsività, fusi/temi reali
e drag & drop del Kanban nativo. Includere il creatore non nel team e i diritti
READMY/READALL combinati: le ACL Search e scheda del core hanno percorsi diversi.
Stage indipendente, task personali senza progetto
e worklog restano decisioni separate, senza implementazione strutturale.

Rapporti: [implementazione](TASKS_IMPLEMENTATION.md),
[decisioni aperte](TASKS_OPEN_DECISIONS.md), [QA](../../QA.md).
