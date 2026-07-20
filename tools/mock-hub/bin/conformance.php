<?php

declare(strict_types=1);

/**
 * Suite di conformità al contratto Iwexa <-> PayPoc v3.0.
 *
 * Non testa il mock: testa QUALUNQUE Hub. È il criterio di accettazione
 * dell'Hub vero — si punta a un altro base URL e deve passare identica.
 *
 *   php bin/conformance.php                          # contro il mock locale
 *   php bin/conformance.php https://hub-staging...   # contro l'Hub vero
 */

$baseUrl = rtrim($argv[1] ?? 'http://127.0.0.1:8800/api/v1', '/');
$secret  = getenv('IWEXA_HMAC_SECRET') ?: 'mock-hub-shared-secret';

$passed = 0;
$failed = 0;

/**
 * @param array<string,string|null> $override header da sostituire; null = ometti
 */
function req(string $method, string $path, ?array $body = null, array $override = []): array
{
    global $baseUrl, $secret;

    $payload   = $body === null ? '' : json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $timestamp = (string) time();
    $signature = hash_hmac('sha256', $payload . $timestamp, $secret);

    // Mappa nome => valore, così un override sostituisce davvero l'header
    // invece di aggiungersi accanto a quello valido.
    $map = [
        'Content-Type'      => 'application/json',
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

    return [$status, json_decode((string) $raw, true)];
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

echo "Conformance suite — contratto Iwexa <-> PayPoc v3.0\n";
echo "Target: $baseUrl\n";

// --- Sicurezza ----------------------------------------------------------
section('Autenticazione');

[$s] = req('GET', '/products', null, ['X-Iwexa-Signature' => 'deadbeef']);
check('Firma non valida respinta con 401', $s === 401, "ricevuto $s");

[$s] = req('GET', '/vendors', null, ['X-Iwexa-Signature' => null, 'X-Iwexa-Timestamp' => null]);
check('Firma mancante respinta con 401', $s === 401, "ricevuto $s");

[$s] = req('GET', '/vendors', null, ['X-Iwexa-Timestamp' => (string) (time() - 3600)]);
check('Timestamp fuori tolleranza respinto con 401 (replay)', $s === 401, "ricevuto $s");

// --- Catalogo -----------------------------------------------------------
section('Catalogo');

[$s, $list] = req('GET', '/products');
check('GET /products risponde 200', $s === 200, "ricevuto $s");
check('Risposta ha items[] e total', isset($list['items'], $list['total']));

$required = [
    'externalProductId', 'productSlug', 'vendorCode', 'vendorName', 'name',
    'listPrice', 'sellPrice', 'vatRate', 'maxApplicableValue', 'currency',
    'inStock', 'fulfillment', 'shippingPolicy', 'category',
];

$product = $list['items'][0] ?? [];
$missing = array_values(array_filter($required, fn ($f) => ! array_key_exists($f, $product)));
check('Product ha tutti i campi obbligatori', $missing === [], 'mancanti: ' . implode(', ', $missing));

check('category ha googleTaxonomyId/Path/localizedPath',
    isset($product['category']['googleTaxonomyId'], $product['category']['googleTaxonomyPath'], $product['category']['localizedPath']));

check('fulfillment.type è FBI o FBV',
    in_array($product['fulfillment']['type'] ?? null, ['FBI', 'FBV'], true));

check('currency è EUR', ($product['currency'] ?? null) === 'EUR');

// Validation Rules del field contract
section('Regole di validazione (field contract §Validation Rules)');

foreach ($list['items'] as $p) {
    $id = $p['externalProductId'];

    if ($p['listPrice'] < $p['sellPrice']) {
        check("[$id] listPrice >= sellPrice", false, "{$p['listPrice']} < {$p['sellPrice']}");
        continue;
    }

    if ($p['maxApplicableValue'] > $p['sellPrice']) {
        check("[$id] maxApplicableValue <= sellPrice", false, "{$p['maxApplicableValue']} > {$p['sellPrice']}");
        continue;
    }

    if ($p['vatRate'] < 0 || $p['vatRate'] > 1) {
        check("[$id] vatRate fra 0 e 1", false, (string) $p['vatRate']);
        continue;
    }

    $sp = $p['shippingPolicy'];
    if ($sp['alwaysFree'] === true && $sp['freeShippingThreshold'] !== null) {
        check("[$id] alwaysFree=true implica threshold null", false);
        continue;
    }
}
check('Tutti i prodotti rispettano le regole di validazione', true);

// --- Prodotto singolo e stock -------------------------------------------
section('Prodotto singolo e stock');

[$s, $single] = req('GET', '/products/' . $product['productSlug']);
check('GET /products/{slug} risponde 200', $s === 200, "ricevuto $s");
check('Ritorna il prodotto giusto', ($single['externalProductId'] ?? null) === $product['externalProductId']);

[$s] = req('GET', '/products/slug-inesistente-xyz');
check('Slug inesistente risponde 404', $s === 404, "ricevuto $s");

[$s, $stock] = req('GET', '/stock/' . $product['externalProductId']);
check('GET /stock/{id} risponde 200', $s === 200, "ricevuto $s");
check('Stock ha inStock booleano', isset($stock['inStock']) && is_bool($stock['inStock']));

// --- Vendor e tassonomia ------------------------------------------------
section('Vendor e tassonomia');

[$s, $vendors] = req('GET', '/vendors');
check('GET /vendors risponde 200', $s === 200, "ricevuto $s");
check('Vendor hanno vendorCode e vendorName',
    isset($vendors[0]['vendorCode'], $vendors[0]['vendorName']));

[$s, $tax] = req('GET', '/taxonomy?locale=it-IT');
check('GET /taxonomy risponde 200', $s === 200, "ricevuto $s");
check('Tassonomia ha nodi con localizedPath', ! empty($tax['nodes'][0]['localizedPath']));

// --- Spedizioni ---------------------------------------------------------
section('Spedizioni — soglia PER PACCHETTO, non per ordine');

$cart = json_encode([['externalProductId' => 'IWX-PRD-10001', 'quantity' => 1]]);
[$s, $quote] = req('GET', '/shipping-quote?country=IT&cart=' . urlencode($cart));
check('GET /shipping-quote risponde 200', $s === 200, "ricevuto $s");
check('Quote raggruppata per pacchetto/vendor', isset($quote['packages'][0]['vendorCode']));

$pkg = $quote['packages'][0] ?? [];
check('Sotto soglia la spedizione si paga', ($pkg['cost'] ?? 0) > 0, 'cost=' . ($pkg['cost'] ?? 'n/d'));

// Stesso vendor, quantità sopra la soglia di 39 EUR
$cart = json_encode([['externalProductId' => 'IWX-PRD-10001', 'quantity' => 40]]);
[, $quote] = req('GET', '/shipping-quote?country=IT&cart=' . urlencode($cart));
check('Sopra soglia la spedizione è gratis', ($quote['packages'][0]['cost'] ?? -1) == 0.0);

// Prodotto alwaysFree
$cart = json_encode([['externalProductId' => 'IWX-PRD-10003', 'quantity' => 1]]);
[, $quote] = req('GET', '/shipping-quote?country=IT&cart=' . urlencode($cart));
check('alwaysFree ignora la soglia', ($quote['packages'][0]['cost'] ?? -1) == 0.0
    && ($quote['packages'][0]['alwaysFree'] ?? false) === true);

// Due vendor distinti -> due pacchetti
$cart = json_encode([
    ['externalProductId' => 'IWX-PRD-10001', 'quantity' => 1],
    ['externalProductId' => 'IWX-PRD-20001', 'quantity' => 1],
]);
[, $quote] = req('GET', '/shipping-quote?country=IT&cart=' . urlencode($cart));
check('Vendor diversi generano pacchetti distinti', count($quote['packages'] ?? []) === 2,
    'pacchetti: ' . count($quote['packages'] ?? []));

// --- Ordini -------------------------------------------------------------
section('Ordini');

$orderPayload = [
    'userId'          => 'USR-TEST-1',
    'shippingAddress' => ['name' => 'Mario Rossi', 'street' => 'Via Roma 1', 'city' => 'Milano', 'zip' => '20100', 'country' => 'IT', 'phone' => '+390000000'],
    'items'           => [
        ['externalProductId' => 'IWX-PRD-10001', 'vendorCode' => '1019', 'quantity' => 2, 'listPriceSnapshot' => 1.89, 'sellPriceSnapshot' => 1.49],
        ['externalProductId' => 'IWX-PRD-20001', 'vendorCode' => '2011', 'quantity' => 1, 'listPriceSnapshot' => 7.90, 'sellPriceSnapshot' => 5.90],
    ],
    'walletApplied'   => 0,
    'totalAgreed'     => 8.88,
    'paymentMethod'   => 'card',
    'paymentToken'    => 'tok_test_123',
];

[$s, $order] = req('POST', '/orders', $orderPayload);
check('POST /orders risponde 201', $s === 201, "ricevuto $s");
check('Ordine ha orderId e status', isset($order['orderId'], $order['status']));
check('Split in una spedizione per vendor', count($order['shipments'] ?? []) === 2,
    'spedizioni: ' . count($order['shipments'] ?? []));
// Schema Shipment del contratto: shipmentId, vendorCode, type, status,
// estimatedDelivery, items sono tutti richiesti su OGNI spedizione.
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

// Campi obbligatori mancanti -> 422
[$s] = req('POST', '/orders', ['userId' => 'X']);
check('Ordine incompleto respinto con 422', $s === 422, "ricevuto $s");

// Prodotto esaurito -> 409
[$s] = req('POST', '/orders', array_merge($orderPayload, [
    'items' => [['externalProductId' => 'IWX-PRD-30002', 'vendorCode' => '3001', 'quantity' => 1, 'listPriceSnapshot' => 49.9, 'sellPriceSnapshot' => 39.9]],
]));
check('Prodotto esaurito respinto con 409', $s === 409, "ricevuto $s");

// Quantità oltre disponibilità -> 409
[$s] = req('POST', '/orders', array_merge($orderPayload, [
    'items' => [['externalProductId' => 'IWX-PRD-30001', 'vendorCode' => '3001', 'quantity' => 999, 'listPriceSnapshot' => 1299, 'sellPriceSnapshot' => 1149]],
]));
check('Quantità oltre disponibilità respinta con 409', $s === 409, "ricevuto $s");

// Cancel e return
[$s, $cancelled] = req('POST', '/orders/' . $orderId . '/cancel');
check('POST /orders/{id}/cancel risponde 200', $s === 200, "ricevuto $s");
check('Cancellazione ripristina lo stock', ($cancelled['stockRestored'] ?? false) === true);

[$s, $ret] = req('POST', '/orders/' . $orderId . '/return', [
    'items'  => [['externalProductId' => 'IWX-PRD-10001', 'quantity' => 1]],
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
