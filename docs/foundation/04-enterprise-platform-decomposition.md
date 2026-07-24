# 04 — Enterprise Platform Decomposition (IWEXA + PayPoc)

> **Sistema:** IWEXA + PayPoc
> **Documento:** `docs/foundation/04-enterprise-platform-decomposition.md`
> **Stato:** Approved
> **Versione:** 1.0.0
> **Data:** 2026-07-24
> **Data di approvazione:** 2026-07-24
> **Autore:** Architecture Foundation
> **Approvatore finale:** Cristiano Plattner
> **Responsabile della proposta tecnica:** Principal Software Architect
> **Prerequisiti:** `00-project-rules.md` — Approved 1.0.0 · `01-glossary.md` — In approvazione ·
> `02-decision-process.md` — Approved 1.0.0 · `03-product-model-strategy.md` — Approved 1.0.1
> **Ambito:** struttura enterprise (macro-**Platform**) di IWEXA + PayPoc — responsabilità,
> confini, ownership, dipendenze, Business Capability.
> **Nota:** descrive **struttura**, non implementazione, API, database, deployment o tecnologia.
> Non autorizza modifiche al codice.

---

> **Legenda di maturità** (usata coerentemente in tutto il documento):
> 🟢 **Operativo** · 🟡 **Parziale** · 🔵 **Progettato / TO-BE** · ❌ **Inesistente**.
> La maturità indica lo **stato di implementazione attuale (AS-IS)**; i confini e le
> responsabilità descritti sono **target (TO-BE)** e non cambiano al variare della maturità.

---

## 1 · Purpose

Questo documento definisce la **scomposizione ufficiale di IWEXA (+ PayPoc) in macro-Platform**:
la **mappa costituzionale della struttura di piattaforma**, subordinata solo alla Costituzione
(`00`). È il **riferimento per tutti i documenti di dominio successivi**: quando un dominio
(Product Lifecycle, Compliance, Pricing, Publication, Fulfillment, …) verrà progettato, la sua
collocazione — *"a quale Platform appartiene?"* — sarà già stabilita qui.

Descrive **esclusivamente**: responsabilità, non-responsabilità, confini, ownership (dati e
policy), dipendenze e **Business Capability**. **Non** descrive comportamento di dominio,
implementazione, API, database, deployment o scelte tecnologiche.

Il documento descrive la **struttura target (TO-BE)**; lo stato di implementazione corrente
(AS-IS) è tracciato separatamente (audit tecnico) e sintetizzato qui dalla **maturità** di ogni
Platform. In coerenza con `02`, i punti decisionali discreti che questa mappa fissa andranno
formalizzati con ADR. Il documento è **Approved** come struttura enterprise di riferimento; i
suoi punti decisionali discreti restano da formalizzare con ADR.

## 2 · Enterprise Principles

Principi generali che governano l'intera piattaforma (derivati dalle fonti approvate):

- **IWEXA è il sistema master** di prodotto, tassonomia, prezzi/regole commerciali, conformità,
  stock/logistica, vendor e pubblicazione. **PayPoc è un Sales Channel**, consumatore di questi
  dati, non il loro master.
- **La struttura segue le Business Capability**, non l'organizzazione tecnica: ogni Platform
  esiste per erogare una o più capability di business.
- **Chi definisce le policy è distinto da chi le esegue** (Separation of Policy and Execution).
- Gli **scambi ai confini di sistema/esterni** sono governati dal **contratto** (Contract First);
  le comunicazioni **interne** fra bounded context non sono oggetto di questo documento.
- **Un solo formato stabile del prodotto** (Canonical Product Model); ogni canale ne riceve una
  *vista* via Channel Mapping (minimo necessario per canale).
- **Distinzione AS-IS / TO-BE** (Art. 2): qui si descrive il target; l'esistente è sintetizzato
  dalla maturità.

## 3 · Business Capabilities

> **Business Capability ≠ Platform.** Una **Business Capability** è *cosa* l'organizzazione deve
> saper fare; una **Platform** è il *componente responsabile* dell'implementazione di **una o
> più** Capability. La relazione **non è 1:1**: una Platform può erogare più Capability (es.
> *Core Product* eroga *Product Acquisition* **e** *Product Normalization*), e una Capability può
> appoggiarsi a una capability trasversale (es. *Product Enrichment* si avvale dell'*AI Capability
> Layer*). La colonna "Platform responsabile" indica l'**owner primario** della Capability. La
> **maturità** di una Capability segue quella della Platform responsabile (§4-5).

Per ogni capability: obiettivo · valore per il business · Platform responsabile.

