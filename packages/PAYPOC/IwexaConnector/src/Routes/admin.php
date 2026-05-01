<?php

Route::middleware(['admin'])->prefix('admin/iwexa')->group(function () {
    // Product Type Mappings
    Route::get('/product-type-mappings', 'Webkul\PAYPOC\IwexaConnector\Controllers\Admin\ProductTypeMappingController@index')
        ->name('admin.iwexa.product-type-mappings.index');
    Route::get('/product-type-mappings/{id}', 'Webkul\PAYPOC\IwexaConnector\Controllers\Admin\ProductTypeMappingController@show')
        ->name('admin.iwexa.product-type-mappings.show');
    Route::post('/product-type-mappings/{id}/approve', 'Webkul\PAYPOC\IwexaConnector\Controllers\Admin\ProductTypeMappingController@approve')
        ->name('admin.iwexa.product-type-mappings.approve');
    Route::post('/product-type-mappings/{id}/reject', 'Webkul\PAYPOC\IwexaConnector\Controllers\Admin\ProductTypeMappingController@reject')
        ->name('admin.iwexa.product-type-mappings.reject');

    // Category Mappings
    Route::get('/category-mappings', 'Webkul\PAYPOC\IwexaConnector\Controllers\Admin\CategoryMappingController@index')
        ->name('admin.iwexa.category-mappings.index');
    Route::get('/category-mappings/create', 'Webkul\PAYPOC\IwexaConnector\Controllers\Admin\CategoryMappingController@create')
        ->name('admin.iwexa.category-mappings.create');
    Route::post('/category-mappings', 'Webkul\PAYPOC\IwexaConnector\Controllers\Admin\CategoryMappingController@store')
        ->name('admin.iwexa.category-mappings.store');
    Route::get('/category-mappings/{id}/edit', 'Webkul\PAYPOC\IwexaConnector\Controllers\Admin\CategoryMappingController@edit')
        ->name('admin.iwexa.category-mappings.edit');
    Route::put('/category-mappings/{id}', 'Webkul\PAYPOC\IwexaConnector\Controllers\Admin\CategoryMappingController@update')
        ->name('admin.iwexa.category-mappings.update');
    Route::delete('/category-mappings/{id}', 'Webkul\PAYPOC\IwexaConnector\Controllers\Admin\CategoryMappingController@destroy')
        ->name('admin.iwexa.category-mappings.destroy');

    // Attribute Mappings
    Route::get('/attribute-mappings', 'Webkul\PAYPOC\IwexaConnector\Controllers\Admin\AttributeMappingController@index')
        ->name('admin.iwexa.attribute-mappings.index');
    Route::get('/attribute-mappings/{id}', 'Webkul\PAYPOC\IwexaConnector\Controllers\Admin\AttributeMappingController@show')
        ->name('admin.iwexa.attribute-mappings.show');
    Route::post('/attribute-mappings/{id}/approve', 'Webkul\PAYPOC\IwexaConnector\Controllers\Admin\AttributeMappingController@approve')
        ->name('admin.iwexa.attribute-mappings.approve');
    Route::post('/attribute-mappings/{id}/configure', 'Webkul\PAYPOC\IwexaConnector\Controllers\Admin\AttributeMappingController@configure')
        ->name('admin.iwexa.attribute-mappings.configure');

    // Sync Jobs
    Route::get('/sync-jobs', 'Webkul\PAYPOC\IwexaConnector\Controllers\Admin\SyncJobController@index')
        ->name('admin.iwexa.sync-jobs.index');
    Route::get('/sync-jobs/{id}', 'Webkul\PAYPOC\IwexaConnector\Controllers\Admin\SyncJobController@show')
        ->name('admin.iwexa.sync-jobs.show');
    Route::post('/sync-jobs/{id}/retry', 'Webkul\PAYPOC\IwexaConnector\Controllers\Admin\SyncJobController@retry')
        ->name('admin.iwexa.sync-jobs.retry');
});
