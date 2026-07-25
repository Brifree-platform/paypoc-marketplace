# ADR-005 — Introduzione dei domini Relazione, Famiglie e Fiducia

> **Stato:** Accepted (approvato 2026-07-25 da Cristiano Plattner)
> **Data:** 2026-07-25
> **Proponente (proposta tecnica):** Principal Software Architect / Guardian del PAYPOC OS
> **Approvatore finale:** Cristiano Plattner
> **Riferimenti:** Ragione di Esistere (ADR-001) · Audit Costituzionale della Foundation · `04` · ADR-004

## Contesto
L'Audit Costituzionale ha rilevato che la maggiore incompatibilità della Foundation con la Ragione di Esistere è **per assenza**, non per contraddizione. La Ragione mette al centro **Relazione, Famiglie e Fiducia**, ma la decomposizione enterprise (`04`) non ne contiene i domini: la Famiglia compare come generico "Customer", non esiste un dominio della Fiducia, e la relazione diretta Maestro↔Famiglia non è modellata.

## Decisione
Introdurre **tre domini di prim'ordine** nell'architettura enterprise, secondo il **metodo di decomposizione già confermato** dall'audit (Platform, confini, ownership, separazione policy/esecuzione):
1. **Relazione** — la relazione diretta Maestro↔Famiglia come dominio modellato ("lo stesso legame, due capi"). È il cuore operativo della tesi.
2. **Famiglie** — la Famiglia/nucleo come soggetto relazionale ed economico (non l'individuo-consumatore generico).
3. **Fiducia** — fiducia, verifica, trasparenza e reputazione come dominio (non un dopo-pensiero).

Confini, ownership e maturità di questi domini saranno dettagliati **estendendo `04`** in un passo successivo.

## Conseguenze
- **File impattati** (da modificare **solo dopo l'approvazione di questo ADR**):
  - `docs/foundation/04-enterprise-platform-decomposition.md` — **esteso** con i domini Relazione, Famiglie, Fiducia (mappa, ownership, dipendenze).
  - `docs/foundation/01-glossary.md` — aggiunta dei termini **Relazione**, **Famiglie** (come dominio), **Fiducia** (come dominio).
- Si collega ad **ADR-004** (Maestro) e prepara i futuri Pilastri del PAYPOC OS.

## Alternative considerate
- **Modellare Famiglie/Fiducia come attributi delle Platform esistenti** → **respinta**: sotto-rappresenta il cuore della tesi; meritano domini di prim'ordine.
- **Rimandarli** → **respinta**: l'audit mostra che *sono* il cuore; rimandarli lascerebbe PayPoc nel vecchio modello commodity.
