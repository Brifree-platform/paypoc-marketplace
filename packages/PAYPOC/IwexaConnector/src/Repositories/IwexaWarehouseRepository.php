<?php

namespace Webkul\PAYPOC\IwexaConnector\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\PAYPOC\IwexaConnector\Models\IwexaWarehouse;

class IwexaWarehouseRepository extends Repository
{
    /**
     * Specify Model class name
     */
    function model(): string
    {
        return IwexaWarehouse::class;
    }

    /**
     * Find warehouse by code
     */
    public function findByCode(string $warehouseCode)
    {
        return $this->findOneByField('warehouse_code', $warehouseCode);
    }

    /**
     * Upsert warehouse
     */
    public function upsert(array $data)
    {
        return $this->updateOrCreate(
            ['warehouse_code' => $data['warehouse_code']],
            $data
        );
    }

    /**
     * Get active warehouses
     */
    public function active()
    {
        return $this->findWhere(['status' => 'active']);
    }

    /**
     * Get warehouses by type
     */
    public function getByType(string $type)
    {
        return $this->findWhere(['type' => $type, 'status' => 'active']);
    }
}