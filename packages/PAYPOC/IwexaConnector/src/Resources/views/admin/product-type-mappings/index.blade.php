@extends('layouts.master')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>Product Type Mappings</h5>
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Source System</th>
                                <th>Source Product Type</th>
                                <th>Vendor Code</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($mappings as $mapping)
                            <tr>
                                <td>{{ $mapping->source_system }}</td>
                                <td>{{ $mapping->source_product_type }}</td>
                                <td>{{ $mapping->vendor_code ?? '-' }}</td>
                                <td>
                                    <span class="badge badge-{{ $mapping->status === 'active' ? 'success' : 'warning' }}">
                                        {{ $mapping->status }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.iwexa.product-type-mappings.show', $mapping->id) }}" class="btn btn-sm btn-info">View</a>
                                    @if($mapping->status === 'draft')
                                    <form method="POST" action="{{ route('admin.iwexa.product-type-mappings.approve', $mapping->id) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.iwexa.product-type-mappings.reject', $mapping->id) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                                    </form>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">
                    {{ $mappings->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
