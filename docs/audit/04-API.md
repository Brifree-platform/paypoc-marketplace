# 04 — API

> Fonte: `src/Routes/api.php`, `src/Routes/admin.php`, i 13 controller in
> `src/Controllers/`, i 10 form request in `src/Http/Requests/`, il middleware
> `src/Http/Middleware/VerifyIwexaSignature.php` e il client `src/Services/IwexaApiService.php`.

## Due superfici distinte

1. **API esterna** — prefisso `bagisto-api/iwexa`, middleware `api` + `throttle:60,1`
   + `iwexa.signature` ([api.php](../../packages/PAYPOC/IwexaConnector/src/Routes/api.php)).
2. **Admin** — prefisso `admin/iwexa`, middleware `admin`
   ([admin.php](../../packages/PAYPOC/IwexaConnector/src/Routes/admin.php)).

## Reperto architetturale centrale

> **La superficie API è quasi interamente IN INGRESSO in scrittura: è l'Hub che
> spinge dati DENTRO Bagisto.** Dei 12 endpoint API esterni, 10 sono scritture
> inbound, 1 è una lettura, 1 è un compute. **Non esiste alcun flusso ordini.**
> Questo è l'**opposto** dell'architettura del contratto v3.1, secondo cui PayPoc
> **legge** il catalogo dall'Hub e **scrive solo** gli ordini. L'intera API di
> scrittura catalogo/stock/vendor/magazzini è concettualmente da eliminare secondo
> il piano di riscrittura del progetto stesso.

## API esterna — endpoint

| Endpoint | Funzione | Auth | Dipende da | Stato |
|---|---|---|---|---|
| `POST /catalog/products/batch` | Import batch prodotti (inbound write) | HMAC | `CatalogImportService` | ⚠️ **funzionante ma con bug fatale** nel service; scrive solo staging, non crea prodotti Bagisto; elaborazione **sincrona inline** |
| `PUT /catalog/products/{sku}` | Update prodotto | HMAC | `CatalogImportService::updateProductIfExists` | Legge header idempotenza ma **non lo usa**; scrive solo staging |
| `POST /catalog/stock` | Update stock (inbound write) | HMAC | `StockUpdateService` | ⚠️ **effetto stub**: scrive solo `iwexa_stock_logs`, non aggiorna l'inventario Bagisto |
| `POST /vendors` | Crea/aggiorna Vendor | HMAC | `VendorImportService` | Completo |
| `PUT /vendors/{vendor_code}` | Aggiorna Vendor | HMAC | `VendorImportService` | Completo |
| `GET /vendors/{vendor_code}` | Legge Vendor | HMAC | `VendorImportService` | Completo — **unica GET pura** |
| `POST /warehouses` | Crea/aggiorna Vendor Warehouse | HMAC | `WarehouseSyncService` | Completo |
| `POST /warehouse-stocks` | Aggiorna stock di magazzino | HMAC | `WarehouseStockSyncService` | Completo |
| `POST /shipping-zones` | Crea/aggiorna zona spedizione | HMAC | `ShippingZoneService` | Completo |
| `POST /shipping-rates` | Crea/aggiorna tariffa | HMAC | `ShippingRateService` | Completo |
| `POST /routing/quote` | Calcola preventivo routing (compute) | HMAC | `RoutingService` | Completo — logica di business più solida del set |
| `POST /webhooks` | Ricevitore webhook | HMAC (middleware **+** ri-verifica interna) | `WebhookProcessorService` | ⚠️ product-update è **stub**; bug `delivery_id` (vedi sotto) |

Tutte le rotte esterne richiedono la firma HMAC. **Nessun endpoint espone il
catalogo in lettura**: PayPoc, con questo codice, non ha modo di *interrogare* l'Hub.

## API admin — endpoint (UI server-rendered, non JSON)

