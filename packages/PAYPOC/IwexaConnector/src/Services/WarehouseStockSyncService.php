<?php

namespace Webkul\PAYPOC\IwexaConnector\Services;

use Webkul\PAYPOC\IwexaConnector\Repositories\IwexaWarehouseStockRepository;
use Webkul\PAYPOC\IwexaConnector\Repositories\IwexaWarehouseRepository;
use Webkul\PAYPOC\IwexaConnector\Repositories\IwexaVendorRepository;
use Exception;

class WarehouseStockSyncService
{
    protected $warehouseStockRepository;
    protected $warehouseRepository;
    protected $vendorRepository;

    public function __construct(
        IwexaWarehouseStockRepository $warehouseStockRepository,
        IwexaWarehouseRepository $warehouseRepository,
        IwexaVendorRepository $vendorRepository
    ) {
        $this->warehouseStockRepository = $warehouseStockRepository;
        $this->warehouseRepository = $warehouseRepository;
        $this->vendorRepository = $vendorRepository;
    }

    /**
     * Update stock for warehouse and SKU
     */
    public function updateStock(array $stockData): array
    {
        $this->validateStockData($stockData);

        $stock = $this->warehouseStockRepository->updateStock($stockData);

        return $stock->toArray();
    }

    /**
     * Validate stock data
     */
    protected function validateStockData(array $data): void
    {
        if (empty($data['warehouse_code'])) {
            throw new Exception('Warehouse code is required');
        }

        if (empty($data['sku'])) {
            throw new Exception('SKU is required');
        }

        if (empty($data['vendor_code'])) {
            throw new Exception('Vendor code is required');
        }

        // Validate warehouse exists
        if (!$this->warehouseRepository->findByCode($data['warehouse_code'])) {
            throw new Exception("Warehouse with code '{$data['warehouse_code']}' does not exist");
        }

        // Validate vendor exists
        if (!$this->vendorRepository->findByCode($data['vendor_code'])) {
            throw new Exception("Vendor with code '{$data['vendor_code']}' does not exist");
        }

        // Validate quantities
        $quantity = $data['quantity'] ?? 0;
        $reserved = $data['reserved_quantity'] ?? 0;

        if ($quantity < 0) {
            throw new Exception('Quantity cannot be negative');
        }

        if ($reserved < 0) {
            throw new Exception('Reserved quantity cannot be negative');
        }

        if ($reserved > $quantity) {
            throw new Exception('Reserved quantity cannot exceed total quantity');
        }

        // Validate preparation times
        $minDays = $data['preparation_time_min_days'] ?? 0;
        $maxDays = $data['preparation_time_max_days'] ?? 0;

        if ($minDays < 0 || $maxDays < 0) {
            throw new Exception('Preparation times cannot be negative');
        }

        if ($minDays > $maxDays) {
            throw new Exception('Minimum preparation time cannot exceed maximum');
        }
    }

    /**
     * Get stock by SKU across all warehouses
     */
    public function getStockBySku(string $sku): array
    {
        return $this->warehouseStockRepository->getBySku($sku)->toArray();
    }

    /**
     * Get available warehouses for SKU and quantity
     */
    public function getAvailableWarehousesForSku(string $sku, int $quantity): array
    {
        return $this->warehouseStockRepository->getAvailableWarehousesForSku($sku, $quantity)->toArray();
    }

    /**
     * Get total available stock for SKU
     */
    public function getTotalAvailableStock(string $sku): int
    {
        return $this->warehouseStockRepository->getTotalAvailableStock($sku);
    }
}