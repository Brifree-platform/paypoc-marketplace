<?php

namespace Webkul\PAYPOC\IwexaConnector\Services;

use Webkul\PAYPOC\IwexaConnector\Repositories\RoutingQuoteRepository;
use Webkul\PAYPOC\IwexaConnector\Repositories\IwexaWarehouseStockRepository;
use Exception;

class RoutingService
{
    protected $routingQuoteRepository;
    protected $warehouseStockRepository;
    protected $shippingRateService;

    public function __construct(
        RoutingQuoteRepository $routingQuoteRepository,
        IwexaWarehouseStockRepository $warehouseStockRepository,
        ShippingRateService $shippingRateService
    ) {
        $this->routingQuoteRepository = $routingQuoteRepository;
        $this->warehouseStockRepository = $warehouseStockRepository;
        $this->shippingRateService = $shippingRateService;
    }

    /**
     * Calculate routing quote for checkout
     */
    public function calculateQuote(array $requestData): array
    {
        $this->validateQuoteRequest($requestData);

        $availableWarehouses = $this->warehouseStockRepository->getAvailableWarehousesForSku(
            $requestData['sku'],
            $requestData['quantity']
        );

        if ($availableWarehouses->isEmpty()) {
            throw new Exception('No warehouses available for this SKU and quantity');
        }

        $candidates = [];

        foreach ($availableWarehouses as $stock) {
            $warehouse = $stock->warehouse;

            if (!$warehouse) {
                continue;
            }

            $shippingRate = $this->shippingRateService->findMatchingRate(
                $warehouse->country,
                $requestData['destination_country'],
                $requestData['product_weight_kg'],
                $requestData['product_dimensions'] ? $this->calculateVolumeFromDimensions($requestData['product_dimensions']) : null
            );

            if (!$shippingRate) {
                continue;
            }

            $candidates[] = [
                'warehouse_code' => $warehouse->warehouse_code,
                'warehouse' => $warehouse,
                'stock' => $stock,
                'shipping_rate' => $shippingRate,
                'shipping_cost_cents' => $shippingRate['price_cents'],
                'final_delivery_max_days' => $stock->preparation_time_max_days + $shippingRate['delivery_max_days'],
            ];
        }

        if (empty($candidates)) {
            throw new Exception('shipping_rate_not_found');
        }

        // Select best candidate: cheapest, then fastest, then central first
        $selectedCandidate = $this->selectBestCandidate($candidates);

        // Create routing quote
        $quoteData = $this->buildQuoteData($requestData, $selectedCandidate);
        $quote = $this->routingQuoteRepository->createQuote($quoteData);

        return $this->formatQuoteResponse($quote);
    }

    /**
     * Validate quote request
     */
    protected function validateQuoteRequest(array $data): void
    {
        if (empty($data['sku'])) {
            throw new Exception('SKU is required');
        }

        if (empty($data['destination_country'])) {
            throw new Exception('Destination country is required');
        }

        if (!isset($data['quantity']) || $data['quantity'] < 1) {
            throw new Exception('Valid quantity is required');
        }

        if (!isset($data['product_weight_kg'])) {
            throw new Exception('Product weight is required');
        }
    }

    /**
     * Calculate volume from dimensions
     */
    protected function calculateVolumeFromDimensions(array $dimensions): int
    {
        return $this->shippingRateService->calculateVolumeCm3(
            $dimensions['length_cm'],
            $dimensions['width_cm'],
            $dimensions['height_cm']
        );
    }

    /**
     * Select best candidate based on criteria
     */
    protected function selectBestCandidate(array $candidates): array
    {
        // Sort by: cheapest shipping, then fastest delivery, then central warehouses first
        usort($candidates, function ($a, $b) {
            // First by shipping cost
            if ($a['shipping_cost_cents'] !== $b['shipping_cost_cents']) {
                return $a['shipping_cost_cents'] <=> $b['shipping_cost_cents'];
            }

            // Then by final delivery time
            if ($a['final_delivery_max_days'] !== $b['final_delivery_max_days']) {
                return $a['final_delivery_max_days'] <=> $b['final_delivery_max_days'];
            }

            // Finally prefer central warehouses
            $aIsCentral = $a['warehouse']->type === 'central' ? 1 : 0;
            $bIsCentral = $b['warehouse']->type === 'central' ? 1 : 0;

            return $bIsCentral <=> $aIsCentral;
        });

        return $candidates[0];
    }

    /**
     * Build quote data array
     */
    protected function buildQuoteData(array $request, array $candidate): array
    {
        $warehouse = $candidate['warehouse'];
        $stock = $candidate['stock'];
        $rate = $candidate['shipping_rate'];

        return [
            'vendor_code' => $request['vendor_code'],
            'sku' => $request['sku'],
            'warehouse_code' => $warehouse->warehouse_code,
            'fulfillment_type' => $warehouse->type,
            'origin_country' => $warehouse->country,
            'destination_country' => $request['destination_country'],
            'destination_postal_code' => $request['destination_postal_code'] ?? null,
            'quantity' => $request['quantity'],
            'product_weight_kg' => $request['product_weight_kg'],
            'product_volume_cm3' => isset($request['product_dimensions']) ?
                $this->calculateVolumeFromDimensions($request['product_dimensions']) : null,
            'shipping_method' => $rate['shipping_method'],
            'shipping_cost_cents' => $rate['price_cents'],
            'currency' => $rate['currency'],
            'preparation_time_min_days' => $stock->preparation_time_min_days,
            'preparation_time_max_days' => $stock->preparation_time_max_days,
            'delivery_min_days' => $rate['delivery_min_days'],
            'delivery_max_days' => $rate['delivery_max_days'],
            'final_delivery_min_days' => $stock->preparation_time_min_days + $rate['delivery_min_days'],
            'final_delivery_max_days' => $stock->preparation_time_max_days + $rate['delivery_max_days'],
            'original_request_payload' => $request,
            'calculated_response_payload' => $candidate,
        ];
    }

    /**
     * Format quote response
     */
    protected function formatQuoteResponse($quote): array
    {
        return [
            'sku' => $quote->sku,
            'vendor_code' => $quote->vendor_code,
            'warehouse_code' => $quote->warehouse_code,
            'fulfillment_type' => $quote->fulfillment_type,
            'origin_country' => $quote->origin_country,
            'destination_country' => $quote->destination_country,
            'shipping_method' => $quote->shipping_method,
            'shipping_cost_cents' => $quote->shipping_cost_cents,
            'currency' => $quote->currency,
            'preparation_time_min_days' => $quote->preparation_time_min_days,
            'preparation_time_max_days' => $quote->preparation_time_max_days,
            'delivery_min_days' => $quote->delivery_min_days,
            'delivery_max_days' => $quote->delivery_max_days,
            'final_delivery_min_days' => $quote->final_delivery_min_days,
            'final_delivery_max_days' => $quote->final_delivery_max_days,
        ];
    }
}