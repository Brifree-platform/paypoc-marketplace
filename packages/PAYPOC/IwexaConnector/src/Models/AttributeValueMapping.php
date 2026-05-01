<?php

namespace Webkul\PAYPOC\IwexaConnector\Models;

use Illuminate\Database\Eloquent\Model;

class AttributeValueMapping extends Model
{
    protected $table = 'paypoc_attribute_value_mappings';

    protected $fillable = [
        'source_value',
        'normalized_value',
        'bagisto_value',
        'attribute_mapping_id',
    ];

    public function attributeMapping()
    {
        return $this->belongsTo(AttributeMapping::class, 'attribute_mapping_id');
    }
}
