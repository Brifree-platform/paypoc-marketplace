# Audit funzionale — brief per il grafico

> **Data:** 2026-07-21
> **Scopo:** dare a chi disegna l'interfaccia l'inventario di **cosa il marketplace
> fa e mostra**, distinguendo ciò che è reale oggi da ciò che è definito ma da
> costruire, così da disegnare contro un bersaglio stabile e non contro codice legacy.
> **Fonte autorevole:** contratto [iwexa_hub_openapi_v3.1.yaml](iwexa_hub_openapi_v3.1.yaml)
> + regole di business dell'[handover](HANDOVER-PROGRAMMATORE.md). Base tecnica: Bagisto (Laravel).

---

## 0. Come leggere questo documento — i tre livelli di realtà

Il progetto è a metà: un contratto solido, un connettore da riscrivere, quasi nessuna
schermata reale. Per non disegnare nel vuoto, ogni voce è etichettata:

| Etichetta | Significato per il grafico |
|---|---|
| 🟢 **REALE** | Esiste e gira oggi (Bagisto di serie o import di prova). Disegnabile subito su dati veri. |
| 🔵 **CONTRATTO** | Definito e stabile nel contratto v3.1, ma **da costruire**. È il grosso del lavoro di design: il bersaglio è fissato, i dati sono noti. |
| ⚫ **FUORI PERIMETRO** | Non esiste e non è previsto a breve (wallet, motore crediti, pagamenti…). **Non disegnare ora**, salvo dove indicato come "design in anticipo". |

> ⚠️ **Non guardare il codice del connettore attuale come riferimento.**
> `packages/PAYPOC/IwexaConnector/` implementa la direzione opposta a quella del
> contratto ed è destinato alla riscrittura. I suoi 11 endpoint API e 3 delle 4 UI
> admin **non rappresentano il prodotto**. Questo audit è costruito sul contratto, non su quel codice.

---

## 1. Cos'è PayPoc, in una frase

Un **marketplace multi-vendor europeo** (stile Amazon) costruito su Bagisto, che
**non possiede il catalogo**: lo riceve da un sistema esterno ("l'Hub"). PayPoc mostra
prodotti, gestisce carrello, checkout e account cliente; l'Hub fornisce prezzi, stock,
spedizioni e stato ordini.

### 🔴 La regola di brand che vincola OGNI schermata

> **L'Hub ("Iwexa") non deve MAI comparire al cliente finale.**

In concreto, per il grafico:
- il magazzino centrale si chiama **"magazzino PayPoc"**, mai "Iwexa" (nei badge, nel copy di spedizione, nelle FAQ)
- il **tracking** è su dominio PayPoc (`tracking.paypoc.example/...`), mai corriere o Iwexa
- nessuna stringa "iwexa" in nessun testo visibile
- i codici tecnici `FBI`/`FBV` sono **interni**: al cliente si traduce sempre in linguaggio PayPoc ("spedito da PayPoc" / "spedito dal venditore")

---

## 2. Maturità per area — dove si concentra il lavoro di design

| Area | Stato | Dove si disegna |
|---|---|---|
| Vetrina: home, categorie, ricerca | 🟢 Bagisto di serie (tema base) | Restyle del tema |
| Scheda prodotto (PDP) | 🔵 dati dal contratto, componenti nuovi | **Grosso del lavoro** |
| Pagina vendor / negozio | 🔵 contratto (`/vendors`) | Nuova |
| Carrello | 🔵 logica multi-pacchetto nuova | **Grosso del lavoro** |
| Checkout (indirizzo→spedizione→pagamento) | 🔵/⚫ flusso contratto, pagamenti fuori perimetro | Design completo, backend parziale |
| Account: ordini, tracking, resi | 🔵 contratto (`/orders`) | Nuova |
| **Credito PayPoc** (il differenziatore) | ⚫ dato presente, motore da costruire | **Design in anticipo** (vedi §6.4) |
| Switch paese / lingua | 🔵 27 paesi UE, lancio IT | Nuovo componente globale |
| Admin: gestione sync/import | 🟢 una UI reale (`sync-jobs`) | Minimo |
| Admin: mapping categorie/attributi | ⚫ in riscrittura, forse eliminata | Non disegnare finché non deciso |

