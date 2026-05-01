<?php

namespace Webkul\PAYPOC\IwexaConnector\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Webkul\PAYPOC\IwexaConnector\Models\IwexaSyncJob;
use Webkul\PAYPOC\IwexaConnector\Services\WebhookProcessorService;

class ProcessWebhook implements ShouldQueue
{
    use Queueable;

    protected $payload;
    protected $signature;
    protected $timestamp;

    public function __construct(array $payload, string $signature, string $timestamp)
    {
        $this->payload = $payload;
        $this->signature = $signature;
        $this->timestamp = $timestamp;
    }

    public function handle(WebhookProcessorService $webhookProcessorService): void
    {
        $webhookProcessorService->processWebhook($this->payload, $this->signature, $this->timestamp);
    }
}
