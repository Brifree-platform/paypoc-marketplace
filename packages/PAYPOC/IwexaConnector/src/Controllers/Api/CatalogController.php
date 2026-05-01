<?php

namespace Webkul\PAYPOC\IwexaConnector\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Webkul\PAYPOC\IwexaConnector\Http\Requests\CatalogBatchRequest;
use Webkul\PAYPOC\IwexaConnector\Http\Requests\ProductUpdateRequest;
use Webkul\PAYPOC\IwexaConnector\Services\CatalogImportService;
use Webkul\PAYPOC\IwexaConnector\Models\IwexaSyncJob;
use Illuminate\Support\Str;

class CatalogController
{
    protected $catalogImportService;

    public function __construct(CatalogImportService $catalogImportService)
    {
        $this->catalogImportService = $catalogImportService;
    }

    public function importBatch(CatalogBatchRequest $request): JsonResponse
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
            'type' => 'catalog_import',
            'status' => 'processing',
            'payload' => $request->validated(),
            'idempotency_key' => $idempotencyKey,
        ]);

        try {
            $result = $this->catalogImportService->importBatch(
                $request->input('products', []),
                $idempotencyKey
            );

            $syncJob->update([
                'status' => 'completed',
                'response' => $result,
            ]);

            $status = empty($result['errors']) ? 'success' : 'partial_failure';

            return response()->json([
                'status' => $status,
                'imported_count' => $result['imported_count'],
                'updated_count' => $result['updated_count'],
                'mapping_pending_count' => $result['mapping_pending_count'],
                'idempotency_key' => $idempotencyKey,
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

    public function updateProduct(ProductUpdateRequest $request, string $sku): JsonResponse
    {
        $idempotencyKey = $request->header('Idempotency-Key') ?? Str::uuid();

        try {
            $result = $this->catalogImportService->updateProductIfExists($sku, $request->validated());

            return response()->json([
                'status' => 'success',
                'sku' => $sku,
                'updated_at' => $result->updated_at->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
