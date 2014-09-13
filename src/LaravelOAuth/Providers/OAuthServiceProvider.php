<?php namespace LaravelOAuth\Providers;

use Illuminate\Support\ServiceProvider;
use LaravelOAuth\Factory;
use OAuth\ServiceFactory;

class OAuthServiceProvider extends ServiceProvider {

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->package('pasadinhas/laravel-oauth');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerOAuth();
        $this->registerCustomServices();
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array();
    }

    private function registerOAuth()
    {
        $this->app['oauth'] = $this->app->share(function ($app) {
            return new Factory(new ServiceFactory(), $this->app['config'], $this->app['url']);
        });
    }

    private function registerCustomServices()
    {
        $this->app['oauth']->registerServices();
    }
}
