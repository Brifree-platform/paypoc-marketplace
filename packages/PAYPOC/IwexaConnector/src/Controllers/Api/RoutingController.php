<?php

namespace Webkul\PAYPOC\IwexaConnector\Controllers\Api;

use Webkul\PAYPOC\IwexaConnector\Services\RoutingService;
use Webkul\PAYPOC\IwexaConnector\Http\Requests\RoutingQuoteRequest;
use Illuminate\Http\JsonResponse;
use Exception;

class RoutingController
{
    protected $routingService;

    public function __construct(RoutingService $routingService)
    {
        $this->routingService = $routingService;
    }

    /**
     * Calculate routing quote
     *
     * @param RoutingQuoteRequest $request
     * @return JsonResponse
     */
    public function calculateQuote(RoutingQuoteRequest $request): JsonResponse
    {
        try {
            $quote = $this->routingService->calculateQuote($request->validated());

            return response()->json([
                'success' => true,
                'data' => $quote,
            ]);
        } catch (Exception $e) {
            $statusCode = $e->getMessage() === 'shipping_rate_not_found' ? 404 : 400;

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], $statusCode);
        }
    }
}