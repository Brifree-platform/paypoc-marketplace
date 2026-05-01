<?php

namespace Webkul\PAYPOC\IwexaConnector\Models;

use Illuminate\Database\Eloquent\Model;

class IwexaWebhookEvent extends Model
{
    protected $table = 'paypoc_iwexa_webhook_events';

    protected $fillable = [
        'event_id',
        'delivery_id',
        'event_type',
        'payload',
        'status',
        'received_at',
        'processed_at',
        'attempts',
        'last_error',
    ];

    protected $casts = [
        'payload' => 'array',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function markProcessed()
    {
        $this->update([
            'status' => 'processed',
            'processed_at' => now(),
        ]);
        return $this;
    }

    public function markFailed($error = null)
    {
        $this->update([
            'status' => 'failed',
            'last_error' => $error,
        ]);
        return $this;
    }

    public function canRetry($maxAttempts = 3)
    {
        return $this->attempts < $maxAttempts;
    }

    public function incrementAttempts()
    {
        $this->increment('attempts');
        return $this;
    }
}
