<?php

namespace Webkul\PAYPOC\IwexaConnector\Services;

use Webkul\PAYPOC\IwexaConnector\Models\ProductTypeMapping;

class ProductTypeMappingService
{
    public function createMapping(array $data): ProductTypeMapping
    {
        return ProductTypeMapping::create($data);
    }

    public function updateMapping(ProductTypeMapping $mapping, array $data): ProductTypeMapping
    {
        $mapping->update($data);
        return $mapping;
    }

    public function findOrCreateMapping(string $sourceSystem, string $sourceProductType, ?string $vendorCode = null): ProductTypeMapping
    {
        $mapping = ProductTypeMapping::where('source_system', $sourceSystem)
            ->where('source_product_type', $sourceProductType)
            ->where('vendor_code', $vendorCode)
            ->first();

        if ($mapping) {
            return $mapping;
        }

        return $this->createMapping([
            'source_system' => $sourceSystem,
            'source_product_type' => $sourceProductType,
            'vendor_code' => $vendorCode,
            'status' => 'draft',
        ]);
    }

    public function approveMapping(ProductTypeMapping $mapping): ProductTypeMapping
    {
        $mapping->update(['status' => 'active']);
        return $mapping;
    }

    public function publishMapping(ProductTypeMapping $mapping): ProductTypeMapping
    {
        $mapping->update(['status' => 'active']);
        return $mapping;
    }
}
