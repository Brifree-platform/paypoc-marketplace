# 15 — Appendice

## A. Integrazioni (§6)

| Integrazione | Stato | Cosa funziona | Cosa manca |
|---|---|---|---|
| **Iwexa Hub** | 🟡 contratto + mock; Hub reale ASSENTE | contratto v3.1 formale; mock eseguibile con 63 controlli di conformità | il software dell'Hub; il client di **lettura** lato PayPoc |
| **Amazon (SP-API)** | 🟡 prototipo su campione | `AmazonMapper` traduce Catalog Items → schema contratto su un JSON di esempio | client SP-API vivo, auth, inventario (FBA), più di 1 categoria mappata |
| **Bagisto** | 🟢 framework base (host) | il package si aggancia via composer path; usa la base repository Bagisto | non è installato nel repo; nessun override/tema |
| **Google Merchant Center** | ❌ ASSENTE | — | tutto (client, feed, OAuth, Shopping) — vedi [09-Google.md](09-Google.md) |
| **Stripe / pagamenti** | ❌ ASSENTE | — | tutto |
| **eBay** | ❌ ASSENTE | — | tutto |
| **Kaufland** | ❌ ASSENTE | — | tutto |
| **Cdiscount** | ❌ ASSENTE | — | tutto |
| **ERP** | ❌ ASSENTE | — | tutto |
| **Corrieri (ShippyPro, ecc.)** | ❌ ASSENTE | tabelle tariffe statiche | integrazione corrieri reale |

Delle integrazioni elencate nel brief, **una sola** (Amazon) esiste come codice, e solo
come **prototipo deterministico su un file di esempio**. Tutte le altre citate (eBay,
Kaufland, Cdiscount, GMC, Stripe, ERP) **non hanno una sola riga** nel repository.

## B. Moduli / package

- Package unico: `webkul/iwexa-connector` (type `bagisto-package`).
- Namespace: `Webkul\PAYPOC\IwexaConnector\` → `src/`.
- ServiceProvider unico: `Providers\IwexaConnectorServiceProvider`.
- Strumenti non-package: `tools/mock-hub`, `tools/bagisto-import`.

## C. Migration (14)

`2026_05_01_0000NN_…`:
1 sync_jobs · 2 products · 3 stock_logs · 4 webhook_events · 5 category_mappings ·
6 product_type_mappings · 7 attribute_mappings · 8 attribute_value_mappings ·
9 vendors · 10 warehouses · 11 warehouse_stocks · 12 shipping_zones ·
13 shipping_rates · 14 routing_quotes. Dettaglio schema in [03-Database.md](03-Database.md).

## D. Dipendenze

| Dove | Dipendenza |
|---|---|
| Package | `php: ^8.1`, `bagisto/bagisto: *` (non fissato) |
| Mock Hub | nessuna (PHP 8.1+ puro) |
| Prototipo import | l'app Bagisto ospite (ProductRepository/CategoryRepository) |
| Test | Pest (dall'app ospite; il repo non ha runner proprio) |

## E. Variabili d'ambiente

Da `Config/iwexa-connector.php` e `.env.example`:

| Variabile | Default | Note |
|---|---|---|
| `IWEXA_API_BASE_URL` | `https://api.iwexa.com` | usata solo dal client morto |
| `IWEXA_API_KEY` | (vuota) | header Bearer in **uscita** |
| `IWEXA_HMAC_SECRET` | (vuota) | firma in entrata/uscita; vuota → 503 |
| `IWEXA_SIGNATURE_TOLERANCE` | 300 | **presente in config, assente in `.env.example`** |
| `IWEXA_SYNC_JOB_RETRY_LIMIT` | 3 | |
| `IWEXA_SYNC_JOB_RETRY_DELAY` | 5 (min) | |
| `IWEXA_IDEMPOTENCY_KEY_EXPIRY` | 24 (h) | usata dal job mai schedulato |
| `IWEXA_WEBHOOK_EVENT_EXPIRY` | 24 (h) | dichiarata, non usata dal codice |
| `IWEXA_BATCH_SIZE_PRODUCTS` | 100 | non imposta un limite server-side reale |
| `IWEXA_BATCH_SIZE_STOCK` | 500 | idem |
| `IWEXA_LOG_CHANNEL` | daily | |

