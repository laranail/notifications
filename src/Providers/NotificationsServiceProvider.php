<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Notifications\Providers;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;
use Simtabi\Laranail\Notifications\Services\NotificationService;

/**
 * Deferred service provider for the self-contained Notifications package.
 *
 * Merges and publishes its config under the `notifications` key and binds the
 * fluent {@see NotificationService} (and the `laranail.notifications` alias).
 */
class NotificationsServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom($this->configPath(), 'notifications');

        $this->app->singleton(NotificationService::class, static function (Application $app): NotificationService {
            /** @var Repository $config */
            $config = $app->make('config');

            /** @var array<string, mixed> $settings */
            $settings = (array) $config->get('notifications', []);

            $logger = $app->bound(LoggerInterface::class) ? $app->make(LoggerInterface::class) : null;

            return new NotificationService($settings, $logger);
        });

        $this->app->alias(NotificationService::class, 'laranail.notifications');
    }

    public function boot(): void
    {
        $this->publishes([
            $this->configPath() => config_path('notifications.php'),
        ], 'laranail-notifications');
    }

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            NotificationService::class,
            'laranail.notifications',
        ];
    }

    /**
     * Absolute path to the package's notifications config file.
     *
     * The provider lives at src/, so the package config/ dir is one level up.
     */
    private function configPath(): string
    {
        return __DIR__ . '/../../config/notifications.php';
    }
}
