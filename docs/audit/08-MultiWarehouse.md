# 08 — Multi Warehouse

> Fonte: `IwexaWarehouse`, `IwexaWarehouseStock`, `WarehouseSyncService`,
> `WarehouseStockSyncService`, `RoutingService`, tabelle shipping/routing, contratto v3.1.

## Terminologia

**Vendor Warehouse** = magazzino di un Vendor (`type: vendor`, codice `VENDOR-{code}`,
fulfillment **FBV**). Il magazzino centrale IWEX è `type: central`, codice
`PAYPOC-CENTRAL`, fulfillment **FBI** (mai brandizzato "Iwexa" verso il cliente).

## Avvertenza

> Il Multi-Warehouse esiste nel codice come **ingestione di dati di magazzino/stock in
> staging + una logica di preventivo routing**. Non esiste come **sistema operativo di
> gestione magazzino**: lo stock ricevuto **non aggiorna l'inventario Bagisto** (metodi
> stub), non c'è sincronizzazione bidirezionale, non c'è gestione spedizioni reale.
> Secondo il piano di riscrittura del progetto, magazzini/zone/tariffe sono un dominio
> **di IWEX** che PayPoc non deve replicare — quindi buona parte di questo codice è
> destinata alla rimozione.

## Tipi di magazzino nel codice

| Tipo | `type` | Codice | Fulfillment | Fonte |
|---|---|---|---|---|
| Centrale IWEX | `central` | `PAYPOC-CENTRAL` | FBI | enum in `warehouses`/`routing_quotes` |
| Vendor Warehouse | `vendor` | `VENDOR-{vendorCode}` | FBV | enum + `vendor_code` |
| Magazzini **esterni** (terzi) | — | — | — | ❌ **ASSENTE**: l'enum ammette solo `central`/`vendor` |

Non esiste alcun concetto di magazzino esterno/terzo oltre a centrale e Vendor.

## Cosa esiste nel codice

### Dati di magazzino (staging)
- `paypoc_iwexa_warehouses`: `warehouse_code` (unique), `vendor_code`, `name`, `type`
  (`central|vendor`), `country`(2), città/indirizzo, `status`, payload originale.
- `paypoc_iwexa_warehouse_stocks`: `(warehouse_code, sku)` unique, `vendor_code`,
  `quantity`, `reserved_quantity`, `available_quantity`, tempi di preparazione min/max.
  Il model calcola `available_quantity = quantity - reserved_quantity` in un hook
  `saving()`, ed espone `hasAvailableStock()`.
- Ingestione: `WarehouseSyncService` (valida centrale-senza-vendor / vendor-con-vendor,
  upsert) e `WarehouseStockSyncService` (valida `reserved ≤ quantity`, `min ≤ max`,
  upsert). Entrambi completi.
- Endpoint inbound: `POST /warehouses`, `POST /warehouse-stocks`.

### Gestione stock
- **Disponibilità**: `available_quantity` derivato; query `getAvailableWarehousesForSku`,
  `getTotalAvailableStock`.
- **Prenotazione**: esiste il campo `reserved_quantity` e il controllo `reserved ≤
  quantity`, ma **nessun percorso di codice prenota davvero** (non c'è flusso ordini che
  incrementi `reserved_quantity`).
- **Aggiornamento inventario Bagisto**: ❌ **stub**. `StockUpdateService::markInStock/
  markOutOfStock` scrivono solo log (vedi [05-IWEX.md](05-IWEX.md)). Lo stock di
  magazzino resta confinato nelle tabelle di staging del connettore.
- **Quantità `signed`**: le colonne stock sono `integer` con segno → valori negativi non
  bloccati dal DB.

### Routing e scelta del magazzino (§11)
`RoutingService::calculateQuote` (il pezzo di logica più solido del package):
1. prende i magazzini con stock disponibile per lo `sku`/quantità richiesti;
2. per ciascuno risolve la **zona** e trova la **tariffa** (`ShippingRateService::
   findMatchingRate` su zona + peso + volume);
3. costruisce candidati con `shipping_cost_cents` e `final_delivery_max_days =
   prep_max + delivery_max`;
4. sceglie il migliore: **costo minore → consegna più rapida → magazzino centrale
   prima** (`selectBestCandidate`);
5. persiste un `RoutingQuote` (snapshot denormalizzato di richiesta + risposta).

Criteri di scelta effettivamente implementati: **disponibilità, costo, tempi,
preferenza al centrale**. NON implementati come criteri: priorità configurabile per
Vendor, vincoli per **paese** oltre a origin/destination della zona, scelta del
**corriere** (il `carrier` è un campo delle tariffe ma non c'è selezione multi-corriere),
gestione **hazmat** nel routing (il campo hazmat del contratto non entra nel calcolo).

### Spedizioni
- `paypoc_shipping_zones` (origine→destinazione, unique) e `paypoc_shipping_rates`
  (fascia peso/volume, `price_cents`, `carrier`, `shipping_method`, giorni consegna).
- Servizi `ShippingZoneService`/`ShippingRateService`: CRUD + `findMatchingRate`.
- Endpoint inbound: `POST /shipping-zones`, `POST /shipping-rates`.
- La tariffa è scelta per fascia di peso/volume ordinando per prezzo. Nessuna
  integrazione con corrieri reali (ShippyPro, ecc.): è una **tabella di tariffe statica**
  alimentata via push.

## Sincronizzazione

- **Direzione**: solo **inbound push** (l'Hub chiama gli endpoint del connettore).
  Nessun **polling** verso l'Hub (il client in uscita è codice morto).
- **Bidirezionale**: ❌ assente. PayPoc non rimanda nulla all'Hub.
- **Real-time**: il webhook `stockChanged` esiste nel contratto; nel connettore il suo
  handler delega a `StockUpdateService`, il cui effetto è stub. Inoltre legge
  `available_quantity` mentre il service si aspetta `quantity` (mismatch).

## Cosa dice il contratto/mock (per confronto)

Nel contratto lo stock è **scritto solo dall'Hub**; PayPoc non ha endpoint per
modificarlo. Il mock **dichiara** il decremento stock su ordine ma **non lo esegue**
davvero sulle fixture. La soglia di spedizione gratuita è **per pacchetto/Vendor**, con
`alwaysFree` che azzera e non concorre — logica presente **nel mock**, non nel connettore.

## Sintesi Multi-Warehouse

Esistono il **modello dati** di magazzini/stock e una **buona logica di preventivo
routing**, ma il sistema **non aggiorna l'inventario reale**, non prenota, non
sincronizza bidirezionalmente e non parla con corrieri. Per giunta è il dominio che il
piano di progetto assegna a IWEX e prevede di **rimuovere** da PayPoc. È codice
tecnicamente ordinato ma in larga parte **fuori strategia** oltre che incompleto.
</content>