| Endpoint | Funzione | Auth | Stato |
|---|---|---|---|
| `product-type-mappings` index/show/approve/reject | Gestione attribute family | `admin` | Completo |
| `category-mappings` (CRUD completo) | Gestione mappa categorie | `admin` | Completo |
| `attribute-mappings` index/show/approve/configure | Gestione mappa attributi | `admin` | Completo |
| `sync-jobs` index/show/retry | Monitor job + retry manuale | `admin` | Completo — **unico endpoint che dispatcha un job** |

> Le view admin estendono il layout Bagisto (`layouts.master`) ma **non sono
> registrate in alcun menu admin** e **non hanno controlli ACL/`@can`**: sono
> protette solo dal middleware `admin` (qualsiasi operatore loggato le raggiunge).

## Il client in uscita è codice morto

`IwexaApiService` è l'**unico** componente che chiama l'Hub. Espone tre metodi:
`catalogBatch` (`POST /api/v1/catalog/products/batch`), `updateProduct`
(`PUT /.../{sku}`), `updateStock` (`POST /api/v1/catalog/product-stock`), con header
`Authorization: Bearer {apiKey}` + `Idempotency-Key`.

Due problemi gravi:
1. **Sono tutti metodi di SCRITTURA che PUSHANO verso l'Hub** (POST/PUT). Non
   esiste alcun metodo GET / lettura catalogo. Contraddice lo scopo dichiarato
   (ingestione Hub→Bagisto).
2. **Sono codice morto**: `catalogBatch`/`updateProduct`/`updateStock` non sono
   invocati da nessuna parte in `src/`. Solo `validateWebhookSignature` (helper
   inbound usato dal `WebhookController`) è esercitato.

## Il bug webhook `delivery_id`

Il `WebhookController` legge quattro header, incluso `X-IWEXA-DELIVERY-ID` in
`$deliveryId`, ma **non lo inoltra** al service: `processWebhook($data, $signature,
$timestamp)` non riceve `$deliveryId`. La dedup del service usa invece
`$payload['delivery_id']` dal **body JSON**. Poiché la colonna `delivery_id` è
`NOT NULL UNIQUE`, un webhook privo di quel campo nel body viola il vincolo →
è la radice del bug storico "il secondo webhook fallisce sempre". Inoltre
`validateIdempotency` restituisce "non duplicato" (quindi **processa**) se `event_id`
o `delivery_id` mancano — la protezione anti-replay si disattiva proprio quando i dati
sono incompleti.

## Incoerenze di contratto API (facts)

- **Convenzioni di risposta divergenti**: Catalog/Stock/Webhook usano
  `{status: 'success'|'error'}`; Vendor/Warehouse/Shipping/Routing usano
  `{success: true|false, message, data}`.
- **Codici di errore divergenti**: Catalog/Stock/Webhook → 500 su errore;
  Vendor/Warehouse/Shipping → 400; Routing → 404 vs 400 tramite la stringa
  sentinella `shipping_rate_not_found`.
- **Nessuna elaborazione asincrona sul percorso d'ingresso**: `importBatch` e
  `updateStock` creano una riga `IwexaSyncJob` "come se" fossero job asincroni ma
  eseguono **inline nel thread della richiesta HTTP**. I job `ProcessCatalogImport`/
  `ProcessStockUpdate` non sono dispatchati dai controller (solo dal retry admin).
  Un batch grande blocca il worker web per tutta la durata.
- **`WebhookRequest` esiste ma non è agganciato**: il body webhook non è validato dal
  livello framework (solo HMAC + `json_decode` + controlli nel service).
- **Idempotenza a colonna**: implementata solo in `importBatch`/`updateStock`;
  `updateProduct` legge l'header ma non lo usa.

## Stato di autenticazione (dettaglio in [11-Security.md](11-Security.md))

Il middleware `iwexa.signature` verifica **solo HMAC** (+ timestamp anti-replay).
**Non verifica il Bearer token**, benché il contratto v3.1 (decisione 4) e il README
del package dichiarino un'autenticazione Bearer. Ogni `FormRequest::authorize()`
restituisce `true`: l'autorizzazione è delegata interamente al middleware.
</content>
