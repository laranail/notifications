# Security Policy

## Supported Versions

We release patches for security vulnerabilities for the latest minor release.

| Version | Supported          |
| ------- | ------------------ |
| 0.x     | :white_check_mark: |

## Reporting a Vulnerability

**Please do not report security vulnerabilities through public GitHub issues.**

Instead, report them via email to **security@simtabi.com**.

You should receive a response within 48 hours. If you do not, please follow up
to ensure we received your original message.

Please include as much of the following as you can, to help us triage quickly:

* Type of issue (e.g. SSRF, request smuggling, secret leakage, injection, etc.)
* Full paths of the source file(s) involved
* The location of the affected code (tag/branch/commit or direct URL)
* Any configuration required to reproduce
* Step-by-step reproduction instructions
* Proof-of-concept or exploit code (if possible)
* Impact, including how an attacker might exploit the issue

> **Prefer GitHub private vulnerability reporting** when you can: open it from this
> repository's Security tab. The report arrives attached to the repo with a draft advisory
> and a CVE request path already in place. Email is the fallback for anyone who would
> rather not use GitHub.

## Policy

We follow [Coordinated Vulnerability Disclosure](https://vuls.cert.org/confluence/display/CVD).
We will acknowledge your report within 48 hours, keep you updated on progress,
develop and test a fix, release a patched version, and then publicly disclose.
Credit is given to reporters unless anonymity is requested.

## Security Best Practices for Users

This package sends notifications over outbound HTTP channels. To keep your
deployment safe:

1. **Keep updated** — always use the latest stable version.
2. **Trust the SSRF guard** — outbound channels (`webhook`, `slack`, `discord`,
   `push`, `apple_business_messages`) validate every target URL via
   `GuardsOutboundUrls`. Do not bypass it with a custom channel that fetches
   unvalidated URLs.
3. **Treat channel URLs as secrets** — store webhook URLs and API keys in
   environment variables; never commit them.
4. **Use HTTPS** for all webhook and provider endpoints.
5. **Allow-list channels** — only enable the channels you actually use; the
   `NotificationChannel` enum is the single source of truth for what can be
   instantiated.

Thank you for helping keep laranail/notifications and its users safe.
