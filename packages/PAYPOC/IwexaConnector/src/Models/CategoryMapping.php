<?php

namespace Webkul\PAYPOC\IwexaConnector\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryMapping extends Model
{
    protected $table = 'paypoc_category_mappings';

    protected $fillable = [
        'source_category',
        'paypoc_category_id',
        'bagisto_category_id',
        'google_product_category',
        'product_type',
        'vendor_code',
        'override',
        'status',
    ];

    protected $casts = [
        'override' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForVendor($query, $vendorCode)
    {
        return $query->where('vendor_code', $vendorCode);
    }

    public function scopeForProductType($query, $productType)
    {
        return $query->where('product_type', $productType);
    }
}
