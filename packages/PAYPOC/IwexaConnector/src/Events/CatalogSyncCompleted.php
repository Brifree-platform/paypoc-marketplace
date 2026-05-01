<?php

namespace Webkul\PAYPOC\IwexaConnector\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Webkul\PAYPOC\IwexaConnector\Models\IwexaSyncJob;

class CatalogSyncCompleted
{
    use Dispatchable;

    public $syncJob;
    public $result;

    public function __construct(IwexaSyncJob $syncJob, array $result)
    {
        $this->syncJob = $syncJob;
        $this->result = $result;
    }
}
