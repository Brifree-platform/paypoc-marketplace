# Audit tecnico — IWEX + PayPoc

> **Data:** 2026-07-23
> **Tipo:** due diligence tecnica, taglio da CTO esterno pre-investimento
> **Oggetto:** repository `Brifree-platform/paypoc-marketplace` allo stato di `main`
> **Metodo:** lettura integrale del codice presente. Ogni affermazione è ancorata a
> file e riga reali. Ciò che non è nel codice è marcato come **assente** — non dedotto,
> non presunto, non anticipato.

---

## Avvertenza di metodo

Questo audit **fotografa ciò che esiste oggi nel repository**. Non propone
implementazioni, non progetta la soluzione target, non fa refactoring. Dove una
funzionalità è prevista da un contratto o da un documento di piano ma **non è
presente nel codice**, l'audit lo dichiara esplicitamente.

Distinzione ricorrente e vincolante in tutto il documento:

| Livello | Significato |
|---|---|
| **CODICE** | Esiste ed è eseguibile nel repository |
| **CONTRATTO** | Specificato in `docs/iwexa_hub_openapi_v3.1.yaml`, non ancora realizzato |
| **PIANO/DOC** | Descritto in un documento di progetto, non ancora realizzato |
| **ASSENTE** | Non esiste in nessuna forma nel repository |

## Terminologia

Nel codice e in questo audit si usano i termini tecnici reali: **Vendor**,
**Vendor Store**, **Vendor Warehouse**, `vendorCode`, `warehouse`, `FBI`/`FBV`.

> Nell'interfaccia utente PayPoc il termine **"Vendor" sarà sostituito da
> "Maestro"**. È **esclusivamente una scelta di branding di presentazione**: non
> cambia l'architettura, i nomi delle tabelle, dei campi o degli endpoint, che
> restano `vendor`/`vendorCode`. Questo audit non usa "Maestro" salvo per ricordare
> questa equivalenza.

---

## Sintesi in una riga

> Non esiste ancora una "piattaforma IWEX + PayPoc" in esecuzione. Esiste **un package
> di integrazione Bagisto** che implementa un'architettura che il progetto stesso ha
> già deciso di **riscrivere**, più un corpus recente di **specifica eseguibile**
> (contratto v3.1 + mock + suite di conformità) che è l'asset più solido del repo.
> L'Hub Iwexa reale **non esiste**. Il marketplace (storefront, ordini, wallet,
> pagamenti) **non esiste**.

Il verdetto di maturità completo è in [01-Executive-Summary.md](01-Executive-Summary.md).

---

## Indice dei documenti

| # | Documento | Contenuto |
|---|---|---|
| — | [README.md](README.md) | Questo indice, metodo, terminologia |
| 01 | [01-Executive-Summary.md](01-Executive-Summary.md) | Verdetto, stato per area, maturità, rischi, priorità |
| 02 | [02-Architecture.md](02-Architecture.md) | Struttura, moduli, dipendenze, decisioni architetturali, diagrammi |
| 03 | [03-Database.md](03-Database.md) | Tabelle, relazioni, chiavi, indici, criticità, ER |
| 04 | [04-API.md](04-API.md) | Endpoint API e admin, autenticazione, stato |
| 05 | [05-IWEX.md](05-IWEX.md) | Cos'è IWEX, dati, responsabilità, routing, stock, flussi |
| 06 | [06-PayPoc.md](06-PayPoc.md) | Bagisto: personalizzazioni, override, moduli, estensioni |
| 07 | [07-MultiVendor.md](07-MultiVendor.md) | Vendor, Vendor Store, onboarding, permessi, dashboard |
| 08 | [08-MultiWarehouse.md](08-MultiWarehouse.md) | Warehouse IWEX/Vendor, stock, sincronizzazione, spedizione |
| 09 | [09-Google.md](09-Google.md) | Merchant Center, feed, taxonomy, product type, SEO, Shopping |
| 10 | [10-AI.md](10-AI.md) | Funzioni AI implementate e previste |
| 11 | [11-Security.md](11-Security.md) | Autenticazione, autorizzazioni, validazioni, vulnerabilità |
| 12 | [12-Performance.md](12-Performance.md) | Colli di bottiglia, query, punti critici |
| 13 | [13-TechnicalDebt.md](13-TechnicalDebt.md) | Debito tecnico, qualità del codice, voto, riutilizzabilità |
| 14 | [14-Roadmap.md](14-Roadmap.md) | Indispensabile / consigliato / futuro, sul codice esistente |
| 15 | [15-Appendix.md](15-Appendix.md) | Migration, package, dipendenze, env, config, integrazioni, diagrammi residui |

---

## Fonti primarie lette per questo audit

- Codice del package: `packages/PAYPOC/IwexaConnector/` (87 file PHP, ~5.660 righe)
- Contratto: `docs/iwexa_hub_openapi_v3.1.yaml`
- Strumenti: `tools/mock-hub/` (mock Hub + suite di conformità), `tools/bagisto-import/`
- Documenti di progetto: `docs/*.md` (handover, riscrittura, riconciliazione, immagini, audit funzionale)
- Storia git di `main`
</content>
