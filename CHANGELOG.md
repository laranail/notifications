# Changelog

All notable changes to `laranail/notifications` are documented here.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Fixed

- **`ConsoleChannel` sent error-level lines to stdout when handed a command's output.**
  `Illuminate\Console\Command::run()` wraps the terminal output in an `OutputStyle`, which is not a
  `ConsoleOutputInterface`, so the `instanceof` check never found the error stream. The channel now
  unwraps the style first, a local copy of `laranail/console`'s `ErrorOutput::unwrap()` since this
  module carries no `laranail/*` dependency. Nothing in the package passes it an `OutputStyle`
  today; an application that injected `$this->output` from a command did. `require` now names
  `illuminate/console` for `OutputStyle`; `laravel/framework` already replaces it, so an
  application installs nothing new.

### Changed

- **Test credentials are generated at run time.** The one literal API key in the tests is now
  generated with `random_bytes()`, since secret scanning reported such literals as leaks, and a
  guard test fails on any literal left behind. The guard is a minimal local copy of
  `laranail/package-tools`' check: package-tools requires PHP `^8.4.1`, and this package still
  supports 8.3, so it is not added as a dependency.
- `require` declares what `src/` uses directly: `illuminate/filesystem` and `illuminate/log`
  (the `File` and `Log` facades in `FileChannel` and `LogChannel`), and `laravel/framework`, for
  `Illuminate\Foundation\Bus\Dispatchable` and the `config_path()`/`now()` helpers, none of which
  has a split package. `laravel/framework` replaces every `illuminate/*` split, so an application installs nothing
  new. `tests/Unit/DeclaredRequirementsTest.php` now fails when `src/` uses an Illuminate component,
  facade or global helper that `require` does not name.

## [0.1.0] - 2026-08-15

### Changed

- **The config key is `laranail.notifications`,** published to `config/laranail/notifications.php`;
  `config('notifications.channels')` is now `config('laranail.notifications.channels')`. Laravel's
  config repository is a flat map and `notifications` is a name an application would very plausibly
  use for its own file.

- **The publish tag follows the org shape:** `laranail-notifications` → `laranail::notifications-config`.

The `notifications` database table and queue name are unchanged — they are not config keys and share
the word by coincidence.

Initial public release.

### Added

- Multi-channel notification service extracted from `laranail/laranail` v2.
- 13 channels: log, email, database, cache, slack, discord, push, sms, webhook, file, console, apple-business-messages, plus an extensible `AbstractNotificationChannel` base.
- `NotificationService` with a channel registry, broadcast, queueable dispatch, and a fluent API.
- `NotificationResult` value object with success / partial-success introspection.
- Auto-discovered `NotificationsServiceProvider` and a publishable `config/notifications.php`.

[Unreleased]: https://github.com/laranail/notifications/compare/v0.1.0...HEAD
