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
}