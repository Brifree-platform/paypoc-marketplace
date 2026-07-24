# 02 — Processo decisionale e preservazione della conoscenza

> **Sistema:** IWEXA + PayPoc
> **Documento:** `docs/foundation/02-decision-process.md`
> **Stato:** Approved
> **Versione:** 1.0.0
> **Data:** 2026-07-23
> **Data di approvazione:** 2026-07-23
> **Approvatore finale:** Cristiano Plattner
> **Responsabile della proposta tecnica:** Principal Software Architect
> **Prerequisito:** `docs/foundation/00-project-rules.md` — Approved 1.0.0
> **Ambito:** processo decisionale del progetto e memoria tecnica
> **Nota:** il documento non autorizza modifiche al codice.

---

## Architectural Decision Workflow

Tutte le decisioni architetturali devono seguire il seguente processo, nell'ordine:

1. **Analisi e discussione tecnica** — a cura del Responsabile della proposta tecnica
   (Principal Software Architect). Vedi 00-project-rules.md, Art. 8.2.
2. **Validazione del committente** — l'Approvatore finale (Art. 8.1). Nessun agente
   automatico può approvare o dichiarare `Accepted` una decisione (Art. 8.3–8.6).
3. **Formalizzazione della decisione** — la decisione viene scritta in forma definitiva.
4. **Inserimento nella Foundation o in un ADR** — se è una decisione architetturale, in
   un ADR (`docs/adr/ADR-NNN-<slug>.md`, Art. 4); se è una regola di processo/governance,
   nella Foundation.
5. **Solo successivamente, implementazione tecnica** — che richiede a sua volta uno STEP
   di implementazione esplicitamente approvato (Art. 11.5). La documentazione non ha
   potere esecutivo sul codice (Art. 5).

## Principi vincolanti

I seguenti principi sono vincolanti e non derogabili:

1. Le conversazioni con ChatGPT, Claude o altri strumenti **non costituiscono
   documentazione ufficiale**.
2. Una **decisione non documentata nel repository non è definitiva**.
3. La **Foundation e gli ADR costituiscono la memoria tecnica ufficiale** del progetto.
4. L'**implementazione può iniziare solo dopo la formalizzazione e l'approvazione** della
   decisione da cui dipende.
5. **Nessun agente automatico può approvare autonomamente** Foundation o ADR.
6. Il **repository Git è la fonte ufficiale della conoscenza progettuale**.

Ogni sviluppatore deve fare riferimento **esclusivamente** alla documentazione presente
nel repository.

## Knowledge Preservation

L'obiettivo del progetto è **evitare qualsiasi perdita di conoscenza tecnica**.

- Ogni decisione significativa presa durante le discussioni progettuali deve essere
  **trasformata in documentazione permanente**.
- La **Foundation e gli ADR costituiscono la memoria tecnica** del progetto.
- Le conversazioni servono **esclusivamente per arrivare alla decisione**; la
  documentazione del repository rappresenta la **versione ufficiale e condivisa**.

## Changelog

| Data | Versione | Modifica | Stato |
|---|---|---|---|
| 2026-07-23 | 1.0.0 | Prima versione approvata del processo decisionale e di preservazione della conoscenza. | Approved |
</content>
