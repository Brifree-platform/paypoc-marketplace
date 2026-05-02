<?php

namespace Webkul\PAYPOC\IwexaConnector\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IwexaVendor extends Model
{
    use HasFactory;

    protected $table = 'paypoc_iwexa_vendors';

    protected $fillable = [
        'vendor_code',
        'vendor_name',
        'legal_name',
        'vat_number',
        'tax_code',
        'address',
        'city',
        'province',
        'postal_code',
        'country',
        'website',
        'status',
        'responsible_person',
        'original_iwexa_payload',
    ];

    protected $casts = [
        'responsible_person' => 'array',
        'original_iwexa_payload' => 'array',
        'status' => 'string',
    ];

    /**
     * Scope for active vendors
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for inactive vendors
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }
}