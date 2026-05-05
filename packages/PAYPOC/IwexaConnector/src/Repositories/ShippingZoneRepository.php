<?php

namespace Webkul\PAYPOC\IwexaConnector\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\PAYPOC\IwexaConnector\Models\ShippingZone;

class ShippingZoneRepository extends Repository
{
    /**
     * Specify Model class name
     */
    function model(): string
    {
        return ShippingZone::class;
    }

    /**
     * Find zone by origin and destination countries
     */
    public function findZone(string $originCountry, string $destinationCountry)
    {
        return $this->findOneByField([
            'origin_country' => $originCountry,
            'destination_country' => $destinationCountry,
            'status' => 'active'
        ]);
    }

    /**
     * Upsert shipping zone
     */
    public function upsert(array $data)
    {
        return $this->updateOrCreate(
            [
                'origin_country' => $data['origin_country'],
                'destination_country' => $data['destination_country']
            ],
            $data
        );
    }

    /**
     * Get all active zones
     */
    public function active()
    {
        return $this->findWhere(['status' => 'active']);
    }
}