# Mock Iwexa Hub

Implementazione eseguibile del contratto **Iwexa ↔ PayPoc v3.0**
(`iwexa_hub_openapi.yaml` + `hub-field-contract.md`).

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

Segreto HMAC condiviso (override con la variabile d'ambiente `IWEXA_HMAC_SECRET`):

```
mock-hub-shared-secret
```

Per provare gli endpoint a mano senza firmare, solo in sviluppo:

```bash
MOCK_HUB_ALLOW_UNSIGNED=1 php -S 127.0.0.1:8800 -t public
```

---

## Suite di conformità

```bash
php bin/conformance.php                                   # contro il mock
php bin/conformance.php https://hub-staging.iwexa.../api/v1   # contro l'Hub vero
```

39 controlli su autenticazione, catalogo, regole di validazione, stock, vendor,
tassonomia, spedizioni e ordini. Esce con codice 1 se anche uno solo fallisce.

**Questa suite è il contratto di consegna dell'Hub.** Un Hub che non la supera
non è integrabile, a prescindere da quanto sembri corretto leggendone il codice.

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

## Endpoint implementati

| Metodo | Percorso | Note |
|---|---|---|
| GET | `/products` | filtri `vendorCode`, `googleTaxonomyId`; paginazione `page`/`pageSize` |
| GET | `/products/{slug}` | 404 se assente |
| GET | `/stock/{externalProductId}` | disponibilità real-time |
| GET | `/vendors` | anagrafica vendor |
| GET | `/shipping-quote?country=&cart=` | costo **per pacchetto** |
| GET | `/taxonomy?locale=` | Google Product Taxonomy localizzata |
| POST | `/orders` | 201 · 409 stock conflict · 422 validazione |
| GET | `/orders/{id}` | stato consolidato con spedizioni |
| POST | `/orders/{id}/cancel` | ripristina lo stock |
| POST | `/orders/{id}/return` | apre un reso |

---

## Regole del contratto implementate

Non sono invenzioni del mock: derivano da `hub-field-contract.md` v3.0, e
l'Hub vero deve replicarle.

- **Soglia spedizione gratuita per pacchetto, non per ordine.** Ogni vendor è
  un pacchetto a sé; il subtotale che attiva la soglia è quello del pacchetto.
- **`alwaysFree: true` ignora la soglia** e non concorre al conteggio degli altri.
- **Split ordine per `vendorCode`**: una spedizione per vendor, con `type` FBI/FBV.
- **`listPrice ≥ sellPrice`** e **`maxApplicableValue ≤ sellPrice`**.
- **`alwaysFree: true` implica `freeShippingThreshold: null`**.
- **Stock scritto solo da Iwexa**: PayPoc non ha alcun endpoint per modificarlo.
- **409 su conflitto di stock**, 422 su payload incompleto.

---

## Scostamento consapevole dalla specifica

`securitySchemes.HmacAuth` nell'OpenAPI descrive la firma sul **solo body**.
Qui si firma **body + timestamp**, con finestra di tolleranza di 300 secondi.

Motivo: senza timestamp una richiesta catturata resta riutilizzabile per sempre.
Dato che l'Hub vero non è ancora stato scritto, questo è il momento giusto per
correggere il contratto invece di ereditarne la debolezza.

**Azione richiesta:** aggiornare `iwexa_hub_openapi.yaml` di conseguenza, oppure
decidere esplicitamente di tornare alla firma sul solo body. Finché la divergenza
resta, va letta come una proposta, non come un fatto compiuto.

Header usati: `X-Iwexa-Signature`, `X-Iwexa-Timestamp` (case-insensitive).
Firma: `hash_hmac('sha256', body . timestamp, secret)`, esadecimale minuscolo.
Sulle richieste senza body si firma la stringa vuota concatenata al timestamp.

---

## Limiti noti

- Dati in memoria da `data/*.json`; gli ordini si persistono in `runtime/` come file
- Lo stock **non** viene decrementato davvero dopo un ordine (il contratto dice che
  l'Hub lo fa: il mock lo dichiara nella risposta ma non muta le fixture)
- Nessuna paginazione reale oltre `page`/`pageSize` in memoria
- Un solo paese di spedizione nelle fixture (`IT`)
