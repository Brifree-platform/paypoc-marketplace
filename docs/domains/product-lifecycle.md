# Product Lifecycle

> **Sistema:** IWEXA + PayPoc
> **Documento:** `docs/domains/product-lifecycle.md`
> **Livello:** Documento di dominio (processo) — vedi [`docs/domains/README.md`](README.md)
> **Tipo:** **normativo**
> **Stato:** In lavorazione (stesura incrementale, **una sezione alla volta**)
> **Versione:** 0.1.0-draft
> **Data:** 2026-07-27
> **Approvatore finale:** Cristiano Plattner
> **Responsabile della proposta tecnica:** Principal Software Architect
> **Prerequisiti:** `docs/domains/README.md` (Approved 1.0.0) · Foundation `03` (modello prodotto) ·
> `04` (Platform) · ADR-004 · ADR-005
> **Nota:** documento **normativo** costruito **per sezioni**, ciascuna approvata
> indipendentemente; raggiunge `Approved` **solo quando tutte le sezioni sono approvate**. Le
> sezioni da «Stati» in poi contengono decisioni architetturali: dove non decise, il documento
> pone **Open Questions** e rimanda agli **ADR**, **senza deciderle implicitamente**.

---

## Stato delle sezioni (avanzamento)

Struttura da indice approvato. Legenda: 🟡 in approvazione · ⚪ da scrivere · ✅ approvata ·
⚠️ contiene decisioni architetturali (Open Question/ADR).

| Sezione | Stato |
|---|---|
| **0 · Scopo** | ✅ **Approved 1.0.0** (2026-07-27) |
| 1 · Obiettivo del Product Lifecycle | ✅ **Approved 1.0.0** (2026-07-27) |
| 2 · Principi | ✅ **Approved 1.0.0** (2026-07-27) |
| 3 · Attori | 🟡 **in approvazione** |
| 4 · Stati | ⚪ ⚠️ |
| 5 · Transizioni | ⚪ ⚠️ |
| 6 · Eventi | ⚪ ⚠️ |
| 7 · Policy | ⚪ ⚠️ |
| 8 · Responsabilità | ⚪ |
| 9 · Ingresso / Acquisizione | ⚪ |
| 10 · Creazione / Normalizzazione | ⚪ |
| 11 · Arricchimento | ⚪ |
| 12 · Validazione & Accettazione | ⚪ ⚠️ |
| 13 · Pubblicazione | ⚪ ⚠️ |
| 14 · Aggiornamenti | ⚪ ⚠️ |
| 15 · Sospensione | ⚪ ⚠️ |
| 16 · Ritiro (end-of-life) | ⚪ ⚠️ |
| 17 · Archiviazione | ⚪ ⚠️ |
| 18 · Allineamento Glossario | ⚪ |
| 19 · Open Questions | ⚪ |
| 20 · ADR collegati | ⚪ |
| 21 · Relazioni con altri documenti | ⚪ |

---

## 0 · Scopo

Questo documento definisce il **Product Lifecycle**: il modello **normativo** della *vita* del
prodotto — i suoi **stati, fasi, transizioni, eventi, responsabilità** e il **fine-vita** (aggiornamenti,
sospensione, ritiro, archiviazione) — attraverso i domini che lo attraversano.

È un documento **di processo** del livello dei documenti di dominio ([`docs/domains/`](README.md)),
**governato dalla Foundation**. Ha **natura normativa**: le sue affermazioni vincolano il disegno
del prodotto, **nei limiti delle decisioni già formalizzate**. Dove una decisione non esiste, il
documento **pone la domanda** (Open Question) e **rimanda all'ADR**, senza deciderla.

> **In una riga:** il **Product Model** (`03`) definisce **cosa il prodotto *è*** — la sua
> *struttura*; il **Product Lifecycle** (questo documento) definisce **come il prodotto *evolve
> nel tempo*** — i suoi *stati e fasi*. **Product Model ≠ Product Lifecycle.**

### 0.1 · Relazione con `03` e `04`

- La **struttura dati** del prodotto — il **Canonical Product Model** — è definita in
  [`foundation/03-product-model-strategy.md`](../foundation/03-product-model-strategy.md) `[DOC]`.
  Questo documento la **referenzia**, non la ridefinisce.
- I **confini delle Platform** (Core Product, Enrichment, Compliance, Publication, Fulfillment &
  Logistics…) sono definiti in
  [`foundation/04-enterprise-platform-decomposition.md`](../foundation/04-enterprise-platform-decomposition.md).
  Per la **regola cardine** del livello, **nessun documento di dominio ridefinisce i confini
  delle Platform** (`04`): questo documento vi si **appoggia**.
- In sintesi: **`03` (Product Model) = cosa il prodotto *è*** (struttura) · **questo documento
  (Product Lifecycle) = come il prodotto *evolve nel tempo*** (stati e fasi). **Product Model ≠
  Product Lifecycle.**

### 0.2 · Fuori scope

Questo documento **non** definisce, e nulla in esso va interpretato come definizione di:

- specifiche API, schema database, entità, JSON, classi, Laravel/Bagisto, codice, algoritmi,
  implementazione (coerente con [`03` §9](../foundation/03-product-model-strategy.md));
