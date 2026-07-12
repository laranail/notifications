# Architecture

`laranail/notifications` is a small, self-contained multi-channel notification
system. It boots lazily, resolves channels from a fixed allow-list, and fails soft.

## Service provider (deferred)

`NotificationsServiceProvider` is a `DeferrableProvider`: nothing boots until the
`NotificationService` (or the `laranail.notifications` alias / `Notifications`
facade) is first resolved. It merges `config/notifications.php` under the
`notifications` key and publishes it with the `laranail-notifications` tag.

## NotificationService

The fluent core. `send($message, $data, $channels, $level)` resolves each requested
channel, sends, and aggregates per-channel outcomes into a `NotificationResult`
(`isSuccessful()` / `hasPartialSuccess()` / `getFailedChannels()` …). `broadcast()`
sends to every enabled channel. A channel failure is **recorded, never thrown** — one
bad channel can't break the others.

## Channel allow-list (enum, not arbitrary classes)

Channels are keyed by the `NotificationChannel` enum, which maps each case to its
concrete channel class. Config can only *toggle/parameterise* the known channels — it
**cannot instantiate arbitrary classes** (the prior loose `class` config was removed).
Custom channels are added explicitly in code via `registerChannel()`.

## Typed message DTO

`NotificationMessage` is an immutable value object (`body`, `subject`, `to`, `level`,
`options`) with `make()`/`fromArray()`/`toArray()`. Channels receive a typed message,
not a loose string + array.

## Queueing

When `queueable` is on, sends are dispatched via the **serializable**
`SendQueuedNotification` job (it carries the message as an array and rebuilds the
service on the worker — no closure capture).

## Security (SSRF + secrets)

Outbound HTTP channels (`webhook`, `slack`, `discord`, `push`,
`apple_business_messages`) use the `GuardsOutboundUrls` trait, which blocks
loopback / RFC1918 / link-local / non-`http(s)` / `file://` targets before any
request. Secrets (webhook URLs, API keys) are never logged. See
[security](security.md).

[← Docs index](../README.md#documentation)
