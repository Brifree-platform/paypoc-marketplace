# 07 — Multi Vendor

> Fonte: `IwexaVendor` (model/migration/repository), `VendorImportService`,
> `VendorController`, endpoint `/vendors`, e il contratto v3.1 (`Vendor`, `vendorCode`).

## Terminologia

Si usano i termini tecnici reali: **Vendor**, **Vendor Store**, **Vendor Warehouse**,
`vendorCode`. Nell'interfaccia PayPoc "Vendor" sarà mostrato come **"Maestro"**: è solo
branding di presentazione e **non cambia nulla** dell'architettura (tabelle, campi,
endpoint restano `vendor*`).

## Avvertenza

> Come funzione di **marketplace multi-vendor operativo** (vetrine, onboarding,
> login vendor, dashboard, permessi), il Multi-Vendor **NON esiste nel codice**. Non
> è installato alcun modulo marketplace Bagisto (Velocity/Marketplace). Ciò che esiste
> è **l'ingestione dell'anagrafica Vendor** dall'Hub in una tabella di staging, e il
> `vendorCode` come chiave di routing. Tutto il resto è **requisito/contratto**, non
> codice.

## Cosa esiste nel codice

### Anagrafica Vendor (staging)
- Tabella `paypoc_iwexa_vendors`: `vendor_code` (unique), `vendor_name`, `legal_name`,
  `vat_number`, `tax_code`, indirizzo, `country`, `website`, `status`,
  `responsible_person` (JSON), `original_iwexa_payload` (JSON).
- Model `IwexaVendor`: scope `active`/`inactive`. **Nessuna relazione Eloquent** —
  benché `vendor_code` sia chiave esterna in almeno sei altre tabelle, il model non
  definisce alcun `hasMany` (vedi [03-Database.md](03-Database.md)).
- `VendorImportService`: `validateVendorPayload`, `createOrUpdateVendor` (upsert),
  `findVendorByCode`, `vendorExists`, `getActiveVendors`. Completo e funzionale.
- Endpoint: `POST /vendors`, `PUT /vendors/{vendor_code}`, `GET /vendors/{vendor_code}`
  (l'unica GET pura del connettore).

Questo copre il **punto 1 dello scopo Vendor**: ricevere e conservare l'anagrafica dei
Vendor spinta dall'Hub. Nient'altro.

### Chiave di routing
`vendorCode` è la chiave con cui, **nel contratto e nel mock**, il carrello si
raggruppa in pacchetti e l'ordine si splitta (una spedizione per Vendor). Nel
**connettore** questo split non avviene: non esiste flusso ordini.

## Cosa NON esiste (requisiti non implementati)

| Requisito richiesto | Stato nel codice |
|---|---|
| **Vendor Store** (vetrina per ogni Vendor) | ❌ ASSENTE — nessun modulo storefront/marketplace |
| **Accesso alla vetrina Vendor da ogni prodotto** | ❌ ASSENTE — non esiste PDP né pagina Vendor renderizzata |
| **Onboarding Vendor** | ❌ ASSENTE — i Vendor arrivano già creati dall'Hub, nessun flusso di registrazione |
| **Login / account Vendor** | ❌ ASSENTE — nessuna autenticazione Vendor |
| **Dashboard Vendor** | ❌ ASSENTE — l'unica UI è admin operatore (mapping/sync-jobs) |
| **Permessi / autorizzazioni Vendor** | ❌ ASSENTE — nessun ACL, nemmeno per l'admin operatore |
| **Prodotti gestiti dal Vendor** | ❌ ASSENTE — i prodotti arrivano dall'Hub, il Vendor non li gestisce in PayPoc |

### Sui requisiti "vetrina" specificati
La richiesta *"ogni Vendor dispone della propria vetrina"* e *"da ogni prodotto deve
essere possibile accedere alla relativa vetrina del Vendor"* sono **obiettivi di
prodotto validi ma non presenti nel codice**. Gli elementi che li abiliterebbero
esistono **solo nel contratto**: `vendorSlug` (per l'URL `/vendor/{vendorSlug}`),
`vendorName`, `logoUrl` nello schema `Vendor`. Il mock serve `/vendors` con questi
campi. Ma **non c'è tema storefront, né rotta pubblica, né vista** che li renderizzi.

## Modello dei dati Vendor: contratto vs codice

| Campo contratto | Presente nel DB connettore? |
|---|---|
| `vendorCode` | ✅ `vendor_code` |
| `vendorName` | ✅ `vendor_name` |
| `vendorSlug` | ❌ non presente nella tabella vendors |
| `logoUrl` | ❌ non presente |

Nota: la tabella vendors conserva molti dati **anagrafici/fiscali** (legal_name,
vat_number, tax_code, responsible_person) che il contratto pubblico `Vendor` **non**
espone — coerente con l'idea che siano dati B2B interni. Ma mancano proprio i due campi
di presentazione (`vendorSlug`, `logoUrl`) che servirebbero alla vetrina.

## Fulfillment per Vendor

Il concetto FBI/FBV (magazzino centrale vs magazzino Vendor) esiste come **enum** nelle
tabelle warehouse/routing (`type: central|vendor`, `warehouseCode` `VENDOR-{code}`), e
la logica di scelta è nel `RoutingService`. Dettaglio in [08-MultiWarehouse.md](08-MultiWarehouse.md).

## Sintesi Multi-Vendor

Esiste **solo** l'ingestione dell'anagrafica Vendor e il `vendorCode` come chiave. Il
marketplace multi-vendor come **esperienza** (vetrine, onboarding, dashboard, permessi)
**non è iniziato**. È l'area con il divario più ampio fra ambizione di prodotto
("marketplace di Maestri con vetrine proprie") e stato del codice (una tabella di
staging con CRUD).
</content>
