<?php

namespace Webkul\PAYPOC\IwexaConnector\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ShippingZone extends Model
{
    use HasFactory;

    protected $table = 'paypoc_shipping_zones';

    protected $fillable = [
        'origin_country',
        'destination_country',
        'name',
        'status',
    ];

    /**
     * Scope for active zones
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get shipping rates for this zone
     */
    public function rates()
    {
        return $this->hasMany(ShippingRate::class, 'shipping_zone_id');
    }

    /**
     * Find zone by origin and destination countries
     */
    public static function findByCountries(string $originCountry, string $destinationCountry)
    {
        return static::where('origin_country', $originCountry)
                    ->where('destination_country', $destinationCountry)
                    ->first();
    }
}