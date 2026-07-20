<?php

Route::middleware(['api', 'throttle:60,1', 'iwexa.signature'])->prefix('bagisto-api/iwexa')->group(function () {
    // Catalog endpoints
    Route::post('/catalog/products/batch', 'Webkul\PAYPOC\IwexaConnector\Controllers\Api\CatalogController@importBatch');
    Route::put('/catalog/products/{sku}', 'Webkul\PAYPOC\IwexaConnector\Controllers\Api\CatalogController@updateProduct');

    // Stock endpoints
    Route::post('/catalog/stock', 'Webkul\PAYPOC\IwexaConnector\Controllers\Api\StockController@updateStock');

    // Vendor endpoints
    Route::post('/vendors', 'Webkul\PAYPOC\IwexaConnector\Controllers\Api\VendorController@store');
    Route::put('/vendors/{vendor_code}', 'Webkul\PAYPOC\IwexaConnector\Controllers\Api\VendorController@update');
    Route::get('/vendors/{vendor_code}', 'Webkul\PAYPOC\IwexaConnector\Controllers\Api\VendorController@show');

    // Warehouse endpoints
    Route::post('/warehouses', 'Webkul\PAYPOC\IwexaConnector\Controllers\Api\WarehouseController@store');
    Route::post('/warehouse-stocks', 'Webkul\PAYPOC\IwexaConnector\Controllers\Api\WarehouseStockController@updateStock');

    // Shipping endpoints
    Route::post('/shipping-zones', 'Webkul\PAYPOC\IwexaConnector\Controllers\Api\ShippingZoneController@store');
    Route::post('/shipping-rates', 'Webkul\PAYPOC\IwexaConnector\Controllers\Api\ShippingRateController@store');

    // Routing endpoints
    Route::post('/routing/quote', 'Webkul\PAYPOC\IwexaConnector\Controllers\Api\RoutingController@calculateQuote');

    // Webhook endpoints
    Route::post('/webhooks', 'Webkul\PAYPOC\IwexaConnector\Controllers\Api\WebhookController@handleWebhook');
});
