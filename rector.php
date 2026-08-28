<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Php83\Rector\ClassConst\AddTypeToConstRector;
use Rector\Php71\Rector\TryCatch\MultiExceptionCatchRector;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;

/**
 * Floor guard only: pin to the PHP 8.3 set so no newer-only syntax slips below
 * the package floor. Deliberately NOT enabling the codeQuality/typeDeclarations
 * prepared sets — the ported channels are reviewed, hardened security code and
 * those sets rewrite intentional, clearer constructs (e.g. `=== null`) for no
 * behavioural gain.
 */
return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withSkip([
        __DIR__ . '/tests/Fixtures',
        // PHP 8.3-only cosmetic noise / churn we don't want:
        AddOverrideAttributeToOverriddenMethodsRector::class,
        // Keeps the explicit `const X = [...]` form (typed `const array` trips the
        // PSR-12 sniff's tokenizer on this toolchain).
        AddTypeToConstRector::class,
        // The channels keep separate ConnectionException + Throwable catches on
        // purpose; merging them makes PHPStan flag the (intentional) first catch
        // as a dead catch.
        MultiExceptionCatchRector::class,
    ])
    ->withSets([
        LevelSetList::UP_TO_PHP_83,
    ]);
