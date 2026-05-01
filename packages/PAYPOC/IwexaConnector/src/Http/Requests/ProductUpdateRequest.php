<?php

namespace Webkul\PAYPOC\IwexaConnector\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_type' => 'nullable|string',
            'source_category' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
            'metadata' => 'nullable|array',
        ];
    }
}
