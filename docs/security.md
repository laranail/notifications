# Security

The notifications package is built to be safe to drive from semi-trusted
configuration and to deliver to external services without leaking secrets or
opening SSRF holes. This page documents the guarantees and how they are enforced.

## SSRF guard for outbound HTTP

The five outbound-HTTP channels — `webhook`, `slack`, `discord`, `push`, and
`apple_business_messages` — share the `GuardsOutboundUrls` trait
(`Simtabi\Laranail\Notifications\Support\GuardsOutboundUrls`). Before any request
is made, the target URL is validated by `isOutboundUrlAllowed()`. A URL is
**rejected** when:

- It is empty (after trimming).
- Its scheme is anything other than `http` or `https` — this explicitly excludes
  `file://`, `gopher://`, and every other non-web scheme.
- It has no host.
- Its host resolves to a blocked target.

A host is **blocked** when it is:

- A named loopback / metadata alias: `localhost`, `ip6-localhost`,
  `metadata.google.internal`, or any `*.localhost` name.
- An IPv6 literal that is loopback (`::1`), unspecified (`::`), unique-local
  (`fc00::/7`), or link-local (`fe80::/10`). IPv4-mapped IPv6 literals
  (`::ffff:a.b.c.d`) are unwrapped and checked as IPv4.
- An IPv4 literal outside the public range — this covers private blocks
  (`10/8`, `172.16/12`, `192.168/16`), loopback (`127/8`), link-local
  (`169.254/16`, including the cloud-metadata address `169.254.169.254`), and
  reserved ranges, all in one `filter_var` check with `FILTER_FLAG_NO_PRIV_RANGE`
  and `FILTER_FLAG_NO_RES_RANGE`.

A non-IP hostname that is not a known internal alias is **allowed**. The guard
deliberately does **no DNS resolution**, so it stays deterministic and
side-effect free; protecting against DNS rebinding is left to network-level
controls.

## Allow-listed channels (no arbitrary-class instantiation)

Channels are resolved exclusively from the `NotificationChannel` enum, which maps
each known key to a concrete class via a `match`. The service builds a channel
with `NotificationChannel::tryFromKey($name)` followed by `new $class($config)`,
where `$class` comes only from the enum — never from a `class` key in config.
A legacy pattern of `new $classFromConfig` was removed for exactly this reason:
configuration can toggle and parameterise channels, but can never name an
arbitrary class to instantiate.

The same allow-list gates the public API: `registerChannel()` and
`setDefaultChannels()` validate every name and throw `InvalidArgumentException`
on an unknown channel. The `$channels` selector passed to `send()` is strictly
typed (`string|array|null`) so an unexpected value is rejected rather than
silently falling through to "all channels".

## Serializable queue job

When queueing is enabled, `send()` dispatches a `SendQueuedNotification` job that
carries **only** a JSON-safe payload — the serialized message array plus the list
of target channel names. It never serializes a live `NotificationService` (with
its HTTP and mailer clients) or a closure capturing one. On the worker, the job's
`handle()` re-resolves a fresh service from the container
(`NotificationService::fromContainer()`), rebuilds the message with
`NotificationMessage::fromArray()`, and calls `dispatchNow()`. The message is
marked `queued` before dispatch so the synchronous send on the worker does not
re-queue itself.

## Fail-soft delivery

Channels return a boolean and never let an exception propagate through the
service. During a dispatch the service catches every `Throwable` per channel,
records a `false` result and a caller-safe error string, and continues to the
next channel. A missing channel, a disabled channel, or invalid configuration is
likewise recorded as a result/error rather than thrown. The outcome is always a
`NotificationResult` — one bad channel can never abort delivery to the others or
crash the caller.

## Never log secrets

Error strings collected in `NotificationResult` are caller-safe summaries —
channels are responsible for never placing tokens, webhook URLs, or other
secrets into them. The outbound channels fail soft on transport and non-2xx
errors **without** echoing the URL or configured headers. The SSRF guard
operates on the URL string alone and emits a boolean, so it never logs the
destination either.

## Regression coverage

The security-relevant behaviours above are pinned by a dedicated regression
suite. Run just those tests with:

```bash
vendor/bin/pest --group=security
```

[← Docs index](../README.md#documentation)
