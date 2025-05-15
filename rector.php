<?php

declare(strict_types = 1);

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\CodeQuality\Rector\FuncCall\CompactToVariablesRector;
use Rector\Config\RectorConfig;
use Rector\Php71\Rector\FuncCall\RemoveExtraParametersRector;
use Rector\Renaming\Rector\MethodCall\RenameMethodRector;
use Rector\Set\ValueObject\SetList;
use Rector\ValueObject\PhpVersion;
use RectorLaravel\Set\LaravelLevelSetList;

return static function (RectorConfig $rectorConfig): void {
    // Paths to analyze
    $rectorConfig->paths([
        __DIR__ . '/app',
        __DIR__ . '/config',
        __DIR__ . '/database',
        __DIR__ . '/resources',
        __DIR__ . '/routes',
        __DIR__ . '/tests',
    ]);

    // Skip specific rules
    $rectorConfig->skip([
        CompactToVariablesRector::class,
        RemoveExtraParametersRector::class => [
            __DIR__ . '/app/Http/Controllers/WebAuthn/WebAuthnLoginController.php',
            __DIR__ . '/app/Http/Controllers/WebAuthn/WebAuthnRegisterController.php',
        ],
        RenameMethodRector::class => [
            __DIR__ . '/app/Http/Controllers/WebAuthn/WebAuthnLoginController.php',
            __DIR__ . '/app/Http/Controllers/WebAuthn/WebAuthnRegisterController.php',
        ],
    ]);

    // Enable caching for Rector
    $rectorConfig->cacheDirectory(__DIR__ . '/storage/rector');
    $rectorConfig->cacheClass(FileCacheStorage::class);

    // Apply sets for Laravel and general code quality
    $rectorConfig->sets([
        LaravelLevelSetList::UP_TO_LARAVEL_110,
        SetList::CODE_QUALITY,
    ]);

    // Define PHP version for Rector
    $rectorConfig->phpVersion(PhpVersion::PHP_84);
};
