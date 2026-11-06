# 01 — Executive Summary

> Due diligence tecnica, taglio da CTO esterno. Nessuna indulgenza verso chi ha
> sviluppato: le affermazioni sono ancorate al codice (vedi 02–15). Se un'area è
> immatura, è detto senza attenuazioni.

## Verdetto in tre righe

1. **Non esiste ancora una piattaforma "IWEX + PayPoc" funzionante.** Esiste un
   **package di integrazione Bagisto** che implementa l'architettura sbagliata, non è
   collegato end-to-end, e non era mai stato eseguito con successo fino a pochi giorni fa.
2. **L'asset reale è recente e documentale**: contratto v3.1 + mock Hub + suite di
   conformità (63 controlli). Vale più del codice del connettore.
3. **Livello di maturità complessivo: pre-implementazione / prototipo iniziale.** In una
   due diligence d'investimento, ciò che si compra oggi è **una specifica ben fatta e
   una base di codice in gran parte da rifare**, non un prodotto.

## Cosa esiste davvero (fatti)

- **~5.660 righe PHP** in un unico package, ben impaginate, con pattern Bagisto standard.
- **1 solo file di test** (10 test, solo il middleware HMAC). Servizi, import, webhook,
  routing: copertura **zero**.
- Il **middleware HMAC** è solido e testato — la cosa fatta meglio.
- Un **bug fatale** sul percorso di import principale; **effetti chiave stub** (stock,
  aggiornamento prodotto via webhook); il **client verso l'Hub è codice morto** e nella
  direzione sbagliata; **nessun flusso ordini**; **nessuna creazione di prodotto Bagisto
  reale**; **strato eventi interamente inerte**; **nessuno scheduler**.
- **Nessuna installazione Bagisto** nel repo: niente tema, storefront, checkout, account.
- Delle integrazioni citate (Amazon, eBay, Kaufland, Cdiscount, Google, Stripe, ERP),
  **solo Amazon** esiste, come **prototipo deterministico su un file di esempio**.

## Stato per area (§2)

Percentuali riferite a **codice realmente presente ed eseguibile**, non a intenzioni.
Stabilità: 🟢 stabile · 🟡 prototipo · 🔴 rotto/stub · ⚪ inesistente.

| Area | Completata | Stato | Stab. | Nota |
|---|---:|---|:---:|---|
| Autenticazione **API (HMAC)** | ~70% | funzionante | 🟢 | manca il Bearer richiesto dal contratto |
| Autenticazione **utenti/vendor** | 0% | non iniziata | ⚪ | nessun login storefront/vendor |
| Utenti (account cliente) | 0% | non iniziata | ⚪ | non nel repo |
| **Vendor** | ~15% | in sviluppo | 🟡 | solo anagrafica staging (CRUD); niente vetrine/onboarding |
| Catalogo / **Prodotti** | ~15% | in sviluppo | 🔴 | bug fatale; scrive solo staging, non crea prodotti Bagisto |
| Categorie | ~20% | in sviluppo | 🟡 | mapping in staging; non crea categorie Bagisto (salvo prototipo) |
| PIM | ~10% | in sviluppo | 🟡 | sottosistema mapping attributi; non crea attributi Bagisto |
| Media / **Immagini** | 0% | non iniziata | ⚪ | import non scarica immagini; solo piano |
| **AI** | 0% | non iniziata | ⚪ | nessun componente AI |
| Traduzioni | 0% | non iniziata | ⚪ | risolte dall'Hub; lang dir del package inesistente |
| Ricerca | 0% | non iniziata | ⚪ | — |
| **Ordini** | 0% | non iniziata | ⚪ | il cuore del contratto, assente |
| Wallet / crediti | 0% | non iniziata | ⚪ | solo il dato `maxApplicableValue` dal contratto |
| Pagamenti | 0% | non iniziata | ⚪ | solo un enum nel contratto |
| Spedizioni | ~30% | in sviluppo | 🟡 | zone/tariffe/routing in staging; niente corrieri; dominio da rimuovere |
| Google Commerce | ~10% | in sviluppo | 🟡 | solo modello dati tassonomia; nessuna integrazione |
| Marketplace (storefront multi-vendor) | ~5% | non iniziata | ⚪ | nessun tema/vetrina |
| Analytics | 0% | non iniziata | ⚪ | — |
| Notifiche | 0% | non iniziata | ⚪ | citate nel README, non implementate |
| **Dashboard** (admin operatore) | ~25% | in sviluppo | 🟡 | sync-jobs + mapping; senza menu/ACL |
| **API** (superficie) | ~40% | in sviluppo | 🔴 | costruita ma direzione errata; largamente da eliminare |

