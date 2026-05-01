<?php

namespace Webkul\PAYPOC\IwexaConnector\Models;

use Illuminate\Database\Eloquent\Model;

class IwexaProduct extends Model
{
    protected $table = 'paypoc_iwexa_products';

    protected $fillable = [
        'sku',
        'parent_sku',
        'item_group_id',
        'ean',
        'iwexa_product_id',
        'vendor_code',
        'product_type',
        'source_category',
        'status',
        'pending_mapping',
        'original_iwexa_payload',
        'meta',
    ];

    protected $casts = [
        'original_iwexa_payload' => 'array',
        'meta' => 'array',
        'pending_mapping' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePendingMapping($query)
    {
        return $query->where('pending_mapping', true);
    }

    public function scopeByVendor($query, $vendorCode)
    {
        return $query->where('vendor_code', $vendorCode);
    }

    public function scopeByProductType($query, $productType)
    {
        return $query->where('product_type', $productType);
    }

    public function scopeByParentSku($query, $parentSku)
    {
        return $query->where('parent_sku', $parentSku);
    }

    public function markPendingMapping()
    {
        $this->update(['pending_mapping' => true]);
        return $this;
    }

    public function markMappingComplete()
    {
        $this->update(['pending_mapping' => false]);
        return $this;
    }

    public function variants()
    {
        return $this->hasMany(self::class, 'parent_sku', 'sku');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_sku', 'sku');
    }
}
