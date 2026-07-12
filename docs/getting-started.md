# Getting started

Send your first multi-channel notification and read the result. For the full reference see the
[Documentation index](../README.md#documentation).

## 1. Install + publish

```bash
composer require laranail/notifications
php artisan vendor:publish --tag=laranail-notifications
```

The `NotificationsServiceProvider` is auto-discovered. See [Installation](installation.md) for details.

## 2. Configure a channel

Set the channel's keys in `config/notifications.php` (or via env) — e.g. Slack's incoming-webhook URL:

```php
// config/notifications.php
'channels' => [
    'slack' => ['webhook_url' => env('NOTIFICATIONS_SLACK_WEBHOOK_URL')],
],
```

See [Channels](channels.md) for each channel's keys and [Configuration](configuration.md) for all options.

## 3. Send

```php
use Simtabi\Laranail\Notifications\Services\NotificationService;

$result = app(NotificationService::class)->send(
    message: 'Backup completed',
    data: ['size_mb' => 142],
    channels: ['log', 'slack'],
    level: 'info',
);

if ($result->isSuccessful()) {
    // all channels delivered
}
foreach ($result->getFailedChannels() as $channel) {
    // handle failures
}
```

Broadcast to every registered channel:

```php
app(NotificationService::class)->broadcast('Server is on fire!', ['host' => gethostname()], 'critical');
```

## Next steps

- [Channels](channels.md) — the 12 built-in channels + registering your own.
- [Configuration](configuration.md) — every key + queueing.
- [Security](security.md) — the SSRF guard + channel allow-list.
- [Architecture](architecture.md) — how the service, channels, and queue job fit together.

---

[← Docs index](../README.md#documentation)
