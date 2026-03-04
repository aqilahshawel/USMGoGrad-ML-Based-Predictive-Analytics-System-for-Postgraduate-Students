<?php

namespace App\Services;

use App\Models\CalendarIntegration;
use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Log;
use Throwable;

class GoogleTokenService
{
    public function ensureValidAccessToken(CalendarIntegration $integration): ?string
    {
        if ($integration->provider !== 'google') {
            return null;
        }

        if ($integration->status !== CalendarIntegration::STATUS_CONNECTED) {
            return null;
        }

        if (! $integration->access_token || $this->isExpired($integration)) {
            return $this->refreshAccessToken($integration);
        }

        return $integration->access_token;
    }

    public function refreshAccessToken(CalendarIntegration $integration): ?string
    {
        if (! $integration->refresh_token) {
            $this->markAsRevoked($integration);

            return null;
        }

        try {
            $client = $this->makeGoogleClient();
            $tokenPayload = $client->fetchAccessTokenWithRefreshToken($integration->refresh_token);

            if (! is_array($tokenPayload) || isset($tokenPayload['error']) || empty($tokenPayload['access_token'])) {
                $this->markAsRevoked($integration);

                if (is_array($tokenPayload) && isset($tokenPayload['error'])) {
                    Log::warning('Google token refresh failed', [
                        'user_id' => $integration->user_id,
                        'provider' => $integration->provider,
                        'error' => $tokenPayload['error'],
                        'error_description' => $tokenPayload['error_description'] ?? null,
                    ]);
                }

                return null;
            }

            $integration->access_token = $tokenPayload['access_token'];

            if (! empty($tokenPayload['refresh_token'])) {
                $integration->refresh_token = $tokenPayload['refresh_token'];
            }

            if (! empty($tokenPayload['scope'])) {
                $integration->scopes = preg_split('/\s+/', trim((string) $tokenPayload['scope'])) ?: $integration->scopes;
            }

            if (! empty($tokenPayload['expires_in'])) {
                $integration->token_expires_at = now()->addSeconds((int) $tokenPayload['expires_in']);
            }

            $integration->status = CalendarIntegration::STATUS_CONNECTED;
            $integration->save();

            return $integration->access_token;
        } catch (Throwable $exception) {
            Log::warning('Google token refresh exception', [
                'user_id' => $integration->user_id,
                'provider' => $integration->provider,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function isExpired(CalendarIntegration $integration): bool
    {
        if (! $integration->token_expires_at) {
            return true;
        }

        return $integration->token_expires_at->lte(now()->addMinute());
    }

    private function makeGoogleClient(): GoogleClient
    {
        $client = new GoogleClient();
        $client->setClientId((string) config('services.google.client_id'));
        $client->setClientSecret((string) config('services.google.client_secret'));
        $client->setRedirectUri((string) config('services.google.redirect'));

        return $client;
    }

    private function markAsRevoked(CalendarIntegration $integration): void
    {
        $integration->access_token = null;
        $integration->token_expires_at = null;
        $integration->status = CalendarIntegration::STATUS_REVOKED;
        $integration->save();
    }
}
