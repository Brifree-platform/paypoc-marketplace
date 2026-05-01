<?php

namespace Webkul\PAYPOC\IwexaConnector\Services;

use Webkul\PAYPOC\IwexaConnector\Models\AttributeMapping;
use Webkul\PAYPOC\IwexaConnector\Models\AttributeValueMapping;

class AttributeMappingService
{
    public function createMapping(int $productTypeMappingId, array $data): AttributeMapping
    {
        $data['product_type_mapping_id'] = $productTypeMappingId;
        return AttributeMapping::create($data);
    }

    public function updateMapping(AttributeMapping $mapping, array $data): AttributeMapping
    {
        $mapping->update($data);
        return $mapping;
    }

    public function findOrCreateMapping(int $productTypeMappingId, string $sourceAttributeCode): AttributeMapping
    {
        $mapping = AttributeMapping::where('product_type_mapping_id', $productTypeMappingId)
            ->where('source_attribute_code', $sourceAttributeCode)
            ->first();

        if ($mapping) {
            return $mapping;
        }

        return $this->createMapping($productTypeMappingId, [
            'source_attribute_code' => $sourceAttributeCode,
            'status' => 'draft',
        ]);
    }

    public function createValueMapping(int $attributeMappingId, string $sourceValue, string $normalizedValue): AttributeValueMapping
    {
        return AttributeValueMapping::create([
            'attribute_mapping_id' => $attributeMappingId,
            'source_value' => $sourceValue,
            'normalized_value' => $normalizedValue,
        ]);
    }

    public function validateMapping(array $data): ?array
    {
        $errors = [];

        if (empty($data['source_attribute_code'])) {
            $errors['source_attribute_code'] = 'Source attribute code is required';
        }

        return empty($errors) ? null : $errors;
    }

    public function approveMapping(AttributeMapping $mapping): AttributeMapping
    {
        $mapping->update(['status' => 'active']);
        return $mapping;
    }
}
