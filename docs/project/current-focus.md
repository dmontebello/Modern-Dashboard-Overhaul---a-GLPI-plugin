# MDO — Focus corrente (snapshot 25 ottobre 2026)

**I valori sotto sono stati verificati prima del commit di governance; ricontrollare GitHub live.** Gli stati degli item sono **solo** nel [backlog](../product/backlog.md).

| Fonte | Verifica |
|---|---|
| `Main` | `0dd4debe1b68088190132c0dfcca9c3bae068099`, 0.8.0 Pilot |
| `Dev` all'audit | `3f1a5b398d92a2b064ddcb6cc15c44eb2ae85889`, codice e ZIP 0.8.1-dev.6; dopo questo documento HEAD cambierà |
| Dev.6 sorgente testato | `080532ab6c6e5774e5445ddd80dec0c938e43726` |
| Build dev.6 | [Actions #40225944788 SUCCESS](https://github.com/dmontebello/Modern-Dashboard-Overhaul---a-GLPI-plugin/actions/runs/40225944788); [metadata e checksum](../releases/0.8.1-dev.6.build.json) |
| Collaudo GLPI | Codice/CI presenti, **schema2, ACL, POST, upload/download, DnD e backup/rollback non collaudati live** |
| PR | [#3](https://github.com/dmontebello/Modern-Dashboard-Overhaul---a-GLPI-plugin/pull/3) integrata in Dev; la sua descrizione WIP originaria va letta come snapshot precedente al run finale |

## Prima azione prioritaria

**P0: collaudo mirato della 0.8.1-dev.6 su copia GLPI 11.0.9**, con snapshot coordinato database + plugin + `GLPI_DOC_DIR/mdoverhaul`, upgrade amministrativo schema1→2 e prove reali di permessi (READMY/READALL, più profili, due entità, altre entità negate), POST Stage (CSRF e concorrenza), Kanban, diario completo, file privati e restore. Procedure nella [matrice staging](../dev/STAGING_0_8_1.md) e nella [release note](../releases/0.8.1-dev.6.md). Non eseguire su produzione senza autorizzazione e backup.

**P0: regressioni** TASK-01 (creazione ProjectTask fino a Salva), SQL 1054 Kanban, filtro Search Standalone, Settings dark, layout mini Ticket, toolbar dashboard e legenda. Il generico riscontro storico «Funziona» non copre tutti i casi; annotare PASS/FAIL/NON ESEGUITO per [item](../product/backlog.md).

**Prossimo sviluppo autorizzato:** dev.7 form unico nuova task con Project facoltativo; seguono dev.8 checklist, dev.9 conversione e link bidirezionali, poi 0.8.2 brief progetto. Consultare [Tasks V2](../specs/TASKS_UNIFIED_WORKSPACE_V2.md) e [roadmap dettagliata](../specs/TASKS_CHECKLIST_CONVERSION_LINKS_ROADMAP.md). Dev.6 ancora in collaudo, non promuovere `Main` sulla sola CI.

## Rischi/decisioni aperti

- Vista Tutte limitata a **1000 candidate per origine**: filtri e totali parziali; performance/ACL da misurare.
- Stage operativo ProjectTask deriva dalla percentuale nativa; Stage/Status Notion realmente indipendenti e worklogs sono [scelte aperte](../product/open-decisions.md).
- MDO note/allegati e note/documenti nativi ProjectTask non sono un sistema unico; preservare gli uni e gli altri.
- I vecchi file `docs/dev/STATUS.md`, `docs/RELEASE_PROCESS.md`, `UPDATE.md` contengono sezioni storiche: non usarne le prime righe come fonte del candidato attuale.
- [Controlli documentali](documentation-checks.md): attuale CI valida documenti release ma non ancora ID/link/stati del backlog. Il publisher immutabile richiede un bump quando si tocca tooling/workflow.

## Cosa lasciare alla prossima chat

Aggiornare nel repository [backlog](../product/backlog.md), [implementation-state](../implementation-state.md), questa pagina, specifiche/decisioni, QA e release notes pertinenti. Annotare SHA esatto, CI, build, prova reale con esito e prossimo passo. Senza accesso scrittura fornire patch e dichiarare il limite.


## Checkpoint dev.7 — 25 ottobre 2026

MDO-TASK-007 è in sviluppo sul branch dedicato. Form unico senza scelta preliminare, mappatura ProjectTask/StandaloneTask e test dedicati; ancora da consolidare il run CI esatto, il publisher Dev e il collaudo reale. MDO-TASK-003–006 restano in collaudo operativo dev.6. Next milestone dev.8 checklist, senza anticipare conversione dev.9.


## Consuntivo 0.8.1-dev.7 — pubblicazione verificata, 25 ottobre 2026

**MDO-TASK-007 IN COLLAUDO OPERATIVO; NON COLLAUDATO LIVE.** Build GitHub Actions [#40236015750](https://github.com/dmontebello/Modern-Dashboard-Overhaul---a-GLPI-plugin/actions/runs/40236015750): bundle PASS, publish_dev_zip PASS; source SHA `6aa2654fa81d6e65be4184256d06a81d66603753`, compilazione UTC 2026-10-25T21:34:49Z; archivio immutabile `Modern_Dashboard_Overhaul_0.8.1-dev.7.zip`; SHA512 `4d45752f6c40907adeea3ee40ee2e8c631b965231717aefce0945eba821fd8def701ad0786ffc3897be4d630c24160f05651f3a211e5e9e664081d19c3e2b2c3`. Metadati in [0.8.1-dev.7.build.json](releases/0.8.1-dev.7.build.json). Test automatici: 67 unit PHP, 69 Tasks, 62 Standalone, 44 Task Activity, 26 creazione unificata; Chromium 86 Dashboard, 175 Tasks, 72 nuova task, 560 Settings, 108 Standalone. 9 test packaging. **Prima pipeline ufficiale fallita solo sul gate documentale UPDATE.md privo della versione dev.7; aggiunta nota, nuovo test di non-scrittura su ACL negato e CI finale verde.** Non confondere mock con salvataggio o permessi reali GLPI 11.0.9. Next steps: backup DB+plugin+GLPI_DOC_DIR, staging test nuovo form con progetto/indipendente, READMY/entità, CSRF, Select2, scadenza, verifica unicità inserimento. `Main` e produzione invariati; prossima feature prevista dev.8 checklist.


## Focus 26 ottobre 2026 — nuovo feedback di collaudo

**P0 ora:** correggere DnD dopo il primo spostamento (token CSRF consumato GLPI11) e UX visuale drag; **P1:** eliminazione per riga dalla lista Tutte con DELETE per item, cestino, conferma e AJAX. Sviluppo dev.8 UX su branch dedicato. L'utente preferisce immediatezza e visualizzazione alle estensioni standalone: lasciare le indipendenti opzionali, non rimuovere dati o cambiare modello senza decisione esplicita. Checklist posticipata. Prova live del fix e CI ancora da acquisire, Main invariato.


## Priorità 26/10 — Kanban dev.9

Tre P0 in sviluppo: Project tutte colonne stati, All DnD misto, numeri colonne immediati. Implementare test PHP e Chromium e verificare LIVE in staging prima di CHIUSA. In Dev.9 solo bugfix, checklist e conversione rinviate. Non modificare Main.

## Consuntivo 0.8.1-dev.9 — 26 ottobre 2026

**PUBBLICATA SU Dev, NON COLLAUDATA LIVE.** [GitHub Actions #40306267311](https://github.com/dmontebello/Modern-Dashboard-Overhaul---a-GLPI-plugin/actions/runs/40306267311): `bundle` PASS e `publish_dev_zip` PASS. Source SHA `0c6e810711a2c08c7a111573b57b516a27c4065d`, ZIP `Modern_Dashboard_Overhaul_0.8.1-dev.9.zip`, manifest e sidecar SHA512 `6ada5ce06f3bb97db1fed21d24a0cc3e1fed7a3d2722f37750819876abb018077315c90c96670af300589ebc84621cb1ab75f694191f0c1454ba851a97148658`. Provenienza completa in `docs/releases/0.8.1-dev.9.build.json` (build UTC 2026-10-26T10:19:10Z). Suite PHP: 67 unit, 72 Tasks, 62 Standalone, 48 Activity, 26 creazione e 11 UX delete. Chromium: 86 Dashboard, 175 Tasks, test DnD ripetuto e mixed All per due sorgenti, 72 form, 560 Settings e 108 Standalone. Tutto su GLPI doubles e Chromium fixture, NON equivalenti al backend reale.

**MDO-TASK-016/017/018: IN COLLAUDO OPERATIVO**: colonne ProjectState compresi stati vuoti, drag nella vista All per Project/Independent, contatori immediati. Prove LIVE ancora aperte: Project con sola task Processing mostra altri stati a 0; DnD stesso elemento ripetuto in Project/Independent/All; 2 TODO e 1 Processing -> 1 TODO e 2 Processing senza refresh; DB/persistenza/ACL/CSRF; verificare auto_projectstates impostato a 0 su spostamento nativo. Stato dopo il fix NON CHIUSO. Main 0.8.0 invariato, niente deploy automatico, nessuna migrazione DB rispetto a dev.8. Checklist e conversioni rimangono in backlog.



## Nuovo focus 26 ottobre 2026 — Project Kanban non trascinabile

Bug LIVE dev.9: Project mostra 0/1-99/100 e solo Independent DnD. Fix candidato dev.10: Project = stati GLPI nativi di default; All = Kanban default; se core configura 3 stati, il drag All aggiorna nativamente ProjectState senza toccare percent_done automatico; UI card 260×78. Collaudo real-world e fallback su mapping assente ancora da fare. Checklist e conversione restano futuri. Main invariato.


## Dev.10 pubblicata — CI 26/10/2026

Source tested `9ddd6396523eea8a4d6edf1c3ac90c2023769a1c`; Actions [#40321113463](https://github.com/dmontebello/Modern-Dashboard-Overhaul---a-GLPI-plugin/actions/runs/40321113463) PASS (bundle + publish). Artefatto immutabile `Modern_Dashboard_Overhaul_0.8.1-dev.10.zip`, SHA512 `fd874d54955f9e58d58788231ec2e30b8d505c5089cc91f72ff2ee18b8ad62b8912f20ce8c4e2d6a96d74abed5fbd7f6bd4883e2e4ce97de1a268b70dc60ccd2`; metadati `docs/releases/0.8.1-dev.10.build.json`. Stato dei fix MDO-TASK-019, MDO-UI-004, MDO-UI-005: **IN COLLAUDO OPERATIVO**, NON certificati LIVE. Main resta 0.8.0. All / Kanban e Project / stato GLPI di default, card ~260×78; mappatura nativa stato usata in All quando presente nella configurazione GLPI o univoca per nome. Verificare mapping reale e automatic-progress del progetto, contatori e permessi. In assenza di mapping univoco non inventare ID o aggirare ACL.
