<?php

namespace Webkul\PAYPOC\IwexaConnector\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class IwexaWarehouse extends Model
{
    use HasFactory;

    protected $table = 'paypoc_iwexa_warehouses';

    protected $fillable = [
        'warehouse_code',
        'vendor_code',
        'name',
        'type',
        'country',
        'city',
        'address',
        'postal_code',
        'status',
        'original_iwexa_payload',
    ];

    protected $casts = [
        'original_iwexa_payload' => 'array',
    ];

    /**
     * Scope for active warehouses
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for central warehouses
     */
    public function scopeCentral($query)
    {
        return $query->where('type', 'central');
    }

    /**
     * Scope for vendor warehouses
     */
    public function scopeVendor($query)
    {
        return $query->where('type', 'vendor');
    }

    /**
     * Get warehouse stocks
     */
    public function stocks()
    {
        return $this->hasMany(IwexaWarehouseStock::class, 'warehouse_code', 'warehouse_code');
    }
}