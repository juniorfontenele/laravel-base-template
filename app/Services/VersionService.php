<?php

declare(strict_types = 1);

namespace App\Services;

use Illuminate\Support\Facades\File;

class VersionService
{
    public static function version(): string
    {
        $version = self::readVersion() ?? self::generateVersion();

        return $version . '-' . config('app.env');
    }

    protected static function generateVersion(): string
    {
        if (! is_null(config('app.github.sha'))) {
            $hash = substr(config('app.github.sha'), 0, 7);
            $branch = config('app.github.ref');
        } else {
            exec('git rev-parse --short --verify HEAD 2> /dev/null', $outputHash);
            $hash = $outputHash[0] ?? 'x';
            exec('git rev-parse --abbrev-ref HEAD 2> /dev/null', $outputBranch);
            $branch = $outputBranch[0] ?? 'x';
        }
        exec('git describe --tags HEAD 2> /dev/null', $outputTag);
        $date = date('Ymd');
        $tag = $outputTag[0] ?? 'v1.0.0';
        $appVersion = "$date-$hash";

        return $appVersion;
    }

    public static function writeVersion(): void
    {
        File::replace(base_path('VERSION'), self::generateVersion());
    }

    public static function readVersion(): string|null
    {
        if (! file_exists(base_path('VERSION'))) {
            return null;
        }

        return file_get_contents(base_path('VERSION')) ?: null;
    }
}
