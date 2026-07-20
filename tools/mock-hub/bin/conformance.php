<?php

declare(strict_types=1);

/**
 * Suite di conformità al contratto Iwexa <-> PayPoc v3.1.
 *
 * Non testa il mock: testa QUALUNQUE Hub. È il criterio di accettazione
 * dell'Hub vero — si punta a un altro base URL e deve passare identica.
 *
 *   php bin/conformance.php                          # contro il mock locale
 *   php bin/conformance.php https://hub-staging...   # contro l'Hub vero
 */

$baseUrl = rtrim($argv[1] ?? 'http://127.0.0.1:8800/api/v1', '/');
$secret  = getenv('IWEXA_HMAC_SECRET') ?: 'mock-hub-shared-secret';
$apiKey  = getenv('IWEXA_API_KEY') ?: 'mock-hub-api-key';

// EAN di riferimento nelle fixture (decisione 2: l'identità è l'EAN)
const EAN_PASTA    = '8076809513722';  // Barilla, FBI, soglia 39
const EAN_PASSATA  = '8005110002106';  // Mutti, FBV, alwaysFree con cost 6.90
const EAN_PROFUMO  = '8059777990122';  // Gea Profumi, FBV, hazmat UN1266
const EAN_ESAURITO = '0194644092009';  // Anker, fuori stock

$passed = 0;
$failed = 0;

/**
 * @param array<string,string|null> $override header da sostituire; null = ometti
 */
function req(string $method, string $path, ?array $body = null, array $override = []): array
{
    global $baseUrl, $secret, $apiKey;

    $payload   = $body === null ? '' : json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $timestamp = (string) time();
    $signature = hash_hmac('sha256', $payload . $timestamp, $secret);

    $map = [
        'Content-Type'      => 'application/json',
        'Authorization'     => 'Bearer ' . $apiKey,
        'X-Iwexa-Signature' => $signature,
        'X-Iwexa-Timestamp' => $timestamp,
    ];

    foreach ($override as $name => $value) {
        if ($value === null) {
            unset($map[$name]);
        } else {
            $map[$name] = $value;
        }
    }

    $headers = [];
    foreach ($map as $name => $value) {
        $headers[] = "$name: $value";
    }

    $ch = curl_init($baseUrl . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => 10,
    ]);

    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    }

    $raw    = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [$status, json_decode((string) $raw, true), (string) $raw];
}

function check(string $name, bool $condition, string $detail = ''): void
{
    global $passed, $failed;

    if ($condition) {
        $passed++;
        echo "  \033[32m✓\033[0m $name\n";
    } else {
        $failed++;
        echo "  \033[31m✗\033[0m $name" . ($detail ? " — $detail" : '') . "\n";
    }
}

function section(string $title): void
{
    echo "\n\033[1m$title\033[0m\n";
}

echo "Conformance suite — contratto Iwexa <-> PayPoc v3.1\n";
echo "Target: $baseUrl\n";

// --- Sicurezza (decisione 4: Bearer + HMAC + anti-replay) ---------------
section('Autenticazione — Bearer + HMAC + finestra anti-replay');

[$s] = req('GET', '/products', null, ['Authorization' => null]);
check('Bearer mancante respinto con 401', $s === 401, "ricevuto $s");

[$s] = req('GET', '/products', null, ['Authorization' => 'Bearer chiave-sbagliata']);
check('Bearer errato respinto con 401', $s === 401, "ricevuto $s");

[$s] = req('GET', '/products', null, ['X-Iwexa-Signature' => 'deadbeef']);
check('Firma non valida respinta con 401', $s === 401, "ricevuto $s");

[$s] = req('GET', '/products', null, ['X-Iwexa-Signature' => null, 'X-Iwexa-Timestamp' => null]);
check('Firma mancante respinta con 401', $s === 401, "ricevuto $s");

