# 01 — Glossario ufficiale · IWEXA + PayPoc

> **Sistema:** IWEXA + PayPoc
> **Documento:** `docs/foundation/01-glossary.md`
> **Stato:** In approvazione
> **Versione:** 1.0.0-draft
> **Data:** 2026-07-23
> **Approvatore finale:** Cristiano Plattner
> **Responsabile della proposta tecnica:** Principal Software Architect
> **Prerequisito:** `docs/foundation/00-project-rules.md` — Approved 1.0.0
> **Ambito:** terminologia ufficiale del progetto
> **Nota:** il documento **non autorizza modifiche al codice**.

---

## Come leggere questo Glossario

Questo è (in stato di proposta) la **fonte unica** della terminologia del sistema. Ogni
termine ha: **definizione, ambito, proprietario funzionale, fonte, stato, uso tecnico,
uso UI, sinonimi vietati, note/relazioni** (Art. 10 di `00-project-rules.md`).

**Stati ammessi** (combinabili se coerenti con `00-project-rules.md`):

| Stato | Significato |
|---|---|
| `APPROVED` | Naming ufficiale deciso dal committente |
| `PROPOSED` | Proposto, in attesa di approvazione |
| `CODE` | Esiste ed è eseguibile oggi nel repository (AS-IS) |
| `CONTRACT` | Definito nel contratto v3.1 / mock / suite (TO-BE, non implica implementazione) |
| `TODO` | Concetto o implementazione pendente e tracciata |
| `UNKNOWN` | Non determinabile dalle fonti / questione aperta |
| `DEPRECATED` | Da non usare più |
| `UI-ONLY` | Termine esclusivamente di presentazione al cliente |

Principio guida (Art. 1–2 della Costituzione): **non si presenta come esistente ciò che è
solo previsto**; quando una fonte manca si scrive `UNKNOWN` e si apre una domanda, non si
inventa. La baseline contrattuale citata è **v3.1** (baseline attualmente approvata).

**Language Convention.** La Foundation è redatta in **italiano** e il testo descrittivo
resta in italiano. I **nomi ufficiali** dei concetti architetturali, dei pattern, dei
componenti software e della terminologia consolidata dell'ingegneria del software restano
in **inglese** e **non si traducono** (es. *System of Record*, *Source of Truth*,
*Canonical Product Model*, *Product Lifecycle*, *Sales Channel*, *Channel Mapping*,
*Checkout*, *Wallet*, *Cashback*, *Loyalty*, *Compliance*). Ogni voce riporta il **termine
ufficiale in inglese** e una **definizione descrittiva in italiano**. I nomi di dominio
deliberatamente scelti in italiano dal committente (es. UI *Maestro* per *Vendor*,
*Credito*, *Fornitore*, *Prezzo pubblico*, *Ordine commerciale*) restano come decisi.

---

# Parte A — Termini normativi fondamentali (§4)

