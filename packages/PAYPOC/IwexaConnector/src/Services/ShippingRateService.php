<?php

namespace Webkul\PAYPOC\IwexaConnector\Services;

use Webkul\PAYPOC\IwexaConnector\Repositories\ShippingRateRepository;
use Webkul\PAYPOC\IwexaConnector\Repositories\ShippingZoneRepository;
use Exception;

class ShippingRateService
{
    protected $shippingRateRepository;
    protected $shippingZoneRepository;

    public function __construct(
        ShippingRateRepository $shippingRateRepository,
        ShippingZoneRepository $shippingZoneRepository
    ) {
        $this->shippingRateRepository = $shippingRateRepository;
        $this->shippingZoneRepository = $shippingZoneRepository;
    }

    /**
     * Calculate volume in cm³ from dimensions
     */
    public function calculateVolumeCm3(float $lengthCm, float $widthCm, float $heightCm): int
    {
        return (int) ($lengthCm * $widthCm * $heightCm);
    }

    /**
     * Find matching shipping rate
     */
    public function findMatchingRate(string $originCountry, string $destinationCountry, float $weightKg, ?int $volumeCm3 = null): ?array
    {
        $zone = $this->shippingZoneRepository->findZone($originCountry, $destinationCountry);

        if (!$zone) {
            return null;
        }

        $rate = $this->shippingRateRepository->findMatchingRate($zone->id, $weightKg, $volumeCm3);

        return $rate ? $rate->toArray() : null;
    }

    /**
     * Create or update shipping rate
     */
    public function createOrUpdateRate(array $rateData): array
    {
        $this->validateRateData($rateData);

        $rate = $this->shippingRateRepository->upsert($rateData);

        return $rate->toArray();
    }

    /**
     * Validate rate data
     */
    protected function validateRateData(array $data): void
    {
        if (empty($data['shipping_zone_id'])) {
            throw new Exception('Shipping zone ID is required');
        }

        if (!isset($data['min_weight_kg']) || !isset($data['max_weight_kg'])) {
            throw new Exception('Weight range is required');
        }

        if ($data['min_weight_kg'] > $data['max_weight_kg']) {
            throw new Exception('Minimum weight cannot exceed maximum weight');
        }

        if (isset($data['min_volume_cm3']) && isset($data['max_volume_cm3'])) {
            if ($data['min_volume_cm3'] > $data['max_volume_cm3']) {
                throw new Exception('Minimum volume cannot exceed maximum volume');
            }
        }

        if (empty($data['price_cents']) || $data['price_cents'] < 0) {
            throw new Exception('Valid price in cents is required');
        }

        if (empty($data['shipping_method'])) {
            throw new Exception('Shipping method is required');
        }

        if (!isset($data['delivery_min_days']) || !isset($data['delivery_max_days'])) {
            throw new Exception('Delivery time range is required');
        }

        if ($data['delivery_min_days'] > $data['delivery_max_days']) {
            throw new Exception('Minimum delivery days cannot exceed maximum');
        }
    }

    /**
     * Get rates for zone
     */
    public function getRatesForZone(string $originCountry, string $destinationCountry): array
    {
        $zone = $this->shippingZoneRepository->findZone($originCountry, $destinationCountry);

        if (!$zone) {
            return [];
        }

        return $this->shippingRateRepository->getByZone($zone->id)->toArray();
    }
}