---

## 3. Il modello dati del prodotto — cosa il grafico deve saper mostrare

Ogni prodotto che arriva alla vetrina ha questi campi (schema `Product` del contratto).
**Importante:** prezzo, IVA e spedizione arrivano già **risolti per un solo paese** (quello
selezionato). Il grafico mostra *un* prezzo, non una tabella per paese.

| Campo | Cosa è | Impatto UI |
|---|---|---|
| `name`, `description`, `bullets` | Testo, **già tradotto** nella lingua scelta | Titolo, descrizione, elenco puntato |
| `images[]` | URL su **dominio PayPoc** (mai Amazon) | Galleria; la prima è la principale |
| `brand` | Marca | Badge/label marca |
| `category.localizedPath` | Breadcrumb già localizzato, es. *Salute e bellezza › Cura della persona › Cosmetici › Profumi* | Breadcrumb, navigazione |
| `listPrice` | Prezzo di listino | Prezzo **barrato** se > sellPrice |
| `sellPrice` | Prezzo di vendita | Prezzo in evidenza |
| `vatRate` | Aliquota IVA del paese (0.22 IT, 0.20 FR…) | Nota "IVA inclusa" |
| `maxApplicableValue` | Credito max applicabile (40% del listino) | **Badge credito** (vedi §6.4) |
| `currency` | Sempre `EUR` | Simbolo € |
| `country` | Paese risolto | Contesto prezzo/consegna |
| `inStock` / `stockQuantity` | Disponibilità | Badge "Disponibile" / "Esaurito" / "Ultimi N pezzi" |
| `fulfillment.type` | `FBI` (magazzino PayPoc) o `FBV` (venditore) | Badge consegna — **tradotto**, mai "Iwexa" |
| `fulfillment.prepTimeDays` + `deliveryTimeDays{min,max}` | Tempi | "Consegna stimata in 3–6 giorni" |
| `shippingPolicy.cost` / `freeShippingThreshold` / `alwaysFree` | Politica spedizione **per pacchetto** | "Spedizione €4,50 — gratis sopra €39" |
| `hazmat` (nullable) | Merce pericolosa (profumi, batterie) | Nota trasporto / avvertenza (vedi §6.5) |
| `compliance` (nullable) | Dati GPSR **obbligatori per legge**: produttore, ingredienti, avvertenze | **Blocco legale in PDP** (vedi §6.6) |
| `vendorCode` / `vendorName` / `vendorSlug` | Venditore | Link alla pagina vendor |

---

## 4. Inventario schermate — VETRINA (storefront)

### 4.1 🟢 Home / landing
Bagisto ne fornisce una di serie. Elementi tipici: hero, categorie in evidenza,
prodotti nuovi/in evidenza, carousel. **Lavoro:** restyling di marca.

### 4.2 🟢/🔵 Listing categoria + ricerca + filtri
Griglia prodotti con filtri. Dal contratto i filtri sensati sono: **vendor**,
**categoria** (Google Taxonomy), **prezzo**, disponibilità. Card prodotto: immagine,
nome, marca, prezzo (barrato + vendita), badge stock, badge consegna, badge credito.

### 4.3 🔵 Scheda prodotto (PDP) — la schermata più ricca
È qui che si concentra il design. Deve ospitare, oltre al blocco classico
(galleria + titolo + prezzo + CTA acquisto):

- prezzo **barrato/vendita** + nota IVA inclusa
- **badge credito PayPoc** ("Applica fino a €68 di credito") — §6.4
- badge disponibilità (con stato "esaurito" gestito, es. Anker in esempio)
- **badge consegna** FBI/FBV tradotto + finestra "3–6 giorni"
- riga spedizione: "Spedizione €4,50 — Gratis sopra €39" (o "Spedizione gratuita")
- descrizione + bullet
- **blocco conformità (GPSR)** quando presente: produttore, contatto, ingredienti,
  avvertenze di sicurezza — §6.6. È obbligatorio per legge sui cosmetici, non opzionale.
