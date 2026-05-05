<?php

namespace Webkul\PAYPOC\IwexaConnector\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShippingRateUpsertRequest extends FormRequest
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
            'shipping_zone_id' => 'required|integer|exists:paypoc_shipping_zones,id',
            'min_weight_kg' => 'required|decimal:0,3|min:0',
            'max_weight_kg' => 'required|decimal:0,3|gte:min_weight_kg',
            'min_volume_cm3' => 'nullable|integer|min:0',
            'max_volume_cm3' => 'nullable|integer|min:0',
            'price_cents' => 'required|integer|min:0',
            'currency' => 'nullable|string|size:3',
            'carrier' => 'nullable|string|max:255',
            'shipping_method' => 'required|string|max:255',
            'delivery_min_days' => 'required|integer|min:0',
            'delivery_max_days' => 'required|integer|min:0|gte:delivery_min_days',
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
            'shipping_zone_id.required' => 'Shipping zone ID is required',
            'shipping_zone_id.exists' => 'Shipping zone does not exist',
            'min_weight_kg.required' => 'Minimum weight is required',
            'min_weight_kg.decimal' => 'Minimum weight must be a valid decimal',
            'min_weight_kg.min' => 'Minimum weight cannot be negative',
            'max_weight_kg.required' => 'Maximum weight is required',
            'max_weight_kg.decimal' => 'Maximum weight must be a valid decimal',
            'max_weight_kg.gte' => 'Maximum weight must be greater than or equal to minimum weight',
            'min_volume_cm3.integer' => 'Minimum volume must be an integer',
            'min_volume_cm3.min' => 'Minimum volume cannot be negative',
            'max_volume_cm3.integer' => 'Maximum volume must be an integer',
            'max_volume_cm3.min' => 'Maximum volume cannot be negative',
            'price_cents.required' => 'Price in cents is required',
            'price_cents.integer' => 'Price in cents must be an integer',
            'price_cents.min' => 'Price in cents cannot be negative',
            'currency.size' => 'Currency must be a 3-character code',
            'carrier.max' => 'Carrier name cannot exceed 255 characters',
            'shipping_method.required' => 'Shipping method is required',
            'shipping_method.max' => 'Shipping method cannot exceed 255 characters',
            'delivery_min_days.required' => 'Minimum delivery days is required',
            'delivery_min_days.integer' => 'Minimum delivery days must be an integer',
            'delivery_min_days.min' => 'Minimum delivery days cannot be negative',
            'delivery_max_days.required' => 'Maximum delivery days is required',
            'delivery_max_days.integer' => 'Maximum delivery days must be an integer',
            'delivery_max_days.min' => 'Maximum delivery days cannot be negative',
            'delivery_max_days.gte' => 'Maximum delivery days must be greater than or equal to minimum',
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

        if (!$this->has('currency')) {
            $this->merge(['currency' => 'EUR']);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $minVolume = $this->input('min_volume_cm3');
            $maxVolume = $this->input('max_volume_cm3');

            if ($minVolume !== null && $maxVolume !== null && $minVolume > $maxVolume) {
                $validator->errors()->add('min_volume_cm3', 'Minimum volume cannot exceed maximum volume');
            }
        });
    }
}