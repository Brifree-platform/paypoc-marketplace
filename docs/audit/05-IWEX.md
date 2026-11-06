# 05 — IWEX (Iwexa Hub)

> Fonte: `docs/iwexa_hub_openapi_v3.1.yaml`, `tools/mock-hub/`, e il lato connettore
> (`IwexaApiService`, tabelle di staging `iwexa_*`).

## Avvertenza fondamentale

> **L'Hub Iwexa reale NON esiste.** Non c'è alcun software dell'Hub nel repository né
> in produzione: i server nel contratto sono domini segnaposto (`hub.iwexa.example`).
> Ciò che esiste è: (1) un **contratto** che descrive cosa l'Hub dovrà fare, (2) un
> **mock eseguibile** che lo implementa a scopo di sviluppo/collaudo, (3) sul lato
> PayPoc, **tabelle di staging** e un **client in uscita che è codice morto**.

Quindi questo documento descrive IWEX su due piani, sempre distinti: **CONTRATTO/MOCK**
(cosa dovrà essere) e **CODICE CONNETTORE** (cosa PayPoc fa realmente oggi).

## Cosa rappresenta IWEX

Nel disegno del progetto IWEX è il **master e orchestratore B2B**, invisibile al
cliente finale. Possiede: catalogo, prezzi, IVA, stock, codici Vendor, magazzino
centrale, routing degli ordini e integrazione con i corrieri. PayPoc è in **sola
lettura** sui dati prodotto e in **scrittura solo** sugli ordini.

## Dati gestiti da IWEX (da contratto)

Prodotto risolto per paese e lingua: identità **EAN**, `productSlug`, `vendorCode`/
`vendorName`, prezzi (`listPrice`/`sellPrice`/`vatRate`/`maxApplicableValue`), tassonomia
Google localizzata, `fulfillment` (FBI/FBV, `warehouseCode`, tempi), `shippingPolicy`
per paese, `hazmat`, `compliance` (GPSR), immagini su dominio PayPoc, stock.

## API che IWEX espone (contratto v3.1 / mock)

| Metodo | Percorso | Funzione | Nel mock |
|---|---|---|---|
| GET | `/products` | Catalogo risolto per `?country=`/`?locale=`/`?updated_since=` | ✅ |
| GET | `/products/{ean\|slug}` | Dettaglio per EAN o slug | ✅ |
| GET | `/stock/{ean}` | Disponibilità real-time | ✅ |
| GET | `/vendors` | Anagrafica Vendor | ✅ |
| GET | `/shipping-quote` | Costo spedizione per pacchetto | ✅ |
| GET | `/taxonomy` | Google Product Taxonomy localizzata | ✅ |
| POST | `/orders` | Crea ordine, split per Vendor, routing FBI/FBV | ✅ |
| GET | `/orders/{id}` | Stato ordine con spedizioni | ✅ |
| POST | `/orders/{id}/cancel` | Annulla, ripristina stock | ✅ |
| POST | `/orders/{id}/return` | Apre reso | ✅ |
| webhook | `productUpdated` / `stockChanged` / `orderStatusChanged` | Push Hub→PayPoc | firma HMAC |

Il mock (`tools/mock-hub/`, PHP puro senza dipendenze) implementa tutti questi
endpoint con autenticazione **Bearer + HMAC + finestra anti-replay** e supera una
**suite di conformità di 63 controlli** (`bin/conformance.php`). Il mock è, di fatto,
la specifica eseguibile e il criterio di accettazione per l'Hub reale.

**Limiti dichiarati del mock** (dal suo README): dati in memoria da `data/*.json`,
ordini persistiti come file in `runtime/`; **lo stock non viene realmente decrementato**
dopo un ordine (la risposta lo dichiara ma le fixture non mutano); solo **3 paesi su 27**
(IT/FR/DE); nessuna paginazione reale oltre `page`/`pageSize` in memoria.

## Cosa fa davvero il CONNETTORE verso IWEX (codice)

Molto poco, e nella direzione sbagliata:

