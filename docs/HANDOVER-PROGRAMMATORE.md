# PayPoc ↔ Iwexa Hub — Documento di consegna

> **Destinatario:** sviluppatore incaricato di costruire l'Hub Iwexa
> **Data:** 2026-07-20
> **Contratto di riferimento:** v3.1
> **Repository:** `github.com/Brifree-platform/paypoc-marketplace`

---

## 1. In breve

PayPoc è un marketplace multi-vendor europeo costruito su **Bagisto** (Laravel).
Non possiede il catalogo: lo riceve da un sistema chiamato **Iwexa Hub**, che è
il *single source of truth* per prodotti, prezzi, stock, spedizioni e ordini.

**L'Hub non esiste ancora. Costruirlo è il tuo lavoro.**

Questo pacchetto contiene tre cose che rendono quel lavoro verificabile:

| Cosa | A cosa serve |
|---|---|
| `iwexa_hub_openapi_v3.1.yaml` | Il contratto: cosa l'Hub deve esporre |
| `tools/mock-hub/` | Un Hub simulato che implementa il contratto — riferimento eseguibile |
| `tools/mock-hub/bin/conformance.php` | **63 controlli**: il collaudo del tuo Hub |

Il criterio di consegna è uno solo e non è opinabile:

```bash
php bin/conformance.php https://tuo-hub.example/api/v1
```

**Deve passare tutta.** Un Hub che non supera la suite non è integrabile, a
prescindere da quanto sembri corretto leggendone il codice.

---

## 2. Come funziona l'integrazione

La direzione conta, ed è stata la fonte dell'errore principale del progetto finora.

```
  Iwexa Hub  ──── catalogo, stock, prezzi ────►  PayPoc   (PayPoc LEGGE)
  Iwexa Hub  ──── stato ordine + tracking ────►  PayPoc   (webhook)
  PayPoc     ──── ordini, resi ───────────────►  Iwexa Hub (PayPoc SCRIVE)
```

**PayPoc è in sola lettura sui dati prodotto e in scrittura solo sugli ordini.**
Non riceve mai scritture di catalogo, prezzi, stock o categorie: le interroga.

Il flusso di sincronizzazione previsto è un cron lato PayPoc ogni 15 minuti su
`GET /products?updated_since=...`, più i webhook per gli aggiornamenti in tempo reale.

---

## 3. Le sei decisioni da rispettare

Deliberate il 2026-07-20 riconciliando due specifiche precedenti che divergevano.
Il ragionamento completo è in `RICONCILIAZIONE-CONTRATTO.md`.

### 3.1 Prezzi e IVA risolti per paese

`listPrice`, `sellPrice`, `vatRate` e `shippingPolicy` sono **valori singoli**,
ma l'Hub li risolve per il paese richiesto:

```
GET /products?country=IT   →  vatRate 0.22
GET /products?country=FR   →  vatRate 0.20
GET /products?country=DE   →  vatRate 0.19
```

Senza `country` si assume `IT`. Un paese non coperto risponde **422**, mai un
default silenzioso: un'IVA sbagliata è un problema fiscale, non un dettaglio.

La risposta dichiara sempre il paese risolto nel campo `country`.

### 3.2 L'identità del prodotto è l'EAN

`externalProductId` è l'**EAN** (8 o 13 cifre). `productSlug` resta per gli URL.
Se il tuo catalogo viene da Amazon, l'**ASIN non è la chiave**: l'EAN si trova in
`externally_assigned_product_identifier` con `type: ean`.

Entrambi devono funzionare: `/products/{ean}` e `/products/{slug}`.

### 3.3 Iwexa non deve mai comparire al cliente finale

Regola vincolante di brand. In concreto:

- il magazzino centrale si chiama **`PAYPOC-CENTRAL`**, mai `IWEXA-CENTRAL`
- `trackingUrl` sta su **dominio PayPoc**, mai su dominio Iwexa o del corriere
- nessun campo che raggiunge PayPoc contiene la stringa "iwexa"

La suite di conformità lo verifica cercando quella stringa nell'intero payload
del catalogo.

> Nota: `FBI` e `FBV` restano come valori tecnici dell'enum `fulfillment.type`.
> Sono interni. È il *copy mostrato al cliente* che deve dire "magazzino PayPoc".

### 3.4 Autenticazione doppia

Ogni richiesta richiede **entrambi** gli schemi:

```
Authorization:      Bearer <IWEXA_API_KEY>
X-Iwexa-Signature:  hash_hmac('sha256', body + timestamp, IWEXA_HMAC_SECRET)
X-Iwexa-Timestamp:  <unix seconds>
```

- il **Bearer** identifica il chiamante
- l'**HMAC** garantisce che il corpo non sia stato alterato
- il **timestamp** deve stare entro **±300 secondi**: oltre, la richiesta è
  rifiutata. Senza questa finestra una richiesta catturata resterebbe
  riutilizzabile per sempre