[$s] = req('GET', '/products', null, ['X-Iwexa-Timestamp' => (string) (time() - 3600)]);
check('Timestamp fuori tolleranza respinto con 401 (replay)', $s === 401, "ricevuto $s");

// --- Catalogo -----------------------------------------------------------
section('Catalogo');

[$s, $list] = req('GET', '/products?country=IT&locale=it');
check('GET /products risponde 200', $s === 200, "ricevuto $s");
check('Risposta ha items[] e total', isset($list['items'], $list['total']));

$required = [
    'externalProductId', 'productSlug', 'vendorCode', 'vendorName', 'name',
    'listPrice', 'sellPrice', 'vatRate', 'maxApplicableValue', 'currency',
    'inStock', 'fulfillment', 'shippingPolicy', 'category', 'updatedAt',
];

$product = $list['items'][0] ?? [];
$missing = array_values(array_filter($required, fn ($f) => ! array_key_exists($f, $product)));
check('Product ha tutti i campi obbligatori', $missing === [], 'mancanti: ' . implode(', ', $missing));

check('currency è EUR', ($product['currency'] ?? null) === 'EUR');
check('fulfillment.type è FBI o FBV', in_array($product['fulfillment']['type'] ?? null, ['FBI', 'FBV'], true));

// Decisione 2 — l'identità è l'EAN
section('Identità prodotto — EAN (decisione 2)');

$eanProblems = [];
foreach ($list['items'] as $p) {
    if (! preg_match('/^\d{8}$|^\d{13}$/', (string) $p['externalProductId'])) {
        $eanProblems[] = $p['externalProductId'];
    }
}
check('externalProductId è un EAN valido (8 o 13 cifre)', $eanProblems === [], implode(', ', $eanProblems));

[$s, $byEan] = req('GET', '/products/' . EAN_PROFUMO . '?country=IT');
check('GET /products/{ean} risolve per EAN', $s === 200 && ($byEan['externalProductId'] ?? null) === EAN_PROFUMO, "ricevuto $s");

[$s, $bySlug] = req('GET', '/products/' . ($byEan['productSlug'] ?? 'x') . '?country=IT');
check('Lo slug resta utilizzabile per gli URL', $s === 200 && ($bySlug['externalProductId'] ?? null) === EAN_PROFUMO);

// Decisione 1 — valore singolo risolto per paese
section('Prezzi e IVA per paese (decisione 1)');

[, $it] = req('GET', '/products/' . EAN_PROFUMO . '?country=IT');
[, $fr] = req('GET', '/products/' . EAN_PROFUMO . '?country=FR');
[, $de] = req('GET', '/products/' . EAN_PROFUMO . '?country=DE');

check('listPrice è un numero singolo, non un oggetto', is_numeric($it['listPrice'] ?? null));
check('vatRate è un numero singolo, non un oggetto', is_numeric($it['vatRate'] ?? null));
check('IVA italiana 0.22', ($it['vatRate'] ?? null) == 0.22, 'ricevuto ' . json_encode($it['vatRate'] ?? null));
check('IVA francese 0.20', ($fr['vatRate'] ?? null) == 0.20, 'ricevuto ' . json_encode($fr['vatRate'] ?? null));
check('IVA tedesca 0.19', ($de['vatRate'] ?? null) == 0.19, 'ricevuto ' . json_encode($de['vatRate'] ?? null));
check('Il prezzo cambia con il paese', ($it['listPrice'] ?? 0) != ($fr['listPrice'] ?? 0));
check('La risposta dichiara il paese risolto', ($it['country'] ?? null) === 'IT');
check('maxApplicableValue è il 40% di listPrice',
    abs((float) ($it['maxApplicableValue'] ?? 0) - (float) $it['listPrice'] * 0.40) < 0.01,
    ($it['maxApplicableValue'] ?? '?') . ' vs ' . ((float) ($it['listPrice'] ?? 0) * 0.4));

