<?php

declare(strict_types = 1);

namespace App\Events\Http;

use Illuminate\Database\Eloquent\Model;

class MaxRequestsLimit
{
    /**
     * Create a new event instance.
     */
    public function __construct(
        public string $ip,
        public int $maxEvents,
        public mixed $attempts,
        public int $decaySeconds,
        public int $availableIn,
        public int $returnCode,
        public ?string $returnMessage
    ) {
        //
    }

    /** @return array<string, mixed> */
    public function getContext(): array
    {
        return [
            'rate_limiting' => [
                'type' => 'requests',
                'ip' => $this->ip,
                'max_events' => $this->maxEvents,
                'attempts' => $this->attempts,
                'decay_seconds' => $this->decaySeconds,
                'available_in' => $this->availableIn,
                'return_code' => $this->returnCode,
                'return_message' => $this->returnMessage,
            ],
        ];
    }

    public function getSubject(): ?Model
    {
        return null;
    }
}
