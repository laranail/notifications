<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Notifications\Tests\Unit;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Guards against pre-rename authorship debris (Omar Chouman / personal email /
 * LaraUtilX) in shipped files.
 */
#[Group('security')]
class NoLegacyDebrisTest extends TestCase
{
    /** Precise debris tokens (NOT bare "omar" — that false-matches `fromArray`). */
    private const DEBRIS = [
        'omar.chouman',
        'omarchouman',
        'Omar Chouman',
        'lara-util-x',
        'laraUtilX',
        'LaraUtilX',
    ];

    public function test_no_legacy_authorship_debris_in_shipped_files(): void
    {
        $root = dirname(__DIR__, 2);
        $scan = ['src', 'config', 'docs', '.github'];
        $rootFiles = ['composer.json', 'LICENSE', 'README.md', 'CONTRIBUTING.md', 'SECURITY.md', 'CODE_OF_CONDUCT.md', 'CHANGELOG.md'];

        $files = [];
        foreach ($rootFiles as $f) {
            if (is_file($root . '/' . $f)) {
                $files[] = $root . '/' . $f;
            }
        }
        foreach ($scan as $dir) {
            $path = $root . '/' . $dir;
            if (!is_dir($path)) {
                continue;
            }
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)) as $file) {
                /** @var \SplFileInfo $file */
                if ($file->isFile()) {
                    $files[] = $file->getPathname();
                }
            }
        }

        $offenders = [];
        foreach ($files as $path) {
            $contents = (string) file_get_contents($path);
            foreach (self::DEBRIS as $token) {
                if (stripos($contents, $token) !== false) {
                    $offenders[] = $token . ' in ' . $path;
                }
            }
        }

        $this->assertSame([], $offenders, "Legacy debris found:\n" . implode("\n", $offenders));
    }
}