- eventuale **nota merce pericolosa** (hazmat) — §6.5
- link/box **venditore**

### 4.4 🔵 Pagina vendor (negozio)
Dal contratto `/vendors`: `vendorName`, `logoUrl`, slug. Header negozio + griglia dei
suoi prodotti. Nuova, da disegnare.

### 4.5 🔵 Carrello — attenzione: multi-pacchetto
Non è un carrello lineare. L'ordine si **divide per venditore** in pacchetti, e ogni
pacchetto ha **la sua** soglia di spedizione gratuita. Vedi §6.1 — è la parte con più
logica di UI nuova (barre di avanzamento per-pacchetto).

### 4.6 🔵/⚫ Checkout
Flusso: **indirizzo → spedizione → pagamento → riepilogo**. L'indirizzo (in particolare
il **paese**) determina prezzi, IVA e spedizione. Metodi di pagamento previsti dal
contratto: `card, paypal, klarna, applepay, googlepay`. ⚫ Nota: il **motore di pagamento
non esiste** — la UI si disegna, il backend è da costruire.

### 4.7 🔵 Conferma ordine + tracking
Stato ordine (`pending → confirmed → shipped → delivered → cancelled`) e **spedizioni
separate per venditore**, ciascuna con stato (`preparing/shipped/in_transit/delivered`),
tracking number, **link tracking su dominio PayPoc**, corriere, data stimata.

### 4.8 🔵 Account cliente
Ordini, dettaglio ordine con tracking, **resi**, indirizzi, e (design in anticipo)
saldo credito. Bagisto fornisce lo scheletro account; i contenuti ordine/tracking/resi
vanno adattati al modello del contratto.

---

## 5. Inventario schermate — ADMIN

Bagisto ha un pannello admin completo di serie (prodotti, ordini, clienti, CMS…).
In più il connettore aggiunge:

| Schermata admin | Stato | Note per il grafico |
|---|---|---|
| **Sync jobs** (lista, dettaglio, retry) | 🟢 reale e mantenuta | Unica UI custom che sopravvive alla riscrittura. Tabella job + stato + azione retry. |
| Category mappings (CRUD) | ⚫ in riscrittura/forse eliminata | Non disegnare: la tassonomia arriva già normalizzata dall'Hub. |
| Product-type mappings | ⚫ idem | idem |
| Attribute mappings | ⚫ idem | idem |

Decisione aperta (§9 del piano di riscrittura): quanta admin di mapping serva davvero.
Finché non è decisa, **solo `sync-jobs` è da disegnare** lato admin custom.

---

## 6. Regole di business che toccano direttamente la UI

### 6.1 Spedizione gratuita PER PACCHETTO, mai sul totale
Il carrello si divide per `vendorCode`. Ogni pacchetto ha la propria
`freeShippingThreshold`. Serve una **barra di avanzamento per pacchetto**:
*"Aggiungi €7 di prodotti Barilla per la spedizione gratuita"* — riferita a quel
venditore, non al totale carrello. È l'errore più facile da commettere nel design.

### 6.2 Articoli `alwaysFree`
Alcuni prodotti hanno spedizione sempre gratuita (es. Mutti in esempio). Non mostrano
soglia, e **non contano** verso la soglia degli altri pacchetti dello stesso venditore.

### 6.3 Un ordine → più spedizioni, più date
Il riepilogo ordine e il tracking devono reggere **N spedizioni** con stati e date di
consegna diversi, non una sola barra di stato.

### 6.4 ⚫→🔵 Credito PayPoc (design in anticipo)
È il **differenziatore** del marketplace. L'Hub fornisce `maxApplicableValue` (40% del
listino) per ogni prodotto — il dato **c'è**. Il *motore* che applica il credito in
checkout **non esiste ancora**. Per il grafico: **disegnalo** (badge in PDP, applicazione
in carrello/checkout, saldo in account, riga `walletApplied` nei totali), sapendo che è
UI in anticipo sul backend. Totali ordine previsti: `subtotal`, `shipping`,
`walletApplied`, `total`.

