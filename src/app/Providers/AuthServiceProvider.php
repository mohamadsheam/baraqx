<?php

namespace App\Providers;

use App\Services\Auth\AuthService;
use App\Services\Auth\OtpService;
use App\Services\Notifications\MailNotificationService;
use App\Services\Notifications\NotificationManager;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(OtpService::class, function ($app) {
            return new OtpService;
        });

        $this->app->singleton(NotificationManager::class, function ($app) {
            $manager = new NotificationManager;
            $manager->register($app->make(MailNotificationService::class));

            return $manager;
        });

        $this->app->singleton(AuthService::class, function ($app) {
            return new AuthService(
                $app->make(OtpService::class),
                $app->make(NotificationManager::class)
            );
        });
    }

    public function boot(): void
    {
        //
    }
}