Mock: `IWEXA_API_KEY` (`mock-hub-api-key`), `IWEXA_HMAC_SECRET` (`mock-hub-shared-secret`),
`MOCK_HUB_ALLOW_UNSIGNED=1` (bypass firma, solo dev).

## F. Servizi (14)

`IwexaApiService` (uscita, morto) · `CatalogImportService` (bug) · `StockUpdateService`
(stub) · `WebhookProcessorService` (product stub) · `VendorImportService` ·
`WarehouseSyncService` · `WarehouseStockSyncService` · `ShippingZoneService` ·
`ShippingRateService` · `RoutingService` · `CategoryMappingService` ·
`ProductTypeMappingService` · `AttributeMappingService` · `AttributeProvisioningService`.

## G. Diagramma Multi-Vendor (§24)

```
   Iwexa Hub ──push──► POST /vendors ──► VendorImportService ──► paypoc_iwexa_vendors
   (ASSENTE)                                                     (anagrafica staging)
                                                                       │
   vendorCode ── chiave di routing (usata SOLO nel contratto/mock per lo split ordine)
                                                                       │
   ┌───────────────── ASSENTE nel codice ─────────────────────────────┴───────────┐
   │  Vendor Store (vetrina)   Onboarding   Login Vendor   Dashboard   Permessi    │
   │  accesso alla vetrina dal prodotto     prodotti gestiti dal Vendor            │
   └──────────────────────────────────────────────────────────────────────────────┘
   Nota UI: "Vendor" → "Maestro" (solo branding).
```

## H. Diagramma Multi-Warehouse (§24)

```
   Iwexa Hub ──push──► POST /warehouses      ──► WarehouseSyncService     ──► paypoc_iwexa_warehouses
   (ASSENTE)           POST /warehouse-stocks ──► WarehouseStockSyncService──► paypoc_iwexa_warehouse_stocks
                                                                                  │  available = quantity - reserved
   tipi: central (PAYPOC-CENTRAL, FBI) | vendor (VENDOR-{code}, FBV) | esterni = ASSENTI
                                                                                  │
   POST /routing/quote ──► RoutingService ──► sceglie warehouse:
                            costo min → consegna rapida → centrale prima
                            (usa shipping_zones + shipping_rates)  ──► paypoc_iwexa_routing_quotes
                                                                                  │
   ┌──────────── stub/assente ────────────┐
   │ aggiornamento inventario Bagisto = STUB (solo log)                          │
   │ prenotazione reale (reserved) = mai incrementata                            │
   │ sincronizzazione bidirezionale = assente ; corrieri reali = assenti         │
   └─────────────────────────────────────────────────────────────────────────────┘
```

## I. Diagramma API (§24)

```
   bagisto-api/iwexa/*  [api + throttle:60,1 + iwexa.signature(HMAC)]
     INBOUND WRITE (10):  catalog/products/batch · catalog/products/{sku} · catalog/stock ·
                          vendors(POST/PUT) · warehouses · warehouse-stocks ·
                          shipping-zones · shipping-rates · webhooks
     READ (1):            GET vendors/{code}
     COMPUTE (1):         routing/quote
   admin/iwexa/*  [admin]  product-type-mappings · category-mappings · attribute-mappings · sync-jobs
   OUTBOUND (IwexaApiService): catalog push ×3 — CODICE MORTO
```

## L. Documenti di progetto (contesto, non codice)

`docs/iwexa_hub_openapi_v3.1.yaml` (contratto) · `HANDOVER-PROGRAMMATORE.md` ·
`PIANO-RISCRITTURA.md` · `RICONCILIAZIONE-CONTRATTO.md` · `PIANO-IMMAGINI.md` ·
`AUDIT-FUNZIONALE.md`. Questi descrivono intenzioni e piani; **non** vanno confusi con
funzionalità esistenti.
</content>
