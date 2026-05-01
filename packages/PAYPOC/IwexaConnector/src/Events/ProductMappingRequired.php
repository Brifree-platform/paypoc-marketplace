<?php

namespace Webkul\PAYPOC\IwexaConnector\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Webkul\PAYPOC\IwexaConnector\Models\IwexaProduct;

class ProductMappingRequired
{
    use Dispatchable;

    public $product;

    public function __construct(IwexaProduct $product)
    {
        $this->product = $product;
    }
}
