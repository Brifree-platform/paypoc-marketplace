# 00 — Project Rules · La Costituzione del progetto

> **Sistema:** IWEXA + PayPoc — dove **IWEXA** è il nome ufficiale del sistema centrale.
> **Repository tecnico:** `paypoc-marketplace` (nome del repository reale, non del sistema).
> **Documento:** `docs/foundation/00-project-rules.md`
> **Stato:** Approved (STEP 01B)
> **Versione:** 1.0.0
> **Data:** 2026-07-23
> **Approvazione:** approvato il **2026-07-23** da **Cristiano Plattner** (Committente e
> responsabile del progetto), con le modifiche obbligatorie recepite in questa versione.
> **Ambito:** governa la **documentazione**, il **contratto** e il **processo** del
> sistema IWEXA + PayPoc. **Non** autorizza alcuna modifica al codice.
> **Prerequisito:** STEP 01A — Repository Discovery — concluso e approvato.

---

## Preambolo

Questo documento è la **norma di grado più alto** del progetto: ogni altro documento,
decisione e convenzione le è subordinato. Esiste per una ragione precisa, emersa dal
Repository Discovery (STEP 01A):

- del progetto era stato scritto **codice prima che il contratto fosse stabile**, con il
  risultato di un'architettura invertita rispetto all'obiettivo;
- la **documentazione divergeva dal codice** (il README del package dichiarava
  protezioni e componenti inesistenti);
- non esisteva una **distinzione formale** fra "ciò che è" e "ciò che deve diventare",
  né un modo per **tracciare le decisioni**.

La Costituzione codifica le regole che impediscono il ripetersi di questi fallimenti.
Non è aspirazionale: è **vincolante** per chiunque scriva codice, contratto o
documentazione in questo repository.

---

## Articolo 0 — Ambito e destinatari

1. Si applica a **tutti** i contributori: sviluppatori, architetti, autori di
   documentazione, committente, e a qualunque agente automatico.
2. Governa: la cartella `docs/`, il **contratto** (`docs/iwexa_hub_openapi_v*.yaml`), il
   mock e la suite di conformità, e il **processo** di decisione/revisione.
3. **Non** è un mandato a modificare il codice. Le regole che nominano il codice ne
   disciplinano il *rapporto con la documentazione*, non ne autorizzano l'alterazione.

## Articolo 1 — Obiettivo della documentazione

1. La documentazione ha **un solo scopo**: essere una rappresentazione **vera,
   verificabile e non ambigua** del sistema — di ciò che è e di ciò che deve diventare.
2. Ogni affermazione deve essere **ancorabile** a una fonte: codice reale, contratto,
   una decisione (ADR) o un documento tracciato. Un'affermazione senza fonte non è
   documentazione: è opinione, e non entra.
3. È **vietato presentare come esistente** ciò che è solo previsto. La distanza fra
   "sembra fatto" e "è fatto" è la causa prima dei problemi del progetto (vedi Preambolo).
