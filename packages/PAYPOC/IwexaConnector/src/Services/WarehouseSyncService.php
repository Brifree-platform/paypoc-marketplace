<?php

namespace Webkul\PAYPOC\IwexaConnector\Services;

use Webkul\PAYPOC\IwexaConnector\Repositories\IwexaWarehouseRepository;
use Webkul\PAYPOC\IwexaConnector\Repositories\IwexaVendorRepository;
use Exception;

class WarehouseSyncService
{
    protected $warehouseRepository;
    protected $vendorRepository;

    public function __construct(
        IwexaWarehouseRepository $warehouseRepository,
        IwexaVendorRepository $vendorRepository
    ) {
        $this->warehouseRepository = $warehouseRepository;
        $this->vendorRepository = $vendorRepository;
    }

    /**
     * Create or update warehouse from Iwexa payload
     */
    public function createOrUpdateWarehouse(array $warehouseData): array
    {
        $this->validateWarehouseData($warehouseData);

        $warehouse = $this->warehouseRepository->upsert($warehouseData);

        return $warehouse->toArray();
    }

    /**
     * Validate warehouse data
     */
    protected function validateWarehouseData(array $data): void
    {
        if (empty($data['warehouse_code'])) {
            throw new Exception('Warehouse code is required');
        }

        if (empty($data['name'])) {
            throw new Exception('Warehouse name is required');
        }

        if (empty($data['type']) || !in_array($data['type'], ['central', 'vendor'])) {
            throw new Exception('Warehouse type must be central or vendor');
        }

        if (empty($data['country'])) {
            throw new Exception('Warehouse country is required');
        }

        // For vendor warehouses, vendor must exist
        if ($data['type'] === 'vendor') {
            if (empty($data['vendor_code'])) {
                throw new Exception('Vendor code is required for vendor warehouses');
            }

            if (!$this->vendorRepository->findByCode($data['vendor_code'])) {
                throw new Exception("Vendor with code '{$data['vendor_code']}' does not exist");
            }
        }

        // For central warehouses, vendor_code should be null
        if ($data['type'] === 'central' && !empty($data['vendor_code'])) {
            throw new Exception('Central warehouses cannot have a vendor code');
        }
    }

    /**
     * Find warehouse by code
     */
    public function findWarehouseByCode(string $warehouseCode): ?array
    {
        $warehouse = $this->warehouseRepository->findByCode($warehouseCode);

        return $warehouse ? $warehouse->toArray() : null;
    }

    /**
     * Get active warehouses
     */
    public function getActiveWarehouses(): array
    {
        return $this->warehouseRepository->active()->toArray();
    }
}