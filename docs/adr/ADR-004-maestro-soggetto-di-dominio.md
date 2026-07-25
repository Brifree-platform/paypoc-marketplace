# ADR-004 — Il Maestro come soggetto di dominio di prim'ordine

> **Stato:** Accepted (approvato 2026-07-25 da Cristiano Plattner)
> **Data:** 2026-07-25
> **Proponente (proposta tecnica):** Principal Software Architect / Guardian del PAYPOC OS
> **Approvatore finale:** Cristiano Plattner
> **Riferimenti:** Ragione di Esistere (ADR-001) · `01-glossary` (regola Vendor/Maestro) · `04` (Vendor & Supplier Platform)

## Contesto
Il Glossario (`01`) definisce **"Vendor"** come termine tecnico di dominio e **"Maestro"** come **rinomina di sola UI**. La Ragione di Esistere pone il **Maestro** al centro: è la sua *libertà di relazione* la cosa che PayPoc restituisce. Modellare il soggetto centrale come un generico "Vendor/Supplier" importa il modello mentale del **fornitore-commodity** che la tesi rifiuta ("il linguaggio guida l'architettura").

## Decisione
1. Il **Maestro è un soggetto di dominio di prim'ordine**, definito da **identità, storia, relazione e libertà** — non una rinomina UI di "Vendor" né un'anagrafica fornitore.
2. Un Maestro **può anche essere**, giuridicamente, un vendor/fornitore; ma il dominio lo modella come **soggetto relazionale**, non come mera fonte di approvvigionamento.
3. **Limite di prudenza (coerente con la libertà e con Contract First):** questa decisione riguarda il **livello concettuale/di dominio**. **Non** impone di rinominare d'ufficio i campi tecnici esistenti (`vendor`, `vendorCode`) nel codice, nel database, nelle API o nel contratto v3.1 — quella è una **decisione separata di livello codice**, governata da Contract First, da prendere solo quando (e se) crea valore. **Non si butta via una capability tecnica utile.**
4. **Questo limite non indebolisce l'elevazione del Maestro a soggetto di dominio.** Cambia **subito** il *modello concettuale* — il Maestro è il soggetto centrale in Foundation, PAYPOC OS e prodotto; il naming fisico dei campi è una **migrazione successiva**, non un declassamento del concetto.

## Conseguenze
- **File impattati** (da modificare **solo dopo l'approvazione di questo ADR**):
  - `docs/foundation/01-glossary.md` — "Maestro" elevato da `UI-ONLY` a **soggetto di dominio**; riesame della regola "Vendor tecnico / Maestro UI-only".
  - `docs/foundation/04-enterprise-platform-decomposition.md` — la "Vendor & Supplier Platform" ri-orientata sul **Maestro** come soggetto relazionale (confini dettagliati insieme ad ADR-005).
  - *(eventuale)* `docs/foundation/00-project-rules.md` Art. 9 — allineamento della regola terminologica.
- Si collega ad **ADR-005**: Maestro e Famiglie sono i due capi del dominio **Relazione**.

## Alternative considerate
- **Mantenere Vendor come dominio + Maestro solo UI** → **respinta**: perpetua il pensiero commodity e contraddice la Ragione.
- **Rinominare subito Vendor→Maestro anche in codice/contratto** → **respinta**: prematuro, romperebbe il contratto v3.1 e capability utili; ora cambia il **concetto di dominio**, non il naming fisico.
