<?php

declare(strict_types = 1);

namespace App\System\Proxy\Services;

use App\System\Proxy\Contracts\ProxyInterface;

class InternalProxyService implements ProxyInterface
{
    public static function getTrustedProxies(): array
    {
        return [
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
        ];
    }
}
