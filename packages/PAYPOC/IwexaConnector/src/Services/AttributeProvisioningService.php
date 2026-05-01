<?php

namespace Webkul\PAYPOC\IwexaConnector\Services;

use Webkul\PAYPOC\IwexaConnector\Models\ProductTypeMapping;
use Webkul\PAYPOC\IwexaConnector\Models\IwexaProduct;
use Illuminate\Support\Facades\Log;

class AttributeProvisioningService
{
    protected $productTypeMappingService;
    protected $attributeMappingService;

    public function __construct(
        ProductTypeMappingService $productTypeMappingService,
        AttributeMappingService $attributeMappingService
    ) {
        $this->productTypeMappingService = $productTypeMappingService;
        $this->attributeMappingService = $attributeMappingService;
    }

    public function provisionAttribute(string $sourceAttributeCode, int $productTypeMappingId): void
    {
        $mapping = $this->attributeMappingService->findOrCreateMapping($productTypeMappingId, $sourceAttributeCode);
        Log::info("Provisioned attribute: {$sourceAttributeCode} for product type mapping {$productTypeMappingId}");
    }

    public function createDraftMapping(string $sourceAttributeCode, int $productTypeMappingId): void
    {
        $this->attributeMappingService->createMapping($productTypeMappingId, [
            'source_attribute_code' => $sourceAttributeCode,
            'status' => 'draft',
        ]);
    }

    public function createDraftProductTypeMapping(string $sourceSystem, ?string $sourceProductType, ?string $vendorCode): ProductTypeMapping
    {
        if (!$sourceProductType) {
            throw new \Exception('Source product type is required for draft mapping');
        }

        return $this->productTypeMappingService->findOrCreateMapping($sourceSystem, $sourceProductType, $vendorCode);
    }

    public function markProductPendingMapping(string $sku): void
    {
        $product = IwexaProduct::where('sku', $sku)->first();
        if ($product) {
            $product->markPendingMapping();
            Log::info("Marked product {$sku} as pending mapping");
        }
    }

    public function getProductsMappingPending()
    {
        return IwexaProduct::pendingMapping()->get();
    }
}
