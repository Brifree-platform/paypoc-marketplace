# ADR-001 — La Ragione di Esistere come norma suprema

> **Stato:** Accepted (approvato 2026-07-25 da Cristiano Plattner)
> **Data:** 2026-07-25
> **Proponente (proposta tecnica):** Principal Software Architect / Guardian del PAYPOC OS
> **Approvatore finale:** Cristiano Plattner
> **Riferimenti:** PAYPOC OS — Livello -1 (Ragione di Esistere) · `00-project-rules` Art. 11 · `02-decision-process`

## Contesto
La Foundation tecnica (`00`–`04`) è stata definita **prima** che la Ragione di Esistere di PayPoc fosse formulata. La Costituzione tecnica (`00`) si autodichiara "norma di grado più alto" (Art. 11). Al termine di un lungo confronto è stata certificata la Ragione di Esistere — la **categoria** che PayPoc vuole creare — che **precede e vincola** ogni scelta di dominio, strumento e modello economico. Va fissata la sua supremazia normativa.

## Decisione
1. La **Ragione di Esistere diventa la norma suprema** dell'intero PAYPOC OS e della Foundation:
   > **PayPoc è l'infrastruttura che restituisce libertà di relazione tra chi crea valore e chi lo sceglie.**
   >
   > **Ragione di Esistere:** PayPoc esiste per restituire ai Maestri la libertà di costruire un rapporto diretto con le Famiglie, affinché entrambe recuperino il valore, la fiducia e la libertà di scelta che la crescente distanza tra produzione e consumo ha progressivamente ridotto.
   >
   > **Keystone:** *Libertà* (di relazione, di scelta). Il Potere e ogni altro meccanismo sono **strumenti**.
2. **Gerarchia normativa** (dal grado più alto):
   1. Ragione di Esistere (PAYPOC OS — Livello -1)
   2. `00-project-rules.md` (Costituzione tecnica)
   3. ADR `Accepted`
   4. contratto approvato
   5. Foundation e altri documenti
   6. documentazione descrittiva
   7. codice (limitatamente alla descrizione dell'AS-IS)
3. **Test costituzionale** di ogni ADR: *"Questa decisione aumenta la libertà di relazione tra chi crea valore e chi lo sceglie?"* — con tre guardrail: **niente lock-in · niente rotture imposte · nessuno sconto travestito da relazione.** Se una risposta è NO → non approvabile.
4. **Se uno strumento confligge con la Ragione, cambia lo strumento, non la Ragione.** La Ragione è sovrana sul disegno, ma resta **falsificabile**: se la realtà di mercato ne smentisse le premesse empiriche, va **riesaminata**, non difesa come dogma.

## Conseguenze
- Nasce il **PAYPOC OS** come corpus sovraordinato alla Foundation tecnica; il suo **Livello -1** conterrà il testo autorevole della Ragione.
- La **PAYPOC OS MAP** (`docs/PAYPOC-OS-MAP.md`) offre l'orientamento visivo a questa gerarchia; è un **documento di orientamento, non una fonte normativa autonoma** — la norma resta questo ADR e la Ragione di Esistere.
- **File impattati** (da modificare in un passo successivo, **solo dopo l'approvazione di questo ADR**):
  - `docs/foundation/00-project-rules.md` — Art. 11 (gerarchia) + riferimento alla Ragione come norma suprema.
  - `docs/foundation/02-decision-process.md` — incorporazione (opzionale) del test costituzionale come gate di ogni ADR.
  - *(nuovo)* documento **PAYPOC OS — Livello -1** (casa e collocazione da decidere).

## Alternative considerate
- **Mantenere `00` come norma suprema**, trattando la Ragione come un documento Foundation qualunque → **respinta**: la Ragione è il *perché* dell'intero progetto; non può essere pari agli strumenti che la servono.
- **Fondere la Ragione dentro `00`** → **respinta**: `00` è governance *tecnica*; la Ragione è la costituzione *di prodotto/filosofica*. Sono strati distinti e la loro separazione va preservata.
