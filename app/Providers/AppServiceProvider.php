<?php

namespace App\Providers;

use App\Filament\Commands\FileGenerators\Resources\CustomResourceFormSchemaClassGenerator;
use App\Filament\Commands\FileGenerators\Resources\CustomResourceInfolistSchemaClassGenerator;
use App\Filament\Commands\FileGenerators\Resources\CustomResourceTableClassGenerator;
use Filament\Commands\FileGenerators\Resources\Schemas\ResourceFormSchemaClassGenerator;
use Filament\Commands\FileGenerators\Resources\Schemas\ResourceInfolistSchemaClassGenerator;
use Filament\Commands\FileGenerators\Resources\Schemas\ResourceTableClassGenerator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->bind(
            ResourceTableClassGenerator::class,
            CustomResourceTableClassGenerator::class
        );
        $this->app->bind(
            ResourceFormSchemaClassGenerator::class,
            CustomResourceFormSchemaClassGenerator::class
        );
        $this->app->bind(
            ResourceInfolistSchemaClassGenerator::class,
            CustomResourceInfolistSchemaClassGenerator::class
        );
    }
}
