# 13 — Debito tecnico, qualità del codice, riutilizzabilità

> Consolida i reperti di 02–12. Ogni voce è ancorata a codice reale.

## Parte 1 — Debito tecnico (per gravità)

### 🔴 Bloccanti (impediscono il funzionamento)

| # | Debito | Evidenza |
|---|---|---|
| B1 | **`CatalogImportService` ha un bug fatale**: usa `Log::` e `Str::uuid()` senza importarli → `Class not found` a runtime sui percorsi di errore e di generazione item-group | `CatalogImportService.php:57,170` (manca `use` a righe 5-9) |
| B2 | **L'import non crea prodotti Bagisto**: scrive solo la tabella di staging `iwexa_products`; nessun service tocca `ProductRepository`/inventory Bagisto | tutti i Services (grep) |
| B3 | **Effetto stock stub**: `markInStock`/`markOutOfStock` solo log; lo stock non raggiunge Bagisto | `StockUpdateService.php:57-71` |
| B4 | **Webhook product-update stub**: `handleCatalogProductUpdated` logga e ritorna true | `WebhookProcessorService.php:50` |
| B5 | **Client uscita = codice morto e direzione errata**: `catalogBatch/updateProduct/updateStock` mai invocati e push-verso-Hub; nessun metodo di lettura | `IwexaApiService.php` |
| B6 | **Nessun flusso ordini** (il cuore del contratto) | assente in tutto il repo |

### 🟠 Gravi (funzionano male / rischi reali)

| # | Debito | Evidenza |
|---|---|---|
| G1 | **Bug dedup webhook `delivery_id`**: header letto ma non inoltrato; dedup su body; vincolo `NOT NULL UNIQUE` → "secondo webhook fallisce" | `WebhookController.php:30,43`; migration 000004 |
| G2 | **Manca il Bearer** in ingresso (contratto+README lo dichiarano) | `VerifyIwexaSignature.php` |
| G3 | **Admin senza ACL né menu** | `admin.php`, view Blade |
| G4 | **Elaborazione batch sincrona** nel thread HTTP (perf/DoS) | `CatalogController.php:45`, `StockController.php:44` |
| G5 | **Nessuno scheduler**: `ReleaseExpiredIdempotencyKeys` mai eseguito → tabella webhook cresce all'infinito | grep repo, nessun Kernel/schedule |
| G6 | **Integrità referenziale quasi assente** (3 FK totali; vendor_code/sku/warehouse_code stringhe libere) | 14 migration |

### 🟡 Medi (qualità / coerenza / codice morto)

| # | Debito | Evidenza |
|---|---|---|
| M1 | **Eventi peso morto**: 5 eventi mai dispatchati né ascoltati | `Events/*`, provider |
| M2 | **Job morti**: `ProcessWebhook`, `ReleaseExpiredIdempotencyKeys` orfani | `Jobs/*` |
| M3 | **`ReleaseExpiredIdempotencyKeys` mismatch nome/effetto**: cancella `webhook_events`, non idempotency key; legge la config key sbagliata | `ReleaseExpiredIdempotencyKeys.php:16-18` |
| M4 | **Traduzioni morte**: `loadTranslationsFrom` punta a `Resources/lang/` inesistente | provider :93 |
| M5 | **`WebhookRequest` non agganciato**: body webhook non validato dal framework | `WebhookController` usa `Request` generico |
| M6 | **Convenzioni di risposta/errore incoerenti** fra controller | vedi [04-API.md](04-API.md) |
| M7 | **Indici duplicati / `->indexed()` non-standard** | migration 000001-000005 |
| M8 | **Duplicazione di concetti** (value_mapping JSON vs tabella; google_product_category su 2 tabelle; tempi prep/consegna su 3 tabelle) | vedi [03-Database.md](03-Database.md) |
| M9 | **README del package fuorviante**: dichiara Bearer, "8 migrations" (sono 14), "3 API controller" (sono 9), architettura push | `README.md` |
| M10 | **Mismatch di campo** stock webhook: handler legge `available_quantity`, service legge `quantity` | `WebhookProcessorService.php:66` vs `StockUpdateService.php:21` |
| M11 | **`approveMapping` == `publishMapping`** (identici); `CategoryMappingRepository::findDrafts` usa `where('status','inactive')` | `ProductTypeMappingService.php:39-45`; `CategoryMappingRepository.php:26` |
| M12 | **Import inutilizzato / dipendenze morte** (es. `IwexaSyncJob` in CatalogImportService; `catalogImportService` iniettato e non usato in WebhookProcessor) | vari service |

