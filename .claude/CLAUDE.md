# PAYPOC Marketplace — Iwexa Connector

Monorepo che contiene un singolo package Laravel/Bagisto: **IwexaConnector**, modulo di
integrazione tra il marketplace PAYPOC (basato su Bagisto) e **Iwexa Hub**.
Gestisce sincronizzazione catalogo, stock, magazzini, vendor, spedizioni, routing
e ricezione webhook.

- **Codice del package:** [packages/PAYPOC/IwexaConnector/](packages/PAYPOC/IwexaConnector/)
- **Namespace PSR-4:** `Webkul\PAYPOC\IwexaConnector\` → `src/`
- **Composer:** `webkul/iwexa-connector` (type `bagisto-package`)
- **PHP:** `^8.1`, dipende da `bagisto/bagisto`

> Nota: il repository contiene solo il package, non l'installazione completa di
> Bagisto. Il package va installato dentro un'app Bagisto esistente (vedi Installazione).

## Architettura

Il modulo segue il pattern Bagisto/Laravel: **Controller → Service → Repository → Model**,
con Job per l'elaborazione asincrona ed Event per estendere il comportamento.

### Servizi ([src/Services/](packages/PAYPOC/IwexaConnector/src/Services/))
- `IwexaApiService` — client REST verso Iwexa Hub (riceve base URL, API key, HMAC secret)
- `CatalogImportService` — import batch prodotti con validazione
- `StockUpdateService` — aggiornamento quantità e stato stock
- `VendorImportService` — import/aggiornamento vendor
- `WarehouseSyncService` / `WarehouseStockSyncService` — magazzini e relativo stock
- `ShippingZoneService` / `ShippingRateService` — zone e tariffe di spedizione
- `RoutingService` — calcolo preventivi di routing/spedizione
- `CategoryMappingService` — mappatura categorie sorgente → Bagisto (con override per vendor)
- `ProductTypeMappingService` — assegnazione attribute family
- `AttributeMappingService` — normalizzazione valori attributo
- `AttributeProvisioningService` — crea draft di mapping per attributi sconosciuti
- `WebhookProcessorService` — validazione e routing degli eventi webhook

### Controller ([src/Controllers/](packages/PAYPOC/IwexaConnector/src/Controllers/))
- **API** (`Controllers/Api/`): Catalog, Stock, Vendor, Warehouse, WarehouseStock,
  ShippingZone, ShippingRate, Routing, Webhook
- **Admin** (`Controllers/Admin/`): ProductTypeMapping, CategoryMapping,
  AttributeMapping, SyncJob

### Job asincroni ([src/Jobs/](packages/PAYPOC/IwexaConnector/src/Jobs/))
`ProcessCatalogImport`, `ProcessStockUpdate`, `ProcessWebhook`,
`RetryFailedSyncJob`, `ReleaseExpiredIdempotencyKeys`

### Repository / Model
- Repository in [src/Repositories/](packages/PAYPOC/IwexaConnector/src/Repositories/)
- Model Eloquent in [src/Models/](packages/PAYPOC/IwexaConnector/src/Models/):
  IwexaSyncJob, IwexaProduct, IwexaStockLog, IwexaWebhookEvent, IwexaVendor,
  IwexaWarehouse, IwexaWarehouseStock, ShippingZone, ShippingRate, RoutingQuote,
  CategoryMapping, ProductTypeMapping, AttributeMapping, AttributeValueMapping

### Database ([src/Database/Migrations/](packages/PAYPOC/IwexaConnector/src/Database/Migrations/))
14 migration (`2026_05_01_000001` … `000014`): sync job, prodotti, stock log,
webhook event, mapping (category/product-type/attribute/attribute-value),
vendor, warehouse, warehouse stock, shipping zone/rate, routing quote.

### Eventi ([src/Events/](packages/PAYPOC/IwexaConnector/src/Events/))
`CatalogSyncStarted`, `CatalogSyncCompleted`, `SyncJobFailed`,
`ProductMappingRequired`, `AttributeProvisioningRequired`

## ⚠️ Doppio ServiceProvider (importante)

Esistono **due** classi `IwexaConnectorServiceProvider` con lo stesso nome ma namespace diversi:

1. [src/Providers/IwexaConnectorServiceProvider.php](packages/PAYPOC/IwexaConnector/src/Providers/IwexaConnectorServiceProvider.php)
   — namespace `...\Providers`. **È quello registrato in `composer.json`.**
   Registra: Vendor, Warehouse, WarehouseStock, ShippingZone, ShippingRate, Routing.
   Carica anche le traduzioni (`loadTranslationsFrom`).
2. [src/IwexaConnectorServiceProvider.php](packages/PAYPOC/IwexaConnector/src/IwexaConnectorServiceProvider.php)
   — namespace radice `...\IwexaConnector`. **Non referenziato in composer.json.**
   Registra: IwexaApiService, CatalogImport, StockUpdate, CategoryMapping,
   ProductTypeMapping, AttributeMapping, AttributeProvisioning, Webhook + relativi repository.

I due provider registrano binding **disgiunti**. Il provider attivo (Providers/) non
registra i servizi catalogo/stock/webhook, che però vengono normalmente risolti via
auto-wiring del container (singleton non strettamente necessari). **Prima di modificare
la registrazione dei servizi, verifica quale provider è effettivamente in uso** ed
evita di duplicare/divergere ulteriormente i due file (candidato a consolidamento).

## Configurazione ([src/Config/iwexa-connector.php](packages/PAYPOC/IwexaConnector/src/Config/iwexa-connector.php))

Variabili `.env` (vedi anche `.env.example`):

```env
IWEXA_API_BASE_URL=https://api.iwexa.com
IWEXA_API_KEY=...
IWEXA_HMAC_SECRET=...
IWEXA_SYNC_JOB_RETRY_LIMIT=3
IWEXA_SYNC_JOB_RETRY_DELAY=5          # minuti
IWEXA_IDEMPOTENCY_KEY_EXPIRY=24       # ore
IWEXA_WEBHOOK_EVENT_EXPIRY=24         # ore
IWEXA_BATCH_SIZE_PRODUCTS=100
IWEXA_BATCH_SIZE_STOCK=500
IWEXA_LOG_CHANNEL=daily
```

## Rotte

### API — prefix `bagisto-api/iwexa`, middleware `api` + `throttle:60,1`
([src/Routes/api.php](packages/PAYPOC/IwexaConnector/src/Routes/api.php))
- `POST  /catalog/products/batch` — import batch prodotti
- `PUT   /catalog/products/{sku}` — aggiorna prodotto
- `POST  /catalog/stock` — aggiorna stock
- `POST  /vendors`, `PUT /vendors/{vendor_code}`, `GET /vendors/{vendor_code}`
- `POST  /warehouses`, `POST /warehouse-stocks`
- `POST  /shipping-zones`, `POST /shipping-rates`
- `POST  /routing/quote`
- `POST  /webhooks` — receiver webhook (HMAC-SHA256)

### Admin — prefix `admin/iwexa`, middleware `admin`
([src/Routes/admin.php](packages/PAYPOC/IwexaConnector/src/Routes/admin.php))
- `product-type-mappings` (index/show/approve/reject)
- `category-mappings` (CRUD completo)
- `attribute-mappings` (index/show/approve/configure)
- `sync-jobs` (index/show/retry)

Le view admin (Blade) sono in [src/Resources/views/admin/](packages/PAYPOC/IwexaConnector/src/Resources/views/admin/),
namespace view `iwexa`.

## Installazione

Il package va copiato in un'app Bagisto:

```bash
# da dentro l'app Bagisto
php artisan vendor:publish --tag=iwexa-migrations
php artisan vendor:publish --tag=iwexa-config
php artisan vendor:publish --tag=iwexa-views   # opzionale
php artisan migrate
```

## Sicurezza e affidabilità
- **HMAC-SHA256** su tutti i webhook (secret condiviso)
- **Idempotenza**: chiavi di idempotenza + dedup webhook (`event_id` + `delivery_id`)
- **Bearer token** per l'autenticazione API
- **Rate limiting**: 60 richieste/minuto (`throttle:60,1`)
- **Retry**: fino a 3 tentativi sui sync job falliti, con retry manuale da admin

## Test
Cartelle `tests/Unit/` e `tests/Feature/` predisposte in
[packages/PAYPOC/IwexaConnector/tests/](packages/PAYPOC/IwexaConnector/tests/)
(al momento senza file di test). Non è ancora configurato un runner a livello di repo.

## Convenzioni
- Codice e identificatori in inglese; alcuni messaggi di commit/PR in italiano.
- Seguire il pattern esistente Controller → Service → Repository → Model.
- I nuovi servizi vanno registrati come singleton nel ServiceProvider **attivo**
  (`src/Providers/...`) — vedi nota sul doppio provider sopra.
- Le migration usano il prefisso data `2026_05_01_0000NN`; mantenere la numerazione progressiva.

## Branch / Git
- Remote: `origin` → `github.com/Brifree-platform/paypoc-marketplace.git`
- Branch principale: `main` (unico branch sul remote). Il branch `pr-1-iwexa-connector`
  è stato mergiato con la PR #1 ed eliminato.
