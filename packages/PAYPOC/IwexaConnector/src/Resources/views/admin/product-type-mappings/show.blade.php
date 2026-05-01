@extends('layouts.master')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>Product Type Mapping: {{ $mapping->source_product_type }}</h5>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-3">Source System</dt>
                        <dd class="col-sm-9">{{ $mapping->source_system }}</dd>
                        
                        <dt class="col-sm-3">Source Product Type</dt>
                        <dd class="col-sm-9">{{ $mapping->source_product_type }}</dd>
                        
                        <dt class="col-sm-3">Vendor Code</dt>
                        <dd class="col-sm-9">{{ $mapping->vendor_code ?? '-' }}</dd>
                        
                        <dt class="col-sm-3">Status</dt>
                        <dd class="col-sm-9">
                            <span class="badge badge-{{ $mapping->status === 'active' ? 'success' : 'warning' }}">
                                {{ $mapping->status }}
                            </span>
                        </dd>
                    </dl>

                    <h6 class="mt-4">Attribute Mappings</h6>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Source Attribute Code</th>
                                <th>Bagisto Attribute Code</th>
                                <th>Variant Axis</th>
                                <th>Required</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($attributeMappings as $attr)
                            <tr>
                                <td>{{ $attr->source_attribute_code }}</td>
                                <td>{{ $attr->bagisto_attribute_code ?? '-' }}</td>
                                <td><span class="badge badge-{{ $attr->variant_axis ? 'info' : 'secondary' }}">{{ $attr->variant_axis ? 'Yes' : 'No' }}</span></td>
                                <td><span class="badge badge-{{ $attr->required ? 'warning' : 'secondary' }}">{{ $attr->required ? 'Yes' : 'No' }}</span></td>
                                <td>{{ $attr->status }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
