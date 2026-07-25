# PAYPOC OS MAP

> **"Da dove parto?"** — Questa pagina risponde in 30 secondi.
> **Stato:** Approved · **Versione:** 1.0.1 · **Data:** 2026-07-25 · **Approvato:** 2026-07-25 (Cristiano Plattner)

L'autorità scende dall'alto: **ogni strato è giustificato da quello sopra di sé.**
Il *business definisce il software*, non viceversa.

> **Il PAYPOC OS non coincide con la Foundation tecnica.** La Foundation è **uno degli
> strati governati** dal PAYPOC OS.

```
        ┌──────────────────────────────────────────────────────────────┐
        │  PAYPOC OS · LIVELLO -1 — RAGIONE DI ESISTERE   (norma suprema)│
        │  «L'infrastruttura che restituisce libertà di relazione       │
        │   tra chi crea valore e chi lo sceglie.»                      │
        │  → il PERCHÉ. Ogni decisione si misura qui.                   │
        └──────────────────────────────────────────────────────────────┘
                                   │  determina
                                   ▼
        ┌──────────────────────────────────────────────────────────────┐
        │  COSTITUZIONE TECNICA (00)                                    │
        │  → COME lavoriamo: regole, ruoli, gerarchia delle fonti.      │
        └──────────────────────────────────────────────────────────────┘
                                   │  è attuata da
                                   ▼
        ┌──────────────────────────────────────────────────────────────┐
        │  ADR  (Architecture Decision Records)                        │
        │  → COSA è stato deciso, e perché. Ogni decisione è tracciata. │
        └──────────────────────────────────────────────────────────────┘
                                   │  formano
                                   ▼
        ┌──────────────────────────────────────────────────────────────┐
        │  FOUNDATION                                                  │
        │  → L'ARCHITETTURA: glossario, processo, modello prodotto,    │
        │    decomposizione in Platform.                               │
        └──────────────────────────────────────────────────────────────┘
                                   │  guida
                                   ▼
        ┌──────────────────────────────────────────────────────────────┐
        │  SOFTWARE                                                    │
        │  → Il CODICE che implementa la Foundation (via contratto),   │
        │    sul substrato tecnico: Bagisto / Laravel.                 │
        └──────────────────────────────────────────────────────────────┘
                                   │  produce
                                   ▼
        ┌──────────────────────────────────────────────────────────────┐
        │  PAYPOC IN ESERCIZIO                                         │
        │  → Il risultato concreto. È l'esito, non la tesi;            │
        │    non si esaurisce in un marketplace.                       │
        └──────────────────────────────────────────────────────────────┘
```

## Dove vive ogni strato (e in che stato è)

| Strato | Cosa contiene | Dove nel repo | Stato |
|---|---|---|---|
| **Ragione di Esistere** | Il perché · norma suprema | `docs/paypoc-os/` *(in arrivo)* — oggi registrata in `docs/adr/ADR-001` | 🅿️ definita, documento OS da scrivere |
| **Costituzione Tecnica** | Regole, ruoli, ADR, gerarchia | `docs/foundation/00-project-rules.md` | ✅ Approved 2.0.0 |
| **ADR** | Le decisioni architetturali | `docs/adr/` | ✅ ADR-001/004/005 Accepted · 002/003 riservati |
| **Foundation** | Glossario, processo, prodotto, Platform | `docs/foundation/` (00–04 + README) | ✅ 00,02,03,04 Approved · 01,README In approvazione |
| **Contratto** *(Contract First)* | Spec integrazione + mock + suite | `docs/iwexa_hub_openapi_v3.1.yaml` · `tools/mock-hub/` | ✅ baseline v3.1 |
| **Software** *(sul substrato Bagisto/Laravel)* | Il connettore + strumenti. Bagisto è l'app ospite esterna, agganciata via composer/symlink | `packages/PAYPOC/IwexaConnector/` · `tools/` | 🟡 da riallineare (vedi `docs/audit/`) |
| **PayPoc in esercizio** | Il risultato concreto — non solo un marketplace | — | ⚪ non ancora esistente come prodotto |

## Da dove parto? (percorso di lettura)
1. **Questa mappa.**
2. **Ragione di Esistere** (il perché) → `ADR-001` (in attesa del documento OS Livello -1).
3. **Costituzione** `00` → come si decide e si lavora.
4. **ADR** `docs/adr/` → cosa è già stato deciso.
5. **Foundation** `docs/foundation/` → l'architettura (leggi `README.md` come indice).
6. **Software / Contratto** → l'implementazione (sul substrato Bagisto).

**Se sei…** un *business/prodotto* → parti da Ragione di Esistere + Foundation. Un
*sviluppatore* → Foundation + Contratto + Software. Vuoi lo *stato reale del codice* →
`docs/audit/` (audit tecnico, riservato/locale).

> **Regola d'oro.** In caso di conflitto progettuale, cambia lo **strato inferiore**. La
> Ragione di Esistere **guida il disegno** ed è riesaminabile **soltanto davanti a
> evidenze reali che ne smentiscano le premesse**, non per adattarla alla convenienza di
> uno strumento.
