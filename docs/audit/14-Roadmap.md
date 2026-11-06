# 14 — Roadmap

> **Vincolo di metodo:** questa roadmap è **derivata dallo stato del codice esistente**
> (i reperti di 02–13), non da un catalogo di funzionalità desiderate. Non progetta
> soluzioni: **ordina per priorità gli interventi che il codice attuale richiede** e
> segnala dove interi domini sono **assenti** (green-field). Le scelte di prodotto (es.
> quanto riusare vs riscrivere) restano del committente e del Software Architect.

## Il bivio a monte di tutto

Il codice implementa la direzione **opposta** al contratto v3.1 (push in ingresso vs
pull in lettura). Il progetto stesso ha già prodotto un `PIANO-RISCRITTURA`. Quindi la
**decisione zero**, da cui dipende tutta la roadmap, è:

> **Confermare che il connettore va riscritto verso l'architettura di lettura del
> contratto v3.1, tenendo solo l'impalcatura riutilizzabile.** Finché questa decisione
> non è esplicita, ogni intervento sotto rischia di essere lavoro su codice destinato
> alla rimozione.

## Indispensabile

Interventi senza i quali *nessuna* parte del sistema è utilizzabile.

1. **Fissare la direzione dell'integrazione** (decisione zero sopra) e la versione
   Bagisto (`composer.json` oggi `*`).
2. **Client di lettura del catalogo Hub**: oggi `IwexaApiService` è morto e sa solo
   scrivere. Serve il percorso `GET /products` (+ stock/vendors/taxonomy/shipping-quote)
   — è la funzione base mancante.
3. **Ponte staging → catalogo Bagisto**: oggi l'import non crea prodotti reali (B2). Va
   collegato (o si adotta il percorso del prototipo `bagisto-import`).
4. **Flusso ordini** (`POST /orders`, stato, cancel, return): il cuore del contratto,
   **interamente assente**.
5. **Correggere i bloccanti se si tiene il codice attuale**: bug fatale
   `CatalogImportService` (B1), bug dedup webhook `delivery_id` (G1).
6. **Copertura test oltre il middleware**: import, webhook, routing sono a zero.

## Consigliato

Interventi che alzano affidabilità e sicurezza di ciò che si tiene.

1. **Autenticazione doppia**: aggiungere il Bearer al middleware (G2), come da contratto.
2. **ACL e menu admin** per le pagine operatore (G3).
3. **Integrità DB**: introdurre le FK mancanti su `vendor_code`/`sku`/`warehouse_code`,
   rimuovere indici duplicati, uniformare i tipi (G6, M7).
4. **Pulizia del codice morto**: agganciare o rimuovere i 5 eventi e i 2 job orfani
   (M1, M2); correggere `ReleaseExpiredIdempotencyKeys` e schedularlo (G5, M3).
5. **Asincronia reale sul percorso d'ingresso** (o suo equivalente pull): togliere
   l'elaborazione batch sincrona (G4).
6. **Allineare il README del package** al codice (M9) — oggi induce in errore.
7. **Uniformare convenzioni di risposta/errore** e agganciare `WebhookRequest` (M5, M6).

## Futuro (domini green-field, oggi assenti)

Non sono "prossimi passi" del codice esistente: sono **domini che non esistono** e che
il prodotto prevede. Vanno pianificati a parte, non sono stimabili da questo repo.

- **Storefront & Multi-Vendor operativo**: tema, PDP, Vendor Store (vetrine),
  carrello/checkout, onboarding, dashboard, permessi — vedi [07-MultiVendor.md](07-MultiVendor.md).
- **Motore crediti / wallet / bonus** (il differenziatore PayPoc): il dato
  `maxApplicableValue` arriva dal contratto, il motore **non esiste**.
- **Pagamenti** (card/paypal/klarna/applepay/googlepay sono solo un enum nel contratto).
- **Google Commerce** (Merchant Center, feed, Shopping) — vedi [09-Google.md](09-Google.md).
- **AI / traduzioni automatiche** — vedi [10-AI.md](10-AI.md).
- **Altri marketplace** (eBay, Kaufland, Cdiscount) e **ERP** — assenti.
- **L'Hub Iwexa reale** — esiste solo come contratto + mock; è un progetto a sé.

## Sequenza logica (dedotta, non prescrittiva)

```
[0] Confermare direzione + versione Bagisto
        │
[1] Impalcatura da tenere: middleware(+Bearer), mock+suite, sync-job/webhook tracking
        │
[2] Client di LETTURA Hub  ──►  [3] Ponte verso catalogo Bagisto  ──►  storefront
        │
[4] Flusso ORDINI (con test)  ──►  webhook stato/tracking
        │
[5] Domini green-field (crediti, pagamenti, Google, multi-vendor UI…)
```

Il valore già capitalizzato (contratto v3.1 + mock + suite di conformità) rende i passi
[2]–[4] **verificabili** contro un bersaglio stabile: è il vero asset di questa roadmap.
</content>
