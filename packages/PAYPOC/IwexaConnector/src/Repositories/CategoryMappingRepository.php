<?php

namespace Webkul\PAYPOC\IwexaConnector\Repositories;

use Webkul\PAYPOC\IwexaConnector\Models\CategoryMapping;
use Illuminate\Database\Eloquent\Collection;

class CategoryMappingRepository
{
    public function findById(int $id): ?CategoryMapping
    {
        return CategoryMapping::find($id);
    }

    public function findBySourceCategory(string $category, ?string $vendorCode = null): ?CategoryMapping
    {
        $query = CategoryMapping::where('source_category', $category);
        
        if ($vendorCode) {
            $query->where('vendor_code', $vendorCode);
        }
        
        return $query->first();
    }

    public function findDrafts(): Collection
    {
        return CategoryMapping::where('status', 'inactive')->get();
    }

    public function findForVendor(string $vendorCode): Collection
    {
        return CategoryMapping::forVendor($vendorCode)->get();
    }

    public function create(array $data): CategoryMapping
    {
        return CategoryMapping::create($data);
    }

    public function update(CategoryMapping $mapping, array $data): CategoryMapping
    {
        $mapping->update($data);
        return $mapping;
    }
}
