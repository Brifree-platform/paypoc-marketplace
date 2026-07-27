# Domain Architecture

*Indice e governance dei documenti di dominio* — `docs/domains/`

> **Sistema:** IWEXA + PayPoc
> **Documento:** `docs/domains/README.md`
> **Livello:** Documenti di dominio (governati dalla Foundation, **non** ne fanno parte)
> **Stato:** Approved
> **Versione:** 1.0.0
> **Data:** 2026-07-27
> **Data di approvazione:** 2026-07-27 (Cristiano Plattner)
> **Approvatore finale:** Cristiano Plattner
> **Responsabile della proposta tecnica:** Principal Software Architect
> **Prerequisiti:** Foundation `00` (Approved 2.0.0) · `01` · `03` (Approved 1.0.1) ·
> `04` (Approved 1.1.0) · ADR-004 · ADR-005
> **Ambito:** indice e architettura del livello dei documenti di dominio.
> **Nota:** documento indice; **non introduce domini né decisioni**. I domini sono quelli
> della decomposizione enterprise `04`; qui se ne pianifica solo la **documentazione**.

---

## 0 · Scopo del livello

I **documenti di dominio** specificano, dominio per dominio, le regole di business, gli stati
e i processi dei domini già definiti dalla **Enterprise Platform Decomposition** (`04`).

- **Sono governati dalla Foundation, ma non ne fanno parte.** La Foundation (`00`–`04`) è il
  quadro stabile; questi documenti lo *riempiono* per singolo dominio.
- **Regola cardine (ereditata da `04`):** `04` possiede i **confini delle Platform**;
  **nessun documento di dominio li ridefinisce**. Questi documenti abitano i domini, non li
  ridisegnano.

## 1 · Posizione nella gerarchia

```
Ragione di Esistere (OS Livello -1)
  → Costituzione tecnica (00) → ADR → Foundation (00–04)
    → Documenti di dominio (questo livello · docs/domains/)
      → Software → Bagisto → PayPoc in esercizio
```

- **Il modello di prodotto** (Canonical Product Model) resta in
  [`foundation/03-product-model-strategy.md`](../foundation/03-product-model-strategy.md)
  — **documento fondativo, non un domain doc**. I documenti di dominio lo **referenziano**.
- **Il Contratto v3.1** possiede il **formato d'integrazione** (wire); nessun domain doc lo
  ridefinisce.
- **Il Modello Economico** (Potere, Wallet/Credito) è **livello OS**, non un domain doc:
  `pricing`/`commerce` lo *referenziano*, non lo *possiedono*.

## 2 · Catalogo dei documenti di dominio

> **Nota.** I raggruppamenti del catalogo (*Prodotto*, *Commerciale & Conformità*, *Soggetti*,
> *Logistica & Commercio*, *Trasversali*) sono **puramente organizzativi** e **non
> rappresentano una gerarchia architetturale** del dominio.

Legenda stato: ⭐ prossimo da scrivere · 📝 pianificato (non materializzato) · ⚠️ dipende da
ADR / Open Question.

| # | `docs/domains/…` | Dominio `04` | Possiede (scope) | Confine — referenzia, non possiede | Stato |
|---|---|---|---|---|---|
| **Prodotto** |
| 1 | `product-lifecycle.md` | trasversale prodotto | **stati, fasi, transizioni, fine-vita** | modello (→ `03`), policy canale (→ `publication`) | ⭐ **prossimo** (indice approvato) |
| 2 | `product-acceptance.md` | 4.5 (policy) | policy/processo di accettazione | esecuzione (→ `publication`), stati (→ `product-lifecycle`) | 📝 ⚠️ Open Question → ADR |
| 3 | `enrichment.md` | 4.2 Enrichment | attributi, contenuto, filtri, benefici, claim, documentazione | conformità legale (→ `compliance`) | 📝 |
| 4 | `publication.md` | 4.5 Publication | Channel Mapping, viste di canale, stato di pubblicazione | policy accettazione (→ `product-acceptance`) | 📝 ⚠️ sbocco canali = **ADR-002** |
| **Commerciale & Conformità** |
| 5 | `pricing.md` | 4.3 Pricing | prezzo di listino, IVA, regole per Paese | Modello Economico/Potere (→ **OS**) | 📝 |
| 6 | `compliance.md` | 4.4 Compliance | GPSR, hazmat, restrizioni per Paese | policy esterna (legge) | 📝 |
| **Soggetti** |
| 7 | `maestro.md` | 4.7 Maestro | Maestro soggetto relazionale, onboarding, store/warehouse | naming fisico `vendor*` (Contract First) | 📝 [ADR-004] |
| 8 | `relazione-famiglie-fiducia.md` | 4.9 · 4.10 · 4.11 | i **tre domini ADR-005 in un unico documento** (legame diretto, Famiglia come soggetto, fiducia/verifica/reputazione) | entità tecnica Customer (→ `commerce`), conformità legale (→ `compliance`) | 📝 ⚠️ [ADR-005] — splittabile in futuro |
| **Logistica & Commercio** |
| 9 | `fulfillment-logistics.md` | 4.6 | stock, warehouse, shipping, routing, tracking | pubblicazione (→ `publication`) | 📝 |
| 10 | `commerce.md` | 4.8 Commerce | ordine, checkout, payment, customer value | Modello Economico (→ **OS**) | 📝 |
| **Trasversali** |
| 11 | `ai-capability.md` | 5.2 | capability AI consumate dagli altri domini | **non** è System of Record (`04`) | 📝 ❌ inesistente oggi |