[$s] = req('GET', '/products/' . EAN_PROFUMO . '?country=ZZ');
check('Paese non gestito respinto con 422', $s === 422, "ricevuto $s");

// Decisione 5 — multi-lingua
section('Contenuto multi-lingua (decisione 5)');

[, $localeIt] = req('GET', '/products/' . EAN_PASTA . '?country=IT&locale=it');
[, $localeEn] = req('GET', '/products/' . EAN_PASTA . '?country=IT&locale=en');

check('Il nome cambia con il locale', ($localeIt['name'] ?? '') !== ($localeEn['name'] ?? ''),
    ($localeIt['name'] ?? '?') . ' / ' . ($localeEn['name'] ?? '?'));
check('localizedPath è un array di stringhe risolto per locale',
    isset($localeIt['category']['localizedPath'][0]) && is_string($localeIt['category']['localizedPath'][0]));
check('localizedPath cambia con il locale',
    ($localeIt['category']['localizedPath'] ?? []) !== ($localeEn['category']['localizedPath'] ?? []));

// Decisione 6 — sync incrementale
section('Sync incrementale (decisione 6)');

[$s, $all]    = req('GET', '/products?country=IT');
[$s2, $delta] = req('GET', '/products?country=IT&updated_since=2026-07-19T00:00:00Z');

check('updated_since accettato', $s2 === 200, "ricevuto $s2");
check('Il delta è più piccolo del catalogo completo',
    ($delta['total'] ?? 0) < ($all['total'] ?? 0),
    'delta ' . ($delta['total'] ?? '?') . ' / totale ' . ($all['total'] ?? '?'));
check('Il delta contiene solo prodotti più recenti della data',
    ! array_filter($delta['items'] ?? [], fn ($p) => strtotime($p['updatedAt']) <= strtotime('2026-07-19T00:00:00Z')));

[$s] = req('GET', '/products?country=IT&updated_since=non-una-data');
check('updated_since non valido respinto con 422', $s === 422, "ricevuto $s");

// Decisione 3 — Iwexa non deve mai comparire al cliente
section('Iwexa invisibile al cliente (decisione 3)');

[, , $rawList] = req('GET', '/products?country=IT&locale=it');
check('Nessuna occorrenza di "iwexa" nel payload catalogo',
    stripos($rawList, 'iwexa') === false,
    'trovata a offset ' . stripos($rawList, 'iwexa'));

$fbi = null;
foreach ($all['items'] ?? [] as $p) {
    if (($p['fulfillment']['type'] ?? '') === 'FBI') {
        $fbi = $p;
        break;
    }
}
check('Il magazzino FBI non è brandizzato Iwexa',
    $fbi !== null && stripos((string) $fbi['fulfillment']['warehouseCode'], 'iwexa') === false,
    $fbi['fulfillment']['warehouseCode'] ?? 'nessun prodotto FBI nelle fixture');

// Dati regolatori
section('Dati regolatori (hazmat e GPSR)');

[, $profumo] = req('GET', '/products/' . EAN_PROFUMO . '?country=IT');
check('hazmat presente sui prodotti che lo richiedono',
    ($profumo['hazmat']['unRegulatoryId'] ?? null) === 'UN1266',
    json_encode($profumo['hazmat'] ?? null));
check('hazmat dichiara la classe di trasporto', isset($profumo['hazmat']['transportClass']));
check('compliance ha ingredienti e avvertenze (obbligatori UE sui cosmetici)',
    ! empty($profumo['compliance']['ingredients']) && ! empty($profumo['compliance']['safetyWarning']));
check('compliance ha il contatto del produttore', ! empty($profumo['compliance']['contactEmail']));

// --- Stock, vendor, tassonomia ------------------------------------------
section('Stock, vendor e tassonomia');

[$s, $stock] = req('GET', '/stock/' . EAN_PASTA);
check('GET /stock/{ean} risponde 200', $s === 200, "ricevuto $s");
check('Stock ha inStock booleano', isset($stock['inStock']) && is_bool($stock['inStock']));

