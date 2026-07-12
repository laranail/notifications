<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Notifications\Enums;

use Simtabi\Laranail\Notifications\Channels\AppleBusinessMessagesChannel;
use Simtabi\Laranail\Notifications\Channels\CacheChannel;
use Simtabi\Laranail\Notifications\Channels\ConsoleChannel;
use Simtabi\Laranail\Notifications\Channels\DatabaseChannel;
use Simtabi\Laranail\Notifications\Channels\DiscordChannel;
use Simtabi\Laranail\Notifications\Channels\EmailChannel;
use Simtabi\Laranail\Notifications\Channels\FileChannel;
use Simtabi\Laranail\Notifications\Channels\LogChannel;
use Simtabi\Laranail\Notifications\Channels\PushChannel;
use Simtabi\Laranail\Notifications\Channels\SlackChannel;
use Simtabi\Laranail\Notifications\Channels\SmsChannel;
use Simtabi\Laranail\Notifications\Channels\WebhookChannel;
use Simtabi\Laranail\Notifications\Contracts\NotificationChannelInterface;

/**
 * Native backed enum of the notification channels the toolkit ships.
 *
 * Ported from the legacy `Simtabi\Laranail\Shared\Enums\NotificationChannelEnum`
 * but WITHOUT the `laranail/enumerator` dependency: labels, configuration and
 * concrete-class mapping are expressed directly with `match` so the module stays
 * free of any `laranail/*` package. This is the single source of truth for the
 * channel allow-list — only the cases below may ever be resolved to a class.
 */
enum NotificationChannel: string
{
    case LOG = 'log';
    case EMAIL = 'email';
    case DATABASE = 'database';
    case CACHE = 'cache';
    case FILE = 'file';
    case CONSOLE = 'console';
    case WEBHOOK = 'webhook';
    case SLACK = 'slack';
    case DISCORD = 'discord';
    case SMS = 'sms';
    case PUSH = 'push';
    case APPLE_BUSINESS_MESSAGES = 'apple_business_messages';

    /**
     * Human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::LOG => 'Log',
            self::EMAIL => 'Email',
            self::DATABASE => 'Database',
            self::CACHE => 'Cache',
            self::FILE => 'File',
            self::CONSOLE => 'Console',
            self::WEBHOOK => 'Webhook',
            self::SLACK => 'Slack',
            self::DISCORD => 'Discord',
            self::SMS => 'SMS',
            self::PUSH => 'Push Notification',
            self::APPLE_BUSINESS_MESSAGES => 'Apple Business Messages',
        };
    }

    /**
     * Concrete channel implementation FQCN for this case.
     *
     * @return class-string<NotificationChannelInterface>
     */
    public function channelClass(): string
    {
        return match ($this) {
            self::LOG => LogChannel::class,
            self::EMAIL => EmailChannel::class,
            self::DATABASE => DatabaseChannel::class,
            self::CACHE => CacheChannel::class,
            self::FILE => FileChannel::class,
            self::CONSOLE => ConsoleChannel::class,
            self::WEBHOOK => WebhookChannel::class,
            self::SLACK => SlackChannel::class,
            self::DISCORD => DiscordChannel::class,
            self::SMS => SmsChannel::class,
            self::PUSH => PushChannel::class,
            self::APPLE_BUSINESS_MESSAGES => AppleBusinessMessagesChannel::class,
        };
    }

    /**
     * Whether the channel needs configuration before it can deliver.
     */
    public function requiresConfig(): bool
    {
        return in_array($this, [
            self::EMAIL, self::FILE, self::WEBHOOK, self::SLACK, self::DISCORD,
            self::SMS, self::PUSH, self::APPLE_BUSINESS_MESSAGES,
        ], true);
    }

    /**
     * Whether the channel performs outbound HTTP and is subject to the SSRF guard.
     */
    public function isOutboundHttp(): bool
    {
        return in_array($this, [
            self::WEBHOOK, self::SLACK, self::DISCORD, self::PUSH, self::APPLE_BUSINESS_MESSAGES,
        ], true);
    }

    /**
     * All channel keys.
     *
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }

    /**
     * Resolve a string key to a case, or null when it is not a known channel.
     */
    public static function tryFromKey(string $key): ?self
    {
        return self::tryFrom($key);
    }
}