> **Coperto altrove (nessun domain doc):** il **modello di prodotto** → Foundation `03`;
> il **formato d'integrazione** → Contratto v3.1; il **Modello Economico** → livello OS.

## 3 · Decisioni di struttura (registrate)

1. **`03-product-model-strategy.md` resta nella Foundation** — documento fondativo, **non** spostato.
2. **`integration-trust.md` non viene creato** — il dominio (`04` §5.1) **non è ancora
   giustificato**; già coperto da Contratto + Foundation. Riapribile in futuro con ADR.
3. **Relazione, Famiglie e Fiducia = un unico documento** (`relazione-famiglie-fiducia.md`)
   finché **ADR futuri** non ne richiederanno la separazione.

## 4 · Regole di confine (anti-sovrapposizione, anti-lacuna)

1. **Entità vs Processo.** I documenti-**entità** (`enrichment`, `pricing`, `compliance`,
   `maestro`, `relazione-famiglie-fiducia`, `fulfillment-logistics`, `commerce`, `ai-capability`)
   possiedono *struttura/identità*; i documenti-**processo** (`product-lifecycle`,
   `product-acceptance`, `publication`) possiedono *flussi e stati* e **referenziano** le entità.
   Un dato è definito **una sola volta**, citato altrove.
2. **Policy ≠ Esecuzione.** `product-acceptance` (policy) e `publication` (esecuzione) restano
   distinti — «Separation of Policy and Execution» (`04`).
3. **Il Contratto (v3.1)** possiede il formato d'integrazione; nessun domain doc lo ridefinisce.
4. **Il Modello Economico** è livello OS; `pricing`/`commerce` lo referenziano, non lo possiedono.
5. **Ogni documento di dominio chiude con `Relazioni con altri documenti`** → i confini sono
   dichiarati, non impliciti.

## 5 · Convenzioni comuni dei documenti di dominio

- Struttura minima: **meta header** · **Scopo** · (contenuto di dominio) · **Relazioni con
  altri documenti** · **Open Questions** · **ADR collegati** · **Changelog**.
- Ogni affermazione **ancorata a una fonte** ed etichettata (`CODE`/`CONTRACT`/`DOC`/`DECISION`/
  `TODO`/`UNKNOWN`) — `00` Art. 10; distinzione **AS-IS / TO-BE** — Art. 2.
- **Nessun documento di dominio ridefinisce i confini delle Platform** (`04`).

## 6 · Stato di materializzazione e ordine

- **Materializzato oggi:** *solo* questo `README.md`.
- **Prossimo da scrivere:** `product-lifecycle.md` (indice già approvato), dopo l'approvazione
  di questo indice.
- **Tutti gli altri:** **pianificati, non materializzati**; verranno scritti in modo
  incrementale, **gated dagli ADR** dove indicato (Acceptance, Publication → ADR-002, ADR-005).

## Changelog

| Data | Versione | Modifica | Stato |
|---|---|---|---|
| 2026-07-27 | 1.0.0-draft | Prima stesura dell'indice del livello **documenti di dominio**: catalogo ancorato a `04`, regole di confine, convenzioni comuni. Decisioni recepite: `03` resta in Foundation; `integration-trust` non creato; Relazione/Famiglie/Fiducia in un unico documento. Materializzato solo il README. | In approvazione |
| 2026-07-27 | 1.0.0 | Rifiniture editoriali (titolo → **Domain Architecture** · «Indice e governance dei documenti di dominio»; nota che i raggruppamenti del catalogo sono **organizzativi, non gerarchici**) e **approvazione** del committente: promosso a **Approved 1.0.0**. Nessuna modifica a contenuto o decisioni. | Approved |
