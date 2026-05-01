@extends('layouts.master')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>Sync Jobs</h5>
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Attempts</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($syncJobs as $job)
                            <tr>
                                <td>{{ $job->id }}</td>
                                <td><span class="badge badge-info">{{ $job->type }}</span></td>
                                <td>
                                    <span class="badge badge-{{ $job->status === 'completed' ? 'success' : ($job->status === 'failed' ? 'danger' : 'warning') }}">
                                        {{ $job->status }}
                                    </span>
                                </td>
                                <td>{{ $job->attempts }}</td>
                                <td>{{ $job->created_at->format('Y-m-d H:i:s') }}</td>
                                <td>
                                    <a href="{{ route('admin.iwexa.sync-jobs.show', $job->id) }}" class="btn btn-sm btn-info">View</a>
                                    @if($job->status === 'failed')
                                    <form method="POST" action="{{ route('admin.iwexa.sync-jobs.retry', $job->id) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-warning">Retry</button>
                                    </form>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">
                    {{ $syncJobs->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
