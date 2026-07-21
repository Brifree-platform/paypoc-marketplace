# Piano immagini prodotto

> **Data:** 2026-07-21
> **Stato:** proposta
> **Contesto:** l'import di prova (`tools/bagisto-import/`) ha portato in Bagisto
> tutti i dati testuali del prodotto, ma **non le immagini**. Questo documento
> spiega perché e definisce la strada per demo e produzione.

---

## 1. Perché le immagini non sono state importate

Due motivi, uno tecnico e uno di sostanza.

**Tecnico.** Bagisto non usa URL remoti: vuole file reali su disco
(`storage/app/public/product/{id}/`) più una riga in `product_images` per
ciascuno. Registrare un URL in un campo non basta — la scheda prodotto non lo
userebbe. Importare un'immagine significa scaricarla e registrarla.

**Di sostanza.** Le URL del payload puntano a **`m.media-amazon.com`**, il CDN
con cui Amazon serve le foto sulle *sue* pagine (è ciò che la SP-API
restituisce). Non sono asset PayPoc:

- ripubblicarle su un marketplace terzo è una questione di **licenza d'uso**,
  da chiarire con il vendor, non un default tecnico
- servirle in hotlink dal dominio Amazon viola la **decisione 3** (nessuna
  infrastruttura terza visibile al cliente) e lascia ad Amazon il controllo su
  disponibilità e formati

---

## 2. Decisione contrattuale proposta

Il contratto v3.1 definisce `images[]` come array di URL ma **non dice chi le
ospita**. Va fissato:

> `images[]` contiene esclusivamente URL su **dominio PayPoc** (bucket
> S3/CloudFront di proprietà). Mai URL di CDN terzi (Amazon, vendor, corrieri).

È coerente con la decisione 3 ed è verificabile: la suite di conformità può
controllare che nessun URL immagine punti fuori dai domini PayPoc.

Conseguenza sul flusso: è **l'Hub** che ingerisce le immagini (da Amazon SP-API,
dal vendor, o da upload manuale), le archivia nel proprio S3 e le espone con URL
propri. Il connettore Bagisto riceve sempre URL già "nostri".

---

## 3. Fase demo — locale, subito disponibile

Per vedere la scheda prodotto completa nel Bagisto di sviluppo:

1. scaricare le 8 immagini del payload di esempio (una per variante, alla
   risoluzione massima — la selezione è già implementata in
   `AmazonMapper::bestImages()`)
2. collegarle al prodotto via repository Bagisto (`product_images`)
3. estendere `tools/bagisto-import/import-amazon-product.php` con un flag
   `--with-images`

Uso locale di sviluppo: nessun problema di pubblicazione. **Non** va usato in
produzione — vedi §1.

Stima: mezz'ora di lavoro, nessuna dipendenza nuova.

---

## 4. Fase produzione — S3

Le immagini di produzione sono già su AWS (bucket del committente). La strada
corretta non è scaricarle in locale ma far parlare Bagisto direttamente con S3.

### 4.1 Configurazione Bagisto

Bagisto (Laravel) supporta S3 come filesystem nativo:

```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=eu-south-1        # o la regione reale del bucket
AWS_BUCKET=paypoc-media
AWS_URL=https://media.paypoc.example # CloudFront o dominio custom del bucket
```

Richiede il pacchetto `league/flysystem-aws-s3-v3` (composer). Con `AWS_URL`
impostato su un dominio proprio, gli URL serviti al cliente restano PayPoc
anche se dietro c'è S3 — decisione 3 rispettata.

### 4.2 Flusso del connettore

Con `images[]` già su dominio PayPoc (vedi §2), il connettore ha due opzioni:

| Opzione | Come | Quando ha senso |
|---|---|---|
| **A. Riferimento** | registra gli URL dell'Hub senza copiare i file | se Hub e Bagisto condividono lo stesso bucket/CDN |
| **B. Copia in S3** | scarica dall'Hub e ricarica nel bucket Bagisto | se i due sistemi hanno storage separati |

L'opzione A è più semplice ma richiede che il tema Bagisto sappia mostrare URL
esterni al proprio storage (personalizzazione del media handling). L'opzione B
usa Bagisto senza modifiche ma duplica i file. **Da decidere insieme
all'architettura di hosting dell'Hub** (domanda aperta §9.4 dell'handover).

### 4.3 Aggiornamenti

Il webhook `productUpdated` porta lo schema Product completo, incluse le
`images[]`. Alla ricezione, il connettore deve riconciliare: aggiungere le
nuove, rimuovere le eliminate, invalidare eventuali cache/CDN.

---

## 5. Convenzioni proposte per l'Hub

Da concordare con il programmatore, poi da fissare nel contratto:

- **Naming**: chiave basata sull'EAN, es. `products/{ean}/{n}.jpg` — così
  l'URL è deterministico e la riconciliazione banale
- **Ordine**: la prima immagine dell'array è la principale (MAIN); l'ordine
  dell'array è l'ordine di visualizzazione
- **Formati**: JPEG/WebP, lato lungo ≥ 1000px (Amazon fornisce già le varianti
  a 1000px — il mapper seleziona quelle)
- **Alt text**: la Spec §6.4 prevedeva `images[]` con "URL CDN, ordine, alt
  text"; se serve l'alt text per l'accessibilità/SEO, `images[]` va promosso
  da array di stringhe a array di oggetti `{url, alt}` — **decisione da
  prendere prima che l'Hub sia costruito**, è un cambio di schema

---

## 6. Domande aperte

1. Le immagini prodotto sono già tutte nel bucket AWS, o quello attuale
   contiene altro (loghi, materiale marketing)?
2. C'è già un CloudFront/dominio custom davanti al bucket, o gli URL sono
   `s3.amazonaws.com` nudi? (Il dominio custom serve per la decisione 3.)
3. Chi carica le immagini dei nuovi prodotti: l'Hub in automatico dalla
   SP-API, o un flusso manuale?
4. `images[]` resta array di stringhe o diventa `{url, alt}`? (vedi §5)

---

## 7. Ordine di esecuzione consigliato

| Passo | Chi | Dipende da |
|---|---|---|
| 1. Fissare §2 nel contratto (immagini su dominio PayPoc) | committente + programmatore | — |
| 2. Decidere schema `images[]` (stringhe vs `{url, alt}`) | committente + programmatore | — |
| 3. Demo locale con `--with-images` | qui | niente, si può fare subito |
| 4. Configurare S3 su Bagisto | qui | credenziali bucket + dominio |
| 5. Scegliere opzione A o B (§4.2) | insieme | architettura hosting Hub |
| 6. Aggiornare mock e conformance con il controllo dominio immagini | qui | passo 1 e 2 |
