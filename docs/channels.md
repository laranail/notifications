# Channels

Every channel implements
`Simtabi\Laranail\Notifications\Contracts\NotificationChannelInterface` and, in
practice, extends `AbstractNotificationChannel` (which owns the shared
config-merge, `enabled`, and validation plumbing). Channels are resolved only
from a fixed allow-list — the `Simtabi\Laranail\Notifications\Enums\NotificationChannel`
enum — so a class name in config can never be instantiated arbitrarily (see
[Security](security.md)).

## The built-in channels

The enum defines **twelve** channels. Eleven of them ship with default config
out of the box; `console` is allow-listed and usable but has no default config
entry, so register it explicitly if you want it.

| Key | Class | What it does | Required config keys | Outbound HTTP (SSRF-guarded) |
| --- | --- | --- | --- | --- |
| `log` | `LogChannel` | Writes through the Laravel log stack. | — | No |
| `email` | `EmailChannel` | Sends via the framework mailer (`from` / `to` / `default_subject` from config). | — | No |
| `database` | `DatabaseChannel` | Persists the notification to a configured table. | `table` | No |
| `cache` | `CacheChannel` | Stores under a key prefix with a TTL. | — | No |
| `file` | `FileChannel` | Appends to a log file, with optional rotation and a max size. | `path` | No |
| `console` | `ConsoleChannel` | Writes to STDOUT — handy inside CLI jobs. Not in default config. | — | No |
| `slack` | `SlackChannel` | Posts to a Slack incoming webhook. | `webhook_url` | Yes |
| `discord` | `DiscordChannel` | Posts to a Discord webhook. | `webhook_url` | Yes |
| `sms` | `SmsChannel` | Sends via a generic SMS provider (API key + URL). | `api_key`, `from` | No |
| `push` | `PushChannel` | Push notifications (OneSignal credentials by default). | `api_key`, `app_id` | Yes |
| `webhook` | `WebhookChannel` | POSTs (or GET/PUT/PATCH/DELETE) to an arbitrary URL with headers. | `url` | Yes |
| `apple_business_messages` | `AppleBusinessMessagesChannel` | Delivers to Apple Messages for Business. | `business_id`, `api_key`, `api_secret` | Yes |

A channel whose required keys are missing or empty fails its `validateConfig()`
check; the send records an `Invalid configuration` error for that channel and
moves on (it does not throw). The five outbound-HTTP channels additionally
validate every target URL through the SSRF guard before making a request.

## Sending to channels

The fluent service is reached via the `Notifications` facade,
`app(NotificationService::class)`, or `app('laranail.notifications')`. Every
delivery returns a `NotificationResult` aggregating per-channel outcomes.

### To a single channel

```php
use Simtabi\Laranail\Notifications\Facades\Notifications;

Notifications::send('Deployment finished', channels: 'slack');
```

### To several channels

```php
$result = Notifications::send(
    message: 'Backup completed',
    data: ['size_mb' => 142],
    channels: ['log', 'slack'],
    level: 'info',
);

if ($result->isSuccessful()) {
    // every targeted channel delivered
}

foreach ($result->getFailedChannels() as $channel) {
    // inspect $result->getErrors()[$channel] ?? null
}
```

### To the default channels

Omit the `channels` argument to use the configured (or runtime) default set —
see [Configuration](configuration.md#setting-the-default-channels).

```php
Notifications::send('Build green', data: ['status' => 'green']);
```

### To every registered channel

```php
Notifications::broadcast('Server is on fire!', ['host' => gethostname()], 'critical');
```

## Using a typed message

`send()` and `broadcast()` accept either a raw body string (the `data` bag is
folded into a message for you) or an immutable `NotificationMessage` DTO for
full control over subject, recipient, level, and channel-specific options:

```php
use Simtabi\Laranail\Notifications\DataTransferObjects\NotificationMessage;

$message = new NotificationMessage(
    body:    'Your export is ready',
    subject: 'Export complete',
    to:      'ops@example.com',
    level:   'info',
    options: ['icon' => ':white_check_mark:'],
);

Notifications::send($message, channels: ['email', 'slack']);
```

`NotificationMessage` also offers `make($body, $data, $level)`,
`fromArray($payload)`, and the instance helpers `option()`, `withOptions()`,
`toData()`, and `toArray()`.

## The result object

`NotificationResult` exposes:

- `isSuccessful(): bool` — all targeted channels delivered, no errors.
- `hasPartialSuccess(): bool` — at least one channel failed or errored.
- `getResults(): array<string, bool>` — channel name → delivered?
- `getErrors(): array<string, string>` — channel name → caller-safe error.
- `getSuccessfulChannels(): array<int, string>`
- `getFailedChannels(): array<int, string>`
- `toArray(): array`

For a queued send the result is `['queued' => true]` rather than per-channel
outcomes, since delivery happens later on the worker.

## Registering your own channel

Implement `NotificationChannelInterface` (or extend
`AbstractNotificationChannel`) and register the instance under an allow-listed
name:

```php
Notifications::registerChannel('log', $myChannel);
```

Note the name must be one of the enum keys — `registerChannel()` rejects an
unknown name with `InvalidArgumentException`. This keeps the allow-list as the
single source of truth for which channels can ever exist.

[← Docs index](../README.md#documentation)
