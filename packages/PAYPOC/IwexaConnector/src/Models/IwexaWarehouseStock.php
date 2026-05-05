<?php

namespace Webkul\PAYPOC\IwexaConnector\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class IwexaWarehouseStock extends Model
{
    use HasFactory;

    protected $table = 'paypoc_iwexa_warehouse_stocks';

    protected $fillable = [
        'warehouse_code',
        'sku',
        'vendor_code',
        'quantity',
        'reserved_quantity',
        'available_quantity',
        'preparation_time_min_days',
        'preparation_time_max_days',
        'original_iwexa_payload',
    ];

    protected $casts = [
        'original_iwexa_payload' => 'array',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->available_quantity = $model->quantity - $model->reserved_quantity;
        });
    }

    /**
     * Get warehouse relationship
     */
    public function warehouse()
    {
        return $this->belongsTo(IwexaWarehouse::class, 'warehouse_code', 'warehouse_code');
    }

    /**
     * Scope for available stock
     */
    public function scopeAvailable($query)
    {
        return $query->where('available_quantity', '>', 0);
    }

    /**
     * Check if stock is available for given quantity
     */
    public function hasAvailableStock(int $quantity): bool
    {
        return $this->available_quantity >= $quantity;
    }
}