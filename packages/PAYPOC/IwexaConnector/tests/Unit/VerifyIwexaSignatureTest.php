<?php

use Illuminate\Http\Request;
use Webkul\PAYPOC\IwexaConnector\Http\Middleware\VerifyIwexaSignature;

const TEST_SECRET = 'secret-di-test-non-usare-in-produzione';

/**
 * Builds a signed (or deliberately mis-signed) request and runs it through the
 * middleware, returning the resulting status code. 200 means the request was
 * let through to the controller.
 */
function runMiddleware(array $options = []): int
{
    $body = $options['body'] ?? '{"vendor_code":"TEST"}';
    $timestamp = (string) ($options['timestamp'] ?? time());
    $signature = $options['signature'] ?? hash_hmac('sha256', $body . $timestamp, TEST_SECRET);

    $request = Request::create('/bagisto-api/iwexa/vendors', 'POST', [], [], [], [], $body);

    if ($signature !== null) {
        $request->headers->set(VerifyIwexaSignature::SIGNATURE_HEADER, $signature);
    }

    if (! ($options['omitTimestamp'] ?? false)) {
        $request->headers->set(VerifyIwexaSignature::TIMESTAMP_HEADER, $timestamp);
    }

    return (new VerifyIwexaSignature())
        ->handle($request, fn () => response()->json(['ok' => true]))
        ->getStatusCode();
}

beforeEach(function () {
    config()->set('iwexa-connector.iwexa_hmac_secret', TEST_SECRET);
    config()->set('iwexa-connector.signature_tolerance_seconds', 300);
});

it('accetta una richiesta firmata correttamente', function () {
    expect(runMiddleware())->toBe(200);
});

it('rifiuta una richiesta senza header di firma', function () {
    expect(runMiddleware(['signature' => null, 'omitTimestamp' => true]))->toBe(401);
});

it('rifiuta una firma non valida', function () {
    expect(runMiddleware(['signature' => 'deadbeef']))->toBe(401);
});

it('rifiuta se il body viene alterato dopo la firma', function () {
    $signature = hash_hmac('sha256', '{"vendor_code":"ORIGINALE"}' . time(), TEST_SECRET);

    expect(runMiddleware([
        'body'      => '{"vendor_code":"ALTERATO"}',
        'signature' => $signature,
    ]))->toBe(401);
});

it('rifiuta un timestamp fuori dalla finestra di tolleranza (replay)', function () {
    expect(runMiddleware(['timestamp' => time() - 3600]))->toBe(401);
});

it('rifiuta un timestamp troppo avanti nel futuro', function () {
    expect(runMiddleware(['timestamp' => time() + 3600]))->toBe(401);
});

it('accetta uno scarto di orologio dentro la tolleranza', function () {
    expect(runMiddleware(['timestamp' => time() - 120]))->toBe(200);
});

it('rifiuta un timestamp non numerico', function () {
    expect(runMiddleware(['timestamp' => 'non-un-numero']))->toBe(401);
});

it('firma correttamente una richiesta senza body', function () {
    expect(runMiddleware(['body' => '']))->toBe(200);
});

it('fallisce chiuso quando il secret non e configurato', function () {
    config()->set('iwexa-connector.iwexa_hmac_secret', '');

    // Deve rifiutare (503), non lasciar passare: un .env incompleto
    // non deve mai tradursi in API aperte.
    expect(runMiddleware())->toBe(503);
});
