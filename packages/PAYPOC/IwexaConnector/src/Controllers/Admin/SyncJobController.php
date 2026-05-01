<?php

namespace Webkul\PAYPOC\IwexaConnector\Controllers\Admin;

use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Webkul\PAYPOC\IwexaConnector\Models\IwexaSyncJob;
use Webkul\PAYPOC\IwexaConnector\Jobs\RetryFailedSyncJob;

class SyncJobController
{
    public function index(): View
    {
        $syncJobs = IwexaSyncJob::orderBy('created_at', 'desc')->paginate(20);
        return view('iwexa::admin.sync-jobs.index', ['syncJobs' => $syncJobs]);
    }

    public function show(int $id): View
    {
        $syncJob = IwexaSyncJob::findOrFail($id);
        return view('iwexa::admin.sync-jobs.show', ['syncJob' => $syncJob]);
    }

    public function retry(int $id): RedirectResponse
    {
        $syncJob = IwexaSyncJob::findOrFail($id);

        if (!$syncJob->canRetry()) {
            return back()->with('error', 'This sync job cannot be retried');
        }

        dispatch(new RetryFailedSyncJob($syncJob->id));

        return back()->with('success', 'Sync job queued for retry');
    }
}