[$s] = req('GET', '/products/0000000000000?country=IT');
check('EAN inesistente risponde 404', $s === 404, "ricevuto $s");

[$s, $vendors] = req('GET', '/vendors');
check('GET /vendors risponde 200', $s === 200, "ricevuto $s");
check('Vendor hanno vendorCode e vendorName', isset($vendors[0]['vendorCode'], $vendors[0]['vendorName']));

[$s, $tax] = req('GET', '/taxonomy?locale=it');
check('GET /taxonomy risponde 200', $s === 200, "ricevuto $s");
check('Tassonomia risolve localizedPath per locale',
    isset($tax['nodes'][0]['localizedPath'][0]) && is_string($tax['nodes'][0]['localizedPath'][0]));

// --- Spedizioni ---------------------------------------------------------
section('Spedizioni — soglia PER PACCHETTO, non per ordine');

$cart = json_encode([['externalProductId' => EAN_PASTA, 'quantity' => 1]]);
[$s, $quote] = req('GET', '/shipping-quote?country=IT&cart=' . urlencode($cart));
check('GET /shipping-quote risponde 200', $s === 200, "ricevuto $s");
check('Quote raggruppata per pacchetto/vendor', isset($quote['packages'][0]['vendorCode']));
check('Sotto soglia la spedizione si paga', ($quote['packages'][0]['cost'] ?? 0) > 0);

$cart = json_encode([['externalProductId' => EAN_PASTA, 'quantity' => 40]]);
[, $quote] = req('GET', '/shipping-quote?country=IT&cart=' . urlencode($cart));
check('Sopra soglia la spedizione è gratis', ($quote['packages'][0]['cost'] ?? -1) == 0.0);

$cart = json_encode([['externalProductId' => EAN_PASSATA, 'quantity' => 1]]);
[, $quote] = req('GET', '/shipping-quote?country=IT&cart=' . urlencode($cart));
check('alwaysFree ignora costo e soglia',
    ($quote['packages'][0]['cost'] ?? -1) == 0.0 && ($quote['packages'][0]['alwaysFree'] ?? false) === true);

$cart = json_encode([
    ['externalProductId' => EAN_PASTA, 'quantity' => 1],
    ['externalProductId' => EAN_PROFUMO, 'quantity' => 1],
]);
[, $quote] = req('GET', '/shipping-quote?country=IT&cart=' . urlencode($cart));
check('Vendor diversi generano pacchetti distinti', count($quote['packages'] ?? []) === 2,
    'pacchetti: ' . count($quote['packages'] ?? []));

[, $quoteIt] = req('GET', '/shipping-quote?country=IT&cart=' . urlencode(json_encode([['externalProductId' => EAN_PASTA, 'quantity' => 1]])));
[, $quoteFr] = req('GET', '/shipping-quote?country=FR&cart=' . urlencode(json_encode([['externalProductId' => EAN_PASTA, 'quantity' => 1]])));
check('Il costo di spedizione cambia con il paese',
    ($quoteIt['packages'][0]['cost'] ?? 0) != ($quoteFr['packages'][0]['cost'] ?? 0));

// --- Ordini -------------------------------------------------------------
section('Ordini');

$orderPayload = [
    'userId'          => 'USR-TEST-1',
    'shippingAddress' => ['name' => 'Mario Rossi', 'street' => 'Via Roma 1', 'city' => 'Milano', 'zip' => '20100', 'country' => 'IT', 'phone' => '+390000000'],
    'items'           => [
        ['externalProductId' => EAN_PASTA, 'vendorCode' => '1019', 'quantity' => 2, 'listPriceSnapshot' => 1.89, 'sellPriceSnapshot' => 1.49],
        ['externalProductId' => EAN_PROFUMO, 'vendorCode' => '2044', 'quantity' => 1, 'listPriceSnapshot' => 170.0, 'sellPriceSnapshot' => 149.0],
    ],
    'walletApplied'   => 0,
    'totalAgreed'     => 151.98,
    'paymentMethod'   => 'card',
    'paymentToken'    => 'tok_test_123',
];

