<?php

namespace Webkul\PAYPOC\IwexaConnector\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\PAYPOC\IwexaConnector\Models\ShippingRate;

class ShippingRateRepository extends Repository
{
    /**
     * Specify Model class name
     */
    function model(): string
    {
        return ShippingRate::class;
    }

    /**
     * Find matching rate for zone, weight and volume
     */
    public function findMatchingRate(int $zoneId, float $weightKg, ?int $volumeCm3 = null)
    {
        $query = $this->model->where('shipping_zone_id', $zoneId)
                            ->where('status', 'active')
                            ->where('min_weight_kg', '<=', $weightKg)
                            ->where('max_weight_kg', '>=', $weightKg);

        if ($volumeCm3 !== null) {
            $query->where(function ($q) use ($volumeCm3) {
                $q->whereNull('min_volume_cm3')
                  ->orWhere('min_volume_cm3', '<=', $volumeCm3);
            })->where(function ($q) use ($volumeCm3) {
                $q->whereNull('max_volume_cm3')
                  ->orWhere('max_volume_cm3', '>=', $volumeCm3);
            });
        }

        return $query->orderBy('price_cents')->first();
    }

    /**
     * Upsert shipping rate
     */
    public function upsert(array $data)
    {
        // For upsert, we need to find by zone_id, weight range, and volume range
        $existing = $this->model->where('shipping_zone_id', $data['shipping_zone_id'])
                               ->where('min_weight_kg', $data['min_weight_kg'])
                               ->where('max_weight_kg', $data['max_weight_kg'])
                               ->when(isset($data['min_volume_cm3']), function ($q) use ($data) {
                                   return $q->where('min_volume_cm3', $data['min_volume_cm3']);
                               })
                               ->when(isset($data['max_volume_cm3']), function ($q) use ($data) {
                                   return $q->where('max_volume_cm3', $data['max_volume_cm3']);
                               })
                               ->first();

        if ($existing) {
            $existing->update($data);
            return $existing;
        }

        return $this->create($data);
    }

    /**
     * Get rates for zone
     */
    public function getByZone(int $zoneId)
    {
        return $this->findWhere([
            'shipping_zone_id' => $zoneId,
            'status' => 'active'
        ]);
    }
}