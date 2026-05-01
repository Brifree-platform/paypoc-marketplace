<?php

namespace Webkul\PAYPOC\IwexaConnector\Repositories;

use Webkul\PAYPOC\IwexaConnector\Models\IwexaSyncJob;

class IwexaSyncJobRepository
{
    public function findById(int $id): ?IwexaSyncJob
    {
        return IwexaSyncJob::find($id);
    }

    public function findByIdempotencyKey(string $key): ?IwexaSyncJob
    {
        return IwexaSyncJob::where('idempotency_key', $key)->first();
    }

    public function findFailed()
    {
        return IwexaSyncJob::failed()->get();
    }

    public function findExpired(int $hoursAgo = 24)
    {
        return IwexaSyncJob::expired($hoursAgo)->get();
    }

    public function create(array $data): IwexaSyncJob
    {
        return IwexaSyncJob::create($data);
    }

    public function update(IwexaSyncJob $job, array $data): IwexaSyncJob
    {
        $job->update($data);
        return $job;
    }
}
