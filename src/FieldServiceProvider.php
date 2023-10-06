<?php

namespace Nevadskiy\Nova\Collection;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Nova\Events\ServingNova;
use Laravel\Nova\Nova;

class FieldServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->booted(function () {
            $this->routes();
        });

        Nova::serving(function (ServingNova $event) {
            Nova::script('collection-field', __DIR__.'/../dist/js/field.js');
        });
    }

    protected function routes(): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        Route::middleware('nova')
            ->prefix('/nova-vendor/collection-field')
            ->group(__DIR__.'/../routes/api.php');
    }
}
