@extends('layouts.master')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>Attribute Mapping: {{ $mapping->source_attribute_code }}</h5>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-3">Source Attribute Code</dt>
                        <dd class="col-sm-9">{{ $mapping->source_attribute_code }}</dd>
                        
                        <dt class="col-sm-3">Bagisto Attribute Code</dt>
                        <dd class="col-sm-9">{{ $mapping->bagisto_attribute_code ?? '-' }}</dd>
                        
                        <dt class="col-sm-3">Required</dt>
                        <dd class="col-sm-9">{{ $mapping->required ? 'Yes' : 'No' }}</dd>
                        
                        <dt class="col-sm-3">Variant Axis</dt>
                        <dd class="col-sm-9">{{ $mapping->variant_axis ? 'Yes' : 'No' }}</dd>
                        
                        <dt class="col-sm-3">Status</dt>
                        <dd class="col-sm-9">
                            <span class="badge badge-{{ $mapping->status === 'active' ? 'success' : 'warning' }}">
                                {{ $mapping->status }}
                            </span>
                        </dd>
                    </dl>

                    <h6 class="mt-4">Value Mappings</h6>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Source Value</th>
                                <th>Normalized Value</th>
                                <th>Bagisto Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($valueMapping as $vm)
                            <tr>
                                <td>{{ $vm->source_value }}</td>
                                <td>{{ $vm->normalized_value }}</td>
                                <td>{{ $vm->bagisto_value ?? '-' }}</td>
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
