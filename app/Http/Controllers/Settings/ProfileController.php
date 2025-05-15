<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Models\Session;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        /** @var Collection<int, Session> $sessions */
        $sessions = $user->sessions()->orderByDesc('last_activity')
            ->get()
            ->map(function (Session $session) {
                /** @var CarbonImmutable $lastActivity */
                $lastActivity = $session->last_activity;

                return [
                    'id' => $session->id,
                    'ip' => $session->ip_address,
                    'user_agent' => $session->user_agent,
                    'last_activity' => $lastActivity->diffForHumans(),
                    'self' => $session->id === session()->getId(),
                ];
            })->sortByDesc('self');

        return Inertia::render('settings/profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
            'sessions' => $sessions,
            'timezones' => timezone_identifiers_list(),
        ]);
    }

    /**
     * Update the user's profile settings.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()?->fill($request->validated());

        if ($request->user()?->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()?->save();

        return to_route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user?->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
