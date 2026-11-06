# 02 — Architettura

> Fonte: struttura del repository, `composer.json`, `IwexaConnectorServiceProvider`,
> e i risultati per sottosistema (vedi 03–12).

## Struttura del repository (monorepo)

```
paypoc-marketplace/
├── packages/PAYPOC/IwexaConnector/   ← il package (l'unico "prodotto" versionato)
│   └── src/{Controllers,Services,Repositories,Models,Jobs,Events,
│            Http/{Middleware,Requests},Providers,Config,Database/Migrations,
│            Routes,Resources/views}
│   └── tests/Unit/                    ← 1 solo file di test
├── tools/
│   ├── mock-hub/                      ← Hub simulato + suite conformità (63 controlli)
│   └── bagisto-import/                ← prototipo one-shot import in Bagisto
└── docs/                             ← contratto v3.1 + documenti di progetto + questo audit
```

**Nessuna app Bagisto** è versionata: il package si aggancia a un'app ospite via
repository `path` con symlink (vedi [06-PayPoc.md](06-PayPoc.md)).

## Pattern architetturale del package

Segue il pattern Bagisto/Laravel **Controller → Service → Repository → Model**, con Job
per l'asincrono ed Event per l'estensione. Sulla carta è ordinato; nei fatti diversi
strati sono incompleti o non collegati.

```
                         ┌──────────────────────────────────────────┐
   Iwexa Hub (ASSENTE)   │              IwexaConnector               │
   ────────────────────► │                                          │
   push inbound          │  Routes/api.php ──[iwexa.signature HMAC]  │
   (catalog, stock,      │        │                                 │
    vendor, warehouse,   │        ▼                                 │
    shipping, webhook)   │  Controllers/Api ──► Services ──► Repos ──► Models ──► DB staging (14 tab.)
                         │        │                │                              │
                         │        │                └─ StockUpdate  = STUB         │
                         │        │                └─ CatalogImport= bug + staging │
                         │        │                └─ WebhookProc  = product STUB  │
                         │        ▼                                              │
   Admin operatore ────► │  Routes/admin.php ──[admin]──► Controllers/Admin ──► views (no menu, no ACL)
                         │                                                       │
                         │  Jobs: RetryFailedSyncJob (solo manuale) ─┐          │
                         │        ProcessCatalog/Stock (solo da retry)│          │
                         │        ProcessWebhook, ReleaseExpired = MORTI         │
                         │  Events (5) = MAI dispatchati, MAI ascoltati          │
                         │                                                       │
   Iwexa Hub (ASSENTE)   │  IwexaApiService (OUTBOUND push) = CODICE MORTO       │
   ◄──────────────────── │        catalogBatch/updateProduct/updateStock (mai chiamati; direzione errata)
                         └──────────────────────────────────────────┘
```

## Moduli e responsabilità (stato reale)

| Strato | Responsabilità | Stato reale |
|---|---|---|
| **Controllers/Api** (9) | Ricezione push inbound + webhook | Funzionanti come I/O; convenzioni di risposta incoerenti; elaborazione sincrona |
| **Controllers/Admin** (4) | UI operatore mapping + sync-jobs | Complete; senza menu né ACL |
| **Services** (14) | Logica di dominio | Misto: alcuni completi (Vendor, Warehouse, Routing, Shipping, mapping), alcuni **stub/rotti** (Catalog, Stock, WebhookProc product) |
| **Repositories** (11) | Accesso dati | Due stili: 5 "plain" (statici Eloquent) + 6 su base Bagisto. Funzionanti |
| **Models** (14) | Entità + scope | 3 cluster relazionali + isole; `IwexaVendor` centrale ma senza relazioni |
| **Jobs** (5) | Asincrono/retry | Solo `RetryFailedSyncJob` è agganciato (manuale); 2 morti |
| **Events** (5) | Estensione | **Peso morto**: mai dispatchati, mai ascoltati |
| **Middleware** | HMAC in ingresso | Solido e testato |
| **IwexaApiService** | Client uscita verso Hub | **Codice morto**, direzione errata |

## Dipendenze

- Esterne: `bagisto/bagisto: "*"` (non fissato), PHP `^8.1`. Nessun'altra dipendenza
  Composer dichiarata dal package.
- Interne (DI): l'unico binding non banale è `IwexaApiService` (3 argomenti scalari,
  bind manuale come singleton). Gli altri service/repository autowirano su oggetti.
- Il **mock** non ha dipendenze (PHP puro). Il **prototipo import** dipende dall'app
  Bagisto ospite (usa i suoi repository).

## Decisioni architetturali deducibili dal codice (§22)

Decisioni **effettivamente prese e leggibili nel codice**:

1. **Pattern di staging**: i dati inbound sono salvati in tabelle proprie `iwexa_*`
   (con payload grezzo per tracciabilità), **non** direttamente nel catalogo Bagisto.
   Il passo staging→Bagisto **non è implementato** — è una decisione a metà.
2. **Firma HMAC su `body + timestamp` con finestra anti-replay** (±300s), scelta più
   forte della "solo body" della spec originale. Coerente col contratto v3.1.
3. **Idempotenza a chiave** su `sync_jobs.idempotency_key` + dedup webhook su
   `(event_id, delivery_id)`. Implementata sul percorso d'ingresso ma con lacune
   (cleanup mai eseguito, header delivery_id non inoltrato).
4. **Naming a prefisso** `paypoc_`/`paypoc_iwexa_` e numerazione migration progressiva.
5. **Service come singleton** nel provider; **repository in due stili** (plain + base
   Bagisto) — incoerenza di stile non risolta.
6. **Tutto sincrono sul percorso d'ingresso**: i job asincroni esistono ma non sono usati
   per l'ingestione. Decisione (implicita) di elaborare inline.
7. **Direzione inbound-push**: l'intera superficie API riceve scritture dall'Hub —
   decisione **contraddetta** dal contratto v3.1 e dal piano di riscrittura, che vogliono
   PayPoc in lettura (pull) e scrittura solo ordini.

> Le decisioni 1, 6 e 7 sono le più conseguenti e sono anche quelle che il progetto
> stesso ha già deciso di **ribaltare** nella riscrittura pianificata.

## Coerenza architetturale

La **forma** è coerente (layering Bagisto standard, naming disciplinato). La
**sostanza** no: la direzione dell'integrazione è opposta all'obiettivo, gli strati
asincrono/eventi sono inerti, e il ponte verso il catalogo Bagisto reale manca. È
un'architettura **plausibile sulla carta ma non collegata end-to-end**.

## Diagrammi correlati

- Database / ER → [03-Database.md](03-Database.md)
- API → [04-API.md](04-API.md)
- Multi-Vendor → [07-MultiVendor.md](07-MultiVendor.md)
- Multi-Warehouse → [08-MultiWarehouse.md](08-MultiWarehouse.md)
- Flussi dati → [05-IWEX.md](05-IWEX.md)
</content>
