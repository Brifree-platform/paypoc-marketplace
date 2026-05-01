<?php

namespace Webkul\PAYPOC\IwexaConnector\Models;

use Illuminate\Database\Eloquent\Model;

class AttributeMapping extends Model
{
    protected $table = 'paypoc_attribute_mappings';

    protected $fillable = [
        'product_type_mapping_id',
        'source_attribute_code',
        'bagisto_attribute_code',
        'bagisto_attribute_id',
        'required',
        'variant_axis',
        'searchable',
        'filterable',
        'validation_rules',
        'unit_mapping',
        'value_mapping',
        'status',
    ];

    protected $casts = [
        'required' => 'boolean',
        'variant_axis' => 'boolean',
        'searchable' => 'boolean',
        'filterable' => 'boolean',
        'validation_rules' => 'array',
        'unit_mapping' => 'array',
        'value_mapping' => 'array',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeRequired($query)
    {
        return $query->where('required', true);
    }

    public function scopeVariantAxis($query)
    {
        return $query->where('variant_axis', true);
    }

    public function productTypeMapping()
    {
        return $this->belongsTo(ProductTypeMapping::class, 'product_type_mapping_id');
    }

    public function attributeValueMappings()
    {
        return $this->hasMany(AttributeValueMapping::class, 'attribute_mapping_id');
    }
}
