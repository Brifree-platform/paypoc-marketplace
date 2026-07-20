# Riconciliazione del contratto Iwexa ↔ PayPoc

> **Data:** 2026-07-20
> **Stato:** ✅ **decise tutte e sei** — recepite nel contratto v3.1 e nel mock
> **Origine:** lettura completa dell'handover (`~/Desktop/PayPoc_Bagisto_Handover/`)

## Decisioni prese

| # | Questione | Decisione |
|---|---|---|
| 1 | Prezzi e IVA per paese | **Valore singolo risolto per `?country=`** — ogni paese ha la propria aliquota |
| 2 | Chiave d'identità prodotto | **EAN** — lo slug resta per gli URL, l'ASIN non è la chiave |
| 3 | Iwexa visibile al cliente | **No** — magazzino `PAYPOC-CENTRAL`, tracking su dominio PayPoc |
| 4 | Autenticazione | **Bearer + HMAC + finestra anti-replay** |
| 5 | Contenuto multi-lingua | **Sì** — risolto per `?locale=` |
| 6 | Sync incrementale | **Sì** — `?updated_since=` su `/products` |

Recepite in:
- **[iwexa_hub_openapi_v3.1.yaml](iwexa_hub_openapi_v3.1.yaml)** — il contratto da consegnare al programmatore
- **[tools/mock-hub](../tools/mock-hub/)** — implementazione eseguibile, 63 controlli di conformità

Aggiunti inoltre al contratto due oggetti emersi dai payload reali e assenti da
entrambe le specifiche precedenti: **`hazmat`** (merci pericolose — il profumo
d'esempio è UN1266, infiammabile) e **`compliance`** (dati GPSR obbligatori per
legge sui cosmetici in UE).

Il resto del documento conserva l'analisi che ha portato a queste decisioni.

---

---

## Il problema

Esistono **tre** descrizioni dell'integrazione Iwexa ↔ PayPoc, e non coincidono.

| # | Fonte | Data | Direzione | Stato |
|---|---|---|---|---|
| 1 | `PayPoc_Spec_IT.pdf` §6 | Aprile 2026, v1.0 | pull | descrive endpoint `/v1/...` |
| 2 | `iwexa_hub_openapi.yaml` + `hub-field-contract.md` | 2026-04-29, v3.0 | pull | descrive endpoint diversi |
| 3 | `IwexaConnector` (codice) | 2026-05-01 | **push** | non corrisponde a nessuna delle due |

Sulla **direzione** i documenti 1 e 2 concordano: PayPoc interroga l'Hub. La Spec §6.6
è esplicita — *"cron Bagisto ogni 15 min chiama `GET /products?updated_since=...`"*.
Il codice (3) è quindi semplicemente sbagliato, e la questione è chiusa.

Ma **1 e 2 non concordano fra loro** su quasi ogni dettaglio. Prima di scrivere la
fase 1 serve stabilire quale prevale, perché il modello dati dipende da questo.

---

## Divergenze fra Spec §6 e OpenAPI v3.0

| Aspetto | Spec §6 (v1.0) | OpenAPI + field contract (v3.0) | Impatto |
|---|---|---|---|
| **Identità prodotto** | `gtin` (`GET /v1/products/{gtin}`) | `productSlug` + `externalProductId` | Alto — è la chiave primaria |
| **Prezzo** | `list_price` **per paese**: `{IT: 6.99, FR: 7.49, DE: 7.29}` | `listPrice` numero singolo | **Molto alto** |
| **IVA** | `vat_rate` **per paese**: `{IT: 0.22, FR: 0.20}` | `vatRate` numero singolo | **Molto alto** |
| **Lingue** | `title`, `bullets`, `description` multi-lingua | stringhe singole | Alto — servono 27 paesi |
| **Sync incrementale** | `?updated_since=ISO8601` | assente | Alto — senza, si risincronizza tutto |
| **Contenuto A+** | `a_plus_content` strutturato (Hero, Benefits, HowToUse, Ingredients, Routine) | assente | Medio — il prototipo ha i componenti |
| **Dogana** | `hs_code` | assente | Medio — serve per spedizioni extra-UE |
| **Resi** | `POST /v1/returns` | `POST /orders/{id}/return` | Basso |
| **Vendor** | `GET /v1/vendors/{code}` | `GET /vendors` (lista) | Basso |
| **Tassonomia** | `category` generica | Google Taxonomy obbligatoria | Alto |
| **Autenticazione** | **Bearer token + HMAC su body** | solo HMAC | Alto — vedi sotto |
| **Webhook** | 10 eventi, notazione `product.updated` | 3 eventi, notazione `productUpdated` | Alto |
| **Endpoint webhook** | `POST /api/hub/webhook` | non specificato | Basso |

### Perché il prezzo per paese è il punto più serio

La Spec §0.5 impone *"IVA EU: gestione aliquote per paese di consegna"* e §1.1
*"ogni prodotto disponibile in tutti i 27 paesi EU, nessun blocco geografico"*.

Con un `listPrice` e un `vatRate` singoli — come nell'OpenAPI — questo è
**impossibile da rappresentare**. Anche assumendo prezzo unico in euro, l'IVA
cambia per forza da paese a paese (22% IT, 20% FR, 19% DE).

