# 09 — Google Commerce

> Fonte: sottosistema mapping (`category_mappings`, `product_type_mappings`,
> `attribute_mappings`), `AmazonMapper`, contratto v3.1 (`category`, `/taxonomy`),
> prototipo `tools/bagisto-import/`.

## Sintesi

Esiste **solo il modello dati della tassonomia Google** (ID/percorso/product type) come
campi nelle tabelle di mapping e nel contratto. **Non esiste alcuna integrazione con
Google**: né Merchant Center, né generazione feed, né Shopping, né client API Google.
Le voci "Google Merchant feed generation" sono elencate nel README del package tra i
**"Future Enhancements"** — cioè esplicitamente non fatte.

## Per area richiesta

| Area | Stato | Evidenza |
|---|---|---|
| **Merchant Center** | ❌ ASSENTE | nessun client Google API, nessun OAuth, nessuna credenziale Google in config |
| **Feed** | ❌ ASSENTE | nessuna generazione feed XML/TSV; nessuna rotta/comando di export |
| **Taxonomy** | 🟡 solo dati/mappa | `google_product_category` in `category_mappings` e `product_type_mappings`; `googleTaxonomyId`/`googleTaxonomyPath`/`localizedPath` nel contratto; `/taxonomy` nel mock |
| **Product Type** | 🟡 solo dati | `product_type_mappings` porta `google_product_type`, `amazon_product_type`; nessun uso verso Google |
| **Attributi** | 🟡 mapping in staging | sottosistema attribute-mapping (draft provisioning), ma **non crea attributi Bagisto reali** |
| **SEO** | 🟡 minimo (prototipo) | `url_key`/slug, `meta_title`/`meta_description` impostati **solo** nel prototipo di import; nessun dato strutturato/sitemap custom |
| **Shopping** | ❌ ASSENTE | nessuna campagna/integrazione Shopping |

## Tassonomia: cosa c'è davvero

La Google Product Taxonomy è presente come **concetto di dati**, non come integrazione:
- Il contratto impone path **identici** fra Iwexa e PayPoc (nessuna rimappatura lato
  PayPoc): l'Hub fornisce `googleTaxonomyId` + `localizedPath` già normalizzati.
- `AmazonMapper::PRODUCT_TYPE_MAP` traduce Amazon `productType` → tassonomia Google, ma
  contiene **una sola voce** (`PERSONAL_FRAGRANCE` → id 479). Qualsiasi altro
  `productType` finisce in `missing[category]`.
- Il mock serve `/taxonomy` leggendo da `data/taxonomy.json`.
- Nel prototipo di import, l'albero categorie Bagisto è costruito **da `localizedPath`**,
  coerente con la regola "nessuna rimappatura".

## Il sottosistema di mapping (categorie/product-type/attributi)

Nel connettore esiste un impianto di mapping completo a livello CRUD:
`CategoryMappingService`, `ProductTypeMappingService`, `AttributeMappingService`,
`AttributeProvisioningService`, con 4 tabelle, 3 controller admin e viste. Serve a
riconciliare categorie/attributi di una sorgente esterna con Bagisto e a creare
**draft** per gli sconosciuti. Limiti rilevanti:
- il provisioning crea **righe di mapping in stato draft**, **non** attributi/famiglie
  Bagisto reali;
- `google_product_category` è duplicato su due tabelle (vedi [03-Database.md](03-Database.md));
- secondo il piano di riscrittura, poiché la tassonomia arriva **già normalizzata**
  dall'Hub, gran parte di questo sottosistema è candidato a ridimensionamento.

## Cosa servirebbe per "Google Commerce" (e manca del tutto)

Client Merchant Center (Content API), autenticazione OAuth/service account, mapping
prodotto→feed (con GTIN/EAN, brand, GPC, prezzo, disponibilità), scheduler di export,
gestione errori/diagnostica feed, gestione varianti (`item_group_id` esiste come colonna
ma non è usato in alcun feed). **Nessuno di questi elementi è presente.**

## Sintesi Google

**Livello dati** presente (tassonomia, product type come campi); **livello
integrazione** pari a zero. "Google Commerce" come funzione non è iniziato: è una voce
di roadmap, non di codebase.
</content>
