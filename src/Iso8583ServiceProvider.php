<?php

namespace TerminalHero\Iso8583;

use Illuminate\Support\ServiceProvider;
use TerminalHero\Iso8583\Specs\Standard1987Spec;

class Iso8583ServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('iso8583', function ($app) {
            // Default to Standard1987Spec if no custom spec is bound
            // Users can override this by binding their own spec to the container
            return new Iso8583(new Standard1987Spec());
        });
    }

    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        // No longer publishing the array config as we moved to OOP Specs
    }
}
