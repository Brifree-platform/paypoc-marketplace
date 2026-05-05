<?php

namespace Webkul\PAYPOC\IwexaConnector\Controllers\Api;

use Webkul\PAYPOC\IwexaConnector\Services\WarehouseSyncService;
use Webkul\PAYPOC\IwexaConnector\Http\Requests\WarehouseUpsertRequest;
use Illuminate\Http\JsonResponse;
use Exception;

class WarehouseController
{
    protected $warehouseService;

    public function __construct(WarehouseSyncService $warehouseService)
    {
        $this->warehouseService = $warehouseService;
    }

    /**
     * Create a new warehouse
     *
     * @param WarehouseUpsertRequest $request
     * @return JsonResponse
     */
    public function store(WarehouseUpsertRequest $request): JsonResponse
    {
        try {
            $warehouse = $this->warehouseService->createOrUpdateWarehouse($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Warehouse created successfully',
                'data' => $warehouse,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create warehouse',
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}