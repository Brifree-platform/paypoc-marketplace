@extends('layouts.master')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>Create Category Mapping</h5>
                </div>
                <form action="{{ route('admin.iwexa.category-mappings.store') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label for="source_category">Source Category *</label>
                            <input type="text" class="form-control @error('source_category') is-invalid @enderror" id="source_category" name="source_category" value="{{ old('source_category') }}" required>
                            @error('source_category')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>

                        <div class="form-group">
                            <label for="paypoc_category_id">PAYPOC Category ID</label>
                            <input type="number" class="form-control @error('paypoc_category_id') is-invalid @enderror" id="paypoc_category_id" name="paypoc_category_id" value="{{ old('paypoc_category_id') }}">
                            @error('paypoc_category_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>

                        <div class="form-group">
                            <label for="bagisto_category_id">Bagisto Category ID</label>
                            <input type="number" class="form-control @error('bagisto_category_id') is-invalid @enderror" id="bagisto_category_id" name="bagisto_category_id" value="{{ old('bagisto_category_id') }}">
                            @error('bagisto_category_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>

                        <div class="form-group">
                            <label for="vendor_code">Vendor Code</label>
                            <input type="text" class="form-control @error('vendor_code') is-invalid @enderror" id="vendor_code" name="vendor_code" value="{{ old('vendor_code') }}">
                            @error('vendor_code')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>

                        <div class="form-group">
                            <label for="status">Status *</label>
                            <select class="form-control @error('status') is-invalid @enderror" id="status" name="status" required>
                                <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Create</button>
                        <a href="{{ route('admin.iwexa.category-mappings.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