L'OpenAPI compensa in parte con `GET /products?country=IT`: l'Hub restituisce i
valori per il paese richiesto. Funziona, ma significa **una copia del catalogo per
paese** invece di un catalogo con prezzi per paese — 27 chiamate invece di 1, e
una decisione di caching completamente diversa.

**Va deciso esplicitamente**, non lasciato all'implementazione.

---

## Contraddizione da risolvere: Iwexa è visibile al cliente?

Le due risposte sono incompatibili.

**Iwexa NON deve essere visibile** — tre fonti concordi:

- Spec §0.3: *"Hub Iwexa: orchestratore B2B **invisibile al cliente**"*
- Spec §1.2: *"Hub Iwexa: orchestratore tecnico B2B. **Mai esposto al cliente**"*
- Spec §0.3 + §1.3: *"FBI: magazzino centrale, **brandizzato 'magazzino PayPoc'** al cliente"*
- Audit §8 (regole UI vincolanti): *"Magazzino centrale brandizzato 'magazzino PayPoc' (FBI). Iwexa orchestratore, mai visibile all'utente"*

**Ma il field contract espone Iwexa al cliente**, in due punti:

- §"Shipping label copy (exact, used in PDP, cart, checkout)":
  `Spedizione €4,50 — Gratis sopra €39 **da magazzino Iwexa**`
- §Order Schema: `trackingUrl` — *"**ALWAYS Iwexa/ShippyPro domain**"*

Il primo è copy mostrato al cliente su pagina prodotto, carrello e checkout: viola
direttamente la regola. Il secondo è un link su cui il cliente clicca per il tracking.

**Decisione richiesta:** se vale la regola "Iwexa invisibile", il copy del field
contract va corretto in *"da magazzino PayPoc"* e il dominio di tracking va
mascherato dietro PayPoc. Non è un dettaglio estetico — è una scelta di brand con
implicazioni contrattuali verso i vendor.

---

## Un chiarimento che assolve il codice esistente

CLAUDE.md dichiarava *"Bearer token per l'autenticazione API"*, e in sede di analisi
l'ho classificato come funzionalità documentata ma mai implementata.

La Spec §6.2 dice: *"Autenticazione: **Bearer token (API key) + HMAC signature su
body**"* — entrambi. Quindi l'affermazione non era inventata: veniva da qui.
Restava comunque non implementata in ingresso, ma l'origine è legittima.

**Conseguenza pratica:** se la Spec §6.2 prevale, il middleware HMAC che ho scritto
va affiancato da una verifica del Bearer token, non sostituito.

---

## Cosa assumono oggi il mock e il mapper

Sono costruiti sull'**OpenAPI v3.0** (fonte 2). Se la riconciliazione premia la
Spec §6, cambia:

| Componente | Cosa cambierebbe |
|---|---|
| `data/products.json` | `listPrice`/`vatRate` diventano oggetti per paese |
| `src/Hub.php` | `/products/{slug}` → `/products/{gtin}`; aggiunta `?updated_since` |
| `bin/conformance.php` | asserzioni su prezzo per paese e sync incrementale |
| `AmazonMapper` | `externalProductId` da ASIN a GTIN (l'EAN c'è già: `8059777990122`) |
| Middleware HMAC | affiancare la verifica Bearer |

Nessuna di queste è una riscrittura: sono modifiche circoscritte. Ma vanno fatte
**prima** della fase 1, non dopo, perché il modello dati Bagisto ci poggia sopra.

---

## Note tecniche minori emerse

- **Laravel**: la Spec §0.4 indica Laravel 10 / PHP 8.1+. L'installazione reale è
  **Laravel 12.56 / PHP 8.3**. Non è un problema, ma il `composer.json` del
  connettore dichiara `"bagisto/bagisto": "*"`: va fissato alla versione reale.
- **Redis**: richiesto dalla Spec §0.4 per cache, sessioni, quote shipping e code.
  **Non è installato** sulla macchina di sviluppo.
- **Valuta**: Bagisto è configurato in **USD**; il contratto ammette solo `EUR`.
- **Locale**: Bagisto ha solo `en`; il contenuto è italiano e servono 27 paesi.
- **hazmat e GPSR**: nessuna delle due specifiche prevede campi per i dati hazmat
  (UN1266, classe di trasporto) né per ingredienti/avvertenze/contatto produttore,
  entrambi presenti nel payload Amazon reale e obbligatori per legge sui cosmetici UE.

---

## Decisioni richieste, in ordine di impatto

1. **Prezzi e IVA per paese**: oggetto per-paese (Spec) o valore singolo con
   parametro `?country=` (OpenAPI)? Determina il modello dati.
2. **Chiave d'identità del prodotto**: `gtin`/EAN o `externalProductId`/ASIN?
   Il payload Amazon offre entrambi.
3. **Iwexa visibile o no** nel copy di spedizione e nel dominio di tracking?
4. **Autenticazione**: solo HMAC, o Bearer + HMAC come da Spec §6.2?
5. **Contenuto multi-lingua**: quando serve? Se il lancio è solo IT, si può
   rimandare — ma il modello dati va predisposto ora.
6. **Sync incrementale** (`updated_since`): senza, ogni sincronizzazione rilegge
   l'intero catalogo. Da aggiungere al contratto.

Finché queste restano aperte, ogni riga scritta rischia di essere riscritta — che è
esattamente il modo in cui è nato il connettore attuale.