- la **policy di accettazione** in dettaglio → `product-acceptance` (⚠️ Open Question / ADR);
- la **meccanica di distribuzione sui canali** → `publication` (⚠️ sbocco canali = ADR-002);
- il **Modello Economico** (Potere, Wallet/Credito) → **livello OS**.

Questi aspetti sono **referenziati** qui, ma **decisi altrove**.

> **Nota normativa.** Eventuali riferimenti a elementi implementativi presenti nel documento
> hanno valore **esclusivamente esplicativo** e **non normativo**.

## 1 · Obiettivo del Product Lifecycle

*(La §0 definisce **cosa** è questo documento. Questa sezione risponde a una domanda diversa —
**perché** il progetto ha bisogno del Product Lifecycle come documento autonomo, e **quale
obiettivo** persegue — senza ripetere le definizioni della §0.)*

### 1.1 · Perché esiste il Product Lifecycle

Il **Product Model** (`03`) descrive la **struttura** del prodotto — cosa il prodotto *è*. La
struttura, però, **non descrive il comportamento del prodotto nel tempo**: come nasce, come
evolve e come si conclude il suo ciclo di vita.

Senza un riferimento dedicato, questo comportamento resterebbe **implicito e distribuito** tra
domini e processi diversi, con il rischio che ciascuno interpreti a modo proprio *quando* e
*come* lo stato di un prodotto possa cambiare.

Il progetto necessita quindi di un **modello normativo unico** che governi l'**evoluzione** del
prodotto: è questa la ragione per cui il Product Lifecycle esiste come documento **autonomo**,
distinto dal Product Model.

### 1.2 · Obiettivo

L'obiettivo di questo documento **non** è *descrivere il prodotto* — lo fa il Product Model — ma
**governarne l'evoluzione**. In particolare:

- **garantire la coerenza** dell'evoluzione del prodotto lungo tutto il suo ciclo di vita;
- **garantire l'uniformità** con cui il prodotto evolve, evitando comportamenti eterogenei tra contesti;
- **evitare interpretazioni divergenti** tra i domini che toccano il prodotto;
- **costituire il riferimento normativo** per **ogni processo che ne governa l'evoluzione**.

### 1.3 · Criterio di successo

Il documento raggiunge il proprio scopo quando vale questo principio:

> **Qualunque processo che modifica lo stato di un prodotto deve poter essere ricondotto al
> Product Lifecycle, oppure essere formalmente dichiarato fuori scope.**

Non è ammessa una terza possibilità: nessun cambiamento di stato **informale, implicito o non
ricondotto** a questo documento.

Inoltre, come **regola di governance**:

> **Nessun altro documento può introdurre autonomamente modifiche al ciclo di vita del
> prodotto.** Ogni evoluzione del modello di ciclo di vita passa **esclusivamente** per questo
> documento e per gli **ADR** che lo governano; gli altri documenti di dominio vi si
> **conformano**, non lo estendono di propria iniziativa.

## 2 · Principi

I principi seguenti **guidano** il ciclo di vita del prodotto e **vincolano** ogni definizione
successiva. **Sono principi, non decisioni:** non stabiliscono *quali* evoluzioni esistano, ma le
**regole** cui quelle definizioni dovranno conformarsi.

1. **Unicità.** Il prodotto ha **un solo** ciclo di vita normativo: nessun ciclo parallelo o
   divergente per dominio.
2. **Tracciabilità.** Ogni evoluzione del prodotto è **registrata e attribuibile**; nessun
   cambiamento silenzioso o non ricostruibile (coerente con `00` Art. 11.6).
3. **Explicit Evolution.** Il prodotto evolve **solo per atti espliciti e dichiarati**, mai come
   **effetto collaterale implicito** di un altro processo.
4. **Separation of Policy and Execution.** Chi **definisce** una regola di evoluzione è distinto
   da chi la **esegue** (principio ereditato da `04`).
5. **Subordinazione normativa.** Il ciclo di vita opera **entro la gerarchia** del progetto
   (Ragione di Esistere → `00` → ADR → Foundation → documenti di dominio) e le è **subordinato**.
