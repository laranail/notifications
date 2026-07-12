# Changelog

All notable changes to `laranail/notifications` are documented here.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.1.0] - 2026-07-11

Initial public release.

### Added

- Multi-channel notification service extracted from `laranail/laranail` v2.
- 13 channels: log, email, database, cache, slack, discord, push, sms, webhook, file, console, apple-business-messages, plus an extensible `AbstractNotificationChannel` base.
- `NotificationService` with a channel registry, broadcast, queueable dispatch, and a fluent API.
- `NotificationResult` value object with success / partial-success introspection.
- Auto-discovered `NotificationsServiceProvider` and a publishable `config/notifications.php`.
