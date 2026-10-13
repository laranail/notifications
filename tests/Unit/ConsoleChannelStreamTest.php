<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Notifications\Tests\Unit;

use LogicException;
use Illuminate\Console\OutputStyle;
use Symfony\Component\Console\Input\ArrayInput;
use Simtabi\Laranail\Notifications\Tests\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Simtabi\Laranail\Notifications\Channels\ConsoleChannel;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Simtabi\Laranail\Notifications\DataTransferObjects\NotificationMessage;

/**
 * A terminal stand-in: stdout and stderr are separate buffers, as they are for a real
 * `ConsoleOutput`, so a test can tell which one a line went to.
 */
final class SplitBufferedOutput extends BufferedOutput implements ConsoleOutputInterface
{
    private OutputInterface $stderr;

    public function __construct()
    {
        parent::__construct();
        $this->stderr = new BufferedOutput;
    }

    public function getErrorOutput(): OutputInterface
    {
        return $this->stderr;
    }

    public function setErrorOutput(OutputInterface $error): void
    {
        $this->stderr = $error;
    }

    public function section(): ConsoleSectionOutput
    {
        throw new LogicException('Sections are not used by these tests.');
    }

    public function stderr(): string
    {
        return $this->stderr instanceof BufferedOutput ? $this->stderr->fetch() : '';
    }
}

/**
 * Stream routing when the channel is handed a command's output.
 *
 * `Illuminate\Console\Command::run()` wraps the terminal output in an `OutputStyle`, which is not a
 * `ConsoleOutputInterface`, so a bare `instanceof` check sends error-level lines to stdout.
 */
final class ConsoleChannelStreamTest extends TestCase
{
    public function test_an_error_reaches_stderr_through_an_output_style(): void
    {
        $console = new SplitBufferedOutput;
        $channel = new ConsoleChannel([], new OutputStyle(new ArrayInput([]), $console));

        $this->assertTrue($channel->send(new NotificationMessage('disk full', level: 'error')));

        $this->assertSame('', $console->fetch());
        $this->assertStringContainsString('[ERROR] disk full', $console->stderr());
    }

    public function test_an_info_line_stays_on_stdout_through_an_output_style(): void
    {
        $console = new SplitBufferedOutput;
        $channel = new ConsoleChannel([], new OutputStyle(new ArrayInput([]), $console));

        $this->assertTrue($channel->send(new NotificationMessage('deployed', level: 'info')));

        $this->assertStringContainsString('[INFO] deployed', $console->fetch());
        $this->assertSame('', $console->stderr());
    }

    public function test_an_error_reaches_stderr_on_a_bare_console_output(): void
    {
        $console = new SplitBufferedOutput;
        $channel = new ConsoleChannel([], $console);

        $this->assertTrue($channel->send(new NotificationMessage('disk full', level: 'error')));

        $this->assertSame('', $console->fetch());
        $this->assertStringContainsString('[ERROR] disk full', $console->stderr());
    }
}
