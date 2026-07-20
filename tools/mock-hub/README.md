# Mock Iwexa Hub

Implementazione eseguibile del contratto **Iwexa ↔ PayPoc v3.1**
([`docs/iwexa_hub_openapi_v3.1.yaml`](../../docs/iwexa_hub_openapi_v3.1.yaml)).

Serve a due scopi distinti:

1. **Per PayPoc** — sviluppare il connettore contro qualcosa di reale invece che
   contro un'ipotesi. Ogni discrepanza rispetto al contratto emerge subito.
2. **Per chi costruisce l'Hub vero** — è il criterio di accettazione. Non
   "leggi lo YAML e fidati", ma "il tuo Hub deve superare questa suite".

Nessuna dipendenza: solo PHP 8.1+.

---

## Avvio

```bash
cd tools/mock-hub
php -d opcache.enable=0 -S 127.0.0.1:8800 -t public
```

> **Perché `opcache.enable=0`.** Con opcache attiva (`revalidate_freq=2` di default)
> il server può servire per qualche secondo il bytecode precedente dopo una modifica
> ai file. In sviluppo questo rende i risultati dipendenti dal tempo: una modifica
> sembra non avere effetto, e poco dopo ce l'ha. Disattivarla rende il comportamento
> deterministico. Verificato: la stessa mutazione risultava rilevata o non rilevata
> a seconda dell'istante in cui girava la suite.

Base URL: `http://127.0.0.1:8800/api/v1`

