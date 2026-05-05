<?php

namespace Webkul\PAYPOC\IwexaConnector\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\PAYPOC\IwexaConnector\Models\RoutingQuote;

class RoutingQuoteRepository extends Repository
{
    /**
     * Specify Model class name
     */
    function model(): string
    {
        return RoutingQuote::class;
    }

    /**
     * Create routing quote
     */
    public function createQuote(array $data)
    {
        return $this->create($data);
    }

    /**
     * Get latest quote for SKU
     */
    public function latestForSku(string $sku)
    {
        return $this->model->latestForSku($sku)->first();
    }

    /**
     * Get quotes by vendor
     */
    public function getByVendor(string $vendorCode)
    {
        return $this->findWhere(['vendor_code' => $vendorCode]);
    }

    /**
     * Get quotes by warehouse
     */
    public function getByWarehouse(string $warehouseCode)
    {
        return $this->findWhere(['warehouse_code' => $warehouseCode]);
    }
}