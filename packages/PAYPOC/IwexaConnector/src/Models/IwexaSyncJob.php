<?php

namespace Webkul\PAYPOC\IwexaConnector\Models;

use Illuminate\Database\Eloquent\Model;

class IwexaSyncJob extends Model
{
    protected $table = 'paypoc_iwexa_sync_jobs';

    protected $fillable = [
        'type',
        'status',
        'payload',
        'response',
        'attempts',
        'last_error',
        'idempotency_key',
    ];

    protected $casts = [
        'payload' => 'array',
        'response' => 'array',
    ];

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeExpired($query, $hoursAgo = 24)
    {
        return $query->where('created_at', '<', now()->subHours($hoursAgo));
    }

    public function markProcessing()
    {
        $this->update(['status' => 'processing']);
        return $this;
    }

    public function markCompleted()
    {
        $this->update(['status' => 'completed']);
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
        return $this->attempts < $maxAttempts && $this->status === 'failed';
    }

    public function incrementAttempts()
    {
        $this->increment('attempts');
        return $this;
    }
}
