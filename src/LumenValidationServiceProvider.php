<?php

namespace Lumen\Validation;

use Illuminate\Support\ServiceProvider;

class LumenValidationServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Boot any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->resolving(BaseRequest::class, function (BaseRequest $request, $app) {
            $request = $request->createFrom($app['request'], $request);
            $request->withContainer($app);

            return $request;
        });

        $this->app->afterResolving(BaseRequest::class, function (BaseRequest $request) {
            $request->validate();
        });
    }
}
