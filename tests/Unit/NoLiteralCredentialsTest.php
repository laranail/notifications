<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Notifications\Tests\Unit;

use RecursiveIteratorIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;

/**
 * Keeps literal credentials out of the tests, so secret scanning has nothing to report.
 *
 * A secret scanner cannot tell a test key from a leaked one and reports both: any literal under a
 * name such as `password`, `secret`, `token` or `api_key`, and the password in a URL. Tests
 * generate such values at run time (`bin2hex(random_bytes(16))`) instead.
 *
 * A minimal local copy of laranail/package-tools' AssertsNoLiteralCredentials. This package
 * supports PHP 8.3, and package-tools requires ^8.4.1, so depending on it, even in require-dev,
 * would break the 8.3 CI leg. Mark a deliberate fixed value on its line, or the line above, with
 * `// @credential-literal-exempt: <reason>`.
 */
final class NoLiteralCredentialsTest extends TestCase
{
    private const string EXEMPTION = '@credential-literal-exempt';

    public function test_the_tests_hold_no_literal_credentials(): void
    {
        [$scanned, $offenders] = self::scan(dirname(__DIR__));

        // A floor, so a path that stops matching fails instead of reporting a clean suite.
        self::assertGreaterThanOrEqual(8, $scanned, "Only {$scanned} PHP file(s) were scanned.");
        self::assertSame([], $offenders, "These tests hold a literal credential:\n  " . implode("\n  ", $offenders));
    }

    public function test_the_scan_recognises_a_literal(): void
    {
        $name = 'api' . '_key';

        self::assertCount(1, self::literalsIn("<?php ['{$name}' => 'abcd1234'];"));
        self::assertCount(1, self::literalsIn(sprintf('<?php $x = "https://u:%s@example.test";', 'abcd1234')));
        self::assertSame([], self::literalsIn("<?php ['{$name}' => \$generated];"));
    }

    /**
     * @return array{int, list<string>}
     */
    private static function scan(string $dir): array
    {
        $scanned = 0;
        $offenders = [];

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $scanned++;
            $source = (string) file_get_contents($file->getPathname());
            $lines = explode("\n", $source);

            foreach (self::literalsIn($source) as [$offset, $value]) {
                $line = substr_count($source, "\n", 0, $offset) + 1;

                if (str_contains($lines[$line - 1], self::EXEMPTION) || str_contains($lines[$line - 2] ?? '', self::EXEMPTION)) {
                    continue;
                }

                // Never the value itself: the message lands in CI logs.
                $offenders[] = sprintf('%s:%d %s…', $file->getPathname(), $line, substr($value, 0, 2));
            }
        }

        return [$scanned, $offenders];
    }

    /**
     * @return list<array{int, string}> [byte offset, literal]
     */
    private static function literalsIn(string $source): array
    {
        $name = '(?<name>[\w.-]*(?:password|passwd|secret|token|api_?key|private_?key)[\w.-]*)';
        $literal = '\'(?<value>(?:[^\'\\\\]|\\\\.)*)\'';

        $patterns = [
            '/[\'"]' . $name . '[\'"]\s*=>\s*' . $literal . '/i',
            '/\$(?:this->)?' . $name . '\s*=\s*' . $literal . '/i',
            '/\bconst\s+(?:\??\w+\s+)?' . $name . '\s*=\s*' . $literal . '/i',
            '/[(,]\s*' . $name . '\s*:\s*' . $literal . '/i',
            '/(?:->|::)(?:set|assertSet)\(\s*[\'"]' . $name . '[\'"]\s*,\s*' . $literal . '/i',
        ];

        $found = [];

        foreach ($patterns as $pattern) {
            preg_match_all($pattern, $source, $matches, PREG_OFFSET_CAPTURE);

            foreach ($matches['value'] as $i => [$value, $offset]) {
                if (self::isCredentialShaped($value) && ! self::namesMetadata($matches['name'][$i][0])) {
                    $found[] = [$offset, $value];
                }
            }
        }

        preg_match_all('#\b[a-z][a-z0-9+.-]*://[^\s:/@\'"]+:([^\s@/\'"$%{]+)@#i', $source, $urls, PREG_OFFSET_CAPTURE);

        foreach ($urls[1] as [$value, $offset]) {
            $found[] = [$offset, $value];
        }

        return $found;
    }

    /**
     * Not a credential: short placeholders, prose, validation rules, masks, and a package name or
     * path (`illuminate/auth`).
     */
    private static function isCredentialShaped(string $value): bool
    {
        return strlen($value) >= 4
            && preg_match('/[A-Za-z0-9]/', $value) === 1
            && preg_match('/[\s|]/', $value) === 0
            && preg_match('#^[\w.-]+/[\w./-]+$#', $value) === 0;
    }

    /**
     * A name that holds something about a credential rather than the credential: `token_type`,
     * `password_field`, `api_key_header`.
     */
    private static function namesMetadata(string $name): bool
    {
        return strcasecmp($name, 'passwords') === 0
            || preg_match('/[_.-](?:type|name|field|column|header|driver|table|broker|length|ttl|lifetime|expires?|url|uri|route|prefix|label|format|algo|algorithm|cost|rounds|rules?|message|mode|path|endpoint|placeholder)$/i', $name) === 1;
    }
}
