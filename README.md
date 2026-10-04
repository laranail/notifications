# laranail/notifications

[![Tests](https://github.com/laranail/notifications/actions/workflows/tests.yml/badge.svg)](https://github.com/laranail/notifications/actions/workflows/tests.yml)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

`laranail/notifications` is not published to Packagist, so there is no registry-version badge to show: see [Install](#install).

> Multi-channel notifications for Laravel — 12 SSRF-guarded channels (email, log, database, cache, slack, discord, push, sms, webhook, file, console, apple-business-messages) behind one unified, typed fluent API where `send()`/`broadcast()` return a rich `NotificationResult`. Fail-soft, extensible, queueable.

Compatible with PHP `^8.3 || ^8.4 || ^8.5` and Laravel `^13.0`.

## Install

```bash
composer require laranail/notifications
```

## Quick start guide and usage

### Getting started

The provider is auto-discovered and the `log` channel is on by default, so the example below
works straight after install. To reach anything else:

1. Publish the config if you want to edit it (it lands in `config/laranail/notifications.php`):

   ```bash
   php artisan vendor:publish --tag=laranail::notifications-config
   ```

2. Enable each extra channel through its env keys, e.g. Slack:

   ```dotenv
   NOTIFICATIONS_SLACK_ENABLED=true
   SLACK_WEBHOOK_URL=https://hooks.slack.com/services/...
   ```

### Usage

```php
use Simtabi\Laranail\Notifications\Facades\Notifications;

$result = Notifications::send(
    message: 'Nightly backup completed',
    data: ['size_mb' => 142],
    channels: ['log'],
);

$result->isSuccessful();       // true
$result->getFailedChannels();  // []
```

Broadcast to every registered channel:

```php
Notifications::broadcast('Server is on fire!', ['host' => gethostname()], 'critical');
```

The full walkthrough is in [Getting started](docs/getting-started.md); everything else is in the [documentation index](#documentation).

## <a name="documentation"></a>Documentation

Full documentation is at **[opensource.simtabi.com/documentation/laranail/notifications](https://opensource.simtabi.com/documentation/laranail/notifications/)** — getting started, the channels, the typed result object, SSRF guarding, writing custom channels, queued delivery, and configuration.

## Contributing & security

Issues and PRs are welcome — see [CONTRIBUTING.md](CONTRIBUTING.md). Report vulnerabilities per
[SECURITY.md](SECURITY.md) (opensource@simtabi.com); participation follows the [Code of Conduct](CODE_OF_CONDUCT.md).

## License

MIT © Simtabi LLC. See [LICENSE](LICENSE).
