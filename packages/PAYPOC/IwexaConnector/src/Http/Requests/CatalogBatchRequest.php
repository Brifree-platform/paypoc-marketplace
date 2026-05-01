<?php

namespace Webkul\PAYPOC\IwexaConnector\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CatalogBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'products' => 'required|array|min:1',
            'products.*.sku' => 'required|string',
            'products.*.iwexa_product_id' => 'nullable|string',
            'products.*.vendor_code' => 'required|string',
            'products.*.product_type' => 'nullable|string',
            'products.*.source_category' => 'nullable|string',
            'products.*.parent_sku' => 'nullable|string',
            'products.*.item_group_id' => 'nullable|string',
            'products.*.ean' => 'nullable|string',
            'products.*.currency' => 'required|string',
            'products.*.status' => 'nullable|in:active,inactive',
            'products.*.metadata' => 'nullable|array',
        ];
    }
}
