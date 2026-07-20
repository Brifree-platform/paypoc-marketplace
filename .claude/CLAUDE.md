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

## ServiceProvider

C'è **un solo** provider: [src/Providers/IwexaConnectorServiceProvider.php](packages/PAYPOC/IwexaConnector/src/Providers/IwexaConnectorServiceProvider.php)
(namespace `...\Providers`), registrato in `composer.json`. Registra tutti i servizi
e i repository, l'alias middleware `iwexa.signature`, rotte, view e traduzioni.
I nuovi servizi vanno aggiunti qui.

Fino al 2026-07-20 esisteva un secondo provider omonimo in `src/` con namespace radice,
non referenziato da nessuna parte, che registrava un insieme **disgiunto** di binding.
È stato rimosso dopo aver consolidato le sue registrazioni in quello attivo.

> ⚠️ Nota storica — non ripetere questo errore. Fino al 2026-07-20 questo documento
> affermava che i servizi mancanti «vengono normalmente risolti via auto-wiring del
> container (singleton non strettamente necessari)». **Era falso**: `IwexaApiService`
> ha un costruttore con tre argomenti scalari (`string $baseUrl, $apiKey, $hmacSecret`)
> che l'auto-wiring non può risolvere. Il risultato era che `WebhookController` —
> l'unico controller che dipende da quel servizio — non era costruibile e **ogni
> webhook rispondeva 500**. Prima di assumere che un binding sia superfluo, verificalo:
> `php artisan tinker --execute="app(\Webkul\PAYPOC\IwexaConnector\Services\IwexaApiService::class);"`

## Configurazione ([src/Config/iwexa-connector.php](packages/PAYPOC/IwexaConnector/src/Config/iwexa-connector.php))

Variabili `.env` (vedi anche `.env.example`):

```env
IWEXA_API_BASE_URL=https://api.iwexa.com
IWEXA_API_KEY=...                     # usata in USCITA (Bagisto → Iwexa Hub)
IWEXA_HMAC_SECRET=...                 # firma, in entrata e in uscita
IWEXA_SIGNATURE_TOLERANCE=300         # secondi di scarto accettati sul timestamp
IWEXA_SYNC_JOB_RETRY_LIMIT=3
IWEXA_SYNC_JOB_RETRY_DELAY=5          # minuti
IWEXA_IDEMPOTENCY_KEY_EXPIRY=24       # ore
IWEXA_WEBHOOK_EVENT_EXPIRY=24         # ore
IWEXA_BATCH_SIZE_PRODUCTS=100
IWEXA_BATCH_SIZE_STOCK=500
IWEXA_LOG_CHANNEL=daily
```

## Rotte

### API — prefix `bagisto-api/iwexa`, middleware `api` + `throttle:60,1` + `iwexa.signature`
([src/Routes/api.php](packages/PAYPOC/IwexaConnector/src/Routes/api.php))

**Tutte** le rotte API richiedono una firma HMAC valida (vedi Sicurezza).
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

In sviluppo il package **non va copiato**: si aggancia all'app Bagisto come repository
`path` con `symlink: true`, così le modifiche in questo repo sono immediatamente attive
nell'app. Nel `composer.json` dell'app:

```json
"repositories": [{ "type": "path", "url": "packages/*/*", "options": { "symlink": true } }],
"require": { "webkul/iwexa-connector": "*@dev" }
```

Verifica rapida che l'aggancio sia vivo, dall'app Bagisto:
`php artisan route:list | grep iwexa` (attese 29 rotte) e `php artisan migrate:status`.

Comandi di publish (dall'app Bagisto):

```bash
# da dentro l'app Bagisto
php artisan vendor:publish --tag=iwexa-migrations
php artisan vendor:publish --tag=iwexa-config
php artisan vendor:publish --tag=iwexa-views   # opzionale
php artisan migrate
```

## Sicurezza e affidabilità

### Autenticazione in ingresso — HMAC-SHA256

Ogni richiesta verso `bagisto-api/iwexa/*` (webhook **e** endpoint API) deve essere
firmata. Il controllo è nel middleware `iwexa.signature`
([src/Http/Middleware/VerifyIwexaSignature.php](packages/PAYPOC/IwexaConnector/src/Http/Middleware/VerifyIwexaSignature.php)):

| Header | Contenuto |
|---|---|
| `X-IWEXA-SIGNATURE` | `hash_hmac('sha256', body_grezzo + timestamp, IWEXA_HMAC_SECRET)` |
| `X-IWEXA-TIMESTAMP` | Unix timestamp, entro ±`IWEXA_SIGNATURE_TOLERANCE` secondi da adesso |

- Confronto a **tempo costante** (`hash_equals`)
- **Fail closed**: se `IWEXA_HMAC_SECRET` è vuoto il middleware risponde `503`, non
  lascia passare — un `.env` incompleto non deve aprire le API
- Il timestamp nella firma limita la finestra di **replay**
- Sulle richieste senza body (GET) si firma la stringa vuota + timestamp

Il `WebhookController` valida la firma anche al proprio interno: ridondante rispetto
al middleware, mantenuto come difesa in profondità.

> ⚠️ **`IWEXA_API_KEY` è solo in uscita** (header `Bearer` nelle chiamate
> Bagisto → Iwexa Hub, [IwexaApiService.php:61](packages/PAYPOC/IwexaConnector/src/Services/IwexaApiService.php#L61)).
> Non autentica nulla in ingresso. Fino al 2026-07-20 questo documento dichiarava
> «Bearer token per l'autenticazione API» come se fosse una protezione attiva: non è
> mai esistita, e gli endpoint di scrittura (catalogo, stock, vendor, magazzini) erano
> raggiungibili **senza alcuna credenziale**.

### Altro
- **Idempotenza**: chiavi di idempotenza + dedup webhook (`event_id` + `delivery_id`)
- **Rate limiting**: 60 richieste/minuto (`throttle:60,1`)
- **Retry**: fino a 3 tentativi sui sync job falliti, con retry manuale da admin

## Test

I test girano con **Pest, dall'app Bagisto** (il repo del package non ha un runner proprio):

```bash
# dall'app Bagisto
./vendor/bin/pest --testsuite="Iwexa Connector Unit Test"
```

Perché funzioni, nell'app Bagisto servono due registrazioni una tantum:
- `phpunit.xml` → un `<testsuite>` che punta a `packages/PAYPOC/IwexaConnector/tests/Unit`
- `tests/Pest.php` → `uses(Tests\TestCase::class)->in('../packages/PAYPOC/IwexaConnector/tests');`

Copertura attuale: [tests/Unit/VerifyIwexaSignatureTest.php](packages/PAYPOC/IwexaConnector/tests/Unit/VerifyIwexaSignatureTest.php)
— 10 test sul middleware HMAC (firma valida, mancante, errata, body alterato, replay,
timestamp futuro/non numerico, body vuoto, fail-closed senza secret). Non toccano il
database: istanziano il middleware direttamente.

Servizi, import catalogo e webhook sono ancora **senza copertura**.

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
