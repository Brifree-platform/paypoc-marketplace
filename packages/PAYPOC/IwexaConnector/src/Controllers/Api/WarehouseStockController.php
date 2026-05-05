<?php

namespace Webkul\PAYPOC\IwexaConnector\Controllers\Api;

use Webkul\PAYPOC\IwexaConnector\Services\WarehouseStockSyncService;
use Webkul\PAYPOC\IwexaConnector\Http\Requests\WarehouseStockUpdateRequest;
use Illuminate\Http\JsonResponse;
use Exception;

class WarehouseStockController
{
    protected $warehouseStockService;

    public function __construct(WarehouseStockSyncService $warehouseStockService)
    {
        $this->warehouseStockService = $warehouseStockService;
    }

    /**
     * Update warehouse stock
     *
     * @param WarehouseStockUpdateRequest $request
     * @return JsonResponse
     */
    public function updateStock(WarehouseStockUpdateRequest $request): JsonResponse
    {
        try {
            $stock = $this->warehouseStockService->updateStock($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Warehouse stock updated successfully',
                'data' => $stock,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update warehouse stock',
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}