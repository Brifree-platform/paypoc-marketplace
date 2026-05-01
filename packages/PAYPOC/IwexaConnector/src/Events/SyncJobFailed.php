<?php

namespace Webkul\PAYPOC\IwexaConnector\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Webkul\PAYPOC\IwexaConnector\Models\IwexaSyncJob;

class SyncJobFailed
{
    use Dispatchable;

    public $syncJob;
    public $error;

    public function __construct(IwexaSyncJob $syncJob, string $error)
    {
        $this->syncJob = $syncJob;
        $this->error = $error;
    }
}
