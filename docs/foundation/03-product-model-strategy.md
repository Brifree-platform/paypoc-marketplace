# 03 — Product Model Strategy (IWEXA)

> **Sistema:** IWEXA + PayPoc
> **Documento:** `docs/foundation/03-product-model-strategy.md`
> **Stato:** Approved
> **Versione:** 1.0.1
> **Data:** 2026-07-23
> **Autore:** Architecture Foundation
> **Approvatore finale:** Cristiano Plattner
> **Responsabile della proposta tecnica:** Principal Software Architect
> **Prerequisiti:** `docs/foundation/00-project-rules.md` — Approved 1.0.0 ·
> `docs/foundation/01-glossary.md` — In approvazione (fonte terminologica di riferimento)
> **Ambito:** principi architetturali del ciclo di vita del prodotto IWEXA
> **Nota:** questo documento **non descrive implementazione, database, API o codice**
> (vedi §9). Non autorizza modifiche al codice.

---

## Scopo

Questo documento definisce la **strategia ufficiale del modello prodotto IWEXA**: i
principi architetturali che governano il **ciclo di vita del prodotto** (Product Lifecycle),
dall'acquisizione alla distribuzione verso i canali. È un documento di **principi di dominio**,
non di soluzione tecnica. La terminologia usata è quella del Glossario ([01-glossary.md](01-glossary.md));
i termini nuovi o le decisioni non ancora formalizzate sono marcati `NOTE` o `PROPOSED`.

---

## 1 · Perché IWEXA utilizza gli schemi Amazon come modello di acquisizione

Per la maggior parte delle categorie merceologiche, **Amazon possiede il modello dati di
prodotto più ricco, completo e mantenuto** oggi disponibile. Questa ricchezza e questo
livello di manutenzione rendono gli **schemi Amazon il punto di ingresso naturale** per
l'acquisizione del catalogo: forniscono una struttura già articolata di contenuti,
identificatori e attributi da cui partire.

Gli schemi Amazon sono quindi adottati come **struttura di acquisizione** — il formato
d'ingresso da cui il prodotto viene raccolto e validato.

> **NOTE.** IWEXA **non dipende** da Amazon e Amazon **non è il System of Record**. Gli
> schemi Amazon sono una **struttura di ingresso e validazione**, non la fonte di verità
> del prodotto. La scelta di adottarli come punto d'ingresso è indipendente dal fatto che
> il prodotto, una volta acquisito, viva e sia governato interamente in IWEXA (§2).

## 2 · Ruolo di IWEXA

- **IWEXA rimane il System of Record** del prodotto.
- **Il prodotto nasce in IWEXA**: la sua esistenza, identità e stato sono di IWEXA.
- Gli **schemi Amazon vengono utilizzati esclusivamente come struttura di acquisizione e
  validazione** in ingresso, non come modello autorevole né come sistema proprietario del
  prodotto.

In sintesi: si entra *attraverso* la struttura Amazon, ma si *è* in IWEXA.

## 3 · Normalizzazione — il Canonical Product Model

Una volta acquisito, il prodotto viene **trasformato da IWEXA in un modello interno
normalizzato** — il **Canonical Product Model**.

- Il Canonical Product Model **non coincide** con il payload/JSON Amazon: non è una copia
  della struttura d'ingresso.
- Il Canonical Product Model è il **formato stabile del dominio**: la rappresentazione
  interna, indipendente dalle sorgenti d'acquisizione e dai canali di distribuzione, su cui
  si fonda la coerenza del catalogo nel tempo.

> **NOTE (terminologia).** "Canonical Product Model" è un **termine nuovo** introdotto da
> questa strategia ed è stato **recepito come voce ufficiale nel Glossario** nel corso
> dello STEP 02 (Foundation Consolidation).

## 4 · Distribuzione verso i Canali

Dal Canonical Product Model vengono **generate le rappresentazioni specifiche per ciascun
Sales Channel**. Esempi di canali:

- Amazon
- PayPoc
- eBay
- Kaufland
- futuri marketplace

Principio guida: **ogni canale riceve esclusivamente le informazioni necessarie** a quel
canale (principio del minimo necessario). La rappresentazione per un canale è una *vista*
derivata dal Canonical Product Model, non il Canonical Product Model stesso.

> **NOTE (coerenza con Glossario e `00-project-rules.md`).** I marketplace esterni
> (Amazon, eBay, Kaufland…) appartengono all'**ecosistema IWEXA** e **non** sono moduli
> interni di PayPoc; **PayPoc è uno dei canali**, non il contenitore degli altri.

## 5 · Mapping — trasformazione, non modello

Il percorso concettuale del prodotto è una pipeline di trasformazioni:

```
   Schema Amazon
        │   (acquisizione + validazione in ingresso)
        ▼
   Canonical Product Model         ← formato stabile del dominio (System of Record)
        │   (Channel Mapping = trasformazione in uscita)
        ▼
   Channel Mapping
        │
        ▼
   Marketplace / Canale (Amazon · PayPoc · eBay · Kaufland · futuri)
```

Il **mapping rappresenta una trasformazione** da una rappresentazione a un'altra. **Non
rappresenta il modello dati**: il modello dati stabile è il Canonical Product Model; i
mapping (d'ingresso dagli schemi Amazon, d'uscita verso i canali) sono adattatori attorno
a esso.

