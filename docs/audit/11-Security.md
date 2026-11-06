# 11 — Sicurezza

> Fonte: `src/Http/Middleware/VerifyIwexaSignature.php`, `src/Config/iwexa-connector.php`,
> `src/Routes/api.php`, i 10 form request, `tests/Unit/VerifyIwexaSignatureTest.php`,
> `src/Controllers/Api/WebhookController.php`, `src/Services/IwexaApiService.php`.

## Sintesi

L'unico presidio di sicurezza **realmente implementato e testato** è il middleware
HMAC sulle rotte in ingresso. È fatto bene (fail-closed, tempo costante, finestra
anti-replay, 10 test). Attorno a esso però ci sono **lacune e incoerenze materiali**:
manca il Bearer token che contratto e documentazione dichiarano, l'admin non ha ACL,
il body dei webhook non è validato, e la documentazione dichiara protezioni inesistenti.

## Autenticazione in ingresso — HMAC-SHA256 (implementato)

`VerifyIwexaSignature` protegge tutte le rotte `bagisto-api/iwexa/*`:

- Firma su `body_grezzo + timestamp` con `IWEXA_HMAC_SECRET`, confronto a **tempo
  costante** (`hash_equals`, riga 50).
- **Fail-closed**: secret vuoto → **503**, non lascia passare (righe 28-35). Un `.env`
  incompleto non apre le API.
- **Finestra anti-replay**: timestamp entro ±`IWEXA_SIGNATURE_TOLERANCE` (default 300s),
  in entrambe le direzioni (righe 44-46, 61-70). Timestamp non numerico → rifiutato.
- Header vuoti/mancanti → 401.

**Copertura test:** 10 test Pest sul middleware
([VerifyIwexaSignatureTest.php](../../packages/PAYPOC/IwexaConnector/tests/Unit/VerifyIwexaSignatureTest.php)):
firma valida, mancante, errata, body alterato, replay passato, timestamp futuro,
scarto entro tolleranza, timestamp non numerico, body vuoto, fail-closed senza secret.
È **l'unico file di test dell'intero repository.**

## Vulnerabilità e lacune

### V1 — Manca il Bearer token (contratto e doc lo dichiarano, il codice no) — ALTO
Il contratto v3.1 (decisione 4) impone autenticazione **doppia**: `Authorization:
Bearer` **E** HMAC. Il middleware verifica **solo HMAC**. Il README del package
(sezione Security) dichiara *"Bearer Token: API authentication"* come se fosse attivo:
**non lo è in ingresso**. Chiunque possieda il solo `IWEXA_HMAC_SECRET` può firmare
richieste valide; non c'è un secondo fattore che identifichi il chiamante.
(Il mock Hub, per contro, verifica correttamente Bearer + HMAC — l'asimmetria è solo
lato connettore.)

### V2 — Admin senza ACL — ALTO
Le rotte `admin/iwexa/*` usano solo il middleware `admin` (login operatore). **Nessun
controllo ACL/`@can`**, nessuna registrazione nel sistema permessi Bagisto. Qualsiasi
utente admin autenticato può approvare mapping, modificare categorie, ritentare job —
senza granularità di ruolo. Le view non hanno gate di permesso.

### V3 — Body webhook non validato dal framework — MEDIO
`WebhookRequest` (con regole su `id`, `delivery_id`, `event`, `data`, `timestamp`)
**esiste ma non è agganciato**: il `WebhookController` usa un `Request` generico. Il
payload è validato solo da `json_decode` + accessi difensivi nel service. Campi
mancanti provocano comportamenti indefiniti (vedi V4).

### V4 — Anti-replay/dedup si disattiva su dati incompleti — MEDIO
`WebhookProcessorService::validateIdempotency` restituisce "non duplicato" (→ processa)
se `event_id` **o** `delivery_id` sono assenti. Un attaccante che ometta quei campi
aggira la deduplica. In parallelo il vincolo DB `delivery_id NOT NULL UNIQUE` fa
fallire l'inserimento — il risultato è inconsistente, non sicuro-per-default.

### V5 — Elaborazione sincrona inline = superficie DoS — MEDIO
`importBatch` e `updateStock` elaborano l'intero batch **nel thread HTTP** (i job
asincroni non sono dispatchati). Un batch molto grande (il config ammette fino a 100
prodotti / 500 stock per batch, ma la dimensione non è imposta lato server oltre la
validazione) tiene occupato un worker web. Il `throttle:60,1` limita la frequenza, non
il costo della singola richiesta.

### V6 — Mass assignment ampio — BASSO/MEDIO
I model usano `$fillable` estesi (es. `IwexaProduct`, `IwexaVendor`, `RoutingQuote`) e i
service passano spesso `$request->validated()` o payload interi a `create/update`. Con
validazione presente il rischio è contenuto, ma i campi `status`, `override`,
`original_iwexa_payload` sono fillable e in alcuni percorsi (admin inline validate)
poco vincolati.

### V7 — Integrità dati non garantita dal DB — BASSO (impatto sicurezza indiretto)
Assenza quasi totale di FK e quantità stock `signed` (vedi [03-Database.md](03-Database.md))
significano che dati incoerenti/negativi non sono bloccati a livello DB.

## Rate limiting

`throttle:60,1` — 60 richieste/minuto. Applicato per chiave di rate limiting di
default Laravel (utente o IP). Adeguato come base; non differenzia per endpoint né per
costo.

## Gestione dei segreti

`IWEXA_API_KEY` e `IWEXA_HMAC_SECRET` da `.env` (non versionati; `.env.example` li
lascia vuoti). I documenti di progetto affermano che **nessuna credenziale è mai stata
committata** (verificato sulla history). Non esiste meccanismo di **rotazione** dei
segreti nel codice (è una domanda aperta nell'handover).

## Cosa NON esiste (per completezza)

Nessuna autenticazione utente storefront, nessun OAuth, nessuna gestione ruoli/permessi
custom, nessun audit di sicurezza automatizzato, nessuna protezione CSRF specifica
(le API sono stateless firmate; l'admin eredita quella di Bagisto), nessun secrets
manager. Pagamenti, wallet, dati carta: **inesistenti**, quindi fuori dal perimetro di
attacco attuale — ma anche fuori da qualsiasi copertura.

## Verdetto sicurezza

Il **nucleo HMAC è solido e ben testato** — è la cosa fatta meglio del repository. Ma
la postura complessiva è quella di un **prototipo**: manca il secondo fattore
dichiarato (Bearer), l'admin è senza ACL, l'input webhook non è validato, e la
documentazione **sovradichiara** le protezioni. Prima di qualsiasi esposizione reale,
V1–V4 vanno chiuse.
</content>
