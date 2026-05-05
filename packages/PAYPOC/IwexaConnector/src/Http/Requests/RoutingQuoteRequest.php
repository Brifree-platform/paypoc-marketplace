<?php

namespace Webkul\PAYPOC\IwexaConnector\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RoutingQuoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sku' => 'required|string|max:255',
            'vendor_code' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1',
            'destination_country' => 'required|string|size:2',
            'destination_postal_code' => 'nullable|string|max:20',
            'product_weight_kg' => 'required|decimal:0,3|min:0',
            'product_dimensions' => 'nullable|array',
            'product_dimensions.length_cm' => 'required_with:product_dimensions|decimal:0,2|min:0',
            'product_dimensions.width_cm' => 'required_with:product_dimensions|decimal:0,2|min:0',
            'product_dimensions.height_cm' => 'required_with:product_dimensions|decimal:0,2|min:0',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sku.required' => 'SKU is required',
            'sku.max' => 'SKU cannot exceed 255 characters',
            'vendor_code.required' => 'Vendor code is required',
            'vendor_code.max' => 'Vendor code cannot exceed 255 characters',
            'quantity.required' => 'Quantity is required',
            'quantity.integer' => 'Quantity must be an integer',
            'quantity.min' => 'Quantity must be at least 1',
            'destination_country.required' => 'Destination country is required',
            'destination_country.size' => 'Destination country must be a 2-character code',
            'destination_postal_code.max' => 'Destination postal code cannot exceed 20 characters',
            'product_weight_kg.required' => 'Product weight is required',
            'product_weight_kg.decimal' => 'Product weight must be a valid decimal',
            'product_weight_kg.min' => 'Product weight cannot be negative',
            'product_dimensions.array' => 'Product dimensions must be an array',
            'product_dimensions.length_cm.required_with' => 'Length is required when providing dimensions',
            'product_dimensions.length_cm.decimal' => 'Length must be a valid decimal',
            'product_dimensions.length_cm.min' => 'Length cannot be negative',
            'product_dimensions.width_cm.required_with' => 'Width is required when providing dimensions',
            'product_dimensions.width_cm.decimal' => 'Width must be a valid decimal',
            'product_dimensions.width_cm.min' => 'Width cannot be negative',
            'product_dimensions.height_cm.required_with' => 'Height is required when providing dimensions',
            'product_dimensions.height_cm.decimal' => 'Height must be a valid decimal',
            'product_dimensions.height_cm.min' => 'Height cannot be negative',
        ];
    }
}