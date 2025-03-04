<?php

namespace App\Providers;

use App\Services\ContactService;
use App\Services\FlutterwaveService;
use App\Services\HollaTagsService;
use App\Services\MessageService;
use App\Services\WalletService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register the HollaTagsService
        $this->app->singleton(HollaTagsService::class, function ($app) {
            return new HollaTagsService();
        });
        
        // Register the FlutterwaveService
        $this->app->singleton(FlutterwaveService::class, function ($app) {
            return new FlutterwaveService();
        });
        
        // Register the WalletService
        $this->app->singleton(WalletService::class, function ($app) {
            return new WalletService();
        });
        
        // Register the MessageService with dependencies
        $this->app->singleton(MessageService::class, function ($app) {
            return new MessageService(
                $app->make(HollaTagsService::class),
                $app->make(WalletService::class)
            );
        });
        
        // Register the ContactService
        $this->app->singleton(ContactService::class, function ($app) {
            return new ContactService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Create default directories if they don't exist
        if (!file_exists(storage_path('app/exports'))) {
            mkdir(storage_path('app/exports'), 0755, true);
        }
        
        if (!file_exists(storage_path('app/sender_id_documents'))) {
            mkdir(storage_path('app/sender_id_documents'), 0755, true);
        }
        
        if (!file_exists(storage_path('app/imports'))) {
            mkdir(storage_path('app/imports'), 0755, true);
        }
    }
}