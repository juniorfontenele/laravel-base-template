<?php

declare(strict_types = 1);

namespace App\Events\Http;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HttpResponse
{
    /**
     * Create a new event instance.
     */
    public function __construct(public Request $request, public Response $response)
    {
        //
    }

    /** @return array<string, mixed> */
    public function getContext(): array
    {
        return [];
    }

    public function getSubject(): ?Model
    {
        return null;
    }
}
