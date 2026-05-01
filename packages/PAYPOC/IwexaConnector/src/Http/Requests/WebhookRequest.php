<?php

namespace Webkul\PAYPOC\IwexaConnector\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => 'required|string',
            'delivery_id' => 'required|string',
            'event' => 'required|string',
            'data' => 'required|array',
            'timestamp' => 'required|string',
        ];
    }
}
