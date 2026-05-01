@extends('layouts.master')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>Attribute Mappings</h5>
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Source Attribute</th>
                                <th>Bagisto Attribute</th>
                                <th>Variant Axis</th>
                                <th>Required</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($mappings as $mapping)
                            <tr>
                                <td>{{ $mapping->source_attribute_code }}</td>
                                <td>{{ $mapping->bagisto_attribute_code ?? '-' }}</td>
                                <td><span class="badge badge-{{ $mapping->variant_axis ? 'info' : 'secondary' }}">{{ $mapping->variant_axis ? 'Yes' : 'No' }}</span></td>
                                <td><span class="badge badge-{{ $mapping->required ? 'warning' : 'secondary' }}">{{ $mapping->required ? 'Yes' : 'No' }}</span></td>
                                <td>{{ $mapping->status }}</td>
                                <td>
                                    <a href="{{ route('admin.iwexa.attribute-mappings.show', $mapping->id) }}" class="btn btn-sm btn-info">View</a>
                                    @if($mapping->status === 'draft')
                                    <form method="POST" action="{{ route('admin.iwexa.attribute-mappings.approve', $mapping->id) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">Approve</button>
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
