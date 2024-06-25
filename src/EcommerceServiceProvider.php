<?php

namespace SbscPackage\Ecommerce;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

class EcommerceServiceProvider extends ServiceProvider
{
	public function register()
	{
		//
	}

	public function boot()
	{
		$this->registerRoutes();
		$this->registerViews();
		$this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->registerPublishing();
	}

	protected function registerRoutes()
	{
		Route::group($this->routeConfiguration(), function () {
			$this->loadRoutesFrom(__DIR__.'/../routes/api.php');
			$this->loadRoutesFrom(__DIR__.'/../routes/web.php');
		});
	}

	protected function registerViews()
	{
		$this->loadViewsFrom(__DIR__.'/../resources/views', 'Ecommerce');
	}

	protected function routeConfiguration()
	{
		return [
			'prefix' => 'api',
			'middleware' => ['api'],
		];
	}

	protected function registerPublishing()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/ecommerce.php' => config_path('ecommerce.php'),
            ], 'ecommerce-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/ecommerce'),
            ], 'ecommerce-views');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'ecommerce-migrations');
        }
    }
}