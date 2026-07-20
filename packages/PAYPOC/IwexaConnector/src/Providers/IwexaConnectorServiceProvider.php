<?php
namespace Webkul\PAYPOC\IwexaConnector\Providers;

use Illuminate\Support\ServiceProvider;

class IwexaConnectorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/iwexa-connector.php',
            'iwexa-connector'
        );

        // Register services
        $this->app->singleton(\Webkul\PAYPOC\IwexaConnector\Services\IwexaApiService::class, function ($app) {
            return new \Webkul\PAYPOC\IwexaConnector\Services\IwexaApiService(
                (string) config('iwexa-connector.iwexa_api_base_url'),
                (string) config('iwexa-connector.iwexa_api_key'),
                (string) config('iwexa-connector.iwexa_hmac_secret')
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

        $this->app->singleton(
            \Webkul\PAYPOC\IwexaConnector\Services\VendorImportService::class,
            \Webkul\PAYPOC\IwexaConnector\Services\VendorImportService::class
        );

        $this->app->singleton(
            \Webkul\PAYPOC\IwexaConnector\Services\WarehouseSyncService::class,
            \Webkul\PAYPOC\IwexaConnector\Services\WarehouseSyncService::class
        );

        $this->app->singleton(
            \Webkul\PAYPOC\IwexaConnector\Services\WarehouseStockSyncService::class,
            \Webkul\PAYPOC\IwexaConnector\Services\WarehouseStockSyncService::class
        );

        $this->app->singleton(
            \Webkul\PAYPOC\IwexaConnector\Services\ShippingZoneService::class,
            \Webkul\PAYPOC\IwexaConnector\Services\ShippingZoneService::class
        );

        $this->app->singleton(
            \Webkul\PAYPOC\IwexaConnector\Services\ShippingRateService::class,
            \Webkul\PAYPOC\IwexaConnector\Services\ShippingRateService::class
        );

        $this->app->singleton(
            \Webkul\PAYPOC\IwexaConnector\Services\RoutingService::class,
            \Webkul\PAYPOC\IwexaConnector\Services\RoutingService::class
        );
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../Database/Migrations' => database_path('migrations'),
        ], 'iwexa-migrations');

        $this->publishes([
            __DIR__ . '/../Config/iwexa-connector.php' => config_path('iwexa-connector.php'),
        ], 'iwexa-config');

        $this->publishes([
            __DIR__ . '/../Resources/views' => resource_path('views/iwexa'),
        ], 'iwexa-views');

        $this->app['router']->aliasMiddleware(
            'iwexa.signature',
            \Webkul\PAYPOC\IwexaConnector\Http\Middleware\VerifyIwexaSignature::class
        );

        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/admin.php');

        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'iwexa');
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'iwexa');
    }
}