Sulle richieste senza body si firma la stringa vuota concatenata al timestamp.
Serve che i due sistemi abbiano gli orologi sincronizzati (NTP).

Verifica da riga di comando:
```bash
printf '%s' "${body}${timestamp}" | openssl dgst -sha256 -hmac "$SECRET"
```

### 3.5 Contenuto multi-lingua

Nome, descrizione, bullet e `category.localizedPath` sono risolti dall'Hub per
il `?locale=` richiesto. Il lancio è italiano, ma il modello dati deve reggere
i 27 paesi UE dal primo giorno.

### 3.6 Sync incrementale

`GET /products?updated_since=<ISO8601>` restituisce solo i prodotti modificati
dopo quella data. Ogni prodotto espone `updatedAt`. Senza questo, ogni
sincronizzazione rilegge l'intero catalogo.

Una data non valida risponde **422**.

---

## 4. Due oggetti che potresti non aspettarti

Sono stati aggiunti al contratto dopo aver analizzato un payload reale, e
**non erano presenti in nessuna delle specifiche precedenti**.

### `hazmat` — merci pericolose

Molti prodotti hanno restrizioni di trasporto. Il profumo di esempio è **UN1266,
classe 3, infiammabile**, con eccezioni per paese; un power bank è **UN3481**,
batteria al litio. Senza questi dati non è possibile scegliere corriere e
modalità di spedizione corrette.

### `compliance` — dati GPSR

Per cosmetici, alimentari e prodotti chimici, in UE sono **obbligatori per legge**
sulla scheda prodotto: ingredienti, avvertenze di sicurezza, ragione sociale e
contatto del produttore.

Entrambi gli oggetti sono `nullable`: vanno valorizzati dove il prodotto lo richiede.

---

## 5. Il mock come riferimento

```bash
cd tools/mock-hub
php -d opcache.enable=0 -S 127.0.0.1:8800 -t public
```

Nessuna dipendenza: PHP 8.1+ e basta. È scritto in PHP puro e commentato, ma
**non è un vincolo tecnologico**: costruisci l'Hub nel linguaggio che preferisci.
Il mock serve a mostrare *comportamento atteso*, non implementazione.

Credenziali di sviluppo:

| Variabile | Default |
|---|---|
| `IWEXA_API_KEY` | `mock-hub-api-key` |
| `IWEXA_HMAC_SECRET` | `mock-hub-shared-secret` |

Le fixture in `data/` coprono di proposito i casi limite: FBI con soglia di
spedizione gratuita, FBV `alwaysFree`, prodotto esaurito, prodotti con hazmat,
tre paesi con IVA e costi diversi, due lingue.

Gli EAN sono sintetici, tranne `8059777990122` che viene da un payload Amazon reale.

---

## 6. Regole di business da replicare

Non sono invenzioni del mock: derivano dal field contract e vanno rispettate.

- **La soglia di spedizione gratuita si calcola PER VENDOR / PER PACCHETTO**,
  mai sul totale ordine. È l'errore più facile da commettere.
- **`alwaysFree: true`** azzera il costo, ignora la soglia, e quel pacchetto non
  concorre al conteggio della soglia degli altri.
- **L'ordine si splitta per `vendorCode`**: una spedizione per vendor, ciascuna
  con il proprio `type` FBI/FBV e la propria data di consegna stimata.
- **`listPrice ≥ sellPrice`** sempre.
- **`maxApplicableValue` = 40% di `listPrice`** — è il credito massimo che il
  cliente può applicare a quel prodotto.
- **Lo stock lo scrive solo l'Hub.** All'arrivo di un ordine lo decrementa
  immediatamente; alla cancellazione lo ripristina. PayPoc non lo modifica mai.
- **409** su conflitto di stock, **422** su payload incompleto o paese non gestito.

---

## 7. Stato del lato PayPoc

Onestà sullo stato reale, così non ci sono sorprese.

### Il connettore esistente va riscritto