### 🟢 Minori
Quantità stock `signed`; `vendors.country` `string(255)`; casts numerici mancanti;
indici di lookup mancanti; `dev@paypoc.com` placeholder in composer; migration vendors
condizionale.

## Parte 2 — Qualità del codice e voto (§16)

| Dimensione | Valutazione |
|---|---|
| **Modularità** | Buona *forma* (layering, separazione strati), ma strati inerti (eventi/job) e servizi con responsabilità non onorate. **6/10** |
| **Leggibilità** | Discreta: naming chiaro, metodi corti, stile coerente per file. Alcune classi quasi vuote. **7/10** |
| **Manutenibilità** | Bassa: nessun collegamento end-to-end, molte parti morte, integrità nel codice non nel DB, doc fuorviante. **4/10** |
| **Correttezza** | Bassa: un bug fatale, effetti chiave stub, dedup rotta, direzione errata. **3/10** |
| **Test** | Molto bassa: 1 file, solo il middleware; servizi/import/webhook a copertura **zero**. **2/10** |
| **Sicurezza** | Nucleo HMAC solido; contorno lacunoso (no Bearer, no ACL, input non validato). **5/10** |

### Voto complessivo: **4 / 10**

Motivazione: il codice è **ordinato e ben impaginato** — non è "scritto male" nel senso
stilistico — ma **non funziona end-to-end**, implementa l'**architettura sbagliata**
(per ammissione del progetto stesso), ha un **bug fatale** sul percorso principale,
componenti chiave **stub**, un **intero strato (eventi) inerte**, e **quasi nessun
test**. Come base di partenza per una riscrittura è utile; come sistema è pre-funzionale.

> Un dettaglio che pesa nella due diligence: il modulo **non era mai stato eseguito** —
> il ricevitore webhook rispondeva 500 al 100% delle chiamate fino a pochi giorni fa, e
> nessuno se n'era accorto. Insieme al README che sovradichiara, indica un processo di
> sviluppo **senza collaudo di integrazione**.

## Parte 3 — Riutilizzabilità per modulo (§21)

| Modulo | Verdetto | Motivazione |
|---|---|---|
| Middleware HMAC + test | **MANTENERE** | Solido, testato, riusabile così com'è (aggiungendo il Bearer) |
| Mock Hub + suite conformità (tools) | **MANTENERE** | L'asset più prezioso: specifica eseguibile + criterio di accettazione |
| `AmazonMapper` (tools) | **MANTENERE** (estendere) | Trasformazione deterministica corretta; mappa categoria da ampliare |
| Prototipo import Bagisto (tools) | **MANTENERE** come riferimento | Unico ponte reale verso Bagisto; è un prototipo, non produzione |
| Model/migration sync-job + webhook-event + relativi repo | **MANTENERE** (con fix) | Tracciamento/dedup validi; correggere delivery_id e cleanup |
| `SyncJobController` + view | **MANTENERE** | Unica admin UI coerente e utile |
| `RoutingService` + shipping/routing | **RIFATTORIZZARE o ELIMINARE** | Logica buona ma dominio di IWEX per il piano di riscrittura |
| Warehouse / WarehouseStock (model/service) | **RIFATTORIZZARE o ELIMINARE** | Dominio IWEX; stock non collegato a Bagisto |
| Sottosistema mapping (category/product-type/attribute) | **RIFATTORIZZARE (ridurre)** | Tassonomia arriva normalizzata dall'Hub: serve molto meno |
| `CatalogImportService` / `StockUpdateService` | **RIFATTORIZZARE** | Direzione/collegamento Bagisto da rifare; oggi rotti/stub |
| Controller API inbound (Catalog/Stock/Vendor/Warehouse/Shipping/Routing) | **ELIMINARE** | Superficie di scrittura vietata dal contratto |
| `IwexaApiService` (client uscita) | **SOSTITUIRE** | Va riscritto come client di **lettura** del catalogo Hub |
| Eventi (5) | **ELIMINARE o COLLEGARE** | Oggi inerti; o si agganciano listener o si rimuovono |
| Job `ProcessWebhook`, `ReleaseExpiredIdempotencyKeys` | **ELIMINARE o COLLEGARE** | Codice morto |
</content>
