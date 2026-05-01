<?php

namespace Webkul\PAYPOC\IwexaConnector\Repositories;

use Webkul\PAYPOC\IwexaConnector\Models\AttributeMapping;
use Illuminate\Database\Eloquent\Collection;

class AttributeMappingRepository
{
    public function findById(int $id): ?AttributeMapping
    {
        return AttributeMapping::find($id);
    }

    public function findByProductType(int $productTypeMappingId): Collection
    {
        return AttributeMapping::where('product_type_mapping_id', $productTypeMappingId)->get();
    }

    public function findDrafts(): Collection
    {
        return AttributeMapping::draft()->get();
    }

    public function create(array $data): AttributeMapping
    {
        return AttributeMapping::create($data);
    }

    public function update(AttributeMapping $mapping, array $data): AttributeMapping
    {
        $mapping->update($data);
        return $mapping;
    }
}
