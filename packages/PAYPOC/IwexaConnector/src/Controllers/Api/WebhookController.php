<?php

namespace Webkul\PAYPOC\IwexaConnector\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Webkul\PAYPOC\IwexaConnector\Services\WebhookProcessorService;
use Webkul\PAYPOC\IwexaConnector\Services\IwexaApiService;
use Webkul\PAYPOC\IwexaConnector\Models\IwexaWebhookEvent;

class WebhookController
{
    protected $webhookProcessorService;
    protected $iwexaApiService;

    public function __construct(
        WebhookProcessorService $webhookProcessorService,
        IwexaApiService $iwexaApiService
    ) {
        $this->webhookProcessorService = $webhookProcessorService;
        $this->iwexaApiService = $iwexaApiService;
    }

    public function handleWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('X-IWEXA-SIGNATURE');
        $timestamp = $request->header('X-IWEXA-TIMESTAMP');
        $eventId = $request->header('X-IWEXA-EVENT');
        $deliveryId = $request->header('X-IWEXA-DELIVERY-ID');

        // Validate signature
        if (!$this->iwexaApiService->validateWebhookSignature($payload, $signature, $timestamp)) {
            return response()->json([
                'status' => 'error',
                'error' => 'Invalid signature',
            ], 401);
        }

        $data = json_decode($payload, true);

        // Process webhook
        $success = $this->webhookProcessorService->processWebhook($data, $signature, $timestamp);

        if ($success) {
            return response()->json([
                'status' => 'success',
                'processed' => true,
                'event_id' => $data['id'] ?? $eventId,
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'error' => 'Failed to process webhook',
            ], 500);
        }
    }
}
