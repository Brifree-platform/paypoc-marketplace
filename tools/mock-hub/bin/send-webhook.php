<?php

declare(strict_types=1);

/**
 * Invia a PayPoc un webhook firmato, come farà l'Hub vero.
 *
 *   php bin/send-webhook.php productUpdated
 *   php bin/send-webhook.php stockChanged
 *   php bin/send-webhook.php orderStatusChanged
 *   php bin/send-webhook.php stockChanged http://127.0.0.1:8899/bagisto-api/iwexa/webhooks
 */

require __DIR__ . '/../src/Hub.php';

$hub   = new Hub(__DIR__ . '/../data', __DIR__ . '/../runtime');
$event = $argv[1] ?? 'productUpdated';
$url   = $argv[2] ?? 'http://127.0.0.1:8899/bagisto-api/iwexa/webhooks';

$products = $hub->products();
$country  = getenv('PAYPOC_COUNTRY') ?: Hub::DEFAULT_COUNTRY;
$locale   = getenv('PAYPOC_LOCALE') ?: Hub::DEFAULT_LOCALE;

$payloads = [
    // Il webhook productUpdated porta lo schema Product già risolto per
    // paese e lingua, esattamente come GET /products (decisioni 1 e 5).
    'productUpdated' => $hub->project2($products[0], $country, $locale),

    'stockChanged' => [
        'externalProductId' => $products[1]['externalProductId'],
        'inStock'           => false,
        'quantity'          => 0,
    ],

    'orderStatusChanged' => [
        'orderId'   => 'ORD-EXAMPLE1',
        'status'    => 'shipped',
        'shipments' => [[
            'shipmentId'        => 'SHP-EXAMPLE1',
            'vendorCode'        => $products[0]['vendorCode'],
            'type'              => $products[0]['fulfillment']['type'],
            'status'            => 'shipped',
            'trackingNumber'    => 'PPC1234567890',
            // Decisione 3: il tracking è su dominio PayPoc, mai Iwexa/ShippyPro
            'trackingUrl'       => 'https://tracking.paypoc.example/PPC1234567890',
            'carrier'           => 'BRT',
            'estimatedDelivery' => date('Y-m-d', strtotime('+3 days')),
            'items'             => [['externalProductId' => $products[0]['externalProductId'], 'quantity' => 1]],
        ]],
        'totals' => ['subtotal' => 2.98, 'shipping' => 4.5, 'walletApplied' => 0, 'total' => 7.48],
    ],
];

if (! isset($payloads[$event])) {
    fwrite(STDERR, "Evento sconosciuto: $event\nDisponibili: " . implode(', ', array_keys($payloads)) . "\n");
    exit(1);
}

$body      = json_encode(['id' => 'evt_' . bin2hex(random_bytes(4)), 'type' => $event, 'data' => $payloads[$event]],
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$timestamp = time();
$signature = $hub->signPayload($body, $timestamp);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $body,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Accept: application/json',
        'X-Iwexa-Signature: ' . $signature,
        'X-Iwexa-Timestamp: ' . $timestamp,
        'X-Iwexa-Event: ' . $event,
        'X-Iwexa-Delivery-Id: dlv_' . bin2hex(random_bytes(4)),
    ],
]);

$response = curl_exec($ch);
$status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "→ $event verso $url\n";
echo "← HTTP $status\n";
echo substr((string) $response, 0, 500) . "\n";

exit($status >= 200 && $status < 300 ? 0 : 1);
