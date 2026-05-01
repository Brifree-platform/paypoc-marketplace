<?php

namespace Webkul\PAYPOC\IwexaConnector\Events;

use Illuminate\Foundation\Events\Dispatchable;

class AttributeProvisioningRequired
{
    use Dispatchable;

    public $sourceAttributeCode;
    public $productTypeMappingId;

    public function __construct(string $sourceAttributeCode, int $productTypeMappingId)
    {
        $this->sourceAttributeCode = $sourceAttributeCode;
        $this->productTypeMappingId = $productTypeMappingId;
    }
}
