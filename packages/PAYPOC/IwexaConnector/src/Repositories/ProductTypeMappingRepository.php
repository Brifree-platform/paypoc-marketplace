<?php

namespace Webkul\PAYPOC\IwexaConnector\Repositories;

use Webkul\PAYPOC\IwexaConnector\Models\ProductTypeMapping;
use Illuminate\Database\Eloquent\Collection;

class ProductTypeMappingRepository
{
    public function findById(int $id): ?ProductTypeMapping
    {
        return ProductTypeMapping::find($id);
    }

    public function findBySourceType(string $system, string $type, ?string $vendorCode = null): ?ProductTypeMapping
    {
        return ProductTypeMapping::where('source_system', $system)
            ->where('source_product_type', $type)
            ->where('vendor_code', $vendorCode)
            ->first();
    }

    public function findDrafts(): Collection
    {
        return ProductTypeMapping::draft()->get();
    }

    public function findForVendor(?string $vendorCode = null): Collection
    {
        return ProductTypeMapping::forVendor($vendorCode)->get();
    }

    public function create(array $data): ProductTypeMapping
    {
        return ProductTypeMapping::create($data);
    }

    public function update(ProductTypeMapping $mapping, array $data): ProductTypeMapping
    {
        $mapping->update($data);
        return $mapping;
    }
}
