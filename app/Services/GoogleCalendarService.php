<?php

namespace App\Services;

use App\Models\CalendarIntegration;
use App\Models\User;
use Carbon\Carbon;
use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleCalendarApi;
use Google\Service\Calendar\Event as GoogleCalendarEvent;
use Google\Service\Calendar\EventDateTime;
use RuntimeException;
use Throwable;

class GoogleCalendarService
{
    public function __construct(private readonly GoogleTokenService $tokenService)
    {
    }

    public function isConnected(User $user): bool
    {
        $integration = $this->getIntegration($user);

        return $integration !== null && $integration->status === CalendarIntegration::STATUS_CONNECTED;
    }

    public function getEventsForUser(User $user, ?string $timeMin = null, ?string $timeMax = null): array
    {
        $integration = $this->getIntegration($user);

        if (! $integration || $integration->status !== CalendarIntegration::STATUS_CONNECTED) {
            throw new RuntimeException('Google Calendar is not connected for this user.');
        }

        return $this->runWithClient($integration, function (GoogleClient $client) use ($timeMin, $timeMax) {
            $calendar = new GoogleCalendarApi($client);

            $parameters = [
                'singleEvents' => true,
                'orderBy' => 'startTime',
                'maxResults' => 2500,
            ];

            if ($timeMin) {
                $parameters['timeMin'] = Carbon::parse($timeMin)->toRfc3339String();
            }

            if ($timeMax) {
                $parameters['timeMax'] = Carbon::parse($timeMax)->toRfc3339String();
            }

            $events = $calendar->events->listEvents('primary', $parameters);

            return collect($events->getItems())->map(function (GoogleCalendarEvent $event) {
                $start = $event->getStart()?->getDateTime() ?: $event->getStart()?->getDate();
                $end = $event->getEnd()?->getDateTime() ?: $event->getEnd()?->getDate();

                return [
                    'id' => $event->getId(),
                    'title' => $event->getSummary() ?: '(No title)',
                    'start' => $start,
                    'end' => $end,
                    'color' => 'blue',
                    'source' => 'google',
                ];
            })->values()->all();
        });
    }

    public function createEventForUser(User $user, array $payload): array
    {
        $integration = $this->getIntegration($user);

        if (! $integration || $integration->status !== CalendarIntegration::STATUS_CONNECTED) {
            throw new RuntimeException('Google Calendar is not connected for this user.');
        }

        return $this->runWithClient($integration, function (GoogleClient $client) use ($payload, $integration) {
            $calendar = new GoogleCalendarApi($client);

            $timezone = config('app.timezone', 'UTC');

            $event = new GoogleCalendarEvent([
                'summary' => $payload['title'],
                'description' => $payload['description'] ?? null,
                'start' => new EventDateTime([
                    'dateTime' => Carbon::parse($payload['start'])->toRfc3339String(),
                    'timeZone' => $timezone,
                ]),
                'end' => new EventDateTime([
                    'dateTime' => Carbon::parse($payload['end'])->toRfc3339String(),
                    'timeZone' => $timezone,
                ]),
            ]);

            $created = $calendar->events->insert('primary', $event);

            $integration->last_synced_at = now();
            $integration->save();

            return [
                'id' => $created->getId(),
                'title' => $created->getSummary() ?: $payload['title'],
                'start' => $created->getStart()?->getDateTime() ?: $payload['start'],
                'end' => $created->getEnd()?->getDateTime() ?: $payload['end'],
                'source' => 'google',
            ];
        });
    }

    private function getIntegration(User $user): ?CalendarIntegration
    {
        return $user->googleCalendarIntegration()->first();
    }

    private function runWithClient(CalendarIntegration $integration, callable $callback): mixed
    {
        $accessToken = $this->tokenService->ensureValidAccessToken($integration);

        if (! $accessToken) {
            throw new RuntimeException('Unable to get a valid Google access token.');
        }

        try {
            $client = $this->makeGoogleClient($accessToken);

            return $callback($client);
        } catch (Throwable $exception) {
            if ($this->isUnauthorized($exception)) {
                $refreshedToken = $this->tokenService->refreshAccessToken($integration);

                if ($refreshedToken) {
                    $retryClient = $this->makeGoogleClient($refreshedToken);

                    return $callback($retryClient);
                }
            }

            throw new RuntimeException('Google Calendar API request failed.', 0, $exception);
        }
    }

    private function makeGoogleClient(string $accessToken): GoogleClient
    {
        $client = new GoogleClient();
        $client->setClientId((string) config('services.google.client_id'));
        $client->setClientSecret((string) config('services.google.client_secret'));
        $client->setRedirectUri((string) config('services.google.redirect'));
        $client->setAccessToken($accessToken);

        return $client;
    }

    private function isUnauthorized(Throwable $exception): bool
    {
        return (int) $exception->getCode() === 401;
    }
}
