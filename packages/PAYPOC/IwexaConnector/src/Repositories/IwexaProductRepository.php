<?php

namespace Webkul\PAYPOC\IwexaConnector\Repositories;

use Webkul\PAYPOC\IwexaConnector\Models\IwexaProduct;
use Illuminate\Database\Eloquent\Collection;

class IwexaProductRepository
{
    public function findBySku(string $sku): ?IwexaProduct
    {
        return IwexaProduct::where('sku', $sku)->first();
    }

    public function findByIwexaId(string $id): ?IwexaProduct
    {
        return IwexaProduct::where('iwexa_product_id', $id)->first();
    }

    public function findActive(): Collection
    {
        return IwexaProduct::active()->get();
    }

    public function findPendingMapping(): Collection
    {
        return IwexaProduct::pendingMapping()->get();
    }

    public function findByParentSku(string $parentSku): Collection
    {
        return IwexaProduct::byParentSku($parentSku)->get();
    }

    public function findByVendor(string $vendorCode): Collection
    {
        return IwexaProduct::byVendor($vendorCode)->get();
    }

    public function create(array $data): IwexaProduct
    {
        return IwexaProduct::create($data);
    }

    public function update(IwexaProduct $product, array $data): IwexaProduct
    {
        $product->update($data);
        return $product;
    }
}