| Business Capability | Obiettivo | Valore per il business | Platform responsabile |
|---|---|---|---|
| **Product Acquisition** | Acquisire i dati prodotto dalle sorgenti (schemi Amazon) | Catalogo ricco e rapido da popolare | Core Product |
| **Product Normalization** | Produrre il Canonical Product Model stabile | Coerenza, meno duplicazioni, base per ogni canale | Core Product |
| **Product Enrichment** | Tassonomia, attributi, contenuto, localizzazione, media | Qualità e ricercabilità del prodotto | Enrichment (+ AI Capability Layer) |
| **Regulatory Compliance** | Garantire conformità (GPSR/hazmat) per Paese | Vendibilità legale, riduzione del rischio | Compliance |
| **Pricing & Commercial Rules** | Prezzi/IVA e regole commerciali per Paese, tetto credito | Termini commerciali corretti per mercato | Pricing & Commercial Rules |
| **Product Acceptance** | Decidere se un prodotto è pubblicabile su un canale/Paese | Qualità, brand e conformità in vetrina | Publication (esegue) · policy dai canali + Compliance |
| **Channel Distribution** | Generare la vista di canale (Channel Mapping) e pubblicare | Reach multi-canale senza divergere il modello | Publication |
| **Vendor & Supplier Management** | Anagrafica, onboarding e condizioni dei partner | Base di fornitura governata | Vendor & Supplier |
| **Stock & Fulfillment** | Stock/disponibilità, magazzini, spedizione, tracking | Consegna affidabile | Fulfillment & Logistics |
| **Commerce & Customer Value** | Order, Checkout, Payment, Wallet/Credito/Cashback/Loyalty | Ricavi + differenziatore cliente | Commerce (PayPoc) |
| **Integration & Trust** | Scambio contrattualizzato e autenticato ai confini | Interoperabilità sicura e verificabile | Integration & Trust (trasversale) |
| **AI-Assisted Capability** | Assistere categorizzazione/traduzione/arricchimento | Scala e qualità del catalogo | AI Capability Layer (trasversale) |

## 4 · Platform Decomposition

