<?php

/**
 * Mock Iwexa Hub — implementa iwexa_hub_openapi.yaml v3.0
 *
 * Riferimento eseguibile del contratto Iwexa <-> PayPoc. Serve a due scopi:
 *  1. permettere lo sviluppo del connettore PayPoc contro qualcosa di reale
 *  2. fare da criterio di accettazione per l'Hub vero (vedi ../README.md)
 *
 * Avvio:  php -S 127.0.0.1:8800 -t public
 * Nessuna dipendenza esterna: PHP 8.1+ e basta.
 */

declare(strict_types=1);

require __DIR__ . '/../src/Hub.php';

$hub = new Hub(__DIR__ . '/../data', __DIR__ . '/../runtime');

$method = $_SERVER['REQUEST_METHOD'];
$path   = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/', '/') ?: '/';
$body   = file_get_contents('php://input') ?: '';

header('Content-Type: application/json; charset=utf-8');

// --- Autenticazione HMAC ------------------------------------------------
// Il contratto (securitySchemes.HmacAuth) firma il solo body. Qui firmiamo
// body + timestamp: senza timestamp una richiesta catturata resta
// riutilizzabile per sempre. Vedi PIANO-RISCRITTURA.md §5 — decisione da
// riportare nella specifica prima che l'Hub vero venga scritto.
if (! $hub->verifySignature($body, $_SERVER)) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid or missing signature'], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- Routing ------------------------------------------------------------
$routes = [
    ['GET',  '#^/api/v1/products$#',                    fn ($m) => $hub->listProducts($_GET)],
    ['GET',  '#^/api/v1/products/([\w-]+)$#',           fn ($m) => $hub->getProduct($m[1])],
    ['GET',  '#^/api/v1/stock/([\w-]+)$#',              fn ($m) => $hub->getStock($m[1])],
    ['GET',  '#^/api/v1/vendors$#',                     fn ($m) => $hub->listVendors()],
    ['GET',  '#^/api/v1/shipping-quote$#',              fn ($m) => $hub->shippingQuote($_GET)],
    ['GET',  '#^/api/v1/taxonomy$#',                    fn ($m) => $hub->taxonomy($_GET)],
    ['POST', '#^/api/v1/orders$#',                      fn ($m) => $hub->createOrder($body)],
    ['GET',  '#^/api/v1/orders/([\w-]+)$#',             fn ($m) => $hub->getOrder($m[1])],
    ['POST', '#^/api/v1/orders/([\w-]+)/cancel$#',      fn ($m) => $hub->cancelOrder($m[1])],
    ['POST', '#^/api/v1/orders/([\w-]+)/return$#',      fn ($m) => $hub->returnOrder($m[1], $body)],
];

foreach ($routes as [$verb, $pattern, $handler]) {
    if ($method === $verb && preg_match($pattern, $path, $matches)) {
        [$status, $payload] = $handler($matches);
        http_response_code($status);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
        exit;
    }
}

http_response_code(404);
echo json_encode(['error' => 'Not found', 'path' => $path], JSON_UNESCAPED_UNICODE);
