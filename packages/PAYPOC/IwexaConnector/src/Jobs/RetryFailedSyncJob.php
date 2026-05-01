<?php

namespace Webkul\PAYPOC\IwexaConnector\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Webkul\PAYPOC\IwexaConnector\Models\IwexaSyncJob;

class RetryFailedSyncJob implements ShouldQueue
{
    use Queueable;

    protected $syncJobId;

    public function __construct(int $syncJobId)
    {
        $this->syncJobId = $syncJobId;
    }

    public function handle(): void
    {
        $syncJob = IwexaSyncJob::find($this->syncJobId);

        if (!$syncJob || !$syncJob->canRetry()) {
            return;
        }

        $syncJob->update([
            'status' => 'processing',
            'attempts' => $syncJob->attempts + 1,
        ]);

        // Re-dispatch the original job based on type
        match ($syncJob->type) {
            'catalog_import' => dispatch(new ProcessCatalogImport($syncJob->id)),
            'stock_update' => dispatch(new ProcessStockUpdate($syncJob->id)),
            default => null,
        };
    }
}
