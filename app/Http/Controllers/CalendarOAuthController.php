<?php

namespace App\Http\Controllers;

use App\Models\CalendarIntegration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class CalendarOAuthController extends Controller
{
    public function redirectToGoogle(): RedirectResponse
    {
        return Socialite::driver('google')
            ->scopes(config('services.google.scopes'))
            ->with([
                'access_type' => 'offline',
                'prompt' => 'consent',
                'include_granted_scopes' => 'true',
            ])
            ->redirect();
    }

    public function handleGoogleCallback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            $integration = CalendarIntegration::firstOrNew([
                'user_id' => Auth::id(),
                'provider' => 'google',
            ]);

            $existingRefreshToken = $integration->refresh_token;

            $integration->calendar_email = $googleUser->getEmail();
            $integration->access_token = $googleUser->token;
            $integration->refresh_token = $googleUser->refreshToken ?: $existingRefreshToken;
            $integration->token_expires_at = $googleUser->expiresIn
                ? now()->addSeconds((int) $googleUser->expiresIn)
                : null;
            $integration->scopes = config('services.google.scopes');
            $integration->status = CalendarIntegration::STATUS_CONNECTED;
            $integration->save();

            return redirect('/scheduling')->with('success', 'Google Calendar account connected successfully.');
        } catch (Throwable $exception) {
            return redirect('/scheduling')->with('error', 'Unable to connect Google Calendar. Please try again.');
        }
    }

    public function disconnectGoogle(): RedirectResponse
    {
        $integration = CalendarIntegration::where('user_id', Auth::id())
            ->where('provider', 'google')
            ->first();

        if ($integration) {
            $integration->access_token = null;
            $integration->refresh_token = null;
            $integration->token_expires_at = null;
            $integration->status = CalendarIntegration::STATUS_DISCONNECTED;
            $integration->save();
        }

        return redirect('/scheduling')->with('success', 'Google Calendar account disconnected.');
    }
}
