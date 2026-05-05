<?php

namespace Webkul\PAYPOC\IwexaConnector\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WarehouseStockUpdateRequest extends FormRequest
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
            'sku' => 'required|string|max:255',
            'vendor_code' => 'required|string|max:255',
            'quantity' => 'required|integer|min:0',
            'reserved_quantity' => 'nullable|integer|min:0',
            'preparation_time_min_days' => 'nullable|integer|min:0',
            'preparation_time_max_days' => 'nullable|integer|min:0',
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
            'sku.required' => 'SKU is required',
            'vendor_code.required' => 'Vendor code is required',
            'quantity.required' => 'Quantity is required',
            'quantity.integer' => 'Quantity must be an integer',
            'quantity.min' => 'Quantity cannot be negative',
            'reserved_quantity.integer' => 'Reserved quantity must be an integer',
            'reserved_quantity.min' => 'Reserved quantity cannot be negative',
            'preparation_time_min_days.integer' => 'Preparation time min days must be an integer',
            'preparation_time_min_days.min' => 'Preparation time min days cannot be negative',
            'preparation_time_max_days.integer' => 'Preparation time max days must be an integer',
            'preparation_time_max_days.min' => 'Preparation time max days cannot be negative',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'reserved_quantity' => $this->input('reserved_quantity', 0),
            'preparation_time_min_days' => $this->input('preparation_time_min_days', 0),
            'preparation_time_max_days' => $this->input('preparation_time_max_days', 0),
        ]);
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $quantity = $this->input('quantity', 0);
            $reserved = $this->input('reserved_quantity', 0);

            if ($reserved > $quantity) {
                $validator->errors()->add('reserved_quantity', 'Reserved quantity cannot exceed total quantity');
            }

            $minDays = $this->input('preparation_time_min_days', 0);
            $maxDays = $this->input('preparation_time_max_days', 0);

            if ($minDays > $maxDays) {
                $validator->errors()->add('preparation_time_min_days', 'Minimum preparation time cannot exceed maximum');
            }
        });
    }
}