<?php

namespace Webkul\PAYPOC\IwexaConnector\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Verifies the HMAC-SHA256 signature on inbound Iwexa Hub requests.
 *
 * The signature is computed over the raw request body concatenated with the
 * timestamp, using the shared secret — the same scheme already used for
 * webhooks (see IwexaApiService::validateWebhookSignature).
 */
class VerifyIwexaSignature
{
    public const SIGNATURE_HEADER = 'X-IWEXA-SIGNATURE';

    public const TIMESTAMP_HEADER = 'X-IWEXA-TIMESTAMP';

    public function handle(Request $request, Closure $next)
    {
        $secret = (string) config('iwexa-connector.iwexa_hmac_secret');

        // Fail closed: an unconfigured secret must never mean "allow everything".
        if ($secret === '') {
            Log::channel(config('iwexa-connector.log_channel'))
                ->error('Iwexa HMAC secret is not configured; rejecting inbound request.', [
                    'path' => $request->path(),
                ]);

            return $this->deny('Signature verification unavailable', 503);
        }

        $signature = $request->header(self::SIGNATURE_HEADER);
        $timestamp = $request->header(self::TIMESTAMP_HEADER);

        if (! is_string($signature) || $signature === '' || ! is_string($timestamp) || $timestamp === '') {
            return $this->deny('Missing signature headers');
        }

        if (! $this->timestampIsFresh($timestamp)) {
            return $this->deny('Signature timestamp outside tolerance window');
        }

        $expected = hash_hmac('sha256', $request->getContent() . $timestamp, $secret);

        if (! hash_equals($expected, $signature)) {
            return $this->deny('Invalid signature');
        }

        return $next($request);
    }

    /**
     * Rejects timestamps too far from now, in either direction, so a captured
     * request cannot be replayed indefinitely.
     */
    protected function timestampIsFresh(string $timestamp): bool
    {
        if (! is_numeric($timestamp)) {
            return false;
        }

        $tolerance = (int) config('iwexa-connector.signature_tolerance_seconds', 300);

        return abs(time() - (int) $timestamp) <= $tolerance;
    }

    protected function deny(string $error, int $status = 401): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'error'  => $error,
        ], $status);
    }
}
