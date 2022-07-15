<?php

namespace CrixuAMG\FeatureControl;

use CrixuAMG\FeatureControl\Console\Commands\FeatureCheckRelease;
use CrixuAMG\FeatureControl\Console\Commands\FeatureMakeCommand;
use Illuminate\Support\ServiceProvider;

class FeatureControlServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->registerConfig();
        $this->registerMigrations();
        $this->registerCommands();
    }

    /**
     * Register console commands
     */
    private function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                FeatureMakeCommand::class,
                FeatureCheckRelease::class,
            ]);
        }
    }

    /**
     * Register the migrations
     */
    private function registerMigrations()
    {
        if (!class_exists('CreateFeaturesTable')) {
            $timestamp = date('Y_m_d_His', time());
            $this->publishes([
                __DIR__.'/database/migrations/create_features_table.php.stub' => $this->app->databasePath()."/migrations/{$timestamp}_create_features_table.php",
            ], 'migrations');
        }
    }

    private function registerConfig()
    {
        // Automatically apply the package configuration
        $this->publishes([
            __DIR__.'/../config/config.php' => config_path('feature-control.php')
        ]);
    }
}