4. La documentazione **descrive**; non decide (le decisioni stanno negli ADR) e non
   implementa (l'implementazione sta nel codice).

## Articolo 2 — Distinzione obbligatoria AS-IS / TO-BE

1. Ogni documento, sezione o affermazione che riguarda il comportamento del sistema deve
   dichiarare senza ambiguità se descrive lo **stato attuale (AS-IS)** o l'**architettura
   target (TO-BE)**.
2. **AS-IS** = ciò che esiste ed è eseguibile **oggi** nel repository. La fonte
   autorevole dell'AS-IS è il **codice** (vedi Articolo 11).
3. **TO-BE** = ciò che il sistema deve diventare. La fonte autorevole del TO-BE è il
   **contratto** (per l'integrazione) e gli **ADR** (per le scelte architetturali).
4. **Mescolare i due piani in un'unica affermazione non dichiarata è una violazione.**
   Un documento può trattarli entrambi, ma sempre separati e marcati.
5. Convenzione minima: usare intestazioni o etichette esplicite `AS-IS` / `TO-BE`, e le
   etichette di fonte dell'Articolo 10.

## Articolo 3 — Contract First (principio di integrazione)

1. L'integrazione fra PayPoc e **IWEXA** è governata **prima dal contratto, poi dal
   codice**. La **baseline contrattuale attualmente approvata** è il **contratto v3.1**
   (`docs/iwexa_hub_openapi_v3.1.yaml`) insieme al **mock** e alla **suite di conformità**:
   insieme costituiscono la fonte di verità del comportamento di integrazione fino
   all'approvazione di una versione successiva.
2. Il codice **si conforma al contratto**, mai il contrario per via implicita. Se il
   codice diverge dal contratto, è il codice a essere in difetto — ma il contratto **non
   autorizza automaticamente la modifica del codice** (vedi Articolo 5 e Articolo 11).
3. Il contratto v3.1 è **baseline attualmente approvata, non un riferimento immutabile o
   definitivo**. Resta valido finché non viene **corretto, esteso, sostituito o superato**
   da una nuova versione approvata tramite **ADR**. Ogni cambiamento del contratto avviene
   solo deliberatamente: **nuova versione + ADR + aggiornamento di mock e suite**. Non si
   "aggiusta" il contratto per farlo combaciare con codice esistente senza decisione.
4. Il contratto **non può prevalere sulla Costituzione né su un ADR architetturale
   approvato** (vedi Articolo 11).
5. La **suite di conformità è il criterio di accettazione** dell'integrazione: una
   componente che non la supera non è integrabile, per quanto sembri corretta.
6. Nessuna integrazione (**IWEXA**, marketplace, corrieri, pagamenti) entra in sviluppo
   senza un contratto dichiarato per essa, anche minimo.

## Articolo 4 — Obbligo degli ADR (Architecture Decision Record)

1. **Ogni decisione architetturale** deve essere registrata in un **ADR**. Nessuna
   decisione architetturale è valida se non è scritta.
2. È "architetturale" (non esaustivo) qualsiasi decisione su: direzione di integrazione,
   modello dati, autenticazione/sicurezza, confini di modulo, scelta o rimozione di una
   tecnologia, versione del contratto, politica di versionamento, ciò che si tiene/
   riscrive/elimina del codice esistente.
3. Gli ADR risiedono in **`docs/adr/`**, numerati progressivamente **a tre cifre**
   (`ADR-001-<slug>.md`, `ADR-002-<slug>.md`, `ADR-003-<slug>.md`).
4. Formato minimo di un ADR: **Titolo · Stato · Contesto · Decisione · Conseguenze ·
   Alternative considerate**. Stati ammessi: `Proposed` → `Accepted` → (`Superseded by
   ADR-NNN` | `Deprecated`).
5. Gli ADR sono **append-only e immutabili** una volta `Accepted`: non si cancellano né
   si riscrivono. Una decisione che cambia si esprime con un **nuovo** ADR che *supersede*
   il precedente. La storia delle decisioni deve restare leggibile.
6. Un documento che afferma una scelta architetturale deve **citare l'ADR** che la
   stabilisce (`[DECISION: ADR-NNN]`). Senza ADR, non è una decisione: è una proposta.

## Articolo 5 — Divieto di modifica automatica del codice in conflitto con la documentazione

1. Quando **documentazione e codice sono in conflitto**, è **vietato modificare
   automaticamente il codice** per farlo combaciare con il documento.
2. La ragione è storica e vincolante: nel progetto la documentazione **si è già rivelata
   errata** rispetto al codice (README che dichiarava un'autenticazione inesistente).
   Adeguare il codice a un documento potenzialmente sbagliato **distrugge codice corretto
   sulla base di un'affermazione falsa**.
3. In caso di conflitto, la procedura obbligatoria è:
   a. **fermarsi** e non toccare il codice;
   b. **classificare** il conflitto secondo la gerarchia dell'Articolo 11 (l'AS-IS lo
      dice il codice; il TO-BE lo dice contratto/ADR);
   c. se il documento descriveva erroneamente l'AS-IS → **si corregge il documento**;
   d. se la divergenza è una scelta di TO-BE → si apre un **ADR** e si decide
      esplicitamente; solo un ADR approvato può poi autorizzare un lavoro sul codice, che
      resta comunque fuori dal perimetro della documentazione;
   e. registrare il conflitto e la risoluzione.
4. Nessun automatismo (script, agente) può alterare il codice in conseguenza di una
   divergenza documentale. La documentazione **non ha potere esecutivo sul codice**.

## Articolo 6 — Regole di aggiornamento della documentazione

1. La documentazione si aggiorna **quando cambia la realtà che descrive** (codice,
   contratto, decisione) — non prima, non "in previsione".
2. Ogni aggiornamento deve **preservare la distinzione AS-IS/TO-BE** e le etichette di
   fonte (Articolo 10).
3. Un documento **AS-IS obsoleto è un difetto** da correggere, non da tollerare: se il
   codice cambia e il documento resta indietro, il documento è in errore.
4. Le copie statiche (bundle, PDF, export) sono **fotografie non autorevoli**: alla
   divergenza prevale sempre il documento versionato nel repository.
5. Ogni documento porta in testa: **Stato, Versione, Data, Ambito**; e in coda un
   **changelog** essenziale.
6. Non si aggiunge documentazione "definitiva" o architetturale finché lo **STEP** di
   processo corrispondente non lo prevede e non è approvato (governance a step).

## Articolo 7 — Versionamento

1. **Documenti di foundation** (`docs/foundation/`): versione **SemVer** `MAJOR.MINOR.PATCH`.
   - `PATCH`: correzioni redazionali senza cambio di norma.
   - `MINOR`: aggiunta di regole/sezioni compatibili.
   - `MAJOR`: modifica di una regola vincolante (richiede l'iter dell'Articolo 12).
2. **Contratto**: versionato indipendentemente con il proprio numero (**baseline
   attualmente approvata: v3.1**, vedi Articolo 3). Un cambiamento incompatibile alza la
   major del contratto (`v4.0`), uno compatibile la minor (`v3.2`); ogni cambio è
   accompagnato da un ADR e dall'**aggiornamento di mock e suite**.
3. **ADR**: numerazione progressiva immutabile; non versionati singolarmente (si superano,
   non si versionano).
4. Ogni documento tiene un **changelog** (data · versione · sintesi · autore/revisore).
5. Le date sono **assolute** (ISO `YYYY-MM-DD`), mai relative.

## Articolo 8 — Revisione e approvazione

**Ruoli (distinti e non sovrapponibili).**

1. **Approvatore finale:** il **Committente e responsabile del progetto**. È l'unico che
   può approvare i documenti di Foundation e gli ADR e dichiararli `Accepted`.
   **Per questa fase l'approvatore finale è Cristiano Plattner.**
2. **Responsabile della proposta tecnica:** il **Principal Software Architect**, che
   analizza, progetta e propone. **Autore tecnico e approvatore finale restano distinti:**
   chi propone non approva.

**Un agente automatico (Claude o qualsiasi altro) — può:**

3. analizzare; proporre; documentare; segnalare conflitti e rischi.

**Un agente automatico (Claude o qualsiasi altro) — non può:**

4. approvare autonomamente documenti di Foundation o ADR;
5. dichiarare `Accepted` una decisione senza **conferma esplicita del committente**;
6. **interpretare il silenzio come approvazione**.

**Processo.**

7. Ogni documento di Foundation e ogni ADR entra tramite **revisione esplicita**: nessuna
   auto-approvazione.
8. Il progetto procede a **STEP con gate di approvazione**: uno step non inizia finché il
   precedente non è approvato (es. STEP 01B → STEP 01C). L'approvazione è **registrata**.
9. Le modifiche passano da **change-set tracciabile** (commit/PR) con almeno **un
   revisore** diverso dall'autore, quando il contesto lo consente.
10. Una modifica a un documento AS-IS che contraddice il codice **non è approvabile**
    finché non è verificata sul codice reale.

## Articolo 9 — Terminologia e Glossario

1. Il **Glossario** sarà un documento ufficiale dedicato (previsto in
   `docs/foundation/`) e diventerà la **fonte unica** dei termini. Fino ad allora valgono
   le regole di questo Articolo.
2. Si usano **i termini tecnici reali del codice**: `Vendor`, `Vendor Store`,
   `Vendor Warehouse`, `vendorCode`, `warehouse`, `FBI`/`FBV`, `EAN`.
3. Il termine **"Vendor" diventa "Maestro" solo nell'interfaccia mostrata al cliente**:
   è **esclusivamente il nome di presentazione**. **Vendor non deve essere rinominato
   "Maestro" nel codice, nel database, nelle API o nei contratti**, dove resta `vendor*`.
   La documentazione tecnica usa **Vendor**, non "Maestro".
4. Un termine ha **un solo significato** in tutto il progetto. Sinonimi ambigui vanno
   risolti nel Glossario, non lasciati alla lettura.
5. Termini **non presenti nel repository** (emersi in Discovery: *"potere d'acquisto"*,
   *"Product Acceptance"*) sono `UNKNOWN` finché il Glossario non li definisce con una
   fonte. Non se ne assume il significato.
6. **Non introdurre termini alternativi** — in particolare *Purchasing Power*,
   *Credit Engine*, *Seller*, *Merchant*, *Hub* — salvo che siano citati esplicitamente
   come **sinonimi non approvati**, oppure siano **nomi realmente esistenti nel codice**
   (es. il file `iwexa_hub_openapi_*.yaml`). Il sistema centrale si nomina **IWEXA**.

## Articolo 10 — Convenzioni di etichettatura delle fonti

Ogni affermazione rilevante è marcata con **una o più** etichette che ne dichiarano la
natura e la fonte:

| Etichetta | Significato | Fonte autorevole |
|---|---|---|
| `CODE` | Esiste ed è eseguibile **oggi** nel repository (fatto AS-IS) | il codice |
| `CONTRACT` | Definito nel contratto v3.x / mock / suite (spec TO-BE, non implica implementazione) | il contratto |
| `DOC` | Descritto **solo** in un documento di progetto (intento/piano; non codice, non contratto) | il documento citato |
| `DECISION` | Stabilito da un ADR approvato | l'ADR (`ADR-NNN`) |
| `TODO` | Lavoro noto e pendente, tracciato | il tracker/ADR/documento che lo apre |
| `UNKNOWN` | Non determinabile dal repository / questione aperta | — (va risolto, non presunto) |

Regole d'uso:
1. Sintassi inline: `[CODE]`, `[CONTRACT]`, `[DOC: <file>]`, `[DECISION: ADR-NNN]`,
   `[TODO: <rif>]`, `[UNKNOWN]`.
2. Un'affermazione può combinare etichette (es. un modulo `[CODE]` parziale ma
   `[CONTRACT]` completo). L'assenza di etichetta su un'affermazione fattuale è un difetto.
3. `CODE` e `DOC` **non sono intercambiabili**: se una cosa è solo in un documento, è
   `DOC`, mai `CODE`. Confonderli è la violazione descritta nell'Articolo 1.3.
4. `UNKNOWN` è una risposta **legittima e obbligatoria** quando la fonte manca: non si
   riempie un vuoto con una supposizione.

## Articolo 11 — Gerarchia delle fonti e risoluzione dei conflitti

1. **Per lo stato AS-IS** (ciò che il sistema è): **CODE prevale su DOC**. In conflitto,
   prevale il codice e si corregge il documento (mai il codice — vedi Articolo 5).
2. **Per il comportamento previsto dell'integrazione**: **CONTRACT prevale sul codice non
   conforme**, ma **non autorizza automaticamente la modifica del codice**.
3. **Per le decisioni architetturali**: un **ADR `Accepted` prevale sugli altri documenti
   progettuali**.
4. **Gerarchia normativa generale** (dal grado più alto):
   1. `00-project-rules.md`
   2. ADR `Accepted`
   3. contratto approvato
   4. Foundation e altri documenti
   5. documentazione descrittiva
   6. codice, **limitatamente alla descrizione dell'AS-IS**
5. Questa gerarchia **non significa** che il codice debba essere modificato
   automaticamente: ogni adeguamento richiede uno **STEP di implementazione esplicitamente
   approvato**.
6. Ogni conflitto va **registrato e risolto esplicitamente**, mai silenziosamente.

## Articolo 12 — Emendamenti alla Costituzione

1. Questo documento si modifica **solo** tramite un **ADR di emendamento** approvato
   dall'**approvatore finale** (Articolo 8.1), con incremento **MAJOR** della versione se
   cambia una regola vincolante.
2. Nessuna modifica retroattiva silenziosa: gli emendamenti sono tracciati nel changelog
   e motivati nell'ADR.
3. In caso di dubbio interpretativo, prevale la lettura che **massimizza la verificabilità
   e la separazione AS-IS/TO-BE** (lo scopo dell'Articolo 1).

---

## Changelog

| Data | Versione | Modifica | Stato |
|---|---|---|---|
| 2026-07-23 | 1.0.0-draft | Prima stesura della Costituzione (STEP 01B). | In approvazione |
| 2026-07-23 | 1.0.0 | Recepite le modifiche obbligatorie del committente: nome ufficiale del sistema **IWEXA + PayPoc**; percorso ADR ufficiale `docs/adr/` con numerazione a tre cifre; ruoli di approvazione distinti (approvatore finale vs proposta tecnica) e limiti degli agenti automatici; contratto v3.1 come **baseline contrattuale attualmente approvata**; gerarchia delle fonti; regole di terminologia; correzioni formali. Documento **approvato da Cristiano Plattner**. | Approved |
</content>
