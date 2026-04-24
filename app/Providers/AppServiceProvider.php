<?php

namespace App\Providers;

use App\Events\FileDeleted;
use App\Listeners\SendFileDeletionNotification;
use App\Services\RabbitMQService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    private static bool $fileDeletedListenerRegistered = false;

    public function register(): void
    {
        $this->app->singleton(RabbitMQService::class);
    }

    public function boot(): void
    {
        if (self::$fileDeletedListenerRegistered) {
            return;
        }

        Event::listen(
            FileDeleted::class,
            [SendFileDeletionNotification::class, 'handle'],
        );

        self::$fileDeletedListenerRegistered = true;
    }
}
