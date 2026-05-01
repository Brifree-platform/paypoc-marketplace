<?php

namespace Webkul\PAYPOC\IwexaConnector;

use Illuminate\Support\ServiceProvider;

class IwexaConnectorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register config
        $this->mergeConfigFrom(
            __DIR__ . '/Config/iwexa-connector.php',
            'iwexa-connector'
        );

        // Register services
        $this->app->singleton(\Webkul\PAYPOC\IwexaConnector\Services\IwexaApiService::class, function ($app) {
            return new \Webkul\PAYPOC\IwexaConnector\Services\IwexaApiService(
                config('iwexa-connector.iwexa_api_base_url'),
                config('iwexa-connector.iwexa_api_key'),
                config('iwexa-connector.iwexa_hmac_secret')
            );
        });

        $this->app->singleton(\Webkul\PAYPOC\IwexaConnector\Services\CatalogImportService::class);
        $this->app->singleton(\Webkul\PAYPOC\IwexaConnector\Services\StockUpdateService::class);
        $this->app->singleton(\Webkul\PAYPOC\IwexaConnector\Services\CategoryMappingService::class);
        $this->app->singleton(\Webkul\PAYPOC\IwexaConnector\Services\ProductTypeMappingService::class);
        $this->app->singleton(\Webkul\PAYPOC\IwexaConnector\Services\AttributeMappingService::class);
        $this->app->singleton(\Webkul\PAYPOC\IwexaConnector\Services\AttributeProvisioningService::class);
        $this->app->singleton(\Webkul\PAYPOC\IwexaConnector\Services\WebhookProcessorService::class);

        // Register repositories
        $this->app->singleton(\Webkul\PAYPOC\IwexaConnector\Repositories\IwexaSyncJobRepository::class);
        $this->app->singleton(\Webkul\PAYPOC\IwexaConnector\Repositories\IwexaProductRepository::class);
        $this->app->singleton(\Webkul\PAYPOC\IwexaConnector\Repositories\CategoryMappingRepository::class);
        $this->app->singleton(\Webkul\PAYPOC\IwexaConnector\Repositories\ProductTypeMappingRepository::class);
        $this->app->singleton(\Webkul\PAYPOC\IwexaConnector\Repositories\AttributeMappingRepository::class);
    }

    public function boot(): void
    {
        // Publish migrations
        $this->publishes([
            __DIR__ . '/Database/Migrations' => database_path('migrations'),
        ], 'iwexa-migrations');

        // Publish config
        $this->publishes([
            __DIR__ . '/Config/iwexa-connector.php' => config_path('iwexa-connector.php'),
        ], 'iwexa-config');

        // Publish views
        $this->publishes([
            __DIR__ . '/Resources/views' => resource_path('views/iwexa'),
        ], 'iwexa-views');

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/Routes/api.php');
        $this->loadRoutesFrom(__DIR__ . '/Routes/admin.php');

        // Load views
        $this->loadViewsFrom(__DIR__ . '/Resources/views', 'iwexa');
    }
}
