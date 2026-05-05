<?php

namespace Webkul\PAYPOC\IwexaConnector\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VendorUpsertRequest extends FormRequest
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
            'vendor_code' => 'required|string|max:255',
            'vendor_name' => 'required|string|max:255',
            'legal_name' => 'required|string|max:255',
            'vat_number' => 'nullable|string|max:255',
            'tax_code' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:2',
            'website' => 'nullable|url|max:255',
            'status' => 'required|in:active,inactive',
            'responsible_person' => 'nullable|array',
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
            'vendor_code.required' => 'Vendor code is required',
            'vendor_name.required' => 'Vendor name is required',
            'legal_name.required' => 'Legal name is required',
            'status.required' => 'Status is required',
            'status.in' => 'Status must be either active or inactive',
            'website.url' => 'Website must be a valid URL',
            'country.max' => 'Country code must be 2 characters',
        ];
    }
}