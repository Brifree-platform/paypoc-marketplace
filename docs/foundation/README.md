# Foundation — IWEXA + PayPoc

> **Sistema:** IWEXA + PayPoc
> **Documento:** `docs/foundation/README.md`
> **Stato:** In approvazione
> **Versione:** 1.0.0-draft
> **Data:** 2026-07-23
> **Approvatore finale:** Cristiano Plattner
> **Responsabile della proposta tecnica:** Principal Software Architect
> **Ambito:** indice e guida di lettura della Foundation
> **Nota:** documento indice; non introduce principi né decisioni. Non autorizza modifiche al codice.

---

## Scopo della Foundation

La **Foundation** è la **memoria architetturale ufficiale** del progetto IWEXA + PayPoc:
l'insieme dei documenti che stabiliscono le regole, la terminologia, il processo
decisionale e i principi di dominio su cui poggia tutto il resto. Esiste per garantire
che la conoscenza tecnica sia **vera, verificabile, non ambigua e permanente**, e per
impedire che decisioni importanti restino solo nelle conversazioni.

Ripartizione dei ruoli documentali:

- **la Foundation** rappresenta la **memoria architetturale** del progetto;
- **gli ADR** registrano le **singole decisioni architetturali**;
- **il Glossario** (`01-glossary.md`) è la **fonte unica della terminologia ufficiale**.

## Come leggere la Foundation

- Ogni affermazione è **ancorata a una fonte** ed etichettata (`CODE`, `CONTRACT`, `DOC`,
  `DECISION`, `TODO`, `UNKNOWN`) — vedi `00-project-rules.md`, Art. 10.
- Ogni documento distingue **AS-IS** (ciò che esiste oggi) da **TO-BE** (ciò che deve
  diventare) — Art. 2.
- La **norma di grado più alto** è `00-project-rules.md` (la Costituzione); in caso di
  conflitto vale la gerarchia delle fonti dell'Art. 11.
- Ogni documento porta in testa **Stato, Versione, Data, Ambito** e in coda un **changelog**.

## Ordine consigliato di lettura

1. **`README.md`** — questo indice.
2. **`00-project-rules.md`** — la Costituzione: regole, ruoli, gerarchia delle fonti, ADR.
3. **`01-glossary.md`** — la terminologia ufficiale (leggere prima di ogni documento tematico).
4. **`02-decision-process.md`** — come si prendono e si preservano le decisioni.
5. **`03-product-model-strategy.md`** — i principi del ciclo di vita del prodotto (Product Lifecycle).
6. **`docs/adr/`** — gli ADR, man mano che vengono emessi (oggi non ancora presenti).

## Elenco dei documenti e stato di approvazione

| Documento | Contenuto | Stato | Versione |
|---|---|---|---|
| [README.md](README.md) | Indice e guida di lettura della Foundation | In approvazione | 1.0.0-draft |
| [00-project-rules.md](00-project-rules.md) | Costituzione: regole, ruoli, ADR, gerarchia delle fonti | Approved | 1.0.0 |
| [01-glossary.md](01-glossary.md) | Fonte unica della terminologia ufficiale | In approvazione | 1.0.0-draft |
| [02-decision-process.md](02-decision-process.md) | Processo decisionale e preservazione della conoscenza | Approved | 1.0.0 |
| [03-product-model-strategy.md](03-product-model-strategy.md) | Product Model Strategy (principi del ciclo di vita del prodotto) | Approved | 1.0.1 |

> Gli ADR risiederanno in `docs/adr/` (`ADR-NNN-<slug>.md`). Alla data attuale **non
> esiste ancora alcun ADR**; le decisioni architetturali già formalizzate come strategia
> (es. `03`) attendono i rispettivi ADR (vedi §Relazione tra Foundation e ADR).

## Relazione tra Foundation e ADR

- La **Foundation** definisce **regole, terminologia, processo e principi** (il quadro stabile).
- Gli **ADR** registrano le **singole decisioni architetturali** con contesto, decisione,
  conseguenze e alternative (`00-project-rules.md`, Art. 4).
- Un documento della Foundation che afferma una scelta architetturale deve **citare l'ADR**
  che la stabilisce (`[DECISION: ADR-NNN]`). Senza ADR, una scelta architetturale è una
  **proposta**, non una decisione formalizzata.
- Gerarchia normativa (Art. 11): `00-project-rules.md` → ADR `Accepted` → contratto
  approvato → Foundation e altri documenti → documentazione descrittiva → codice
  (limitatamente alla descrizione AS-IS).

## Relazione tra Foundation e implementazione

- La documentazione **descrive e decide**; **non implementa**. La Foundation **non ha
  potere esecutivo sul codice** (Art. 5).
- L'**implementazione può iniziare solo dopo** la formalizzazione e l'approvazione della
  decisione da cui dipende (`02-decision-process.md`).
- Ogni adeguamento del codice richiede uno **STEP di implementazione esplicitamente
  approvato** (Art. 11.5); una divergenza fra documentazione e codice **non** autorizza la
  modifica automatica del codice (Art. 5).

## Language Convention

- La Foundation è redatta in **italiano**; il **testo descrittivo** resta in italiano.
- I **nomi ufficiali** dei concetti architetturali, dei pattern, dei componenti software e
  della terminologia consolidata dell'ingegneria del software restano in **inglese** e
  **non si traducono** (es. *System of Record*, *Source of Truth*, *Canonical Product
  Model*, *Product Lifecycle*, *Sales Channel*, *Channel Mapping*, *Checkout*, *Wallet*,
  *Cashback*, *Loyalty*, *Compliance*).
- Il **Glossario** riporta il **termine ufficiale in inglese** e una **definizione
  descrittiva in italiano**.
- I nomi di dominio deliberatamente scelti in italiano dal committente (UI *Maestro* per
  *Vendor*, *Credito*, *Fornitore*, *Prezzo pubblico*, *Ordine commerciale*) restano come decisi.

## Changelog

| Data | Versione | Modifica | Stato |
|---|---|---|---|
| 2026-07-23 | 1.0.0-draft | Prima stesura del README della Foundation (STEP 02 — Foundation Consolidation). | In approvazione |
</content>
