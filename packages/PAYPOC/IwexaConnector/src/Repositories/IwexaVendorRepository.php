<?php

namespace Webkul\PAYPOC\IwexaConnector\Repositories;

use Webkul\PAYPOC\IwexaConnector\Models\IwexaVendor;
use Webkul\Core\Eloquent\Repository;

class IwexaVendorRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    function model()
    {
        return IwexaVendor::class;
    }

    /**
     * Find vendor by vendor_code
     *
     * @param string $vendorCode
     * @return IwexaVendor|null
     */
    public function findByVendorCode($vendorCode)
    {
        return $this->findOneByField('vendor_code', $vendorCode);
    }

    /**
     * Check if vendor exists by vendor_code
     *
     * @param string $vendorCode
     * @return bool
     */
    public function existsByVendorCode($vendorCode)
    {
        return $this->model->where('vendor_code', $vendorCode)->exists();
    }

    /**
     * Create or update vendor (upsert)
     *
     * @param array $data
     * @return IwexaVendor
     */
    public function upsert(array $data)
    {
        $vendor = $this->findByVendorCode($data['vendor_code']);

        if ($vendor) {
            $vendor->update($data);
            return $vendor->fresh();
        }

        return $this->create($data);
    }

    /**
     * Get active vendors
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveVendors()
    {
        return $this->model->active()->get();
    }
}