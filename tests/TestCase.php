<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Notifications\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Simtabi\Laranail\Notifications\Providers\NotificationsServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            NotificationsServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Database: sqlite in-memory (channels that persist self-create tables).
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Cache: array driver (CacheChannel); Queue: sync (SendQueuedNotification).
        $app['config']->set('cache.default', 'array');
        $app['config']->set('queue.default', 'sync');
    }
}