6. **Single Source of Lifecycle.** Il ciclo di vita del prodotto ha **un'unica fonte
   autorevole**: **IWEXA** (`[DOC: 03]`, dove ne è stabilita l'autorità sul prodotto); gli altri
   domini vi si **conformano**.

## 3 · Attori

Questa sezione elenca **chi partecipa** al ciclo di vita del prodotto e con **quale autorità**.
Per ogni attore: **Ruolo · Responsabilità · Limiti di autorità**. **Non** si introduce qui alcuna
sequenza di stati o di transizioni.

### 3.1 · IWEXA
- **Ruolo:** **Single Source of Lifecycle** (§2.6) — sistema centrale e autorità unica sul
  prodotto e sulla sua evoluzione. `[DOC: 03]`
- **Responsabilità:** possedere il prodotto, la sua identità e la sua evoluzione; **è l'unico**
  che può farlo evolvere, e ne registra ogni cambiamento (§2.2, §2.3).
- **Limiti di autorità:** **non possiede tutte le policy** — alcune sono **esterne** (Compliance)
  o dei **Sales Channel** (Separation of Policy and Execution, §2.4); **non** è un canale di vendita.

### 3.2 · Maestro
- **Ruolo:** **soggetto di dominio** e **origine** del prodotto — il creatore di valore da cui il
  prodotto proviene. `[DECISION: ADR-004]`
- **Responsabilità:** fornire il prodotto e la sua **identità/storia**; è uno dei due capi della
  relazione con le Famiglie (`ADR-005`).
- **Limiti di autorità:** **non** è il System of Record (lo è IWEXA); **non** decide da solo
  pubblicazione o accettazione; il naming fisico resta `vendor*` (Contract First, ADR-004 §3).

### 3.3 · Compliance
- **Ruolo:** autorità sui **requisiti regolatori** del prodotto (per Paese). `[04 §4.4]`
- **Responsabilità:** definire/codificare i requisiti di conformità (es. GPSR, hazmat) che il
  prodotto deve soddisfare.
- **Limiti di autorità:** **Policy Owner esterno** (legge/regolatore) — IWEXA li **codifica ed
  esegue**; Compliance **non possiede** il prodotto né lo fa evolvere: **pone requisiti**.

### 3.4 · Sales Channels (di cui PayPoc)
- **Ruolo:** **destinazioni** verso cui il prodotto è reso disponibile; **PayPoc** è il canale del
  progetto verso le Famiglie.
- **Responsabilità:** esporre il prodotto e ospitare la relazione con chi lo sceglie.
- **Limiti di autorità:** **non** sono System of Record del prodotto; **non** lo fanno evolvere a
  monte. ⚠️ Il **ruolo nelle policy di accettazione/pubblicazione** e la **natura di PayPoc**
  (canale vs infrastruttura di relazione) sono **Open Questions** (Product Acceptance · ADR-002):
  **non decise qui**.

### 3.5 · Bagisto
- **Ruolo:** **substrato tecnico** — piattaforma ospite su cui gira PayPoc.
- **Responsabilità:** fornire runtime e hosting.
- **Limiti di autorità:** **nessuna autorità sul ciclo di vita** del prodotto — è infrastruttura
  tecnica, non un decisore di dominio (`03` §9).

### 3.6 · Famiglie
- **Ruolo:** **destinatarie/beneficiarie** del prodotto — «chi lo sceglie».
- **Responsabilità:** —
- **Limiti di autorità:** **non** sono attori del ciclo di vita del prodotto: **nessuna autorità**
  sulla sua evoluzione (sono soggetto del dominio *Relazione*, `ADR-005`, non del suo lifecycle).

---

## Changelog

| Data | Versione | Modifica | Stato |
|---|---|---|---|
| 2026-07-27 | 0.1.0-draft | Avvio della stesura **incrementale** (documento normativo, una sezione alla volta). Redatta la sezione **0 · Scopo** (0.1 Relazione con `03`/`04`; 0.2 Fuori scope). In approvazione. | In lavorazione |
| 2026-07-27 | 0.1.0-draft | Micro-modifiche editoriali alla **§0** (*policy*→*responsabilità* nello Scopo; rafforzata la distinzione **Product Model** = «cosa il prodotto è» ≠ **Product Lifecycle** = «come evolve nel tempo»; chiarito che i riferimenti implementativi sono **esplicativi, non normativi**). **§0 · Scopo approvata → Approved 1.0.0.** | In lavorazione |
| 2026-07-27 | 0.1.0-draft | Redatta la sezione **1 · Obiettivo del Product Lifecycle** (1.1 Perché esiste · 1.2 Obiettivo · 1.3 Criterio di successo). **Decision-free**: nessuna decisione su stati, transizioni, policy, Product Acceptance, Publication. In approvazione. | In lavorazione |
| 2026-07-27 | 0.1.0-draft | Rifinita la **§1** (obiettivo reso più astratto — rimossi «stati»/«transizioni»; aggiunta **regola di governance** in 1.3: nessun altro documento modifica autonomamente il ciclo di vita). **§1 approvata → Approved 1.0.0.** | In lavorazione |
| 2026-07-27 | 0.1.0-draft | Redatta la sezione **2 · Principi** (unicità · tracciabilità · Policy/Execution · subordinazione normativa · System of Record). **Decision-free**. In approvazione. | In lavorazione |
| 2026-07-27 | 0.1.0-draft | Rifinita la **§2**: *System of Record* → **Single Source of Lifecycle**; aggiunto principio **Explicit Evolution**; Tracciabilità focalizzata su registrazione/attribuzione. **§2 approvata → Approved 1.0.0.** | In lavorazione |
| 2026-07-27 | 0.1.0-draft | Redatta la sezione **3 · Attori** (IWEXA · Maestro · Compliance · Sales Channels/PayPoc · Bagisto · Famiglie) per **ruolo · responsabilità · limiti di autorità**. **Decision-free**: nessuno stato né transizione; accettazione/pubblicazione e natura di PayPoc lasciate come **Open Question**. In approvazione. | In lavorazione |