`packages/PAYPOC/IwexaConnector/` contiene un modulo Bagisto scritto prima di
questo contratto. **Implementa la direzione opposta** (endpoint in ingresso perché
l'Hub scrivesse dentro Bagisto) e **non ha il flusso ordini**. Nessuno dei suoi
11 endpoint corrisponde al contratto.

`PIANO-RISCRITTURA.md` dettaglia cosa si tiene (sync job, idempotenza, dedup
webhook, middleware HMAC) e cosa si elimina.

### Due bug noti, se ci metti mano

1. **`delivery_id` non viene mai valorizzato.** `WebhookController` legge
   l'header `X-IWEXA-DELIVERY-ID` ma non lo passa a `WebhookProcessorService`.
   Il campo ha un indice univoco, quindi **il secondo webhook ricevuto fallisce
   sempre** con violazione di vincolo.
2. **I tipi di evento del contratto non sono riconosciuti**: finiscono tutti in
   `event_type = unknown`.

### Configurazione Bagisto da sistemare

- valuta impostata su **USD**, il contratto ammette solo **EUR**
- unico locale `en`, ma il contenuto è italiano e servono 27 paesi
- unica famiglia di attributi `Default`: per categorie come le fragranze servono
  attributi dedicati

---

## 8. Fuori perimetro

Queste aree **non** fanno parte dell'integrazione Hub, e allo stato attuale non
esistono affatto: wallet, motore crediti, loyalty, bonus post-ordine, pagamenti,
email transazionali, carrello e checkout funzionanti.

L'Hub fornisce `maxApplicableValue`, ma **il motore che lo applica è tutto da
costruire**. Se il preventivo copre anche quelle, vanno stimate a parte.

---

## 9. Domande aperte da concordare

1. **Rate limit e retry policy** dell'Hub: da definire insieme.
2. **Paesi coperti al lancio**: le fixture ne hanno tre (IT, FR, DE), il
   contratto ne prevede 27.
3. **Valuta nei paesi non-euro**: il contratto ammette solo `EUR`; va confermato
   che si venda in euro anche in Polonia, Svezia, Danimarca.
4. **Chi ospita l'Hub** e con quali ambienti (staging + produzione).
5. **Rotazione dei segreti**: come si cambiano `IWEXA_API_KEY` e
   `IWEXA_HMAC_SECRET` senza interrompere il servizio.
6. **Immagini prodotto**: chi le ingerisce e chi le ospita. Il payload Amazon
   espone URL su `m.media-amazon.com`, che non possono arrivare al cliente
   (decisione 3): l'Hub deve archiviarle su storage PayPoc (S3) ed esporre URL
   propri. Dettagli e schema proposto in `PIANO-IMMAGINI.md`.

---

## 10. Repository e archivio

**Il repository è la fonte autorevole.** L'archivio allegato è una copia di
comodità per partire subito: appena il contratto o il mock cambiano, diventa
obsoleto. Per qualsiasi dubbio fa fede `main`.

```bash
git clone git@github.com:Brifree-platform/paypoc-marketplace.git
cd paypoc-marketplace
```

| Cosa | Percorso nel repo |
|---|---|
| Contratto v3.1 | `docs/iwexa_hub_openapi_v3.1.yaml` |
| Questo documento | `docs/HANDOVER-PROGRAMMATORE.md` |
| Perché il contratto è cambiato | `docs/RICONCILIAZIONE-CONTRATTO.md` |
| Stato del connettore PayPoc | `docs/PIANO-RISCRITTURA.md` |
| Hub simulato + suite | `tools/mock-hub/` |
| Mapper Amazon → contratto | `tools/mock-hub/src/AmazonMapper.php` |
| Payload Amazon reale | `tools/mock-hub/data/amazon-sample-B0H83PBXFF.json` |

> ⚠️ Sul Desktop del committente esiste una vecchia copia
> `iwexa_hub_openapi.yaml` **versione 3.0**, superata. Se ti arriva quella,
> ignorala: descrive un'API diversa e ti farebbe costruire la cosa sbagliata.
> La versione valida è la **3.1**, nel repo.

### Contenuto dell'archivio di comodità

```
paypoc-handover/
├── LEGGIMI.md                          questo documento
├── contratto/
│   ├── iwexa_hub_openapi_v3.1.yaml     il contratto — importabile in Postman/Swagger
│   ├── hub-field-contract.md           regole di business e campi (v3.0, base)
│   └── RICONCILIAZIONE-CONTRATTO.md    perché il contratto è cambiato
├── mock-hub/                           Hub simulato + suite di conformità
│   ├── README.md                       come avviarlo e usarlo
│   ├── public/  src/  data/  bin/
├── piano/
│   └── PIANO-RISCRITTURA.md            stato del connettore PayPoc, cosa va rifatto
└── esempi/
    └── amazon-sample-B0H83PBXFF.json   payload Amazon reale, con il mapper di esempio
```

Da fare, in ordine:

1. avviare il mock e guardare le risposte (`README.md` del mock)
2. leggere il contratto v3.1
3. lanciare la suite contro il mock per vedere com'è fatto un esito verde
4. costruire l'Hub
5. puntare la suite al proprio Hub finché non passa

---

## 11. Un avvertimento che vale la pena leggere

Il connettore PayPoc esistente è stato scritto **due giorni dopo** che il contratto
era stato definito, e implementa comunque l'architettura opposta. Non era codice
scritto male: era plausibile, coerente, ben strutturato. Semplicemente non
corrispondeva alla specifica, e **non era mai stato eseguito** — il ricevitore di
webhook rispondeva 500 al 100% delle chiamate, e nessuno se n'era accorto.

È il motivo per cui questo pacchetto contiene una suite di conformità invece di
sole pagine di documentazione: perché il collaudo sia un fatto verificabile e non
un'impressione. Falla girare presto e spesso.
