<?php

namespace Webkul\PAYPOC\IwexaConnector\Controllers\Api;

use Webkul\PAYPOC\IwexaConnector\Services\VendorImportService;
use Webkul\PAYPOC\IwexaConnector\Http\Requests\VendorUpsertRequest;
use Illuminate\Http\JsonResponse;
use Exception;

class VendorController
{
    protected $vendorService;

    public function __construct(VendorImportService $vendorService)
    {
        $this->vendorService = $vendorService;
    }

    /**
     * Create a new vendor
     *
     * @param VendorUpsertRequest $request
     * @return JsonResponse
     */
    public function store(VendorUpsertRequest $request): JsonResponse
    {
        try {
            $vendor = $this->vendorService->createOrUpdateVendor($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Vendor created successfully',
                'data' => $vendor,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create vendor',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update an existing vendor
     *
     * @param string $vendorCode
     * @param VendorUpsertRequest $request
     * @return JsonResponse
     */
    public function update($vendorCode, VendorUpsertRequest $request): JsonResponse
    {
        try {
            $vendor = $this->vendorService->findVendorByCode($vendorCode);

            if (!$vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor not found',
                ], 404);
            }

            $updatedVendor = $this->vendorService->createOrUpdateVendor(
                array_merge($request->validated(), ['vendor_code' => $vendorCode])
            );

            return response()->json([
                'success' => true,
                'message' => 'Vendor updated successfully',
                'data' => $updatedVendor,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update vendor',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get vendor by vendor_code
     *
     * @param string $vendorCode
     * @return JsonResponse
     */
    public function show($vendorCode): JsonResponse
    {
        try {
            $vendor = $this->vendorService->findVendorByCode($vendorCode);

            if (!$vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $vendor,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve vendor',
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}