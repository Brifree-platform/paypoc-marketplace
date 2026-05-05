<?php

namespace Webkul\PAYPOC\IwexaConnector\Controllers\Api;

use Webkul\PAYPOC\IwexaConnector\Services\ShippingRateService;
use Webkul\PAYPOC\IwexaConnector\Http\Requests\ShippingRateUpsertRequest;
use Illuminate\Http\JsonResponse;
use Exception;

class ShippingRateController
{
    protected $shippingRateService;

    public function __construct(ShippingRateService $shippingRateService)
    {
        $this->shippingRateService = $shippingRateService;
    }

    /**
     * Create a new shipping rate
     *
     * @param ShippingRateUpsertRequest $request
     * @return JsonResponse
     */
    public function store(ShippingRateUpsertRequest $request): JsonResponse
    {
        try {
            $rate = $this->shippingRateService->createOrUpdateRate($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Shipping rate created successfully',
                'data' => $rate,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create shipping rate',
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}