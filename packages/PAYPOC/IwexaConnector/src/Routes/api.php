<?php

Route::middleware(['api', 'throttle:60,1'])->prefix('bagisto-api/iwexa')->group(function () {
    // Catalog endpoints
    Route::post('/catalog/products/batch', 'Webkul\PAYPOC\IwexaConnector\Controllers\Api\CatalogController@importBatch');
    Route::put('/catalog/products/{sku}', 'Webkul\PAYPOC\IwexaConnector\Controllers\Api\CatalogController@updateProduct');

    // Stock endpoints
    Route::post('/catalog/stock', 'Webkul\PAYPOC\IwexaConnector\Controllers\Api\StockController@updateStock');

    // Webhook endpoints
    Route::post('/webhooks', 'Webkul\PAYPOC\IwexaConnector\Controllers\Api\WebhookController@handleWebhook');
});
