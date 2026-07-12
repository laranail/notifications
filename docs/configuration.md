# Configuration

After publishing (`php artisan vendor:publish --tag=laranail-notifications`) the
configuration lives in `config/notifications.php` and is merged into your
application config under the `notifications` key. The defaults are also merged
when the file is not published, so the package works without publishing.

## Top-level keys

| Key | Env var | Default | Description |
| --- | --- | --- | --- |
| `queueable` | `NOTIFICATIONS_QUEUEABLE` | `false` | When `true`, sends are pushed onto a queue instead of delivered inline (see [Queueing](#queueing)). |
| `queue_connection` | `NOTIFICATIONS_QUEUE_CONNECTION` | `null` | Queue connection for the dispatched job; `null` uses the default connection. |
| `queue_name` | `NOTIFICATIONS_QUEUE_NAME` | `notifications` | Queue name the job is pushed onto. |
| `channels` | — | see below | Per-channel configuration map (see [Channels](channels.md)). |

## Channel configuration

Each entry under `channels.<name>` is keyed by a channel name from the
allow-list (the `NotificationChannel` enum) and accepts at minimum:

- `enabled` (bool) — whether the channel is active.
- `default` (bool) — whether the channel is included in the default channel set
  used when `send()` is called without an explicit channel list.

Channels that talk to an external service add their own keys. The shipped
defaults are:

```php
return [
    'queueable'        => env('NOTIFICATIONS_QUEUEABLE', false),
    'queue_connection' => env('NOTIFICATIONS_QUEUE_CONNECTION', null),
    'queue_name'       => env('NOTIFICATIONS_QUEUE_NAME', 'notifications'),

    'channels' => [
        'log' => [
            'enabled' => true,
            'default' => true,
        ],

        'email' => [
            'enabled'         => env('NOTIFICATIONS_EMAIL_ENABLED', false),
            'from'            => env('MAIL_FROM_ADDRESS'),
            'to'              => env('NOTIFICATIONS_EMAIL_TO'),
            'default_subject' => 'System Notification',
        ],

        'slack' => [
            'enabled'     => env('NOTIFICATIONS_SLACK_ENABLED', false),
            'webhook_url' => env('SLACK_WEBHOOK_URL'),
            'username'    => 'Notification Bot',
            'icon'        => ':robot_face:',
        ],

        'discord' => [
            'enabled'     => env('NOTIFICATIONS_DISCORD_ENABLED', false),
            'webhook_url' => env('DISCORD_WEBHOOK_URL'),
            'username'    => 'Notification Bot',
        ],

        'sms' => [
            'enabled' => env('NOTIFICATIONS_SMS_ENABLED', false),
            'api_key' => env('SMS_API_KEY'),
            'api_url' => env('SMS_API_URL'),
            'from'    => env('SMS_FROM_NUMBER'),
        ],

        'push' => [
            'enabled' => env('NOTIFICATIONS_PUSH_ENABLED', false),
            'api_key' => env('ONESIGNAL_API_KEY'),
            'app_id'  => env('ONESIGNAL_APP_ID'),
        ],

        'database' => [
            'enabled' => true,
            'table'   => 'notifications',
        ],

        'cache' => [
            'enabled'    => true,
            'key_prefix' => 'notification_',
            'ttl'        => 3600,
        ],

        'file' => [
            'enabled'  => env('NOTIFICATIONS_FILE_ENABLED', false),
            'path'     => storage_path('logs/notifications.log'),
            'rotation' => true,
            'max_size' => 10485760, // 10MB
        ],

        'webhook' => [
            'enabled' => env('NOTIFICATIONS_WEBHOOK_ENABLED', false),
            'url'     => env('WEBHOOK_URL'),
            'method'  => 'POST',
            'headers' => [],
        ],

        'apple_business_messages' => [
            'enabled'     => env('NOTIFICATIONS_APPLE_ENABLED', false),
            'business_id' => env('APPLE_BUSINESS_ID'),
            'api_key'     => env('APPLE_API_KEY'),
            'api_secret'  => env('APPLE_API_SECRET'),
        ],
    ],
];
```

### Environment variables

| Env var | Channel / setting | Default |
| --- | --- | --- |
| `NOTIFICATIONS_QUEUEABLE` | queueing on/off | `false` |
| `NOTIFICATIONS_QUEUE_CONNECTION` | queue connection | `null` |
| `NOTIFICATIONS_QUEUE_NAME` | queue name | `notifications` |
| `NOTIFICATIONS_EMAIL_ENABLED` | enable email | `false` |
| `MAIL_FROM_ADDRESS` | email `from` | — |
| `NOTIFICATIONS_EMAIL_TO` | email `to` | — |
| `NOTIFICATIONS_SLACK_ENABLED` | enable Slack | `false` |
| `SLACK_WEBHOOK_URL` | Slack incoming webhook URL | — |
| `NOTIFICATIONS_DISCORD_ENABLED` | enable Discord | `false` |
| `DISCORD_WEBHOOK_URL` | Discord webhook URL | — |
| `NOTIFICATIONS_SMS_ENABLED` | enable SMS | `false` |
| `SMS_API_KEY` / `SMS_API_URL` / `SMS_FROM_NUMBER` | SMS provider | — |
| `NOTIFICATIONS_PUSH_ENABLED` | enable push | `false` |
| `ONESIGNAL_API_KEY` / `ONESIGNAL_APP_ID` | push (OneSignal) | — |
| `NOTIFICATIONS_FILE_ENABLED` | enable file | `false` |
| `NOTIFICATIONS_WEBHOOK_ENABLED` | enable webhook | `false` |
| `WEBHOOK_URL` | webhook URL | — |
| `NOTIFICATIONS_APPLE_ENABLED` | enable Apple Business Messages | `false` |
| `APPLE_BUSINESS_ID` / `APPLE_API_KEY` / `APPLE_API_SECRET` | Apple Business Messages | — |

## Enabling and disabling channels

A channel is delivered to only when its `enabled` flag is truthy. The `log`,
`database`, and `cache` channels ship enabled; every outbound or
externally-configured channel ships **disabled** and is turned on via its
`NOTIFICATIONS_*_ENABLED` env var (or by editing the published config).

A disabled channel is not an error: when a send targets a disabled channel the
service records a `false` result for it (not an error entry) and moves on.

## Setting the default channel(s)

Calling `send()` with no `channels` argument delivers to the **default set**.
That set is built two ways:

1. **From config** — every channel whose config has `'default' => true` is added
   to the default set at boot. By default only `log` is marked default.
2. **At runtime** — `setDefaultChannels(['log', 'slack'])` replaces the default
   set. Each name is validated against the allow-list; an unknown name throws
   `InvalidArgumentException`.

If no defaults are configured and none are set at runtime, `send()` falls back
to **all registered channels**.

```php
use Simtabi\Laranail\Notifications\Facades\Notifications;

Notifications::setDefaultChannels(['log', 'slack']);

// Now uses log + slack:
Notifications::send('Nightly job finished');
```

## Queueing

When `queueable` is `true`, `send()` dispatches a `SendQueuedNotification` job
onto `queue_connection` / `queue_name` instead of delivering inline. The job
carries only a JSON-safe payload and re-resolves a fresh service on the worker —
see [Security](security.md#serializable-queue-job) for why. A single message can
opt out of queueing per call with the `sync` option, or be marked already-queued
with the `queued` option (used internally by the worker to prevent re-queueing):

```php
use Simtabi\Laranail\Notifications\DataTransferObjects\NotificationMessage;

$message = new NotificationMessage(body: 'Send me now', options: ['sync' => true]);
Notifications::send($message, channels: ['log']);
```

[← Docs index](../README.md#documentation)
