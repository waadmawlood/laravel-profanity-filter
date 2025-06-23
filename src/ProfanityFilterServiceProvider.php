<?php

namespace Waad\ProfanityFilter;

use Illuminate\Support\ServiceProvider;

class ProfanityFilterServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/profanity-filter.php', 'profanity-filter'
        );
        $this->mergeConfigFrom(
            __DIR__.'/../config/profanity-words.php', 'profanity-words'
        );

        $this->app->singleton('profanity-filter', function ($app) {
            return new ProfanityFilter;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/profanity-filter.php' => config_path('profanity-filter.php'),
        ], 'profanity-filter');

        $this->publishes([
            __DIR__.'/../config/profanity-words.php' => config_path('profanity-words.php'),
        ], 'profanity-words');
    }
}
