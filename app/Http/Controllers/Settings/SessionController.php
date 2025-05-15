<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function destroy(Request $request, string $id): RedirectResponse
    {
        $request->user()?->sessions()->where('id', $id)->delete();

        return to_route('profile.edit');
    }

    public function destroyOthers(Request $request): RedirectResponse
    {
        $request->user()?->sessions()->where('id', '!=', session()->getId())->delete();

        return to_route('profile.edit');
    }
}