- **In uscita**: `IwexaApiService` è l'unico client. Espone `catalogBatch`,
  `updateProduct`, `updateStock` — tutte **scritture push verso l'Hub** — e sono
  **codice morto** (mai invocate). **Non esiste alcun metodo di lettura del catalogo.**
  PayPoc, con questo codice, **non sa interrogare l'Hub**.
- **In ingresso**: gli endpoint `bagisto-api/iwexa/*` ricevono push dall'Hub e i
  webhook. Ma l'handler `productUpdated` è uno **stub** (solo log), l'effetto stock
  è uno **stub**, e l'import non crea prodotti Bagisto reali.

Il risultato netto: l'integrazione Hub↔PayPoc **non è funzionante in nessuna delle due
direzioni** allo stato del codice.

## Routing degli ordini (§11)

- **Nel contratto/mock**: `POST /orders` all'Hub, che **splitta per `vendorCode`**,
  instrada ogni pacchetto FBI (magazzino `PAYPOC-CENTRAL`) o FBV (`VENDOR-{code}`),
  calcola spedizione per pacchetto e stato consolidato. Il routing "vero" è
  responsabilità dell'Hub.
- **Nel connettore**: esiste un `RoutingService` + tabella `routing_quotes` che
  calcola un preventivo scegliendo il magazzino disponibile con **costo minore →
  consegna più rapida → magazzino centrale prima** (`selectBestCandidate`). È la
  logica di business più completa del package, ma:
  - è alimentata solo dai dati di staging inbound (warehouse_stocks, shipping_rates)
  - è **inbound/compute**, un dominio che secondo il piano di riscrittura
    **appartiene interamente a IWEX** e va rimosso da PayPoc.
  - non è collegata ad alcun ordine reale (gli ordini non esistono nel connettore).

## Gestione stock (§12)

- **Regola di contratto**: lo stock lo scrive **solo l'Hub**. PayPoc non ha (e non
  deve avere) endpoint per modificarlo. All'arrivo di un ordine l'Hub decrementa;
  alla cancellazione ripristina.
- **Codice connettore**: ricezione stock via `POST /catalog/stock` →
  `StockUpdateService` che scrive **solo** un log in `iwexa_stock_logs`. I metodi
  `markInStock`/`markOutOfStock` sono **stub** (solo log): **lo stock non raggiunge
  mai l'inventario Bagisto**. Il webhook `stockChanged` passa per un handler che
  legge `available_quantity` mentre il service legge `quantity` (mismatch di campo).
- **Verso Google/marketplace esterni**: nessuna propagazione (vedi [09-Google.md](09-Google.md)).

## Flussi dati (§13)

Flusso **previsto** (contratto) vs **implementato** (codice):

```
PREVISTO (contratto v3.1):

  Produttore/Amazon ──► IWEX (master) ──► [GET /products]  ──► PayPoc (legge, popola Bagisto)
                                     ├──► [webhook push]    ──► PayPoc (aggiorna)
  PayPoc (checkout) ──► [POST /orders] ──► IWEX ──► split per Vendor, routing FBI/FBV
  IWEX ──► [webhook orderStatusChanged] ──► PayPoc (tracking)


IMPLEMENTATO (codice connettore, oggi):

  IWEX ──► [POST /catalog/*, /vendors, /warehouses ...] ──► PayPoc staging tables
                                                             (import prodotti = solo staging;
                                                              stock = solo log; webhook
                                                              productUpdated = stub)
  PayPoc ──► IwexaApiService (push verso Hub) ──► ✗ codice morto, mai invocato
  Ordini ──► ✗ inesistenti
```

La direzione del codice è **opposta** a quella del contratto, ed è documentata come
tale nel piano di riscrittura del progetto.

## Sintesi IWEX

IWEX è ben **specificato** (contratto v3.1) e ben **simulato** (mock + 63 controlli),
ma **non esiste come software** e il connettore che dovrebbe dialogarci implementa la
direzione sbagliata con i pezzi chiave stub o morti. Il valore reale, oggi, è il
**contratto e il mock**, non il codice di integrazione.
</content>
