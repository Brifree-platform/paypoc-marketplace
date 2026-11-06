# 12 — Performance

> Fonte: analisi statica del codice. **Nota di metodo:** non esiste un sistema in
> esecuzione, né dati di carico, né profiling. Non essendoci un'app Bagisto installata,
> **non sono disponibili misure reali**. I punti seguenti sono **rischi strutturali**
> dedotti dal codice, non colli di bottiglia misurati.

## Colli di bottiglia strutturali

### P1 — Elaborazione batch sincrona nel thread HTTP (il più serio)
`CatalogController::importBatch` e `StockController::updateStock` eseguono l'intero
batch **inline nella richiesta** (i job `ProcessCatalogImport`/`ProcessStockUpdate`
non sono dispatchati). Con batch fino a 100 prodotti / 500 stock (default config), ogni
richiesta occupa un worker web per tutta la durata dell'elaborazione, inclusi tutti gli
accessi DB per item. Sotto carico questo esaurisce i worker prima del rate limit
(`throttle:60,1` limita la frequenza, non il costo unitario). È insieme un problema di
performance e di resilienza (vedi [11-Security.md](11-Security.md) V5).

### P2 — Crescita illimitata di `webhook_events`
Il job di pulizia `ReleaseExpiredIdempotencyKeys` **non è mai schedulato** (non esiste
alcuno scheduler nel package). La tabella `paypoc_iwexa_webhook_events` cresce senza
limite, e non ha indici su `status`/`received_at` per le scansioni tipiche. Nel tempo,
lookup e dedup peggiorano.

### P3 — Indici mancanti su colonne di lookup
`products.ean`, `products.iwexa_product_id`, `products.item_group_id`,
`stock_logs.warehouse_code`, `webhook_events.status/received_at/processed_at` non sono
indicizzati (vedi [03-Database.md](03-Database.md) C6). Le ricerche per EAN o per stato
diventano full-scan man mano che le tabelle crescono.

### P4 — Indici duplicati = overhead in scrittura
UNIQUE dichiarati due volte su `products.sku`, `webhook_events.event_id`,
`webhook_events.delivery_id`, più l'indice ridondante su `vendors.vendor_code`.
Doppioni di indice appesantiscono ogni INSERT/UPDATE senza beneficio in lettura.

### P5 — Righe "grasse" per payload JSON
`original_iwexa_payload`/`meta`/`response`/`calculated_response_payload` archiviano il
payload grezzo su molte tabelle. Righe grandi = più I/O per scansione e cache DB meno
efficace. Scelta di tracciabilità con costo prestazionale.

### P6 — Nessuna cache
La Spec di progetto prevede **Redis** per cache/sessioni/quote/code, ma **Redis non è
usato in alcun punto del codice** del package e (per i documenti) non è installato in
sviluppo. Non c'è caching di cataloghi, tassonomie o preventivi routing. Ogni preventivo
`RoutingService` ricalcola da DB.

### P7 — Routing: lavoro lineare per candidato
`RoutingService::calculateQuote` itera su tutti i magazzini con stock per lo `sku`, e
per ciascuno esegue una risoluzione zona + query tariffa. Con pochi magazzini è
irrilevante; con molti Vendor Warehouse per SKU diventa una serie di query per richiesta
(potenziale N+1 sulle tariffe). Nessun batching, nessuna cache delle zone.

### P8 — Job senza tuning
Nessun job imposta `$tries`/`$backoff`/`$timeout`/`$queue`. Tutto sulla coda di default.
Il retry è "fatto a mano" con `release(60)`. In caso di picco, nessuna separazione di
coda tra webhook, import e retry.

## Punti che NON sono un problema oggi

- Il volume attuale è nullo (nessun dato reale, nessun traffico): i rischi sopra sono
  **latenti**, non attivi.
- Le query dei repository sono semplici (find per campo, `updateOrCreate`), senza join
  complessi: il rischio non è la singola query ma l'assenza di indici e la sincronicità.

## Cosa manca per poter parlare di performance reali

Un'app Bagisto installata, dati rappresentativi (migliaia di prodotti, 27 paesi), un
ambiente con code/worker e Redis, e profiling sotto carico. Nulla di tutto ciò è
presente. **Qualsiasi numero di performance oggi sarebbe inventato.**

## Sintesi Performance

Non ci sono colli di bottiglia *misurati* perché non c'è nulla in esecuzione. Ci sono
però **scelte strutturali che diventeranno colli di bottiglia** appena il sistema
riceverà carico reale: elaborazione sincrona dei batch, tabelle senza pulizia né indici
di lookup, assenza di cache. Vanno affrontate prima di qualsiasi test di carico, non
dopo.
</content>
