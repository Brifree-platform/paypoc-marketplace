<?php

namespace Webkul\PAYPOC\IwexaConnector\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StockUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stock_updates' => 'required|array|min:1',
            'stock_updates.*.sku' => 'required|string',
            'stock_updates.*.quantity' => 'required|integer|min:0',
            'stock_updates.*.warehouse_code' => 'nullable|string',
            'stock_updates.*.stock_status' => 'nullable|in:in_stock,out_of_stock',
        ];
    }
}
