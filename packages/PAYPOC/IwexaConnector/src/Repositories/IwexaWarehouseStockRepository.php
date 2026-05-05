<?php

namespace Webkul\PAYPOC\IwexaConnector\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\PAYPOC\IwexaConnector\Models\IwexaWarehouseStock;

class IwexaWarehouseStockRepository extends Repository
{
    /**
     * Specify Model class name
     */
    function model(): string
    {
        return IwexaWarehouseStock::class;
    }

    /**
     * Update stock for warehouse and SKU
     */
    public function updateStock(array $data)
    {
        return $this->updateOrCreate(
            [
                'warehouse_code' => $data['warehouse_code'],
                'sku' => $data['sku']
            ],
            $data
        );
    }

    /**
     * Get stock by SKU across all warehouses
     */
    public function getBySku(string $sku)
    {
        return $this->findWhere(['sku' => $sku]);
    }

    /**
     * Get stock by warehouse and SKU
     */
    public function getByWarehouseAndSku(string $warehouseCode, string $sku)
    {
        return $this->findOneByField([
            'warehouse_code' => $warehouseCode,
            'sku' => $sku
        ]);
    }

    /**
     * Get available warehouses for SKU and quantity
     */
    public function getAvailableWarehousesForSku(string $sku, int $quantity)
    {
        return $this->model->where('sku', $sku)
                          ->where('available_quantity', '>=', $quantity)
                          ->with('warehouse')
                          ->get();
    }

    /**
     * Get total available stock for SKU
     */
    public function getTotalAvailableStock(string $sku): int
    {
        return $this->model->where('sku', $sku)->sum('available_quantity');
    }
}