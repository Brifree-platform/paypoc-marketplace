<?php

namespace Webkul\PAYPOC\IwexaConnector\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RoutingQuote extends Model
{
    use HasFactory;

    protected $table = 'paypoc_iwexa_routing_quotes';

    protected $fillable = [
        'vendor_code',
        'sku',
        'warehouse_code',
        'fulfillment_type',
        'origin_country',
        'destination_country',
        'destination_postal_code',
        'quantity',
        'product_weight_kg',
        'product_volume_cm3',
        'shipping_method',
        'shipping_cost_cents',
        'currency',
        'preparation_time_min_days',
        'preparation_time_max_days',
        'delivery_min_days',
        'delivery_max_days',
        'final_delivery_min_days',
        'final_delivery_max_days',
        'original_request_payload',
        'calculated_response_payload',
    ];

    protected $casts = [
        'original_request_payload' => 'array',
        'calculated_response_payload' => 'array',
    ];

    /**
     * Scope for latest quotes by SKU
     */
    public function scopeLatestForSku($query, string $sku)
    {
        return $query->where('sku', $sku)->orderBy('created_at', 'desc');
    }

    /**
     * Get warehouse relationship
     */
    public function warehouse()
    {
        return $this->belongsTo(IwexaWarehouse::class, 'warehouse_code', 'warehouse_code');
    }

    /**
     * Calculate final delivery times
     */
    public function calculateFinalDeliveryTimes(): void
    {
        $this->final_delivery_min_days = $this->preparation_time_min_days + $this->delivery_min_days;
        $this->final_delivery_max_days = $this->preparation_time_max_days + $this->delivery_max_days;
    }
}