### 6.5 Merci pericolose (hazmat)
Profumi (infiammabili), power bank (litio) hanno restrizioni di trasporto. In PDP può
servire una nota discreta ("spedizione soggetta a normative sul trasporto"). Non è copy
di marketing — è informativo.

### 6.6 Conformità GPSR — blocco legale obbligatorio
Per cosmetici, alimentari, chimici, in UE è **obbligatorio per legge** mostrare in PDP:
ragione sociale + contatto produttore, ingredienti, avvertenze di sicurezza. Va disegnato
come **sezione strutturata** della scheda (non nascosto in fondo alla descrizione).
Esempio reale nel dataset: il profumo Aurora Blue ha ingredienti completi + avvertenza infiammabilità.

### 6.7 Stati stock
`inStock=false` è uno stato reale (l'Anker in esempio è esaurito). Servono gli stati:
disponibile / esaurito / (opz.) ultimi pezzi da `stockQuantity`.

---

## 7. Vincoli trasversali

- **Multi-paese:** lancio **IT**, ma il modello regge i **27 paesi UE**. Serve uno
  **switch paese** che cambia prezzo/IVA/spedizione. (Fixtures attuali: IT, FR, DE.)
- **Multi-lingua:** contenuto risolto per lingua. Serve **switch lingua**.
- **Valuta:** solo **EUR** — un solo simbolo, nessun cambio valuta in UI.
- **Brand "Iwexa invisibile":** vedi §1 — vincola badge, tracking, copy spedizione.

---

## 8. ⚫ Fuori perimetro — NON disegnare adesso

Da handover §8: non esistono e non sono previsti a breve —
**wallet/motore crediti** (il *dato* c'è, il motore no: vedi §6.4 per la parte disegnabile),
**loyalty, bonus post-ordine, pagamenti reali, email transazionali, carrello e checkout
funzionanti**. Il carrello/checkout si **disegnano** (§4.5–4.6) ma il backend è da costruire:
il grafico non deve assumere che "funzionino" oggi.

---

## 9. Dati reali per i mockup

Nel dataset di esempio ([tools/mock-hub/data/products.json](../tools/mock-hub/data/products.json))
ci sono 4 prodotti scelti apposta per coprire i casi limite del design. Usali nei mockup:

| Prodotto | Perché è utile al design |
|---|---|
| **Barilla Spaghetti** (€1,49) | Prezzo basso, FBI "magazzino PayPoc", spedizione con soglia €39, IVA 4% |
| **Mutti Passata** (€1,79) | FBV (venditore), **spedizione sempre gratuita** (`alwaysFree`) |
| **Aurora Blue Extrait de Parfum** (€149) | Alto valore, **hazmat** (infiammabile) + **GPSR completo** (ingredienti, avvertenze). Il caso ricco per la PDP. |
| **Anker PowerCore** (€39,90) | **Esaurito** (`inStock=false`) + hazmat batteria litio. Il caso "stato negativo". |

Tre paesi con prezzi/IVA diversi (IT/FR/DE) e due lingue (it/en) per provare gli switch.

---

## 10. In sintesi, cosa può iniziare il grafico

1. **Restyle vetrina** (home, listing, ricerca) sul tema Bagisto — 🟢 subito, su dati veri.
2. **PDP** completa con i blocchi nuovi (credito, GPSR, consegna, spedizione, stock) — 🔵 il cuore del lavoro.
3. **Carrello multi-pacchetto** e **checkout** con la logica per-venditore — 🔵.
4. **Account: ordini + tracking multi-spedizione + resi** — 🔵.
5. **Componenti globali:** switch paese/lingua, badge di marca (mai "Iwexa") — 🔵.

Non toccare (ora): admin di mapping, pagamenti reali, motore crediti (di cui però si
disegna la UI). Riferimento dati e regole: contratto v3.1 + questo documento.
