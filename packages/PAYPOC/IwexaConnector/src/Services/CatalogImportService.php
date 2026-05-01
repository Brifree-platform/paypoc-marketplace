<?php

namespace Webkul\PAYPOC\IwexaConnector\Services;

use Webkul\PAYPOC\IwexaConnector\Models\IwexaSyncJob;
use Webkul\PAYPOC\IwexaConnector\Models\IwexaProduct;
use Webkul\PAYPOC\IwexaConnector\Models\CategoryMapping;
use Webkul\PAYPOC\IwexaConnector\Models\ProductTypeMapping;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CatalogImportService
{
    protected $categoryMappingService;
    protected $attributeProvisioningService;

    public function __construct(
        CategoryMappingService $categoryMappingService,
        AttributeProvisioningService $attributeProvisioningService
    ) {
        $this->categoryMappingService = $categoryMappingService;
        $this->attributeProvisioningService = $attributeProvisioningService;
    }

    public function importBatch(array $products, string $idempotencyKey): array
    {
        $results = [
            'imported_count' => 0,
            'updated_count' => 0,
            'mapping_pending_count' => 0,
            'errors' => [],
        ];

        foreach ($products as $productData) {
            try {
                $this->validateProductData($productData);
                
                $sku = $productData['sku'];
                $product = IwexaProduct::where('sku', $sku)->first();

                if ($product) {
                    $this->updateProductIfExists($sku, $productData);
                    $results['updated_count']++;
                } else {
                    $this->importSingleProduct($productData);
                    $results['imported_count']++;
                }
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'sku' => $productData['sku'] ?? 'unknown',
                    'error' => $e->getMessage(),
                    'action' => 'pending_mapping',
                ];
                $results['mapping_pending_count']++;
                Log::error('Catalog import error', ['error' => $e->getMessage()]);
            }
        }

        return $results;
    }

    public function importSingleProduct(array $productData): IwexaProduct
    {
        $sku = $productData['sku'];
        $parentSku = $productData['parent_sku'] ?? null;
        $ean = $productData['ean'] ?? null;
        $itemGroupId = $productData['item_group_id'] ?? $this->generateOrGetItemGroupId($productData);
        $productType = $productData['product_type'] ?? null;
        $sourceCategory = $productData['source_category'] ?? null;
        $vendorCode = $productData['vendor_code'];

        // Check if mapping exists or create draft
        $mappingPending = !$this->checkMappingStatus($productType, $vendorCode, $sourceCategory);

        $iwexaProduct = IwexaProduct::create([
            'sku' => $sku,
            'parent_sku' => $parentSku,
            'item_group_id' => $itemGroupId,
            'ean' => $ean,
            'iwexa_product_id' => $productData['iwexa_product_id'] ?? null,
            'vendor_code' => $vendorCode,
            'product_type' => $productType,
            'source_category' => $sourceCategory,
            'status' => $productData['status'] ?? 'active',
            'pending_mapping' => $mappingPending,
            'original_iwexa_payload' => $productData,
            'meta' => $productData['metadata'] ?? [],
        ]);

        if ($mappingPending) {
            $this->attributeProvisioningService->createDraftProductTypeMapping(
                'iwexa',
                $productType,
                $vendorCode
            );
        }

        return $iwexaProduct;
    }

    public function updateProductIfExists(string $sku, array $productData): IwexaProduct
    {
        $product = IwexaProduct::where('sku', $sku)->firstOrFail();
        
        $product->update([
            'original_iwexa_payload' => $productData,
            'product_type' => $productData['product_type'] ?? $product->product_type,
            'source_category' => $productData['source_category'] ?? $product->source_category,
            'status' => $productData['status'] ?? $product->status,
            'meta' => $productData['metadata'] ?? $product->meta,
        ]);

        return $product;
    }

    public function validateProductData(array $productData): bool
    {
        if (empty($productData['sku'])) {
            throw new \Exception('SKU is required');
        }

        if (empty($productData['vendor_code'])) {
            throw new \Exception('Vendor code is required');
        }

        if (empty($productData['currency'])) {
            throw new \Exception('Currency is required');
        }

        return true;
    }

    public function checkMappingStatus(?string $productType, ?string $vendorCode, ?string $sourceCategory): bool
    {
        if (!$productType) {
            return false;
        }

        $mapping = ProductTypeMapping::where('source_system', 'iwexa')
            ->where('source_product_type', $productType)
            ->where('vendor_code', $vendorCode)
            ->first();

        if (!$mapping || $mapping->status === 'draft') {
            return false;
        }

        return true;
    }

    public function generateOrGetItemGroupId(array $productData): string
    {
        if (!empty($productData['item_group_id'])) {
            return $productData['item_group_id'];
        }

        // Generate from parent_sku if available
        if (!empty($productData['parent_sku'])) {
            return $productData['parent_sku'];
        }

        // Generate unique ID
        return 'GRP-' . Str::uuid();
    }

    public function storeOriginalPayload(IwexaProduct $product, array $payload): void
    {
        $product->update(['original_iwexa_payload' => $payload]);
    }
}