### 4.1 Core Product Platform · 🟡 Parziale
- **Scopo:** possedere la verità canonica del prodotto lungo il **Product Lifecycle**.
- **Responsabilità:** acquisizione (schemi Amazon come struttura d'ingresso), validazione, normalizzazione nel **Canonical Product Model**, identità prodotto (**EAN**).
- **Non-responsabilità:** prezzi, pubblicazione, evasione, commercio, conformità legale.
- **Dati posseduti:** Canonical Product Model, identità prodotto.
- **System of Record:** IWEXA (prodotto).
- **Policy Owner:** IWEXA.
- **Executor:** IWEXA.
- **Dipendenze:** Vendor & Supplier (`vendorCode`), Integration & Trust (ingressi esterni), AI Capability Layer (assistenza).

### 4.2 Enrichment Platform · 🟡 Parziale
- **Scopo:** arricchire il Canonical Product Model con tassonomia, attributi e contenuto.
- **Responsabilità:** categorizzazione (Google Product Taxonomy ↔ tassonomia commerciale PayPoc), Attributi/Filtri/Benefici/Claim/Documentazione, contenuto multilingua, media.
- **Non-responsabilità:** decidere la pubblicabilità; possedere il master del prodotto; definire prezzi.
- **Dati posseduti:** arricchimenti canonici (tassonomia/attributi/contenuto).
- **System of Record:** IWEXA (arricchimento canonico).
- **Policy Owner:** IWEXA (regole di arricchimento); la presentazione per canale è a valle.
- **Executor:** IWEXA (consuma AI Capability Layer).
- **Dipendenze:** Core Product, AI Capability Layer.

### 4.3 Pricing & Commercial Rules Platform · 🔵 Progettato / TO-BE
- **Scopo:** possedere prezzi, IVA e regole commerciali **per Paese**, come master distribuito ai canali.
- **Responsabilità:** prezzo di listino/di vendita/IVA per Paese, regole commerciali, `maxApplicableValue` (tetto credito).
- **Non-responsabilità:** applicare il credito al cliente (lo fa Commerce); esporre il **Prezzo pubblico** (lo fa il canale); non è il prodotto.
- **Dati posseduti:** prezzi, IVA, regole per Paese, tetto credito.
- **System of Record:** IWEXA (prezzi/regole).
- **Policy Owner:** IWEXA (regole commerciali); l'esposizione pubblica del prezzo è policy del Sales Channel.
- **Executor:** IWEXA risolve i valori per Paese; Commerce (PayPoc) espone il Prezzo pubblico.
- **Dipendenze:** Core Product.

### 4.4 Compliance Platform · 🟡 Parziale
- **Scopo:** garantire la conformità regolatoria del prodotto per Paese.
- **Responsabilità:** dati **Compliance** (GPSR) e **hazmat**, restrizioni per Paese, gate di conformità verso la pubblicazione.
- **Non-responsabilità:** definire la legge (la recepisce, non la crea); possedere prodotto o canale.
- **Dati posseduti:** dati di conformità/regolatori.
- **System of Record:** IWEXA (dati di compliance).
- **Policy Owner:** **esterno** — legge/regolatore **per Paese** (IWEXA li codifica).
- **Executor:** IWEXA.
- **Dipendenze:** Core Product, Enrichment.

### 4.5 Publication Platform · 🔵 Progettato / TO-BE
- **Scopo:** **eseguire** l'accettazione e la distribuzione del prodotto verso i Sales Channel.
- **Responsabilità:** esecuzione di **Product Acceptance**, **Channel Mapping** (Canonical Product Model → vista di canale), orchestrazione della distribuzione, principio del **minimo necessario per canale**.
- **Non-responsabilità:** **possedere le policy di accettazione** — queste sono definite dai Sales Channel (incl. PayPoc) e dalla Compliance (per Paese); essere master del prodotto o delle transazioni.
- **Dati posseduti:** stato di pubblicazione, mapping di canale.
- **System of Record:** IWEXA (stato pubblicazione / mapping).
- **Policy Owner:** **distribuito** — ogni **Sales Channel** (incl. PayPoc) **+** Compliance (per Paese). L'esito è **per Sales Channel × Paese**.
- **Executor:** IWEXA (motore di Product Acceptance + Channel Mapping).
- **Dipendenze:** Core Product, Enrichment, Compliance, Pricing & Commercial Rules.

> 🔓 **Open Architectural Question.** La **collocazione definitiva** e il **modello di policy**
> di **Product Acceptance** (esecuzione in IWEXA vs policy per Sales Channel × Paese) **non
> sono chiusi da questo documento** e restano da formalizzare con un **ADR** dedicato. Qui se
> ne descrivono la responsabilità di *esecuzione* e la *separazione policy/esecuzione*, non la
> decisione finale.

### 4.6 Fulfillment & Logistics Platform · 🟡 Parziale
- **Scopo:** possedere lo stock e l'evasione fisica.
- **Responsabilità:** stock/disponibilità (**System of Record**), **Warehouse** (centrale **FBI** / vendor **FBV**), routing, **Shipment**, **Carrier**, **Tracking**.
- **Non-responsabilità:** decidere la pubblicazione; lo stock lo scrive **solo** l'Hub; non possiede l'Ordine commerciale.
- **Dati posseduti:** stock, magazzini, spedizioni, tracking.
- **System of Record:** IWEXA (stock/logistica).
- **Policy Owner:** IWEXA (routing) + condizioni del Vendor.
- **Executor:** IWEXA (+ corrieri esterni).
- **Dipendenze:** Vendor & Supplier; riceve l'Order da Commerce.

### 4.7 Vendor & Supplier Platform · 🟡 Parziale
- **Scopo:** possedere identità e rapporto con **Vendor**/**Fornitore**.
- **Responsabilità:** anagrafica, onboarding, condizioni commerciali/operative, **Vendor Store**, **Vendor Warehouse**.
- **Non-responsabilità:** gestire direttamente catalogo, prezzi o stock.
- **Dati posseduti:** Vendor/Fornitore master.
- **System of Record:** IWEXA (partner).
- **Policy Owner:** IWEXA (onboarding, condizioni).
- **Executor:** IWEXA.
- **Dipendenze:** alimenta Core Product, Fulfillment & Logistics, Commerce.

### 4.8 Commerce Platform (PayPoc) · 🔵 Progettato / TO-BE
- **Scopo:** gestire la transazione e l'esperienza commerciale sul Sales Channel **PayPoc**.
- **Responsabilità:** **Order** (Ordine commerciale), **Checkout**, **Payment**, **Customer Value** (Wallet, Credito, Cashback, Loyalty), esposizione del **Prezzo pubblico**.
- **Non-responsabilità:** essere master di prodotto, prezzo, stock o conformità; è **un** canale — gli altri marketplace hanno il proprio commercio, esterno all'ecosistema PayPoc.
- **Dati posseduti:** Ordine commerciale, valori cliente (Wallet…).
- **System of Record:** PayPoc (ordine / valore cliente).
- **Policy Owner:** PayPoc (policy commerciali/UX del canale).
- **Executor:** PayPoc.
- **Dipendenze:** Publication (canale PayPoc), Pricing & Commercial Rules, Fulfillment & Logistics (stato/tracking).
- *(Nota di maturità: Order/Payment sono definiti a contratto; il **Customer Value** — Wallet/Credito/Cashback/Loyalty — è ❌ inesistente oggi.)*

## 5 · Cross-Cutting Capabilities

### 5.1 Integration & Trust · 🟡 Parziale
- **Scopo:** governare gli scambi **ai confini di sistema/esterni**.
- **Responsabilità:** il **Contract** (v3.1) e **Contract First**, autenticazione (Bearer + HMAC + anti-replay), **Webhook**, **Idempotency**, **Conformance Suite**, regola "**IWEXA invisibile al cliente**".
- **Ambito (confine):** si applica **quando un flusso attraversa un confine di sistema o un soggetto esterno** (IWEXA↔PayPoc, IWEXA↔marketplace/corrieri). **Non** è un canale obbligato per ogni comunicazione **interna** fra bounded context: il meccanismo interno dipende dalla struttura di deployment, **che questo documento non decide**.
- **System of Record:** il Contract (governance dell'integrazione).
- **Policy Owner:** `00` Contract First + contratto approvato.
- **Executor:** ai crossing di confine.

### 5.2 AI Capability Layer · ❌ Inesistente
- **Scopo:** fornire capability di AI **consumate** da altri domini.
- **Responsabilità (target):** categorizzazione assistita, traduzione, arricchimento/estrazione attributi, controllo qualità/claim.
- **Non-responsabilità:** **non è System of Record**; non è autonomo — l'eventuale autonomia tecnica non è decisa (resta un ADR futuro).
- **Dati posseduti:** nessuno.
- **System of Record:** nessuno.
- **Policy Owner:** — (capability consumata).
- **Executor:** fornisce capability a Enrichment, Compliance, Publication.
- **Stato AS-IS:** **inesistente** (nessun codice AI oggi).

> **Principio (AI · confine invariante).** L'**AI Capability Layer non modifica direttamente
> alcun System of Record.** Ogni risultato prodotto dall'AI deve essere **validato e persistito
> esclusivamente dal dominio proprietario del dato**.

## 6 · Ownership Matrix

| Dato | System of Record | Policy Owner | Executor |
|---|---|---|---|
| Canonical Product Model, identità (EAN) | Core Product (IWEXA) | IWEXA | IWEXA |
| Tassonomia / Attributi / Contenuto | Enrichment (IWEXA) | IWEXA | IWEXA (+ AI Capability Layer) |
| Prezzi, IVA, regole per Paese, tetto credito | Pricing & Commercial Rules (IWEXA) | IWEXA (esposizione pubblica = canale) | IWEXA · Commerce espone il Prezzo pubblico |
| Conformità (GPSR, hazmat) | Compliance (IWEXA) | **Esterno**: legge/regolatore per Paese | IWEXA |
| Stato di pubblicazione / accettazione | Publication (IWEXA) | **Distribuito**: Sales Channel + Compliance (per Sales Channel × Paese) | IWEXA |
| Stock / disponibilità | Fulfillment & Logistics (IWEXA) — scrive **solo** l'Hub | IWEXA (routing) + condizioni Vendor | IWEXA (+ corrieri) |
| Vendor / Fornitore | Vendor & Supplier (IWEXA) | IWEXA | IWEXA |
| Ordine commerciale / Customer Value | Commerce (PayPoc) | PayPoc | PayPoc |
| Contratto / auth / eventi | Integration & Trust | `00` + contratto | ai confini di sistema |
| — (AI) | Nessuno | — | AI Capability Layer |

## 7 · Platform Dependency Diagram

Diagramma **logico** delle dipendenze (nessuna implicazione di deployment).

```
  CROSS-CUTTING
  ┌────────────────────────────────────────────────────────────────────────────┐
  │ Integration & Trust  — solo ai confini di sistema/esterni (IWEXA↔PayPoc,     │
  │                        IWEXA↔marketplace/corrieri)                           │
  ├────────────────────────────────────────────────────────────────────────────┤
  │ AI Capability Layer  — capability consumata da Enrichment/Compliance/Publ.   │
  │                        (AS-IS: inesistente) · non modifica alcun SoR         │
  └────────────────────────────────────────────────────────────────────────────┘

  CATENA DEL PRODOTTO  (IWEXA = System of Record)

  Vendor & Supplier ─┐
                     ▼
  Core Product ─► Enrichment ─► Compliance ─► Publication ─► Sales Channels
   (Canonical      (taxonomy,    (GPSR,        (esegue         ├─ PayPoc ─► Commerce (PayPoc)
    Product Model,  attributi,    hazmat,       Product          │           Order · Checkout ·
    identità EAN)   contenuto)    per Paese)    Acceptance +      │           Payment · Customer
        ▲                                       Channel Mapping)  ├─ Amazon    Value · Prezzo pubblico
        │                                       policy: canale ×  ├─ eBay/Kaufland
  Pricing & Commercial Rules ──────────────────► Paese           └─ Public Feed / GMC
   (prezzi, IVA, regole per Paese) ─► alimenta Publication e Commerce

  Fulfillment & Logistics  (stock SoR, Warehouse FBI/FBV, Shipment, Carrier, Tracking)
     ▲ stock/disponibilità → Publication, Commerce
     └─ riceve l'Order da Commerce ─► restituisce tracking/stato ai canali
```

## 8 · Architectural Principles

Principi canonici (già approvati nelle fonti; **nessun nuovo principio non approvato introdotto**):

- **Single Source of Truth** — per ogni dato esiste un unico System of Record (Ownership Matrix); nessun altro dominio ne è autorità.
- **Canonical Product Model** — un solo formato stabile del prodotto in IWEXA; i canali ne ricevono viste, non copie divergenti (`03`).
- **Separation of Policy and Execution** — chi **definisce** una policy è distinto da chi la **esegue** (es. Product Acceptance: policy dai canali/Compliance, esecuzione IWEXA).
- **Contract First** — l'integrazione è governata prima dal contratto, poi dal codice (`00` Art. 3); il codice si conforma al contratto.
- **Sales Channels are Consumers** — i Sales Channel (incluso PayPoc) **consumano** i dati master di IWEXA; non ne sono master.
- **Business Capability First** — la struttura in Platform segue le Business Capability, non l'organizzazione tecnica né il deployment.

## 9 · Relationship with Domain Documents

Questo documento definisce la **struttura della piattaforma**: quali Platform esistono, i loro
confini, chi possiede dati e policy, e le dipendenze. **Non** descrive il **comportamento** dei
singoli domini.

I **documenti di dominio successivi** (Product Lifecycle, Product Acceptance, Pricing, Publication,
Fulfillment, Compliance, Vendor, …) descriveranno il comportamento all'interno della Platform qui
assegnata. Regola di collocazione: **ogni documento di dominio dichiara la Platform di
appartenenza secondo questa mappa**.

> **Regola architetturale.** **Nessun documento di dominio può ridefinire i confini delle
> Platform** stabiliti da questo documento. I documenti di dominio possono **esclusivamente
> specializzarne comportamento, processi e regole interne**.

Governance: questo è, dopo la Costituzione, il documento di riferimento della struttura enterprise.
In coerenza con `02-decision-process.md`, i suoi punti decisionali discreti (elenco e confini delle
Platform, Pricing come Platform autonoma, **modello di policy della Product Acceptance — Open
Architectural Question**, AI come Capability Layer, ambito di Integration & Trust) andranno
**formalizzati con ADR**. Con questa approvazione la **macro-architettura è congelata**: le future
modifiche ai confini delle Platform seguono la **governance della Foundation** ed eventuali **ADR**.
Il documento è **Approved** come struttura enterprise di riferimento.

## Changelog

| Data | Versione | Modifica | Stato |
|---|---|---|---|
| 2026-07-24 | 1.0.0-draft | Prima stesura (STEP 06): scomposizione enterprise in macro-Platform — purpose, principi, Business Capability, decomposizione, cross-cutting, ownership matrix, dependency diagram, principi architetturali, relazione con i documenti di dominio. Basata su Foundation `00/02`, `03` Product Model Strategy, Platform Decomposition (STEP 05) e Glossario. | In approvazione |
| 2026-07-24 | 1.0.0-draft | Revisione finale editoriale (STEP 06A): distinzione **Business Capability ≠ Platform**; **legenda di maturità** e marker per Platform; **Product Acceptance** come **Open Architectural Question** (da ADR); principio **AI non modifica i System of Record**; regola **nessun documento di dominio ridefinisce i confini**. **Nessuna modifica ai confini né decisioni architetturali.** | In approvazione |
| 2026-07-24 | 1.0.0 | **Approvata la struttura architetturale** dal committente (STEP 06): documento promosso a **Approved 1.0.0**. Macro-architettura della piattaforma **congelata**; future modifiche ai confini via governance della Foundation e ADR quando applicabile. | Approved |
</content>