Credenziali di sviluppo (override con variabili d'ambiente):

| Variabile | Default |
|---|---|
| `IWEXA_API_KEY` | `mock-hub-api-key` |
| `IWEXA_HMAC_SECRET` | `mock-hub-shared-secret` |

Per provare gli endpoint a mano senza firmare, **solo in sviluppo**:

```bash
MOCK_HUB_ALLOW_UNSIGNED=1 php -d opcache.enable=0 -S 127.0.0.1:8800 -t public
```

---

## Suite di conformità

```bash
php bin/conformance.php                                       # contro il mock
php bin/conformance.php https://hub-staging.iwexa.../api/v1   # contro l'Hub vero
```

**63 controlli** su autenticazione, identità EAN, prezzi e IVA per paese,
multi-lingua, sync incrementale, invisibilità di Iwexa, dati regolatori,
stock, vendor, tassonomia, spedizioni e ordini. Esce con codice 1 se anche
uno solo fallisce.

**Questa suite è il contratto di consegna dell'Hub.** Un Hub che non la supera
non è integrabile, a prescindere da quanto sembri corretto leggendone il codice.

---

## Le sei decisioni recepite

Deliberate il 2026-07-20, dopo la riconciliazione fra `PayPoc_Spec_IT.pdf` §6 e
l'OpenAPI 3.0 (vedi [RICONCILIAZIONE-CONTRATTO.md](../../docs/RICONCILIAZIONE-CONTRATTO.md)).

| # | Decisione | Come si vede nel mock |
|---|---|---|
| 1 | Prezzi e IVA: valore singolo risolto per paese | `?country=IT\|FR\|DE` cambia `listPrice`, `sellPrice`, `vatRate`, `shippingPolicy` |
| 2 | Identità prodotto: **EAN** | `externalProductId` è un EAN; `/products/{ean}` e `/products/{slug}` funzionano entrambi |
| 3 | **Iwexa invisibile al cliente** | magazzino FBI = `PAYPOC-CENTRAL`, `trackingUrl` su dominio PayPoc, nessuna stringa "iwexa" nei payload |
| 4 | Autenticazione più forte possibile | Bearer **+** HMAC **+** finestra anti-replay di 300s |
| 5 | Contenuto multi-lingua | `?locale=it\|en` cambia nome, descrizione, bullet e `localizedPath` |
| 6 | Sync incrementale | `?updated_since=ISO8601` su `/products` |

---

## Autenticazione

Ogni richiesta richiede **entrambi** gli schemi:

```
Authorization:      Bearer <IWEXA_API_KEY>
X-Iwexa-Signature:  hash_hmac('sha256', body + timestamp, IWEXA_HMAC_SECRET)
X-Iwexa-Timestamp:  <unix seconds, entro ±300s>
```

Il Bearer identifica il chiamante, l'HMAC garantisce che il corpo non sia stato
alterato, il timestamp impedisce che una richiesta catturata resti riutilizzabile
per sempre. Sulle richieste senza body si firma la stringa vuota + timestamp.

Esempio di verifica:
```bash
printf '%s' "${body}${timestamp}" | openssl dgst -sha256 -hmac "$SECRET"
```

---

## Webhook Hub → PayPoc

```bash
php bin/send-webhook.php productUpdated
php bin/send-webhook.php stockChanged
php bin/send-webhook.php orderStatusChanged
```

Invia l'evento firmato a `http://127.0.0.1:8899/bagisto-api/iwexa/webhooks`
(secondo argomento per cambiare destinazione).

---

## Mapper Amazon → contratto

```bash
php bin/map-amazon.php                    # analisi leggibile
php bin/map-amazon.php payload.json --json  # solo il Product, per pipe
```

Traduce un item **Amazon SP-API Catalog Items** nello schema `Product` e mostra
cosa manca. Sul payload di esempio: **14 campi su 23** sono ricavabili da Amazon.
I 9 mancanti sono tutti commerciali o logistici — `vendorCode`, `sellPrice`,
`inStock`, `fulfillment`, `shippingPolicy`, `vatRate` — cioè dati di Iwexa.

**Amazon sta a monte dell'Hub, non è l'Hub.**

---

## Endpoint implementati

| Metodo | Percorso | Parametri |
|---|---|---|
| GET | `/products` | `country`, `locale`, `updated_since`, `vendorCode`, `googleTaxonomyId`, `page`, `pageSize` |
| GET | `/products/{ean\|slug}` | `country`, `locale` |
| GET | `/stock/{ean}` | — |
| GET | `/vendors` | — |
| GET | `/shipping-quote` | `country`, `cart` |
| GET | `/taxonomy` | `locale` |
| POST | `/orders` | 201 · 409 stock conflict · 422 validazione |
| GET | `/orders/{id}` | — |
| POST | `/orders/{id}/cancel` | ripristina lo stock |
| POST | `/orders/{id}/return` | apre un reso |

---

## Regole del contratto implementate

Non sono invenzioni del mock: derivano da `hub-field-contract.md` v3.0 e dalle
decisioni di riconciliazione. L'Hub vero deve replicarle.

- **Soglia spedizione gratuita per pacchetto, non per ordine.** Ogni vendor è
  un pacchetto a sé; il subtotale che attiva la soglia è quello del pacchetto.
- **`alwaysFree: true` ignora costo e soglia** e non concorre al conteggio degli altri.
- **Split ordine per `vendorCode`**: una spedizione per vendor, con `type` FBI/FBV.
- **`listPrice ≥ sellPrice`**, **`maxApplicableValue = 40% di listPrice`**.
- **Stock scritto solo dall'Hub**: PayPoc non ha alcun endpoint per modificarlo.
- **409 su conflitto di stock**, 422 su payload incompleto o paese non gestito.
- **Nessun riferimento a Iwexa** nei dati che raggiungono il cliente.

---

## Dati di test

Le fixture in `data/` sono **sintetiche**, inclusi gli EAN, con l'eccezione del
profumo `8059777990122`, che viene da un payload Amazon reale.

Copertura: FBI con soglia, FBV `alwaysFree`, prodotto esaurito, prodotto con
hazmat (UN1266 infiammabile) e batteria al litio (UN3481), tre paesi (IT, FR, DE)
con IVA e costi di spedizione diversi, due lingue (it, en).

---

## Limiti noti

- Dati in memoria da `data/*.json`; gli ordini si persistono in `runtime/` come file
- Lo stock **non** viene decrementato davvero dopo un ordine (il contratto dice che
  l'Hub lo fa: il mock lo dichiara nella risposta ma non muta le fixture)
- Tre paesi su 27 nelle fixture: un paese non coperto restituisce 422, che è il
  comportamento corretto ma va esteso prima del lancio
- Nessuna paginazione reale oltre `page`/`pageSize` in memoria
