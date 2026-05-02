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

        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/admin.php');

        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'iwexa');
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'iwexa');
    }
}
