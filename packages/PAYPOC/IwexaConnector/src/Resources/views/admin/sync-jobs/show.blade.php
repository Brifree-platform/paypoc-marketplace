@extends('layouts.master')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>Sync Job #{{ $syncJob->id }}</h5>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-3">Type</dt>
                        <dd class="col-sm-9"><span class="badge badge-info">{{ $syncJob->type }}</span></dd>
                        
                        <dt class="col-sm-3">Status</dt>
                        <dd class="col-sm-9">
                            <span class="badge badge-{{ $syncJob->status === 'completed' ? 'success' : ($syncJob->status === 'failed' ? 'danger' : 'warning') }}">
                                {{ $syncJob->status }}
                            </span>
                        </dd>
                        
                        <dt class="col-sm-3">Idempotency Key</dt>
                        <dd class="col-sm-9"><code>{{ $syncJob->idempotency_key }}</code></dd>
                        
                        <dt class="col-sm-3">Attempts</dt>
                        <dd class="col-sm-9">{{ $syncJob->attempts }}</dd>
                        
                        <dt class="col-sm-3">Created At</dt>
                        <dd class="col-sm-9">{{ $syncJob->created_at->format('Y-m-d H:i:s') }}</dd>
                        
                        <dt class="col-sm-3">Updated At</dt>
                        <dd class="col-sm-9">{{ $syncJob->updated_at->format('Y-m-d H:i:s') }}</dd>
                    </dl>

                    @if($syncJob->last_error)
                    <div class="alert alert-danger mt-3">
                        <strong>Last Error:</strong>
                        <pre>{{ $syncJob->last_error }}</pre>
                    </div>
                    @endif

                    @if($syncJob->response)
                    <h6 class="mt-4">Response</h6>
                    <pre>{{ json_encode($syncJob->response, JSON_PRETTY_PRINT) }}</pre>
                    @endif
                </div>
                <div class="card-footer">
                    @if($syncJob->status === 'failed')
                    <form method="POST" action="{{ route('admin.iwexa.sync-jobs.retry', $syncJob->id) }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="btn btn-warning">Retry</button>
                    </form>
                    @endif
                    <a href="{{ route('admin.iwexa.sync-jobs.index') }}" class="btn btn-secondary">Back</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
