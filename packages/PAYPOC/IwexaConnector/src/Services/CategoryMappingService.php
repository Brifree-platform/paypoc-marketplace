<?php

namespace Webkul\PAYPOC\IwexaConnector\Services;

use Webkul\PAYPOC\IwexaConnector\Models\CategoryMapping;

class CategoryMappingService
{
    public function createMapping(array $data): CategoryMapping
    {
        return CategoryMapping::create($data);
    }

    public function updateMapping(CategoryMapping $mapping, array $data): CategoryMapping
    {
        $mapping->update($data);
        return $mapping;
    }

    public function findOrCreateMapping(string $sourceCategory, ?string $vendorCode = null): CategoryMapping
    {
        $mapping = CategoryMapping::where('source_category', $sourceCategory)
            ->where(function ($query) use ($vendorCode) {
                if ($vendorCode) {
                    $query->where('vendor_code', $vendorCode)
                        ->orWhereNull('vendor_code');
                } else {
                    $query->whereNull('vendor_code');
                }
            })
            ->first();

        if ($mapping) {
            return $mapping;
        }

        return $this->createMapping([
            'source_category' => $sourceCategory,
            'vendor_code' => $vendorCode,
            'status' => 'active',
        ]);
    }

    public function validateMapping(array $data): ?array
    {
        $errors = [];

        if (empty($data['source_category'])) {
            $errors['source_category'] = 'Source category is required';
        }

        return empty($errors) ? null : $errors;
    }

    public function approveMapping(CategoryMapping $mapping): CategoryMapping
    {
        $mapping->update(['status' => 'active']);
        return $mapping;
    }
}
