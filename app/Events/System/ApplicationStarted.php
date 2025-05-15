<?php

declare(strict_types = 1);

namespace App\Events\System;

use Illuminate\Database\Eloquent\Model;

class ApplicationStarted
{
    /**
     * Create a new event instance.
     */
    public function __construct(public string $host, public string $ip, public string $role)
    {
        //
    }

    public function getSubject(): ?Model
    {
        return null;
    }

    /** @return array<string, mixed> */
    public function getContext(): array
    {
        return [
            'host' => $this->host,
            'ip' => $this->ip,
            'role' => $this->role,
        ];
    }
}
