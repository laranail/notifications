# Installation

`laranail/notifications` is a self-contained, multi-channel notification system
for Laravel. It can be pulled into an application on its own, without the rest of
the laranail toolkit.

## Requirements

| Requirement | Version |
| --- | --- |
| PHP | `^8.3` (8.3, 8.4, 8.5 and above) |
| Laravel | `^13.0` |

## Install with Composer

```bash
composer require laranail/notifications
```

## Auto-discovery

The package registers a **deferred** service provider,
`Simtabi\Laranail\Notifications\NotificationsServiceProvider`, and a
`Notifications` facade alias. Both are auto-discovered by Laravel's package
discovery — there is nothing to register in `config/app.php`.

Because the provider implements `DeferrableProvider`, the `NotificationService`
binding is only resolved when first used. The provider exposes two container
entries:

- `Simtabi\Laranail\Notifications\Services\NotificationService` — the concrete
  service (a singleton).
- `laranail.notifications` — an alias for the same singleton.

You can reach the service in any of three equivalent ways:

```php
use Simtabi\Laranail\Notifications\Facades\Notifications;
use Simtabi\Laranail\Notifications\Services\NotificationService;

// 1. Facade
Notifications::send('Backup completed', channels: ['log']);

// 2. Dependency injection / container resolution
$service = app(NotificationService::class);

// 3. Alias
$service = app('laranail.notifications');
```

## Publishing the configuration

The package ships sensible defaults that are merged into your application config
under the `notifications` key, so it works out of the box with the `log`,
`database`, and `cache` channels enabled. To customise channels, publish the
config file:

```bash
php artisan vendor:publish --tag=laranail-notifications
```

This writes `config/notifications.php` into your application. See
[Configuration](configuration.md) for every key and its default.

## Next steps

- [Configuration](configuration.md) — config keys, env vars, defaults.
- [Channels](channels.md) — the built-in channels and how to target them.
- [Security](security.md) — SSRF guard, allow-list, fail-soft, queue safety.

[← Docs index](../README.md#documentation)
