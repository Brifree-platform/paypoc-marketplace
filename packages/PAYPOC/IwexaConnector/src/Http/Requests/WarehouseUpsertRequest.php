<?php

namespace Webkul\PAYPOC\IwexaConnector\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WarehouseUpsertRequest extends FormRequest
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
            'warehouse_code' => 'required|string|max:255',
            'vendor_code' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
            'type' => 'required|in:central,vendor',
            'country' => 'required|string|size:2',
            'city' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'postal_code' => 'nullable|string|max:20',
            'status' => 'nullable|in:active,inactive',
            'original_iwexa_payload' => 'nullable|array',
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
            'warehouse_code.required' => 'Warehouse code is required',
            'name.required' => 'Warehouse name is required',
            'type.required' => 'Warehouse type is required',
            'type.in' => 'Warehouse type must be central or vendor',
            'country.required' => 'Country is required',
            'country.size' => 'Country must be a 2-character code',
            'status.in' => 'Status must be active or inactive',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if (!$this->has('status')) {
            $this->merge(['status' => 'active']);
        }
    }
}