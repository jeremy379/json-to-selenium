<?php

namespace Famousinteractive\ContentApi;

use Illuminate\Support\ServiceProvider;

class ContentApiServiceProvider extends ServiceProvider
{
    protected $commands = [
    ];

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/Config/famousTranslator.php' => config_path('famousTranslator.php'),
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //include __DIR__.'/Helpers/function.php';
        //include __DIR__.'/routes.php';

        //$this->app->make('Famousinteractive\ContentApi\Controllers\CacheController');

        //$this->commands($this->commands);
    }
}
