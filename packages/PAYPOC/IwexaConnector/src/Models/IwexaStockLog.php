<?php

namespace Webkul\PAYPOC\IwexaConnector\Models;

use Illuminate\Database\Eloquent\Model;

class IwexaStockLog extends Model
{
    protected $table = 'paypoc_iwexa_stock_logs';

    protected $fillable = [
        'sku',
        'warehouse_code',
        'quantity',
        'source',
    ];

    public $timestamps = false;

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
