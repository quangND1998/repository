<?php

namespace Darkness\Repository;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(\Intervention\Image\ImageServiceProvider::class);
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('tag-request', \Darkness\Repository\Middleware\TagForRequestMiddleware::class);
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Darkness\Repository\Commands\QueryCacheFlush::class
            ]);
        }
    }
}
