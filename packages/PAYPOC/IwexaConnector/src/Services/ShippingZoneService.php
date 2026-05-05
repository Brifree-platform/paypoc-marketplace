<?php

namespace Webkul\PAYPOC\IwexaConnector\Services;

use Webkul\PAYPOC\IwexaConnector\Repositories\ShippingZoneRepository;
use Exception;

class ShippingZoneService
{
    protected $shippingZoneRepository;

    public function __construct(ShippingZoneRepository $shippingZoneRepository)
    {
        $this->shippingZoneRepository = $shippingZoneRepository;
    }

    /**
     * Create or update shipping zone
     */
    public function createOrUpdateZone(array $zoneData): array
    {
        $this->validateZoneData($zoneData);

        $zone = $this->shippingZoneRepository->upsert($zoneData);

        return $zone->toArray();
    }

    /**
     * Validate zone data
     */
    protected function validateZoneData(array $data): void
    {
        if (empty($data['origin_country'])) {
            throw new Exception('Origin country is required');
        }

        if (empty($data['destination_country'])) {
            throw new Exception('Destination country is required');
        }

        if (strlen($data['origin_country']) !== 2 || strlen($data['destination_country']) !== 2) {
            throw new Exception('Countries must be 2-character codes');
        }
    }

    /**
     * Find zone by countries
     */
    public function findZone(string $originCountry, string $destinationCountry): ?array
    {
        $zone = $this->shippingZoneRepository->findZone($originCountry, $destinationCountry);

        return $zone ? $zone->toArray() : null;
    }

    /**
     * Get all active zones
     */
    public function getActiveZones(): array
    {
        return $this->shippingZoneRepository->active()->toArray();
    }
}