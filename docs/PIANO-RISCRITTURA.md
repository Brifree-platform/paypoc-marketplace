# Piano di riscrittura — IwexaConnector

> **Data:** 2026-07-20
> **Stato:** proposta, da approvare
> **Contratto di riferimento:** `iwexa_hub_openapi.yaml` + `hub-field-contract.md` v3.0 (2026-04-29, Active)

---

## 1. Perché

Il connettore implementa un'architettura **opposta** a quella del contratto.

| | Contratto | Connettore attuale |
|---|---|---|
| Catalogo | PayPoc **legge** dall'Hub (`GET /products`) | PayPoc **riceve** push (`POST /catalog/products/batch`) |
| Stock | `GET /stock/{externalProductId}` | `POST /catalog/stock` |
| Vendor | `GET /vendors` | `POST /vendors` |
| Spedizioni | `GET /shipping-quote` | `POST /shipping-zones`, `/shipping-rates` |
| Tassonomia | `GET /taxonomy` | assente |
| **Ordini** | `POST /orders`, `/cancel`, `/return`, `GET /orders/{id}` | **assenti** |

Il field contract è esplicito:

> *"PayPoc writes ONLY three things. Never catalog, prices, stock updates, labels, or categories."*
> *"PayPoc never writes stock."*

**Nessuno degli 11 endpoint API attuali corrisponde al contratto.** I due soli endpoint che il
connettore chiama verso l'esterno (`/api/v1/catalog/products/batch`, `/api/v1/catalog/product-stock`)
non esistono nella specifica.