[$s, $order] = req('POST', '/orders', $orderPayload);
check('POST /orders risponde 201', $s === 201, "ricevuto $s");
check('Ordine ha orderId e status', isset($order['orderId'], $order['status']));
check('Split in una spedizione per vendor', count($order['shipments'] ?? []) === 2,
    'spedizioni: ' . count($order['shipments'] ?? []));

$shipmentRequired = ['shipmentId', 'vendorCode', 'type', 'status', 'estimatedDelivery', 'items'];
$shipmentProblems = [];

foreach ($order['shipments'] ?? [] as $i => $sh) {
    foreach ($shipmentRequired as $field) {
        if (! isset($sh[$field]) || $sh[$field] === '' || $sh[$field] === []) {
            $shipmentProblems[] = "shipments[$i].$field";
        }
    }

    if (! in_array($sh['type'] ?? null, ['FBI', 'FBV'], true)) {
        $shipmentProblems[] = "shipments[$i].type non è FBI/FBV";
    }
}

check('Ogni spedizione ha i campi richiesti dallo schema Shipment',
    $shipmentProblems === [], implode(', ', $shipmentProblems));

check('totals ha subtotal/shipping/walletApplied/total',
    isset($order['totals']['subtotal'], $order['totals']['shipping'], $order['totals']['walletApplied'], $order['totals']['total']));

$orderId = $order['orderId'] ?? '';

[$s, $fetched] = req('GET', '/orders/' . $orderId);
check('GET /orders/{id} risponde 200', $s === 200, "ricevuto $s");
check('Ordine recuperato con le spedizioni', count($fetched['shipments'] ?? []) === 2);

[$s] = req('POST', '/orders', ['userId' => 'X']);
check('Ordine incompleto respinto con 422', $s === 422, "ricevuto $s");

[$s] = req('POST', '/orders', array_merge($orderPayload, [
    'items' => [['externalProductId' => EAN_ESAURITO, 'vendorCode' => '3001', 'quantity' => 1, 'listPriceSnapshot' => 49.9, 'sellPriceSnapshot' => 39.9]],
]));
check('Prodotto esaurito respinto con 409', $s === 409, "ricevuto $s");

[$s] = req('POST', '/orders', array_merge($orderPayload, [
    'items' => [['externalProductId' => EAN_PROFUMO, 'vendorCode' => '2044', 'quantity' => 999, 'listPriceSnapshot' => 170, 'sellPriceSnapshot' => 149]],
]));
check('Quantità oltre disponibilità respinta con 409', $s === 409, "ricevuto $s");

[$s, $cancelled] = req('POST', '/orders/' . $orderId . '/cancel');
check('POST /orders/{id}/cancel risponde 200', $s === 200, "ricevuto $s");
check('Cancellazione ripristina lo stock', ($cancelled['stockRestored'] ?? false) === true);

[$s] = req('POST', '/orders/' . $orderId . '/return', [
    'items'  => [['externalProductId' => EAN_PASTA, 'quantity' => 1]],
    'reason' => 'Prodotto danneggiato',
]);
check('POST /orders/{id}/return risponde 200', $s === 200, "ricevuto $s");

[$s] = req('GET', '/orders/ORD-INESISTENTE');
check('Ordine inesistente risponde 404', $s === 404, "ricevuto $s");

// --- Esito --------------------------------------------------------------
echo "\n" . str_repeat('─', 56) . "\n";
printf("  %d superati, %d falliti\n", $passed, $failed);
echo str_repeat('─', 56) . "\n";

exit($failed === 0 ? 0 : 1);
