<?php

namespace Webkul\PAYPOC\IwexaConnector\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Webkul\PAYPOC\IwexaConnector\Models\IwexaSyncJob;
use Webkul\PAYPOC\IwexaConnector\Services\StockUpdateService;

class ProcessStockUpdate implements ShouldQueue
{
    use Queueable;

    protected $syncJobId;

    public function __construct(int $syncJobId)
    {
        $this->syncJobId = $syncJobId;
    }

    public function handle(StockUpdateService $stockUpdateService): void
    {
        $syncJob = IwexaSyncJob::find($this->syncJobId);

        if (!$syncJob || $syncJob->status !== 'processing') {
            return;
        }

        try {
            $payload = $syncJob->payload;
            $stockUpdates = $payload['stock_updates'] ?? [];

            $result = $stockUpdateService->updateBatch(
                $stockUpdates,
                $syncJob->idempotency_key
            );

            $syncJob->update([
                'status' => 'completed',
                'response' => $result,
            ]);
        } catch (\Exception $e) {
            $syncJob->markFailed($e->getMessage());
            $syncJob->incrementAttempts();

            if ($syncJob->canRetry()) {
                $this->release(60);
            }
        }
    }
}