Il modello dati diverge altrettanto: dei campi obbligatori dello schema `Product`, l'unico
presente nel codice è `vendorCode`. Mancano `externalProductId` (l'identità del prodotto),
`listPrice`/`sellPrice`/`maxApplicableValue` (il meccanismo di credito PayPoc),
`googleTaxonomyId`, `fulfillment` FBI/FBV, `shippingPolicy`.

**Prova che il modulo non è mai stato eseguito:** fino al 2026-07-20 `WebhookController` non era
costruibile dal container e ogni webhook rispondeva 500. Una singola chiamata reale l'avrebbe rivelato.

### Circostanza favorevole

`REAL_VS_MOCK.md` classifica l'Hub Iwexa come **INESISTENTE** ("solo contratto in `docs/`"), e i
server nell'OpenAPI sono `hub.iwexa.example` (dominio segnaposto). **Non c'è nulla in produzione
da salvaguardare**: la riscrittura non rompe niente, e le due parti possono nascere allineate.

---

## 2. Destino del codice attuale

87 file PHP, 5.574 righe.

### Da tenere (impalcatura valida)

| Componente | Nota |
|---|---|
| `Http/Middleware/VerifyIwexaSignature` | Si sposta sui soli webhook (vedi §5) |
| `Jobs/ProcessWebhook`, `RetryFailedSyncJob`, `ReleaseExpiredIdempotencyKeys` | Riutilizzabili così |
| `Models/IwexaSyncJob`, `IwexaWebhookEvent` + migration | Tracciamento e dedup restano validi |
| `Repositories/IwexaSyncJobRepository` | — |
| `Controllers/Admin/SyncJobController` + view | Unica admin UI che sopravvive intatta |
| `Events/*` | Ciclo di vita dei job, indipendente dalla direzione |
| `Providers/IwexaConnectorServiceProvider`, `Config/` | Struttura invariata |

### Da eliminare (superficie di scrittura in ingresso)

Il contratto vieta esplicitamente che PayPoc riceva scritture di catalogo, prezzi, stock o categorie.

- **8 controller API**: `Catalog`, `Stock`, `Vendor`, `Warehouse`, `WarehouseStock`,
  `ShippingZone`, `ShippingRate`, `Routing` (resta `Webhook`)
- **6 servizi**: `VendorImport`, `WarehouseSync`, `WarehouseStockSync`, `ShippingZone`,
  `ShippingRate`, `Routing`
- **5 model + migration**: `IwexaWarehouse`, `IwexaWarehouseStock`, `ShippingZone`,
  `ShippingRate`, `RoutingQuote`
- I `Http/Requests` corrispondenti

Magazzini, zone e tariffe di spedizione modellano un dominio che nel contratto **appartiene
interamente a Iwexa**: PayPoc riceve `shippingPolicy` già calcolata per prodotto e
`GET /shipping-quote` per il carrello. Non deve ricostruire quella logica.

### Da ripensare — il sottosistema di mapping

`CategoryMappingService`, `ProductTypeMappingService`, `AttributeMappingService`,
`AttributeProvisioningService` (+ 4 model, 3 controller admin) esistono per riconciliare
categorie e attributi arbitrari provenienti da una sorgente esterna.

Il contratto rende questo problema **inesistente per costruzione**:

> *"Paths must be identical between Iwexa and PayPoc — no remapping, no mismatch"*

Iwexa fornisce `googleTaxonomyId` + `localizedPath` già normalizzati. Resta però un lavoro di
mappatura reale, ma **di natura diversa**: portare la Google Product Taxonomy dentro l'albero
categorie e le attribute family di Bagisto. Va riscritto con quell'obiettivo, non adattato.

**Da decidere:** quanta della UI admin di approvazione mapping ha ancora senso (§6).

---

## 3. Le fasi

### Fase 0 — Mock Hub

Server che implementa `iwexa_hub_openapi.yaml` con dati d'esempio.

**Perché prima di tutto:** oggi il connettore non è testabile contro nulla, quindi ogni riga
scritta è un'ipotesi non verificata — è esattamente così che il webhook è rimasto rotto per mesi.

Il mock è anche la **specifica eseguibile** per chi costruirà l'Hub vero: non "leggi lo YAML e
fidati", ma "il tuo Hub deve superare questa stessa suite". Quando l'Hub reale arriva si cambia
`IWEXA_API_BASE_URL` e si vede subito se rispetta il contratto.

- Endpoint: `/products`, `/products/{slug}`, `/stock/{id}`, `/vendors`, `/shipping-quote`, `/taxonomy`, `/orders*`
- Firma HMAC sulle risposte webhook, per esercitare anche quel percorso
- Dataset di esempio coerente con lo schema `Product` (incluso FBI/FBV e `alwaysFree`)

**Verifica:** i payload del mock validano contro lo schema OpenAPI.

### Fase 1 — Modello dati allineato

Migration e model che rispecchiano lo schema `Product` del contratto.

- `externalProductId` come chiave d'identità (oggi: `sku`)
- `listPrice`, `sellPrice`, `vatRate`, `maxApplicableValue`
- `googleTaxonomyId`, `googleTaxonomyPath`, `localizedPath`
- `fulfillment` (`type` FBI/FBV, `warehouseCode`, `prepTimeDays`, `deliveryTimeDays`)
- `shippingPolicy` (`country`, `cost`, `freeShippingThreshold`, `alwaysFree`)

**Vincolo:** le regole di §"Validation Rules" del field contract vanno applicate come validazione
reale (`listPrice ≥ sellPrice`, `maxApplicableValue ≤ sellPrice`, `alwaysFree=true → threshold null`).

**Verifica:** un payload d'esempio del mock si persiste e si rilegge senza perdita di campi.

### Fase 2 — Client di lettura

`IwexaHubClient` che consuma l'Hub: `GET /products` (paginato), `/products/{slug}`,
`/stock/{id}`, `/vendors`, `/taxonomy`, `/shipping-quote`.

Sostituisce `IwexaApiService`, che oggi chiama endpoint inesistenti.

**Verifica:** contro il mock, un ciclo di import popola prodotti Bagisto reali, navigabili in
vetrina, con prezzi e politiche di spedizione corretti.

### Fase 3 — Flusso ordini

Il cuore mancante, e l'unico punto in cui gira il denaro.

- `POST /orders` — con `listPriceSnapshot`/`sellPriceSnapshot` per riga, `walletApplied`, `totalAgreed`
- `GET /orders/{id}` — stato consolidato con `shipments`
- `POST /orders/{id}/cancel`, `POST /orders/{id}/return`
- Gestione `409 Stock conflict` e `422 Validation error`

**Verifica:** un ordine Bagisto produce una chiamata conforme allo schema `OrderCreate`; il mock
risponde con `shipments` splittati per vendor e lo stato si riflette in Bagisto.

### Fase 4 — Webhook in ingresso

`productUpdated`, `stockChanged`, `orderStatusChanged` — i tre della specifica.

È la parte in cui il codice attuale è più vicino al riutilizzabile: `WebhookProcessorService`,
dedup per `event_id`+`delivery_id` e il middleware HMAC restano nella sostanza.

**Verifica:** il mock invia i tre eventi firmati; prodotto, stock e ordine si aggiornano.

---

## 4. Cosa NON copre questo piano

Da `REAL_VS_MOCK.md`, sono **inesistenti** e fuori perimetro del connettore:

wallet, motore crediti, loyalty, bonus post-ordine, pagamenti, email transazionali, carrello e
checkout funzionanti.

`maxApplicableValue` arriva dall'Hub, ma **il motore che lo applica non esiste**. Il connettore
può consegnare il dato; non può da solo rendere funzionante il meccanismo di credito che
caratterizza PayPoc.

---

## 5. Decisione tecnica aperta — la firma

| | Specifica | Implementazione attuale |
|---|---|---|
| Contenuto firmato | solo body | body + timestamp |
| Finestra anti-replay | assente | ±300s configurabile |

Senza timestamp, una richiesta catturata resta riutilizzabile **per sempre**.

**Raccomandazione:** tenere il timestamp e **aggiornare la specifica**. Dato che l'Hub non è
ancora stato scritto, questo è il momento di correggere il contratto, non di adeguarsi a una sua
debolezza. Il nome header `X-Iwexa-Signature` coincide già (gli header HTTP sono case-insensitive).

---

## 6. Decisioni che spettano al committente

1. **Il contratto v3.0 è confermato?** Tutto il piano ci poggia sopra.
2. **Chi costruisce l'Hub Iwexa, e quando?** Senza Hub reale il connettore non va in produzione,
   per quanto ben scritto. Il mock consente di procedere, non di rilasciare.
3. **Quanta admin UI di mapping serve** una volta che la tassonomia arriva già normalizzata?
4. **Il repo GitHub deve restare pubblico?** Oggi `Brifree-platform/paypoc-marketplace` è
   pubblico. Nessuna credenziale è mai stata committata (verificato su tutta la history), ma
   l'architettura dell'integrazione è leggibile da chiunque.

---

## 7. Rischi

| Rischio | Mitigazione |
|---|---|
| Il contratto cambia mentre si riscrive | Fase 0 lo rende eseguibile: una modifica rompe test visibili |
| L'Hub reale devia dal contratto | Il mock diventa suite di conformità per l'Hub |
| Si riscrive al buio una seconda volta | Nessuna fase parte senza verifica contro il mock |
| Il lavoro non ha committente | Domanda §6.2 — da chiarire **prima** della fase 1 |

---

## 8. Stato attuale (2026-07-20)

Lavoro già completato su `main`, indipendente da questo piano:

- Webhook riparato (era 500 su ogni chiamata: binding mancanti nel provider)
- Endpoint API protetti con HMAC (erano scrivibili senza credenziali)
- 10 test sul middleware, validati per mutazione
- Provider duplicato rimosso
- CLAUDE.md allineato al codice

Nota: gli endpoint messi in sicurezza sono **fra quelli che il piano prevede di eliminare**. La
correzione era comunque corretta — erano aperti e raggiungibili — ma non va scambiata per
validazione della loro esistenza.
