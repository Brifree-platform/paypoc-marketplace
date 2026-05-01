<?php

namespace Webkul\PAYPOC\IwexaConnector\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IwexaApiService
{
    protected $baseUrl;
    protected $apiKey;
    protected $hmacSecret;

    public function __construct(string $baseUrl, string $apiKey, string $hmacSecret)
    {
        $this->baseUrl = $baseUrl;
        $this->apiKey = $apiKey;
        $this->hmacSecret = $hmacSecret;
    }

    public function catalogBatch(array $products, string $idempotencyKey): array
    {
        return $this->makeRequest(
            'POST',
            '/api/v1/catalog/products/batch',
            ['products' => $products],
            $idempotencyKey
        );
    }

    public function updateProduct(string $sku, array $data, string $idempotencyKey): array
    {
        return $this->makeRequest(
            'PUT',
            "/api/v1/catalog/products/{$sku}",
            $data,
            $idempotencyKey
        );
    }

    public function updateStock(array $stocks, string $idempotencyKey): array
    {
        return $this->makeRequest(
            'POST',
            '/api/v1/catalog/product-stock',
            ['stock_updates' => $stocks],
            $idempotencyKey
        );
    }

    public function validateWebhookSignature(string $payload, string $signature, string $timestamp): bool
    {
        $expectedSignature = hash_hmac('sha256', $payload . $timestamp, $this->hmacSecret);
        return hash_equals($expectedSignature, $signature);
    }

    protected function makeRequest(string $method, string $endpoint, array $data, string $idempotencyKey): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Idempotency-Key' => $idempotencyKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->{strtolower($method)}(
                $this->baseUrl . $endpoint,
                $data
            );

            if ($response->failed()) {
                return [
                    'success' => false,
                    'error' => $response->body(),
                    'status_code' => $response->status(),
                ];
            }

            return $response->json();
        } catch (\Exception $e) {
            $this->handleRequestError($e);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function handleRequestError(\Exception $e): void
    {
        Log::error('Iwexa API Request Failed', [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);
    }
}
