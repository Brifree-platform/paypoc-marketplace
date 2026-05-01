<?php

namespace Webkul\PAYPOC\IwexaConnector\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Webkul\PAYPOC\IwexaConnector\Models\IwexaSyncJob;

class CatalogSyncStarted
{
    use Dispatchable;

    public $syncJob;

    public function __construct(IwexaSyncJob $syncJob)
    {
        $this->syncJob = $syncJob;
    }
}
