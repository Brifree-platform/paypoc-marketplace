<?php

namespace Webkul\PAYPOC\IwexaConnector\Models;

use Illuminate\Database\Eloquent\Model;

class ProductTypeMapping extends Model
{
    protected $table = 'paypoc_product_type_mappings';

    protected $fillable = [
        'source_system',
        'source_product_type',
        'vendor_code',
        'bagisto_attribute_family_id',
        'google_product_category',
        'google_product_type',
        'amazon_product_type',
        'status',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeForVendor($query, $vendorCode = null)
    {
        if ($vendorCode) {
            return $query->where('vendor_code', $vendorCode);
        }
        return $query;
    }

    public function attributeMappings()
    {
        return $this->hasMany(AttributeMapping::class, 'product_type_mapping_id');
    }
}
