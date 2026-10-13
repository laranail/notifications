<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Notifications\Channels;

use Throwable;
use Illuminate\Console\OutputStyle;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Simtabi\Laranail\Notifications\DataTransferObjects\NotificationMessage;

/**
 * Writes notifications to the console.
 *
 * Re-implemented natively so the module carries NO `laranail/*` dependency: the
 * legacy adapter delegated to `laranail/console-tools`, which is removed here.
 * Output goes to an injected {@see OutputInterface} when one is available,
 * otherwise straight to STDOUT/STDERR via `fwrite` with a safe fallback.
 *
 * Error-level lines go to the error stream. A command's output is an {@see OutputStyle}, which
 * `Illuminate\Console\Command::run()` wraps around the terminal's `ConsoleOutput` and which is not
 * a {@see ConsoleOutputInterface}, so it is unwrapped first. This is a local copy of
 * `laranail/console`'s `Tools\Support\ErrorOutput::unwrap()`, kept local because this module
 * carries no `laranail/*` dependency.
 */
class ConsoleChannel extends AbstractNotificationChannel
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [], private readonly ?OutputInterface $output = null)
    {
        parent::__construct($config);
    }

    public function getName(): string
    {
        return 'console';
    }

    public function send(NotificationMessage $message): bool
    {
        $line = $this->format($message);
        $isError = in_array(strtolower($message->level), ['error', 'critical', 'alert', 'emergency'], true);

        try {
            if ($this->output !== null) {
                $console = $this->output instanceof OutputStyle ? $this->output->getOutput() : $this->output;

                if ($isError && $console instanceof ConsoleOutputInterface) {
                    $console->getErrorOutput()->writeln($line);
                } else {
                    $this->output->writeln($line);
                }

                return true;
            }

            return $this->writeRaw($line . PHP_EOL, $isError);
        } catch (Throwable) {
            return false;
        }
    }

    protected function getDefaultConfig(): array
    {
        return ['enabled' => true, 'show_data' => true];
    }

    protected function getRequiredConfigKeys(): array
    {
        return [];
    }

    private function format(NotificationMessage $message): string
    {
        $line = '[' . strtoupper($message->level) . '] ' . $message->body;

        if (($this->config['show_data'] ?? true) && $message->options !== []) {
            $encoded = json_encode($message->options);

            if (is_string($encoded)) {
                $line .= ' ' . $encoded;
            }
        }

        return $line;
    }

    /**
     * Write raw bytes to the appropriate stream, falling back to php://stdout
     * when the STDOUT/STDERR constants are not defined (e.g. non-CLI SAPI).
     */
    private function writeRaw(string $text, bool $isError): bool
    {
        $stream = $isError
            ? (defined('STDERR') ? STDERR : @fopen('php://stderr', 'w'))
            : (defined('STDOUT') ? STDOUT : @fopen('php://stdout', 'w'));

        if (! is_resource($stream)) {
            return false;
        }

        return fwrite($stream, $text) !== false;
    }
}
