@extends('layouts.master')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>Category Mappings</h5>
                    <a href="{{ route('admin.iwexa.category-mappings.create') }}" class="btn btn-sm btn-primary float-right">Create Mapping</a>
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Source Category</th>
                                <th>PAYPOC Category</th>
                                <th>Bagisto Category</th>
                                <th>Product Type</th>
                                <th>Vendor Code</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($mappings as $mapping)
                            <tr>
                                <td>{{ $mapping->source_category }}</td>
                                <td>{{ $mapping->paypoc_category_id ?? '-' }}</td>
                                <td>{{ $mapping->bagisto_category_id ?? '-' }}</td>
                                <td>{{ $mapping->product_type ?? '-' }}</td>
                                <td>{{ $mapping->vendor_code ?? '-' }}</td>
                                <td>
                                    <span class="badge badge-{{ $mapping->status === 'active' ? 'success' : 'warning' }}">
                                        {{ $mapping->status }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.iwexa.category-mappings.edit', $mapping->id) }}" class="btn btn-sm btn-info">Edit</a>
                                    <form method="POST" action="{{ route('admin.iwexa.category-mappings.destroy', $mapping->id) }}" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                                    </form>
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
