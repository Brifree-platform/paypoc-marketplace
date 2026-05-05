<?php

namespace Webkul\PAYPOC\IwexaConnector\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShippingZoneUpsertRequest extends FormRequest
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
            'origin_country' => 'required|string|size:2',
            'destination_country' => 'required|string|size:2',
            'name' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive',
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
            'origin_country.required' => 'Origin country is required',
            'origin_country.size' => 'Origin country must be a 2-character code',
            'destination_country.required' => 'Destination country is required',
            'destination_country.size' => 'Destination country must be a 2-character code',
            'name.max' => 'Name cannot exceed 255 characters',
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