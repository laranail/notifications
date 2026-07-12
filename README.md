# laranail/notifications

[![Latest version on Packagist](https://img.shields.io/packagist/v/laranail/notifications.svg)](https://packagist.org/packages/laranail/notifications)
[![Tests](https://github.com/laranail/notifications/actions/workflows/tests.yml/badge.svg)](https://github.com/laranail/notifications/actions/workflows/tests.yml)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

> Multi-channel notifications for Laravel — 12 SSRF-guarded channels (email, log, database, cache, slack, discord, push, sms, webhook, file, console, apple-business-messages) behind one unified, typed fluent API where `send()`/`broadcast()` return a rich `NotificationResult`. Fail-soft, extensible, queueable.

Compatible with PHP `^8.3 || ^8.4 || ^8.5` and Laravel `^13.0`.

## Install

```bash
composer require laranail/notifications
```

## Documentation

Full documentation is at **[opensource.simtabi.com/documentation/laranail/notifications](https://opensource.simtabi.com/documentation/laranail/notifications/)** — getting started, the channels, the typed result object, SSRF guarding, writing custom channels, queued delivery, and configuration.

## Contributing & security

Issues and PRs are welcome — see [CONTRIBUTING.md](CONTRIBUTING.md). Report vulnerabilities per
[SECURITY.md](SECURITY.md) (opensource@simtabi.com); participation follows the [Code of Conduct](CODE_OF_CONDUCT.md).

## License

MIT © Simtabi LLC. See [LICENSE](LICENSE).