**Media pesata onesta della piattaforma: bassa a una cifra percentuale.** Le poche aree
sopra il 20% sono anche quelle che il piano di progetto prevede di rimuovere.

## Punti di forza

1. **Disciplina di specifica recente**: il contratto v3.1 chiude sei divergenze reali e
   il mock lo rende **eseguibile e verificabile** (63 controlli). Raro e prezioso.
2. **Middleware HMAC** ben scritto e testato (fail-closed, anti-replay, tempo costante).
3. **Codice ordinato e leggibile**: naming coerente, metodi corti, pattern standard —
   una buona base *stilistica* per chi riscriverà.
4. **Onestà documentale interna**: i documenti di progetto (riscrittura, handover)
   riconoscono già gli errori architetturali. Il team sa dov'è il problema.

## Rischi

1. **Rischio "documentazione ≠ prodotto"**: molta della sostanza recente è specifica e
   scaffolding; il codice che *fa* qualcosa è poco e in parte rotto. Facile
   sovrastimare la maturità leggendo i documenti.
2. **README del package fuorviante**: dichiara Bearer, conteggi di migration/controller
   errati, e un'architettura che il progetto ha abbandonato. Chi si fida del README
   costruisce sopra un'illusione.
3. **Architettura invertita**: l'intera API di scrittura è concettualmente da buttare;
   il costo di riscrittura è reale, anche se contenuto perché nulla è in produzione.
4. **Dipendenza da un Hub inesistente**: senza l'Hub reale (oggi solo contratto+mock),
   la piattaforma non va in produzione, per quanto buono sia il connettore.
5. **Assenza di collaudo**: 1 file di test e un modulo che rispondeva 500 al 100% senza
   che nessuno se ne accorgesse → processo senza integration testing.
6. **Domini core inesistenti** (ordini, pagamenti, wallet, storefront): il grosso del
   valore di prodotto è ancora tutto da costruire.

## Priorità (sintesi; dettaglio in [14-Roadmap.md](14-Roadmap.md))

1. **Decidere formalmente la direzione** (riscrittura verso il pull del contratto) e la
   versione Bagisto.
2. **Client di lettura del catalogo Hub** + **ponte verso il catalogo Bagisto reale**.
3. **Flusso ordini** con test.
4. **Chiudere le lacune di sicurezza** (Bearer, ACL) e i bug bloccanti (import, webhook).
5. **Ripulire il codice morto** e allineare la documentazione al codice.

## Voto tecnico complessivo: **4 / 10**

Non per cattiva scrittura — lo stile è discreto — ma perché il sistema **non funziona
end-to-end, implementa l'architettura sbagliata, ha un bug fatale sul percorso
principale, componenti chiave stub, uno strato inerte e quasi nessun test.** Come *base
per una riscrittura guidata dal contratto* vale di più della sua somma; come *prodotto*
è pre-funzionale.

## Cosa deve sapere un nuovo CTO, in una pagina (§25)

- **Non stai ereditando un marketplace funzionante.** Stai ereditando un **contratto
  eccellente**, un **mock che è il criterio di collaudo dell'Hub**, e un **connettore in
  gran parte da rifare**. Tratta il repo come "specifica + scaffolding", non come prodotto.
- **La fonte di verità è `main` e il contratto v3.1**, non il README del package (che è
  fuorviante) né la memoria di chi ha sviluppato.
- **L'Hub Iwexa non esiste**: è un progetto parallelo con un proprio committente e
  tempistica. Senza di esso non si rilascia.
- **Tieni**: middleware HMAC, mock+suite, mapper Amazon, tracciamento sync-job/webhook,
  la UI sync-jobs. **Rifai**: client Hub (in lettura), import→Bagisto, ordini. **Elimina**:
  la superficie di scrittura inbound, il client morto, gli eventi inerti.
- **Terminologia**: nel codice è **Vendor / Vendor Store / Vendor Warehouse**; nella UI
  "Vendor" diventa **"Maestro"** — solo branding, non tocca l'architettura.
- **Il rischio maggiore non è tecnico ma di percezione**: la distanza fra quanto il
  progetto *sembra* avanzato (per via dei documenti) e quanto *è* avanzato (per via del
  codice) è ampia. Pianifica sul secondo.
- **La cosa fatta giusta**: negli ultimi giorni il progetto ha smesso di scrivere codice
  al buio e ha costruito una specifica eseguibile con collaudo automatico. È la
  fondazione corretta su cui ricostruire — usala, non ripartire da zero sulla specifica.
</content>
