<?php

namespace Webkul\PAYPOC\IwexaConnector\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Webkul\PAYPOC\IwexaConnector\Http\Requests\StockUpdateRequest;
use Webkul\PAYPOC\IwexaConnector\Services\StockUpdateService;
use Webkul\PAYPOC\IwexaConnector\Models\IwexaSyncJob;
use Illuminate\Support\Str;

class StockController
{
    protected $stockUpdateService;

    public function __construct(StockUpdateService $stockUpdateService)
    {
        $this->stockUpdateService = $stockUpdateService;
    }

    public function updateStock(StockUpdateRequest $request): JsonResponse
    {
        $idempotencyKey = $request->header('Idempotency-Key') ?? Str::uuid();

        // Check if already processed
        $existingJob = IwexaSyncJob::where('idempotency_key', $idempotencyKey)->first();
        if ($existingJob && $existingJob->status === 'completed') {
            return response()->json([
                'status' => 'success',
                'message' => 'Already processed',
                'idempotency_key' => $idempotencyKey,
                'response' => $existingJob->response,
            ]);
        }

        // Create sync job
        $syncJob = IwexaSyncJob::create([
            'type' => 'stock_update',
            'status' => 'processing',
            'payload' => $request->validated(),
            'idempotency_key' => $idempotencyKey,
        ]);

        try {
            $result = $this->stockUpdateService->updateBatch(
                $request->input('stock_updates', []),
                $idempotencyKey
            );

            $syncJob->update([
                'status' => 'completed',
                'response' => $result,
            ]);

            return response()->json([
                'status' => 'success',
                'processed_count' => $result['processed_count'],
                'errors' => $result['errors'],
            ]);
        } catch (\Exception $e) {
            $syncJob->markFailed($e->getMessage());

            return response()->json([
                'status' => 'error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
