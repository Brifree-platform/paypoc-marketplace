<?php

namespace Webkul\PAYPOC\IwexaConnector\Services;

use Webkul\PAYPOC\IwexaConnector\Models\IwexaWebhookEvent;
use Illuminate\Support\Facades\Log;

class WebhookProcessorService
{
    protected $catalogImportService;
    protected $stockUpdateService;

    public function __construct(
        CatalogImportService $catalogImportService,
        StockUpdateService $stockUpdateService
    ) {
        $this->catalogImportService = $catalogImportService;
        $this->stockUpdateService = $stockUpdateService;
    }

    public function processWebhook(array $payload, string $signature, string $timestamp): bool
    {
        try {
            $payloadJson = json_encode($payload);
            
            if (!$this->validateIdempotency($payload['id'] ?? null, $payload['delivery_id'] ?? null)) {
                Log::warning('Duplicate webhook event', ['event_id' => $payload['id']]);
                return true;
            }

            $event = $this->logWebhookEvent($payload, $payload['event'] ?? 'unknown');

            $eventType = $payload['event'] ?? '';
            $data = $payload['data'] ?? [];

            match ($eventType) {
                'catalog.product.updated' => $this->handleCatalogProductUpdated($data),
                'catalog.stock.updated' => $this->handleCatalogStockUpdated($data),
                default => Log::warning("Unknown webhook event: {$eventType}"),
            };

            $this->markEventProcessed($event);
            return true;
        } catch (\Exception $e) {
            Log::error('Webhook processing error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function handleCatalogProductUpdated(array $data): bool
    {
        try {
            Log::info('Processing catalog product update', $data);
            // Handle product update
            return true;
        } catch (\Exception $e) {
            Log::error('Error handling catalog product update', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function handleCatalogStockUpdated(array $data): bool
    {
        try {
            Log::info('Processing catalog stock update', $data);
            $this->stockUpdateService->updateSingleProduct(
                $data['sku'],
                $data['available_quantity'] ?? 0,
                $data['warehouse_code'] ?? 'default',
                $data['stock_status'] ?? 'in_stock'
            );
            return true;
        } catch (\Exception $e) {
            Log::error('Error handling catalog stock update', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function validateIdempotency(string $eventId = null, string $deliveryId = null): bool
    {
        if (!$eventId || !$deliveryId) {
            return true;
        }

        $existing = IwexaWebhookEvent::where('event_id', $eventId)
            ->where('delivery_id', $deliveryId)
            ->first();

        return !$existing;
    }

    public function logWebhookEvent(array $payload, string $eventType): IwexaWebhookEvent
    {
        return IwexaWebhookEvent::create([
            'event_id' => $payload['id'] ?? '',
            'delivery_id' => $payload['delivery_id'] ?? '',
            'event_type' => $eventType,
            'payload' => $payload,
            'status' => 'received',
            'received_at' => now(),
        ]);
    }

    public function markEventProcessed(IwexaWebhookEvent $event): void
    {
        $event->markProcessed();
    }
}
