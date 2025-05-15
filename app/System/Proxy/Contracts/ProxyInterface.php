<?php

declare(strict_types = 1);

namespace App\System\Proxy\Contracts;

interface ProxyInterface
{
    /** @return string[] */
    public static function getTrustedProxies(): array;
}
