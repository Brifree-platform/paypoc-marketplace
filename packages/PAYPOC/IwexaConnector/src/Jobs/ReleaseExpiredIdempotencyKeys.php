<?php

namespace Webkul\PAYPOC\IwexaConnector\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Webkul\PAYPOC\IwexaConnector\Models\IwexaWebhookEvent;
use Illuminate\Support\Facades\Log;

class ReleaseExpiredIdempotencyKeys implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $expiryHours = config('iwexa-connector.idempotency_key_expiry_hours', 24);
        
        $expiredWebhookEvents = IwexaWebhookEvent::where('created_at', '<', now()->subHours($expiryHours))->delete();

        Log::info("Released {$expiredWebhookEvents} expired webhook events");
    }
}
