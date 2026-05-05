<?php

namespace Webkul\PAYPOC\IwexaConnector\Controllers\Api;

use Webkul\PAYPOC\IwexaConnector\Services\ShippingZoneService;
use Webkul\PAYPOC\IwexaConnector\Http\Requests\ShippingZoneUpsertRequest;
use Illuminate\Http\JsonResponse;
use Exception;

class ShippingZoneController
{
    protected $shippingZoneService;

    public function __construct(ShippingZoneService $shippingZoneService)
    {
        $this->shippingZoneService = $shippingZoneService;
    }

    /**
     * Create a new shipping zone
     *
     * @param ShippingZoneUpsertRequest $request
     * @return JsonResponse
     */
    public function store(ShippingZoneUpsertRequest $request): JsonResponse
    {
        try {
            $zone = $this->shippingZoneService->createOrUpdateZone($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Shipping zone created successfully',
                'data' => $zone,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create shipping zone',
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}