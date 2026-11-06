# 06 — PayPoc / Bagisto

> Fonte: `composer.json`, `IwexaConnectorServiceProvider`, struttura del repo, view
> Blade admin, `tools/bagisto-import/`.

## Avvertenza

> **Il repository NON contiene un'installazione Bagisto.** Contiene **solo il package**
> `webkul/iwexa-connector`, pensato per essere agganciato a un'app Bagisto ospite come
> repository `path` con symlink. Quindi non esistono in questo repo: tema storefront,
> personalizzazioni del core Bagisto, override di controller/view Bagisto, moduli
> aggiuntivi. Tutto ciò che segue riguarda **come il package si innesta** in Bagisto,
> non un'app PayPoc personalizzata (che non è versionata qui).

## Il package

- Nome: `webkul/iwexa-connector`, type `bagisto-package`, licenza MIT.
- PHP `^8.1`; dipendenza `bagisto/bagisto: "*"` — **vincolo di versione non fissato**
  (l'app reale, per i documenti di progetto, gira su Laravel 12.56 / PHP 8.3, mentre la
  Spec citava Laravel 10; il `*` non protegge da incompatibilità di major).
- PSR-4: `Webkul\PAYPOC\IwexaConnector\` → `src/`.
- Un solo ServiceProvider auto-registrato via `extra.laravel.providers`.

## Cosa registra il ServiceProvider

`register()`: merge config; bind **singleton** di `IwexaApiService` con i 3 argomenti
scalari (baseUrl/apiKey/hmacSecret) — l'unico non autowirabile; + 6 service e 5
repository "plain" come singleton. `boot()`: publish di migration/config/view; alias
middleware `iwexa.signature`; caricamento rotte api+admin; `loadViewsFrom` (namespace
`iwexa`); `loadTranslationsFrom('../Resources/lang', 'iwexa')`.

### Difetti di registrazione
- **`loadTranslationsFrom` punta a una cartella inesistente**: `src/Resources/lang/`
  **non esiste** nel repo → registrazione morta (nessuna traduzione).
- **Nessun `EventServiceProvider`, nessun listener**: i 5 eventi del package non sono
  mai agganciati (vedi [13-TechnicalDebt.md](13-TechnicalDebt.md)).
- **Nessuna registrazione di menu admin** e **nessun ACL**: le pagine admin non
  compaiono nella sidebar Bagisto e non hanno permessi dedicati.
- I 6 repository che estendono la base Bagisto (`Webkul\Core\Eloquent\Repository`) **non
  sono** elencati nel provider: risolvono via container con il costruttore della base.

## Personalizzazioni / override / estensioni

| Aspetto | Stato |
|---|---|
| Override del core Bagisto | ❌ **nessuno** nel repo |
| Override di controller/view Bagisto | ❌ **nessuno** |
| Tema storefront personalizzato | ❌ **assente** |
| Moduli/estensioni Bagisto aggiuntivi | ❌ **nessuno** (solo questo package) |
| View admin custom | ✅ 9 Blade che estendono `layouts.master` (mapping + sync-jobs) |
| Integrazione con repository Bagisto | ✅ solo indiretta: 6 repo estendono la base Bagisto; **nessun uso** di ProductRepository/CategoryRepository di Bagisto nei service |

> Punto critico: **nessun service del connettore tocca il catalogo Bagisto reale**
> (ProductRepository/Inventory). L'"import" scrive esclusivamente nelle tabelle di
> staging `iwexa_*`. La creazione di un **prodotto Bagisto vero** dai dati importati
> **non è implementata** nel package.

## L'unico ponte reale verso Bagisto: il prototipo di import

`tools/bagisto-import/import-amazon-product.php` è uno **script standalone** (non parte
del package, va lanciato dall'app Bagisto) che dimostra il percorso completo
Amazon → contratto → Bagisto:
- usa `CategoryRepository`/`ProductRepository` **di Bagisto** per creare l'albero
  categorie dal `localizedPath` e un prodotto `simple`;
- inserisce prezzo (segnaposto), descrizione, GPSR nella descrizione, quantità
  (segnaposto), categorie;
- **non importa le immagini** (le URL sono note ma non scaricate).

È l'**unico codice del repo che crea davvero un prodotto Bagisto**, ma è un prototipo
one-shot fuori dal package, con valori segnaposto espliciti (sellPrice=listPrice,
stock=10, vendor=PLACEHOLDER-VENDOR).

## Configurazione Bagisto nota (dai documenti, non nel repo)

I documenti di progetto segnalano che l'app Bagisto reale ha: valuta **USD** (il
contratto ammette solo EUR), unico locale **`en`** (contenuto italiano, servono 27
paesi), unica attribute family **Default**. Sono elementi di configurazione dell'app
ospite, **non presenti/gestiti in questo repo**.

## Sintesi PayPoc/Bagisto

Il "lato PayPoc" versionato è **solo un package di integrazione**, ben strutturato come
scheletro Laravel/Bagisto ma con **innesti difettosi** (traduzioni morte, niente menu/
ACL, eventi non agganciati) e, soprattutto, **nessun collegamento reale al catalogo
Bagisto**. Il marketplace PayPoc come applicazione — tema, storefront, checkout,
account — **non è in questo repository**.
</content>