## IWEXA
- **Definizione:** sistema centrale che gestisce i dati master e le operazioni centrali del progetto.
- **Ambito:** sistema centrale.
- **Proprietario funzionale:** IWEXA (committente).
- **Fonte:** `[CONTRACT]` (il contratto descrive il comportamento atteso dell'Hub) · `[DOC]` (documenti di progetto). **AS-IS:** l'IWEXA reale **non esiste come software** (`UNKNOWN` implementativo).
- **Stato:** `APPROVED` come **nome del sistema**. Le singole responsabilità sono `TO-BE` e vanno marcate `CONTRACT`/`TODO`/`UNKNOWN` secondo le fonti (nessun `DECISION`: non esistono ancora ADR).
- **Utilizzo tecnico:** nome del sistema centrale nei documenti e nei contratti.
- **Utilizzo nella UI:** **mai esposto al cliente** (regola di brand).
- **Sinonimi vietati/non approvati:** IWEX, Hub, Backend, ERP, Supplier Portal. ("Hub" ammesso **solo** dentro un nome tecnico realmente presente, es. il file `iwexa_hub_openapi_*.yaml`.)
- **Note e relazioni:** responsabilità **target** (secondo ADR/documenti futuri): catalogo centrale, prodotti, Vendor, rapporti/onboarding/condizioni dei Fornitori, magazzini, disponibilità, fulfillment, spedizioni, tracking logistico, conformità, distribuzione verso i canali, Product Acceptance. Ognuna resta `TODO`/`CONTRACT`/`UNKNOWN` finché una fonte non la conferma.

## PayPoc
- **Definizione:** canale di vendita e marketplace rivolto ai clienti, collegato a IWEXA.
- **Ambito:** canale di vendita.
- **Proprietario funzionale:** PayPoc.
- **Fonte:** `[CONTRACT]` (lato che legge/ordina) · `[DOC]`. **AS-IS:** nel repo esiste solo il **package connettore**, non l'app storefront (`UNKNOWN` per storefront/checkout).
- **Stato:** `APPROVED` come nome e ruolo generale; le singole funzionalità si classificano secondo la fonte reale.
- **Utilizzo tecnico:** nome del canale/marketplace.
- **Utilizzo nella UI:** nome mostrato al cliente (marchio del canale).
- **Sinonimi vietati/non approvati:** IWEXA, Hub, "semplice sito web", ERP.
- **Note e relazioni:** responsabilità **target**: storefront, esperienza cliente, ricerca, navigazione, carrello, checkout, pagamenti, ordine commerciale locale, area cliente, Wallet, eventuale Credito, eventuali programmi di fidelizzazione, visualizzazione delle informazioni logistiche ricevute da IWEXA, feed pubblici e Google Merchant. **PayPoc non è** il sistema master di catalogo, Fornitori, magazzini o logistica.

## Vendor
- **Definizione:** soggetto commerciale i cui prodotti sono distribuiti nel canale; chiave di raggruppamento/routing.
- **Ambito:** IWEXA (master) + PayPoc (riferimento).
- **Proprietario funzionale:** IWEXA.
- **Fonte:** `[CODE: IwexaVendor, paypoc_iwexa_vendors, /vendors]` · `[CONTRACT: schema Vendor, vendorCode]`.
- **Stato:** `APPROVED` + `CODE` + `CONTRACT`.
- **Utilizzo tecnico:** **deve** restare "Vendor" in codice, database, API, contratti, eventi, documentazione tecnica, nomi di modelli e proprietà (`vendor*`).
- **Utilizzo nella UI:** mostrato al cliente come **Maestro** (vedi Maestro).
- **Sinonimi vietati/non approvati:** Maestro (nel codice), Seller, Merchant, Partner, Supplier.
- **Note e relazioni:** che un Vendor possa **anche** essere un Fornitore **non** rende "Vendor" e "Fornitore/Supplier" sinonimi automatici (vedi Fornitore).

## Maestro
- **Definizione:** nome mostrato al cliente al posto di "Vendor" nelle interfacce PayPoc pubbliche.
- **Ambito:** PayPoc (presentazione).
- **Proprietario funzionale:** PayPoc (branding).
- **Fonte:** `[DOC]` (decisione di branding del committente).
- **Stato:** `APPROVED` + `UI-ONLY`.
- **Utilizzo tecnico:** **nessuno.** Non modifica classi, tabelle, colonne, API, payload, contratti, eventi, identificatori tecnici, né documentazione tecnica che descrive oggetti di sistema.
- **Utilizzo nella UI:** sostituisce visivamente "Vendor" verso il pubblico.
- **Sinonimi vietati/non approvati:** usare "Maestro" come nome tecnico.
- **Note e relazioni:** **Maestro è la rappresentazione UI di Vendor**; **non** è un'entità tecnica distinta, salvo futura decisione approvata tramite ADR.

## Vendor Store
- **Definizione:** vetrina o spazio commerciale associato a un Vendor nel canale PayPoc.
- **Ambito:** PayPoc.
- **Proprietario funzionale:** PayPoc.
- **Fonte:** `[DOC]` (requisito). **AS-IS (STEP 01A):** **non presente nel codice** (nessun storefront/modulo marketplace) → implementazione `TODO`/`UNKNOWN`.
- **Stato:** `APPROVED` come **termine tecnico**; implementazione `TODO`.
- **Utilizzo tecnico:** nome tecnico "Vendor Store".
- **Utilizzo nella UI:** potrà essere presentato in modo coerente con "Maestro", ma il nome tecnico resta Vendor Store.
- **Sinonimi vietati/non approvati:** Maestro Store, Seller Store, Merchant Store (nel codice).
- **Note e relazioni:** collegato a Vendor; abilita "accesso alla vetrina dal prodotto" (requisito, non ancora codice).

## Vendor Warehouse
- **Definizione:** magazzino associato a un Vendor.
- **Ambito:** IWEXA (dominio logistico).
- **Proprietario funzionale:** IWEXA.
- **Fonte:** `[CODE: IwexaWarehouse type=vendor, codice VENDOR-{vendorCode}]` · `[CONTRACT: fulfillment.warehouseCode]`.
- **Stato:** `APPROVED` (termine tecnico) + `CODE` + `CONTRACT`; effetti operativi reali `TODO`.
- **Utilizzo tecnico:** nome tecnico "Vendor Warehouse".
- **Utilizzo nella UI:** non esposto (regola di brand: al cliente "magazzino PayPoc").
- **Sinonimi vietati/non approvati:** Maestro Warehouse, Seller Warehouse, Merchant Warehouse.
- **Note e relazioni:** contrapposto al magazzino centrale `PAYPOC-CENTRAL` (FBI); vedi FBV, Warehouse.

## Fornitore (Supplier)
- **Definizione:** soggetto con cui IWEXA intrattiene il rapporto di approvvigionamento, onboarding, documentazione, condizioni commerciali e operative.
- **Ambito:** IWEXA.
- **Proprietario funzionale:** IWEXA.
- **Fonte:** `[DOC]`. **AS-IS:** nessuna entità "Supplier/Fornitore" distinta nel codice (`UNKNOWN`).
- **Stato:** `PROPOSED` (in attesa di definizione formale in documenti/ADR successivi).
- **Utilizzo tecnico:** da definire; nella documentazione italiana usare **Fornitore**; "Supplier" solo come **traduzione tecnica**, non come sostituzione arbitraria.
- **Utilizzo nella UI:** da definire.
- **Sinonimi vietati/non approvati:** equiparare automaticamente Fornitore = Vendor.
- **Note e relazioni:** un soggetto può ricoprire **sia** Fornitore **sia** Vendor; l'equivalenza richiede una decisione architetturale (vedi Domanda 10).

## Wallet
- **Definizione (proposta):** funzione PayPoc destinata a registrare e rendere disponibile al cliente un valore utilizzabile secondo regole commerciali definite.
- **Ambito:** PayPoc.
- **Proprietario funzionale:** PayPoc.
- **Fonte:** `[DOC]`. **AS-IS:** nessun motore Wallet nel repository (0 occorrenze in `packages/`).
- **Stato:** `PROPOSED` + `TODO`.
- **Utilizzo tecnico:** da definire (nessun modello/tabella oggi).
- **Utilizzo nella UI:** "Wallet" (nome da mantenere, non rinominare).
- **Sinonimi vietati/non approvati:** Balance, Account Balance, Credit Engine, Electronic Money Wallet, Purchasing Power Wallet.
- **Note e relazioni:** **non** è ancora definito come denaro elettronico, conto di pagamento, saldo bancario, cashback, Credito, Potere d'acquisto o Loyalty. Relazione con Credito: vedi Credito e Domande 1–4.

## Credito
- **Definizione (proposta):** valore eventualmente riconosciuto al cliente e utilizzabile su PayPoc secondo regole, limiti e condizioni da definire.
- **Ambito:** PayPoc.
- **Proprietario funzionale:** PayPoc.
- **Fonte:** `[DOC]`. **AS-IS:** nessun motore Credito nel codice; il **dato** `maxApplicableValue` (40% del listino) è calcolato **solo** nel mock/mapper (`tools/`) `[CONTRACT/tools]`, non un motore.
- **Stato:** `PROPOSED` + `TODO`.
- **Utilizzo tecnico:** da definire.
- **Utilizzo nella UI:** "Credito".
- **Sinonimi vietati/non approvati:** Credit Engine, Purchasing Power, Bonus Balance, Cashback, Wallet.
- **Note e relazioni:** Wallet e Credito **non** sono automaticamente sinonimi; il Wallet **potrebbe** essere il contenitore/registro e il Credito **uno** dei valori gestiti — relazione `PROPOSED`, da approvare (Domande 3–4).

## Potere d'acquisto
- **Definizione:** **non definita.**
- **Ambito:** da determinare.
- **Proprietario funzionale:** da determinare.
- **Fonte:** — (termine **assente** dal repository e dalle fonti; emerso solo in discussione).
- **Stato:** `UNKNOWN` / `PROPOSED`.
- **Utilizzo tecnico:** nessuno; **non** creare "Purchasing Power Engine" né attribuire formula/logica.
- **Utilizzo nella UI:** indeterminato.
- **Sinonimi vietati/non approvati:** **Purchasing Power** (traduzione non approvata).
- **Note e relazioni — DOMANDA APERTA:** "Potere d'acquisto è il nome commerciale mostrato al cliente, un valore calcolato, un sinonimo di Credito oppure un concetto da eliminare?" (Domande 5–6).

## Cashback
- **Definizione (neutra):** eventuale beneficio riconosciuto dopo un acquisto secondo regole commerciali ancora da definire.
- **Ambito:** PayPoc.
- **Proprietario funzionale:** PayPoc.
- **Fonte:** `[DOC]`. **AS-IS:** assente dal codice.
- **Stato:** `PROPOSED` + `TODO`.
- **Utilizzo tecnico:** nessuno oggi.
- **Utilizzo nella UI:** da definire.
- **Sinonimi vietati/non approvati:** Wallet, Credito, Potere d'acquisto, Loyalty (come sinonimi automatici).
- **Note e relazioni — DOMANDA APERTA:** Cashback alimenta il Wallet, il Credito o un saldo separato? (Domanda 7).

## Loyalty
- **Definizione (neutra):** insieme futuro di meccanismi di fidelizzazione del cliente PayPoc.
- **Ambito:** PayPoc.
- **Proprietario funzionale:** PayPoc.
- **Fonte:** `[DOC]`. **AS-IS:** assente dal codice.
- **Stato:** `PROPOSED` + `TODO`.
- **Utilizzo tecnico:** nessuno oggi.
- **Utilizzo nella UI:** da definire.
- **Sinonimi vietati/non approvati:** Wallet, Credito, Cashback, Potere d'acquisto (come sinonimi automatici).
- **Note e relazioni — DOMANDA APERTA:** Loyalty è un programma separato o il dominio che governa Cashback e altri benefici? (Domanda 8).

## Product Acceptance
- **Definizione (provvisoria):** processo finale eseguito in IWEXA che determina se un prodotto può essere distribuito e pubblicato su PayPoc.
- **Ambito:** IWEXA.
- **Proprietario funzionale:** IWEXA.
- **Fonte:** — (termine **assente** dal repository). `[DOC]` come proposta.
- **Stato:** `PROPOSED` + `TODO`.
- **Utilizzo tecnico:** nessuno oggi.
- **Utilizzo nella UI:** non esposto.
- **Sinonimi vietati/non approvati:** Product Validation, Product Approval, Catalog Approval, Listing Approval (traduzioni/rinominazioni automatiche).
- **Note e relazioni:** **distinto** da mapping categoria, completezza tecnica, controllo immagini, conformità di sistema, pubblicazione automatica. Controlli/stati/regole/responsabilità/automazioni/revisione umana/gestione per Paese/claim: solo tramite ADR e documenti futuri. Domanda 9.

## Catalogo
- **Definizione:** dominio dei dati di prodotto; il **catalogo master** è di IWEXA.
- **Ambito:** IWEXA (master) → PayPoc (proiezione).
- **Proprietario funzionale:** IWEXA.
- **Fonte:** `[CONTRACT: /products]` · `[CODE]` parziale (staging). **AS-IS:** il connettore scrive solo **staging** (`iwexa_products`), non un catalogo PayPoc completo.
- **Stato:** `APPROVED` come concetto generale; `CODE`/`CONTRACT`/`TODO` per le implementazioni reali.
- **Utilizzo tecnico:** distinguere: **catalogo master IWEXA**, **copia/proiezione locale PayPoc** (Local Projection), **prodotto pubblicato nel canale** (Listing), **dati di staging** (Staging), **feed pubblico** (Public Feed).
- **Utilizzo nella UI:** navigazione/ricerca prodotti (target).
- **Sinonimi vietati/non approvati:** dichiarare l'attuale staging come "catalogo PayPoc completo".
- **Note e relazioni:** vedi Prodotto, Staging, Local Projection, Public Feed.

## Prodotto
- **Definizione:** entità catalografica centrale gestita da IWEXA e distribuita verso PayPoc secondo stati e regole da approvare.
- **Ambito:** IWEXA (master).
- **Proprietario funzionale:** IWEXA.
- **Fonte:** `[CONTRACT: schema Product]` · `[CODE: IwexaProduct]` (staging).
- **Stato:** `APPROVED` (concetto) + `CODE` (staging) + `CONTRACT`.
- **Utilizzo tecnico:** entità prodotto.
- **Utilizzo nella UI:** scheda prodotto (target).
- **Sinonimi vietati/non approvati:** confondere Prodotto con Offerta/Listing/riga d'ordine.
- **Note e relazioni:** **distinto** da offerta commerciale, stock, prezzo, contenuto localizzato, variante, listing pubblicato, riga d'ordine.

## Offerta
- **Definizione (proposta):** combinazione commerciale di prodotto, Vendor, prezzo, disponibilità, condizioni e canale.
- **Ambito:** IWEXA/PayPoc (da definire).
- **Proprietario funzionale:** da definire.
- **Fonte:** `[DOC]`. **AS-IS:** nessuna entità "Offerta" nel codice.
- **Stato:** `PROPOSED`.
- **Utilizzo tecnico:** **nessun modello dati imposto** in questo STEP.
- **Utilizzo nella UI:** da definire.
- **Sinonimi vietati/non approvati:** confondere Offerta con Prodotto.
- **Note e relazioni:** separata da Prodotto; Domanda 14.

## Prezzo pubblico
- **Definizione (proposta):** prezzo ufficialmente esposto al pubblico e utilizzato nei feed pubblici.
- **Ambito:** PayPoc (esposizione).
- **Proprietario funzionale:** da definire (prezzo originato da IWEXA).
- **Fonte:** `[CONTRACT: listPrice/sellPrice]` come dato; il termine "prezzo pubblico" è `[DOC]` proposto.
- **Stato:** `PROPOSED` (in attesa di ADR).
- **Utilizzo tecnico:** da definire.
- **Utilizzo nella UI:** prezzo mostrato in vetrina/feed.
- **Sinonimi vietati/non approvati:** confondere con Credito, Wallet, Cashback, sconto personale, valore riservato agli autenticati.
- **Note e relazioni:** i **feed pubblici** usano il prezzo pubblico; vedi Public Feed, Google Merchant. Domanda 15.

## Ordine commerciale
- **Definizione (proposta):** ordine creato nel canale PayPoc a seguito della conferma del checkout del cliente.
- **Ambito:** PayPoc.
- **Proprietario funzionale:** PayPoc.
- **Fonte:** `[CONTRACT: OrderCreate/Order]` (comportamento) · **AS-IS:** **nessun flusso ordini** nel connettore.
- **Stato:** `PROPOSED` + `TODO`.
- **Utilizzo tecnico:** da definire.
- **Utilizzo nella UI:** "ordine" nell'area cliente (target).
- **Sinonimi vietati/non approvati:** confondere con gli oggetti operativi/logistici gestiti da IWEXA (vedi Fulfillment Order).
- **Note e relazioni:** distinto dall'ordine/incarico operativo inviato a IWEXA. Domande 12–13.

## Fulfillment
- **Definizione (neutra):** insieme delle attività operative necessarie per preparare ed evadere gli articoli ordinati.
- **Ambito:** IWEXA.
- **Proprietario funzionale:** IWEXA.
- **Fonte:** `[CONTRACT: fulfillment {type, warehouseCode, prepTimeDays, deliveryTimeDays}]` · `[CODE: enum FBI/FBV, RoutingService]`.
- **Stato:** `PROPOSED` come responsabilità target; `CODE`/`CONTRACT` secondo il repository.
- **Utilizzo tecnico:** "Fulfillment" (mantenere).
- **Utilizzo nella UI:** non esposto come termine tecnico; tradotto in linguaggio cliente.
- **Sinonimi vietati/non approvati:** usare a rotazione "evasione/logistica/preparazione/distribuzione" come sinonimi perfetti.
- **Note e relazioni:** vedi FBI, FBV, Vendor Warehouse, Spedizione.

## FBI
- **Definizione:** valore dell'enum `fulfillment.type` osservabile nel progetto; nel codice/contratto identifica il fulfillment dal **magazzino centrale** `PAYPOC-CENTRAL`.
- **Ambito:** IWEXA (interno).
- **Proprietario funzionale:** IWEXA.
- **Fonte:** `[CODE: enum central]` · `[CONTRACT: fulfillment.type=FBI]`.
- **Stato:** `CODE` + `CONTRACT`; **espansione dell'acronimo `UNKNOWN`** (non verificata in alcuna fonte).
- **Utilizzo tecnico:** valore enum tecnico interno.
- **Utilizzo nella UI:** **mai** esposto (al cliente: "magazzino PayPoc").
- **Sinonimi vietati/non approvati:** —
- **Note e relazioni:** contrapposto a FBV. Espansione ufficiale: Domanda 11.

## FBV
- **Definizione:** valore dell'enum `fulfillment.type`; identifica il fulfillment da un **Vendor Warehouse** (`VENDOR-{vendorCode}`).
- **Ambito:** IWEXA (interno).
- **Proprietario funzionale:** IWEXA.
- **Fonte:** `[CODE: enum vendor]` · `[CONTRACT: fulfillment.type=FBV]`.
- **Stato:** `CODE` + `CONTRACT`; **espansione dell'acronimo `UNKNOWN`**.
- **Utilizzo tecnico:** valore enum tecnico interno.
- **Utilizzo nella UI:** **mai** esposto.
- **Sinonimi vietati/non approvati:** —
- **Note e relazioni:** contrapposto a FBI. Espansione ufficiale: Domanda 11.

## Spedizione (Shipment)
- **Definizione:** unità logistica di consegna di uno o più articoli di un ordine.
- **Ambito:** IWEXA (logistica) → PayPoc (visualizzazione).
- **Proprietario funzionale:** IWEXA.
- **Fonte:** `[CONTRACT: schema Shipment]`. **AS-IS:** nessuna gestione spedizioni nel connettore.
- **Stato:** `PROPOSED` + `CONTRACT`; `TODO` implementazione.
- **Utilizzo tecnico:** **Shipment** come nome tecnico quando corrisponde a entità/proprietà/payload.
- **Utilizzo nella UI:** **Spedizione** (documentazione/UI italiana).
- **Sinonimi vietati/non approvati:** usare a rotazione Pacco/Corriere come sinonimi di Spedizione.
- **Note e relazioni:** distinguere: Spedizione, Pacco (Package), Tracking, Corriere (Carrier), Etichetta di spedizione, Tariffa di spedizione (Shipping Rate), Regola commerciale di spedizione (shippingPolicy).

## Tracking
- **Definizione (neutra):** informazioni relative allo stato e all'avanzamento logistico di una spedizione.
- **Ambito:** IWEXA (gestione) → PayPoc (visualizzazione).
- **Proprietario funzionale:** IWEXA gestisce il tracking logistico; PayPoc **visualizza** al cliente le informazioni ricevute.
- **Fonte:** `[CONTRACT: Shipment.trackingUrl, webhook orderStatusChanged]`. **AS-IS:** nessun tracking nel connettore.
- **Stato:** `PROPOSED` + `CONTRACT`/`TODO`.
- **Utilizzo tecnico:** stato/avanzamento della spedizione.
- **Utilizzo nella UI:** pagina di tracking cliente (target), **su dominio PayPoc**.
- **Sinonimi vietati/non approvati:** —
- **Note e relazioni:** vedi Tracking URL, Spedizione.

## Sales Channel
- **Definizione:** destinazione commerciale alla quale IWEXA distribuisce prodotti e dati autorizzati.
- **Ambito:** IWEXA (ecosistema).
- **Proprietario funzionale:** IWEXA.
- **Fonte:** `[DOC]`.
- **Stato:** `PROPOSED`.
- **Utilizzo tecnico:** **Sales Channel** (nome ufficiale). In italiano descrittivo: «canale di vendita».
- **Utilizzo nella UI:** non esposto.
- **Sinonimi vietati/non approvati:** —
- **Note e relazioni:** **PayPoc è un canale di vendita.** Gli altri marketplace esterni (Amazon, eBay, Kaufland…) appartengono all'**ecosistema IWEXA** e **non** sono moduli interni di PayPoc; non si crea una fase PayPoc dedicata al loro sviluppo. Vedi Marketplace.

## Google Merchant Center
- **Definizione:** integrazione/feed pubblico di PayPoc verso Google.
- **Ambito:** PayPoc (integrazione in uscita).
- **Proprietario funzionale:** PayPoc.
- **Fonte:** `[DOC]`. **AS-IS:** nessuna integrazione nel codice (solo modello dati tassonomia Google).
- **Stato:** `PROPOSED` + `TODO`.
- **Utilizzo tecnico:** integrazione feed; **non** marketplace master, **non** sistema proprietario del prezzo.
- **Utilizzo nella UI:** non esposto.
- **Sinonimi vietati/non approvati:** trattarlo come marketplace master.
- **Note e relazioni:** i feed usano il **Prezzo pubblico**. **Wallet, Credito e valori personali del cliente non devono essere esposti nel feed**, salvo futura decisione esplicita e compatibile con le regole applicabili. Vedi Public Feed.

## B2C
- **Definizione:** modello commerciale verso il cliente finale.
- **Ambito:** PayPoc (commerciale).
- **Proprietario funzionale:** da definire.
- **Fonte:** `[DOC]`.
- **Stato:** `PROPOSED`.
- **Utilizzo tecnico/UI:** da definire (nessun flusso/prezzo/checkout specifico in questo STEP).
- **Sinonimi vietati/non approvati:** —
- **Note e relazioni:** può condividere dati master IWEXA con B2B.

## B2B
- **Definizione:** modello commerciale verso soggetti business.
- **Ambito:** PayPoc/IWEXA (commerciale).
- **Proprietario funzionale:** da definire.
- **Fonte:** `[DOC]`.
- **Stato:** `PROPOSED`.
- **Utilizzo tecnico/UI:** da definire.
- **Sinonimi vietati/non approvati:** —
- **Note e relazioni:** distinto da B2C; può condividere dati master IWEXA.

---

# Parte B — Termini tecnici e di dominio (§5)

> Schede compatte. Fonti dallo STEP 01A (codice reale), dal contratto v3.1 e dai documenti.
> Dove la fonte manca: `UNKNOWN` + domanda.

## Customer
- **Def.:** cliente finale del canale PayPoc. **Ambito:** PayPoc. **Proprietario:** PayPoc.
- **Fonte:** `[DOC]` (AS-IS: nessuna gestione cliente nel package). **Stato:** `PROPOSED`.
- **Tecnico:** da definire. **UI:** "cliente". **Vietati:** — **Note:** distinguere da User; relazione con Wallet/Credito/Loyalty.

## User
- **Def.:** utente identificato di un ordine. **Ambito:** PayPoc. **Proprietario:** PayPoc.
- **Fonte:** `[CONTRACT: OrderCreate.userId]`. **Stato:** `CONTRACT` + `PROPOSED`.
- **Tecnico:** `userId` nel payload ordine. **UI:** account/login (target). **Vietati:** — **Note:** relazione con Customer da chiarire.

## Vendor Code
- **Def.:** chiave che identifica un Vendor e **raggruppa il carrello in pacchetti / instrada l'ordine**. **Ambito:** IWEXA. **Proprietario:** IWEXA.
- **Fonte:** `[CODE: vendor_code]` · `[CONTRACT: vendorCode]`. **Stato:** `CODE` + `CONTRACT`.
- **Tecnico:** `vendorCode`/`vendor_code`. **UI:** non esposto. **Vietati:** — **Note:** chiave di split ordine/spedizioni per Vendor.

## Warehouse
- **Def.:** magazzino (centrale IWEXA o Vendor). **Ambito:** IWEXA. **Proprietario:** IWEXA.
- **Fonte:** `[CODE: IwexaWarehouse type=central|vendor]` · `[CONTRACT: fulfillment.warehouseCode]`. **Stato:** `CODE` + `CONTRACT`.
- **Tecnico:** `warehouse_code`. **UI:** al cliente "magazzino PayPoc"; mai "IWEXA". **Vietati:** — **Note:** vedi Vendor Warehouse, FBI/FBV. Magazzini **esterni/terzi**: `UNKNOWN` (enum ammette solo central/vendor).

## Inventory
- **Def.:** giacenza/inventario prodotto. **Ambito:** IWEXA (master) / Bagisto (host). **Proprietario:** IWEXA.
- **Fonte:** **AS-IS:** il connettore **non** tocca l'inventario Bagisto (`markInStock/markOutOfStock` = stub). `UNKNOWN` in `packages/`. **Stato:** `TODO` + `UNKNOWN`.
- **Tecnico:** oggi non implementato lato connettore. **UI:** stato disponibilità (target). **Vietati:** confondere Inventory con Stock-log. **Note:** vedi Stock, Availability.

## Stock
- **Def.:** quantità disponibile di un prodotto per magazzino. **Ambito:** IWEXA. **Proprietario:** IWEXA (**solo l'Hub scrive stock**).
- **Fonte:** `[CODE: IwexaWarehouseStock, IwexaStockLog, StockUpdateService(stub)]` · `[CONTRACT: /stock, StockResponse]`. **Stato:** `CODE` (staging/log) + `CONTRACT`.
- **Tecnico:** `quantity/reserved_quantity/available_quantity`. **UI:** disponibile/esaurito (target). **Vietati:** — **Note:** effetto su Bagisto = `TODO` (stub).

## Availability
- **Def.:** disponibilità effettiva. **Ambito:** IWEXA. **Proprietario:** IWEXA.
- **Fonte:** `[CODE: available_quantity = quantity - reserved]` · `[CONTRACT: inStock, stockQuantity]`. **Stato:** `CODE` + `CONTRACT`.
- **Tecnico:** `available_quantity`, `inStock`. **UI:** badge disponibilità. **Vietati:** — **Note:** vedi Stock.

## Price
- **Def.:** prezzo di un prodotto (listino/vendita) per paese. **Ambito:** IWEXA. **Proprietario:** IWEXA.
- **Fonte:** `[CONTRACT: listPrice/sellPrice/vatRate per country]`. **AS-IS:** il connettore **non** memorizza il prezzo. **Stato:** `CONTRACT`.
- **Tecnico:** `listPrice/sellPrice/vatRate`. **UI:** prezzo in vetrina (target). **Vietati:** confondere con Prezzo pubblico (che è il termine ufficiale d'esposizione, `PROPOSED`). **Note:** vedi Prezzo pubblico.

## Category
- **Def.:** categoria merceologica; nel contratto è la **Google Product Taxonomy** localizzata. **Ambito:** IWEXA. **Proprietario:** IWEXA.
- **Fonte:** `[CODE: CategoryMapping]` · `[CONTRACT: category.googleTaxonomyId/localizedPath]`. **Stato:** `CODE` + `CONTRACT`.
- **Tecnico:** mapping categoria. **UI:** breadcrumb/navigazione. **Vietati:** — **Note:** path **identici** Hub↔PayPoc (nessuna rimappatura); vedi Mapping.

## Attribute
- **Def.:** attributo di prodotto e sua normalizzazione. **Ambito:** IWEXA/PayPoc. **Proprietario:** IWEXA.
- **Fonte:** `[CODE: AttributeMapping, AttributeValueMapping]`. **Stato:** `CODE`.
- **Tecnico:** sottosistema mapping attributi (non crea attributi Bagisto reali). **UI:** filtri/scheda (target). **Vietati:** — **Note:** vedi Mapping.

## Variant
- **Def.:** variante di un prodotto (stesso gruppo, SKU figlio). **Ambito:** IWEXA. **Proprietario:** IWEXA.
- **Fonte:** `[CODE: parent_sku, item_group_id, variants() self-join]`. **AS-IS:** solo relazione self-join in staging. **Stato:** `CODE` parziale + `TODO`.
- **Tecnico:** `parent_sku`/`item_group_id`. **UI:** selezione variante (target). **Vietati:** — **Note:** vedi Parent SKU, Item Group.

## SKU
- **Def.:** identificatore prodotto usato come chiave nel connettore. **Ambito:** IWEXA/PayPoc. **Proprietario:** IWEXA.
- **Fonte:** `[CODE: sku (unique)]`. **Stato:** `CODE`.
- **Tecnico:** `sku`. **UI:** non esposto. **Vietati:** — **Note:** **AS-IS** lo SKU è la chiave del connettore, ma l'**identità di contratto è l'EAN** (vedi EAN); relazione da riconciliare.

## EAN
- **Def.:** codice a barre (8/13 cifre); **identità di prodotto** secondo il contratto. **Ambito:** IWEXA. **Proprietario:** IWEXA.
- **Fonte:** `[CODE: ean]` · `[CONTRACT: externalProductId = EAN]` · `[DOC: riconciliazione, decisione 2]`. **Stato:** `CODE` + `CONTRACT`.
- **Tecnico:** `ean` / `externalProductId`. **UI:** non esposto. **Vietati:** trattare l'ASIN come identità. **Note:** l'identità-EAN è nel contratto/documento, **non** ancora un ADR.

## Item Group
- **Def.:** identificatore di gruppo di varianti. **Ambito:** IWEXA. **Proprietario:** IWEXA.
- **Fonte:** `[CODE: item_group_id]`. **Stato:** `CODE`. **Tecnico:** `item_group_id`. **UI:** non esposto. **Vietati:** — **Note:** usato per raggruppare varianti; vedi Variant.

## Parent SKU
- **Def.:** SKU del prodotto padre di una variante. **Ambito:** IWEXA. **Proprietario:** IWEXA.
- **Fonte:** `[CODE: parent_sku]`. **Stato:** `CODE`. **Tecnico:** `parent_sku`. **UI:** non esposto. **Vietati:** — **Note:** vedi Variant.

## Catalog Import
- **Def.:** importazione (batch) di prodotti nel sistema. **Ambito:** PayPoc (ingestione). **Proprietario:** da riconciliare.
- **Fonte:** `[CODE: CatalogImportService, ProcessCatalogImport, /catalog/products/batch]`. **Stato:** `CODE` (AS-IS **difettoso**: scrive solo staging, bug fatale) + `TODO`.
- **Tecnico:** import verso `iwexa_products`. **UI:** non esposto. **Vietati:** — **Note:** AS-IS non crea prodotti Bagisto reali; direzione **inversa** rispetto al contratto.

## Catalog Synchronization
- **Def.:** sincronizzazione del catalogo fra IWEXA e PayPoc. **Ambito:** PayPoc. **Proprietario:** PayPoc (legge).
- **Fonte:** `[CONTRACT: GET /products?updated_since]` (pull, target) · **AS-IS** `[CODE]` push inbound (inverso). **Stato:** `CONTRACT` + `TODO` (+ `CODE` non conforme).
- **Tecnico:** target = pull incrementale. **UI:** non esposto. **Vietati:** — **Note:** il codice attuale non implementa la lettura del catalogo.

## Staging
- **Def.:** tabelle intermedie del connettore (`iwexa_*`) che conservano dati e payload grezzi. **Ambito:** PayPoc (connettore). **Proprietario:** PayPoc.
- **Fonte:** `[CODE: tabelle iwexa_*, original_iwexa_payload]`. **Stato:** `CODE`.
- **Tecnico:** staging/tracciabilità. **UI:** non esposto. **Vietati:** chiamarlo "catalogo PayPoc". **Note:** vedi Catalogo, Local Projection.

## Mapping
- **Def.:** riconciliazione di categorie/product type/attributi/valori sorgente ↔ Bagisto. **Ambito:** PayPoc. **Proprietario:** PayPoc.
- **Fonte:** `[CODE: CategoryMapping, ProductTypeMapping, AttributeMapping, AttributeValueMapping]`. **Stato:** `CODE`.
- **Tecnico:** sottosistema mapping. **UI:** admin operatore. **Vietati:** — **Note:** con tassonomia già normalizzata dall'Hub, ambito da ridurre (nessuna decisione: `TODO`).

## Mapping Approval
- **Def.:** approvazione di un mapping (draft → active) da parte di un operatore admin. **Ambito:** PayPoc. **Proprietario:** PayPoc.
- **Fonte:** `[CODE: approveMapping, pending_mapping, admin/iwexa/*]`. **Stato:** `CODE`.
- **Tecnico:** flusso approvazione mapping. **UI:** admin (senza ACL dedicata, AS-IS). **Vietati:** confondere con **Product Acceptance**. **Note:** distinto da Product Acceptance (che è processo IWEXA sul prodotto).

## Compliance
- **Def.:** dati regolatori obbligatori (GPSR: produttore, contatto, ingredienti, avvertenze). **Ambito:** IWEXA/prodotto. **Proprietario:** IWEXA.
- **Fonte:** `[CONTRACT: compliance]` · `[CODE(tools): AmazonMapper]`. **AS-IS:** non memorizzato nel connettore. **Stato:** `CONTRACT` + `TODO`.
- **Tecnico:** oggetto `compliance` nullable. **UI:** blocco legale in scheda (target). **Vietati:** — **Note:** obbligatorio per legge UE su cosmetici/alimentari/chimici.

## Marketplace
- **Def.:** (a) PayPoc come marketplace verso il cliente; (b) marketplace **esterni** dell'ecosistema IWEXA. **Ambito:** PayPoc / IWEXA. **Proprietario:** rispettivo.
- **Fonte:** `[DOC]`. **Stato:** `PROPOSED`.
- **Tecnico:** — **UI:** — **Vietati:** trattare i marketplace esterni come moduli interni PayPoc. **Note:** vedi Sales Channel.

## Storefront
- **Def.:** vetrina/esperienza cliente PayPoc. **Ambito:** PayPoc. **Proprietario:** PayPoc.
- **Fonte:** `[DOC]`. **AS-IS:** **assente** dal repo (no app Bagisto). **Stato:** `TODO` + `UNKNOWN`.
- **Tecnico:** — **UI:** vetrina pubblica. **Vietati:** — **Note:** dipende dall'app Bagisto ospite.

## Cart
- **Def.:** carrello del cliente. **Ambito:** PayPoc. **Proprietario:** PayPoc.
- **Fonte:** `[CONTRACT: parametro cart in /shipping-quote]`. **AS-IS:** nessun carrello nel codice. **Stato:** `CONTRACT` (parametro) + `TODO`.
- **Tecnico:** `cart` come input di preventivo. **UI:** carrello (target, multi-pacchetto per Vendor). **Vietati:** — **Note:** soglia spedizione **per pacchetto**.

## Checkout
- **Def.:** processo di conferma d'acquisto. **Ambito:** PayPoc. **Proprietario:** PayPoc.
- **Fonte:** `[DOC]`. **AS-IS:** assente. **Stato:** `PROPOSED` + `TODO`.
- **Tecnico:** — **UI:** flusso indirizzo→spedizione→pagamento→riepilogo (target). **Vietati:** — **Note:** genera l'Ordine commerciale.

## Payment
- **Def.:** pagamento dell'ordine. **Ambito:** PayPoc. **Proprietario:** PayPoc.
- **Fonte:** `[CONTRACT: paymentToken in OrderCreate]`. **AS-IS:** nessun motore pagamenti. **Stato:** `CONTRACT` (campo) + `TODO`.
- **Tecnico:** `paymentToken`. **UI:** step pagamento. **Vietati:** — **Note:** vedi Payment Method.

## Payment Method
- **Def.:** metodo di pagamento. **Ambito:** PayPoc. **Proprietario:** PayPoc.
- **Fonte:** `[CONTRACT: enum card, paypal, klarna, applepay, googlepay]`. **Stato:** `CONTRACT`.
- **Tecnico:** `paymentMethod`. **UI:** scelta metodo. **Vietati:** — **Note:** —

## Order / Order Item
- **Def.:** ordine e sua riga. **Ambito:** PayPoc↔IWEXA. **Proprietario:** vedi Ordine commerciale.
- **Fonte:** `[CONTRACT: OrderCreate/Order, items[]]`. **AS-IS:** assente nel connettore. **Stato:** `CONTRACT` + `TODO`.
- **Tecnico:** `items[]` con snapshot prezzo. **UI:** dettaglio ordine. **Vietati:** — **Note:** vedi Ordine commerciale, Fulfillment Order.

## Fulfillment Order
- **Def.:** ordine/incarico **operativo** inviato a IWEXA per l'evasione. **Ambito:** IWEXA. **Proprietario:** IWEXA.
- **Fonte:** — (**non definito** in alcuna fonte). **Stato:** `UNKNOWN` + `PROPOSED`.
- **Tecnico:** — **UI:** non esposto. **Vietati:** confondere con l'Ordine commerciale PayPoc. **Note:** **DOMANDA APERTA 13** (nome ufficiale dell'ordine operativo verso IWEXA).

## Shipment / Package
- **Def.:** Shipment = spedizione (vedi Spedizione); Package = pacco per Vendor nel preventivo. **Ambito:** IWEXA. **Proprietario:** IWEXA.
- **Fonte:** `[CONTRACT: Shipment; shipping-quote.packages[]]`. **Stato:** `CONTRACT` + `TODO`.
- **Tecnico:** `Shipment`, `packages[]`. **UI:** "Spedizione"/"Pacco". **Vietati:** — **Note:** un ordine → più spedizioni/pacchi per Vendor.

## Carrier
- **Def.:** corriere. **Ambito:** IWEXA. **Proprietario:** IWEXA.
- **Fonte:** `[CODE: shipping_rates.carrier]` · `[CONTRACT: Shipment.carrier]`. **Stato:** `CODE` + `CONTRACT`.
- **Tecnico:** `carrier`. **UI:** al cliente non si espone il dominio del corriere (tracking su PayPoc). **Vietati:** — **Note:** nessuna integrazione corriere reale (AS-IS).

## Shipping Rate
- **Def.:** tariffa di spedizione per zona/peso/volume. **Ambito:** IWEXA. **Proprietario:** IWEXA.
- **Fonte:** `[CODE: ShippingRate, paypoc_shipping_rates]`. **Stato:** `CODE`.
- **Tecnico:** `ShippingRate`. **UI:** non esposto. **Vietati:** — **Note:** dominio candidato a rimozione da PayPoc (piano di riscrittura).

## Shipping Zone
- **Def.:** zona origine→destinazione. **Ambito:** IWEXA. **Proprietario:** IWEXA.
- **Fonte:** `[CODE: ShippingZone, paypoc_shipping_zones]`. **Stato:** `CODE`.
- **Tecnico:** `ShippingZone`. **UI:** non esposto. **Vietati:** — **Note:** vedi Shipping Rate.

## Shipping Quote
- **Def.:** preventivo di spedizione per pacchetto. **Ambito:** IWEXA. **Proprietario:** IWEXA.
- **Fonte:** `[CONTRACT: /shipping-quote]`. **AS-IS:** il connettore ha un `RoutingService`/`routing_quote` **distinto** (compute interno). **Stato:** `CONTRACT` + `CODE` (routing quote) + `TODO`.
- **Tecnico:** `/shipping-quote` (contratto) vs routing quote (codice). **UI:** costo spedizione. **Vietati:** — **Note:** soglia gratuita **per pacchetto**; `alwaysFree`.

## Tracking URL
- **Def.:** URL di tracciamento della spedizione, **su dominio PayPoc**. **Ambito:** IWEXA→PayPoc. **Proprietario:** IWEXA (dato), PayPoc (dominio).
- **Fonte:** `[CONTRACT: Shipment.trackingUrl]`. **Stato:** `CONTRACT` + `TODO`.
- **Tecnico:** `trackingUrl`. **UI:** link tracking cliente. **Vietati:** dominio corriere/IWEXA. **Note:** vedi Tracking.

## Webhook
- **Def.:** notifica evento in ingresso da IWEXA a PayPoc. **Ambito:** integrazione. **Proprietario:** PayPoc (ricevitore).
- **Fonte:** `[CODE: WebhookController, /webhooks, WebhookProcessorService]` · `[CONTRACT: webhooks productUpdated/stockChanged/orderStatusChanged]`. **Stato:** `CODE` + `CONTRACT` (AS-IS: parzialmente rotto — handler stub, nomi evento non allineati).
- **Tecnico:** `/webhooks`. **UI:** non esposto. **Vietati:** — **Note:** vedi Delivery ID, Idempotency, Event.

## Event
- **Def.:** evento applicativo/di integrazione. **Ambito:** integrazione. **Proprietario:** —.
- **Fonte:** `[CODE: Events/* (inerti), tipi evento webhook]`. **Stato:** `CODE`.
- **Tecnico:** eventi di dominio (mai dispatchati) + tipi evento webhook. **UI:** non esposto. **Vietati:** — **Note:** i 5 Event del package sono inerti (AS-IS).

## Delivery ID
- **Def.:** identificatore di consegna di un webhook, usato per la deduplicazione. **Ambito:** integrazione. **Proprietario:** IWEXA (emette).
- **Fonte:** `[CODE: delivery_id, X-IWEXA-DELIVERY-ID]`. **Stato:** `CODE`.
- **Tecnico:** `delivery_id`. **UI:** non esposto. **Vietati:** — **Note:** AS-IS con difetto noto (header non inoltrato); vedi Idempotency.

## Idempotency
- **Def.:** garanzia di elaborazione una-sola-volta (chiavi + dedup). **Ambito:** integrazione. **Proprietario:** PayPoc.
- **Fonte:** `[CODE: idempotency_key, dedup event_id+delivery_id]` · `[CONTRACT: header Idempotency-Key]`. **Stato:** `CODE` + `CONTRACT`.
- **Tecnico:** `idempotency_key`. **UI:** non esposto. **Vietati:** — **Note:** cleanup mai schedulato (AS-IS).

## HMAC
- **Def.:** firma HMAC-SHA256 su `body + timestamp` con finestra anti-replay. **Ambito:** sicurezza integrazione. **Proprietario:** entrambi.
- **Fonte:** `[CODE: VerifyIwexaSignature]` · `[CONTRACT: HmacAuth]`. **Stato:** `CODE` + `CONTRACT`.
- **Tecnico:** `X-IWEXA-SIGNATURE`, `X-IWEXA-TIMESTAMP`. **UI:** non esposto. **Vietati:** — **Note:** il contratto richiede **anche** Bearer (non implementato in ingresso, AS-IS).

## Contract
- **Def.:** contratto d'integrazione IWEXA↔PayPoc. **Ambito:** governance/integrazione. **Proprietario:** committente + Architect.
- **Fonte:** `[CONTRACT/DOC: docs/iwexa_hub_openapi_v3.1.yaml]`. **Stato:** `CONTRACT` (baseline **v3.1** attualmente approvata).
- **Tecnico:** OpenAPI. **UI:** non esposto. **Vietati:** — **Note:** governato da `00-project-rules.md` (Contract First).

## Mock
- **Def.:** implementazione eseguibile del contratto per sviluppo/collaudo. **Ambito:** strumenti. **Proprietario:** progetto.
- **Fonte:** `[CODE(tools): tools/mock-hub]`. **Stato:** `CODE`.
- **Tecnico:** server PHP puro. **UI:** non esposto. **Vietati:** confonderlo con l'IWEXA reale. **Note:** non è l'Hub; ne simula il comportamento.

## Conformance Suite
- **Def.:** suite di controlli di conformità al contratto (criterio di accettazione). **Ambito:** strumenti/governance. **Proprietario:** progetto.
- **Fonte:** `[CODE(tools): bin/conformance.php, 63 controlli]`. **Stato:** `CODE`.
- **Tecnico:** suite eseguibile. **UI:** non esposto. **Vietati:** — **Note:** per `00-project-rules.md` è il **criterio di accettazione** dell'integrazione.

## Canonical Product Model
- **Def.:** rappresentazione interna, stabile e normalizzata del prodotto in IWEXA, indipendente dalle sorgenti di acquisizione e dai Sales Channel; **formato stabile del dominio**. **Ambito:** IWEXA. **Proprietario:** IWEXA.
- **Fonte:** `[DOC: 03-product-model-strategy.md]`. **AS-IS:** non implementato (nessun codice). **Stato:** `PROPOSED` (definito in una strategia approvata; ADR non ancora emesso — Art. 4).
- **Tecnico:** modello canonico interno; non coincide col payload/JSON Amazon. **UI:** non esposto. **Vietati/varianti:** «Modello Canonico», «Modello Canonico IWEXA», «Canonical Model». **Note:** dà origine alle rappresentazioni di canale via Channel Mapping.

## Channel Mapping
- **Def.:** trasformazione che genera, dal Canonical Product Model, la rappresentazione specifica di un Sales Channel; è una **trasformazione, non un modello dati**. **Ambito:** IWEXA. **Proprietario:** IWEXA.
- **Fonte:** `[DOC: 03-product-model-strategy.md]`. **Stato:** `PROPOSED`.
- **Tecnico:** adattatore d'uscita Canonical Product Model → canale. **UI:** non esposto. **Vietati/varianti:** «Mapping Canale». **Note:** **distinto** da *Mapping* (riconciliazione categorie/attributi verso Bagisto, `[CODE]`).

## Product Lifecycle
- **Def.:** ciclo di vita del prodotto, dall'acquisizione (schemi Amazon) alla normalizzazione (Canonical Product Model) fino alla distribuzione verso i Sales Channel. **Ambito:** IWEXA. **Proprietario:** IWEXA.
- **Fonte:** `[DOC: 03-product-model-strategy.md]`. **Stato:** `PROPOSED`.
- **Tecnico:** concetto di dominio. **UI:** non esposto. **Vietati/varianti:** — **Note:** in italiano descrittivo: «ciclo di vita del prodotto».

## System of Record
- **Def.:** sistema autorevole per un dato dominio. **Ambito:** governance. **Proprietario:** —.
- **Fonte:** `[DOC]`. **Stato:** `PROPOSED`.
- **Tecnico:** concetto. **UI:** — **Vietati:** — **Note:** IWEXA è il System of Record **target** di catalogo/Vendor/stock/logistica (da confermare per dominio).

## Source of Truth
- **Def.:** fonte di verità per un tipo di affermazione. **Ambito:** governance. **Proprietario:** committente.
- **Fonte:** `[DOC: 00-project-rules.md Art. 11]`. **Stato:** `APPROVED` (definito nella Costituzione).
- **Tecnico:** AS-IS→CODE; integrazione→CONTRACT; architettura→ADR. **UI:** — **Vietati:** — **Note:** vedi gerarchia delle fonti.

## Local Projection
- **Def.:** copia/proiezione locale (PayPoc) del catalogo master IWEXA. **Ambito:** PayPoc. **Proprietario:** PayPoc.
- **Fonte:** `[DOC]`. **AS-IS:** oggi esiste solo lo staging, non una proiezione pubblicata. **Stato:** `PROPOSED`.
- **Tecnico:** — **UI:** — **Vietati:** chiamarla "catalogo master". **Note:** vedi Catalogo, Staging.

## Public Feed
- **Def.:** feed pubblico dei prodotti (es. verso Google). **Ambito:** PayPoc. **Proprietario:** PayPoc.
- **Fonte:** `[DOC]`. **AS-IS:** assente. **Stato:** `PROPOSED` + `TODO`.
- **Tecnico:** — **UI:** non esposto. **Vietati:** esporre Wallet/Credito/valori personali. **Note:** usa il **Prezzo pubblico**; vedi Google Merchant Center.

## Meta-termini di governance (definiti in `00-project-rules.md`)

| Termine | Definizione (rif. Costituzione) | Stato |
|---|---|---|
| AS-IS | Ciò che esiste ed è eseguibile oggi (fonte: codice) | `APPROVED` |
| TO-BE | Ciò che il sistema deve diventare (fonte: contratto/ADR) | `APPROVED` |
| CODE | Esiste ed è eseguibile oggi nel repository | `APPROVED` (etichetta) |
| CONTRACT | Definito nel contratto/mock/suite | `APPROVED` (etichetta) |
| DOC | Descritto solo in un documento | `APPROVED` (etichetta) |
| DECISION | Stabilito da un ADR approvato | `APPROVED` (etichetta) |
| TODO | Lavoro noto e pendente, tracciato | `APPROVED` (etichetta) |
| UNKNOWN | Non determinabile / questione aperta | `APPROVED` (etichetta) |
| ADR | Architecture Decision Record (`docs/adr/ADR-NNN-<slug>.md`) | `APPROVED` |
| Foundation | Insieme dei documenti architetturali fondativi in `docs/foundation/`; **memoria architetturale** ufficiale del progetto (00 Art. 6/8) | `APPROVED` |
| STEP | Unità del processo con **gate di approvazione**: uno STEP non inizia finché il precedente non è approvato (00 Art. 8.8) | `APPROVED` |

---

# Parte C — Termini vietati o non approvati

> Elenco **non esaustivo**. Aggiornabile con nuove voci coerenti con `00-project-rules.md`.

| Termine non approvato | Motivo | Termine corretto |
|---|---|---|
| IWEX | Nome non ufficiale del sistema | IWEXA |
| Hub | Generico e ambiguo | IWEXA, salvo nome tecnico esistente (es. `iwexa_hub_openapi_*.yaml`) |
| Backend | Riduttivo/ambiguo per il sistema centrale | IWEXA |
| Supplier Portal | Non approvato | IWEXA (o "Fornitore" per il ruolo) |
| Seller | Non approvato | Vendor |
| Merchant | Non approvato | Vendor |
| Partner | Non approvato come sinonimo | Vendor |
| Maestro nel codice | Termine esclusivamente UI | Vendor |
| Maestro Store nel codice | Termine non tecnico | Vendor Store |
| Maestro Warehouse nel codice | Termine non tecnico | Vendor Warehouse |
| Purchasing Power | Traduzione non approvata | "Potere d'acquisto" (ancora `PROPOSED`/`UNKNOWN`) |
| Purchasing Power Engine | Motore non definito | Nessun termine approvato |
| Purchasing Power Wallet | Sinonimo non approvato | Wallet (`PROPOSED`) |
| Credit Engine | Motore non definito | Credito (`PROPOSED`) |
| Electronic Money Wallet | Qualifica non approvata | Wallet (`PROPOSED`) |
| Balance / Account Balance | Ambiguo | Wallet, saldo o Credito secondo il contesto |
| Bonus Balance | Non approvato | Credito/Cashback secondo il contesto |
| ERP come sinonimo di IWEXA | Riduttivo e non approvato | IWEXA |
| Product Validation / Product Approval / Catalog Approval / Listing Approval | Rinominazioni di Product Acceptance | Product Acceptance (`PROPOSED`) |
| Fornitore = Vendor (equivalenza automatica) | Non approvata senza decisione | tenere distinti Vendor e Fornitore |

---

# Parte D — Questioni terminologiche da approvare

> Domande aperte. **Non** vengono risolte in questo documento quando la risposta non è già approvata.

1. Qual è la definizione definitiva di **Wallet**?
2. Qual è la definizione definitiva di **Credito**?
3. **Wallet** e **Credito** sono due concetti distinti?
4. Il **Wallet** è il registro del **Credito** oppure comprende altri valori?
5. **"Potere d'acquisto"** deve essere mantenuto?
6. Se mantenuto, è: nome commerciale; valore calcolato; sinonimo di Credito; somma di più benefici?
7. **Cashback** alimenta il Wallet, il Credito o un saldo separato?
8. **Loyalty** è un programma separato o il dominio che governa Cashback e altri benefici?
9. **Product Acceptance** resta il nome ufficiale oppure si adotta un termine italiano?
10. **Vendor** e **Fornitore** sono ruoli distinti anche quando appartengono allo stesso soggetto giuridico?
11. Qual è l'**espansione ufficiale di FBI e FBV**?
12. **"Ordine commerciale"** è il termine ufficiale dell'ordine PayPoc?
13. Qual è il termine ufficiale per gli **ordini/incarichi operativi inviati a IWEXA** (Fulfillment Order)?
14. **"Offerta"** deve diventare un'entità separata dal Prodotto?
15. **"Prezzo pubblico"** è il termine ufficiale da usare nei feed e nella UI non autenticata?

---

## Changelog

| Data | Versione | Modifica | Stato |
|---|---|---|---|
| 2026-07-23 | 1.0.0-draft | Prima stesura del Glossario ufficiale (STEP 01C): recepite le 25 regole terminologiche approvate, censiti i termini tecnici/di dominio dallo STEP 01A e dal contratto v3.1, sezione termini vietati e questioni aperte. | In approvazione |
| 2026-07-23 | 1.0.0-draft | Consolidamento (STEP 02 — Foundation Consolidation): aggiunti **Canonical Product Model**, **Channel Mapping**, **Product Lifecycle**, **Foundation**, **STEP**; formalizzata la **Language Convention**; headword «Canale di vendita» → **Sales Channel**. Nessun termine promosso da PROPOSED ad APPROVED (nessun ADR esistente). | In approvazione |
</content>
