<?php

namespace Webkul\PAYPOC\IwexaConnector\Services;

use Webkul\PAYPOC\IwexaConnector\Models\IwexaStockLog;
use Illuminate\Support\Facades\Log;

class StockUpdateService
{
    public function updateBatch(array $stockUpdates, string $idempotencyKey): array
    {
        $results = [
            'processed_count' => 0,
            'errors' => [],
        ];

        foreach ($stockUpdates as $update) {
            try {
                $this->updateSingleProduct(
                    $update['sku'],
                    $update['quantity'] ?? 0,
                    $update['warehouse_code'] ?? 'default',
                    $update['stock_status'] ?? 'in_stock'
                );
                $results['processed_count']++;
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'sku' => $update['sku'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ];
                Log::error('Stock update error', ['error' => $e->getMessage()]);
            }
        }

        return $results;
    }

    public function updateSingleProduct(string $sku, int $quantity, string $warehouseCode, string $status): bool
    {
        try {
            $this->logStockChange($sku, $warehouseCode, $quantity);

            // Mark product as in/out of stock based on quantity
            if ($quantity <= 0) {
                $this->markOutOfStock($sku);
            } else {
                $this->markInStock($sku);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to update stock for SKU: ' . $sku, ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function markOutOfStock(string $sku): bool
    {
        // This would update the Bagisto product inventory
        // For now, we just log the action
        Log::info("Marking SKU {$sku} as out of stock");
        return true;
    }

    public function markInStock(string $sku): bool
    {
        // This would update the Bagisto product inventory
        // For now, we just log the action
        Log::info("Marking SKU {$sku} as in stock");
        return true;
    }

    public function logStockChange(string $sku, string $warehouseCode, int $quantity): void
    {
        IwexaStockLog::create([
            'sku' => $sku,
            'warehouse_code' => $warehouseCode,
            'quantity' => $quantity,
            'source' => 'iwexa',
        ]);
    }
}