> **NOTE (terminologia).** "Channel Mapping" (trasformazione Canonical Product Model → Sales
> Channel) è una **accezione distinta** dal "Mapping" già in Glossario (riconciliazione
> categorie/attributi verso Bagisto). Le due accezioni vanno tenute separate.

## 6 · Categorie

- Le **categorie Amazon** vengono utilizzate **durante la creazione del prodotto** (fase
  di acquisizione), come parte della struttura d'ingresso.
- **PayPoc può utilizzare una propria tassonomia commerciale**, distinta dalle categorie
  di acquisizione.
- La **tassonomia PayPoc può essere ottenuta tramite mapping approvati** (Mapping
  Approval).

> **PROPOSED / NOTE.** **Come** questa tassonomia commerciale sarà ottenuta e mantenuta
> **non è deciso in questo documento**: nessun modello dati, nessuno schema, nessun ADR.
> La relazione fra categorie Amazon (acquisizione), Google Product Taxonomy (contratto) e
> tassonomia commerciale PayPoc resta una **decisione da formalizzare** (output §3).

## 7 · Attributi — concetti distinti

I seguenti sono **concetti differenti** e **non devono essere confusi** fra loro né
appiattiti su "attributo":

- **Categoria** — collocazione merceologica del prodotto.
- **Attributi** — proprietà descrittive strutturate del prodotto.
- **Filtri** — dimensioni di selezione/navigazione esposte al cliente (derivabili da
  attributi, ma non coincidenti con essi).
- **Benefici** — messaggi di valore/vantaggio del prodotto.
- **Ingredienti** — composizione del prodotto.
- **Claim** — affermazioni dichiarate sul prodotto (con implicazioni di verificabilità e
  responsabilità).
- **Documentazione** — materiali e documenti associati al prodotto.
- **Compliance** — dati e requisiti regolatori obbligatori per legge.

La strategia impone di **trattarli come concetti separati** anche quando, in una sorgente
d'acquisizione, appaiono mescolati.

> **NOTE (terminologia).** Nel Glossario esistono già **Attribute**, **Category** e
> **Compliance** (che include ingredienti/avvertenze GPSR). **Filtri, Benefici, Claim,
> Documentazione** sono **concetti non ancora nel Glossario**: candidati a voce ufficiale
> (output §2). Nessuna definizione implementativa viene fissata qui.

## 8 · Obiettivi della strategia

- **Ridurre le duplicazioni** — un solo formato stabile (il Canonical Product Model) invece
  di molte copie divergenti per sorgente/canale.
- **Garantire la coerenza** — il dominio ha una sola rappresentazione autorevole.
- **Facilitare l'aggiunta di nuovi marketplace** — un nuovo canale = un nuovo mapping
  d'uscita, senza toccare il modello del dominio.
- **Ridurre la manutenzione** — le variazioni di un canale non si propagano al modello né
  agli altri canali.
- **Consentire l'evoluzione indipendente dei marketplace** — ogni canale evolve la propria
  rappresentazione senza vincolare gli altri.

## 9 · Fuori dallo scope

Questo documento **non** definisce, e nulla in esso va interpretato come definizione di:

- specifiche API;
- schema database;
- entità (entity);
- JSON;
- classi;
- Laravel; Bagisto; Supabase;
- codice; algoritmi;
- implementazione.

Tali aspetti saranno oggetto di documenti e ADR successivi, secondo il processo
decisionale del progetto.

---

## Note e decisioni da formalizzare

Elementi emersi durante la stesura e **non decisi autonomamente** (marcati `NOTE`/`PROPOSED`),
riportati anche nell'output di consegna:

- `NOTE` — terminologia **recepita nel Glossario nello STEP 02** (Foundation Consolidation):
  **Canonical Product Model**, **Channel Mapping**, **Product Lifecycle**. Restano descrittivi
  o da definire (non ancora nel Glossario): **Struttura di acquisizione**, **Tassonomia
  commerciale PayPoc**; concetti attributivi distinti **Filtri, Benefici, Claim, Documentazione**.
- `NOTE` — **System of Record**: nel Glossario è `PROPOSED`; questa strategia approvata
  afferma **IWEXA come System of Record**. Il Glossario andrà allineato.
- `PROPOSED` — modalità di ottenimento della tassonomia commerciale PayPoc (§6): non decisa.
- `PROPOSED` — relazione fra categorie Amazon, Google Product Taxonomy e tassonomia PayPoc.

Queste voci **non modificano** altri documenti: sono segnalazioni per i passi successivi.

## Changelog

| Data | Versione | Modifica | Stato |
|---|---|---|---|
| 2026-07-23 | 1.0.0 | Prima stesura della Product Model Strategy (STEP Product Model Strategy): acquisizione via schemi Amazon, IWEXA System of Record, Canonical Product Model, distribuzione multi-canale, mapping come trasformazione, categorie e attributi come concetti distinti, obiettivi e fuori-scope. | Approved |
| 2026-07-23 | 1.0.1 | Consolidamento editoriale (STEP 02 — Foundation Consolidation): applicata la Language Convention e unificata la terminologia — nome ufficiale unico **Canonical Product Model** (ex «Modello Canonico»/«Modello Canonico IWEXA»), **Channel Mapping** (ex «Mapping Canale»), **Sales Channel**. Nessuna modifica ad architettura, decisioni o significato. | Approved |
</content>
