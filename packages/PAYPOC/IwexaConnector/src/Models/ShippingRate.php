<?php

namespace Webkul\PAYPOC\IwexaConnector\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ShippingRate extends Model
{
    use HasFactory;

    protected $table = 'paypoc_shipping_rates';

    protected $fillable = [
        'shipping_zone_id',
        'min_weight_kg',
        'max_weight_kg',
        'min_volume_cm3',
        'max_volume_cm3',
        'price_cents',
        'currency',
        'carrier',
        'shipping_method',
        'delivery_min_days',
        'delivery_max_days',
        'status',
    ];

    /**
     * Get shipping zone relationship
     */
    public function zone()
    {
        return $this->belongsTo(ShippingZone::class, 'shipping_zone_id');
    }

    /**
     * Scope for active rates
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Check if this rate matches the given weight and volume
     */
    public function matches(float $weightKg, ?int $volumeCm3 = null): bool
    {
        if ($weightKg < $this->min_weight_kg || $weightKg > $this->max_weight_kg) {
            return false;
        }

        if ($volumeCm3 !== null) {
            if ($this->min_volume_cm3 !== null && $volumeCm3 < $this->min_volume_cm3) {
                return false;
            }
            if ($this->max_volume_cm3 !== null && $volumeCm3 > $this->max_volume_cm3) {
                return false;
            }
        }

        return true;
    }
}