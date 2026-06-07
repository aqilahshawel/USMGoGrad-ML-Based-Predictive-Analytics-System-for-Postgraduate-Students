<?php

namespace App\Services;

use App\Models\MeetingRequest;
use App\Models\Schedule;
use App\Models\StudentCheckpoint;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class StudentAssistantService
{
    public function __construct(
        private readonly CandidatureTimelineService $timelineService,
        private readonly GoogleCalendarService $googleCalendarService,
        private readonly MeetingRequestService $meetingRequestService,
        private readonly OverdueCheckpointNotificationService $overdueNotificationService,
        private readonly IpsService $ipsService,
        private readonly PaperRecommendationService $paperRecommendationService,
    ) {
    }

    public function tryHandle(User $user, string $message): ?string
    {
        if (! $user->hasRole('student')) {
            return null;
        }

        $normalized = Str::lower(trim($message));

        if ($normalized === '') {
            return 'Please type your question, and I will help you.';
        }

        if ($this->isReminderIntent($normalized)) {
            return $this->buildReminderResponse($user);
        }

        if ($this->isSupervisorAvailabilityIntent($normalized)) {
            return $this->buildSupervisorAvailabilityResponse($user);
        }

        if ($this->isMeetingRecommendationIntent($normalized)) {
            return $this->buildMeetingRecommendationResponse($user);
        }

        if ($this->isCheckpointIntent($normalized)) {
            return $this->buildCheckpointResponse($user);
        }

        if ($this->isDurationIntent($normalized)) {
            return $this->buildDurationResponse($user);
        }

        if ($this->isIdealPeriodIntent($normalized)) {
            return $this->buildIdealPeriodResponse($user);
        }

        if ($this->isRecommendationIntent($normalized)) {
            return $this->buildRecommendationResponse($user);
        }

        if ($this->isIpsVivaVoceDownloadsIntent($normalized)) {
            return $this->buildVivaVoceDownloadsResponse();
        }

        if ($this->isNewsIntent($normalized)) {
            return $this->buildNewsResponse();
        }

        if ($this->isHelpIntent($normalized)) {
            return $this->helpText();
        }

        return null;
    }

    private function isReminderIntent(string $message): bool
    {
        return Str::contains($message, ['remind', 'reminder', 'upcoming', 'due soon', 'alert']);
    }

    private function isSupervisorAvailabilityIntent(string $message): bool
    {
        return (Str::contains($message, ['supervisor', 'sv'])
            && Str::contains($message, ['free', 'available', 'availability', 'week', 'slot', 'schedule']))
            || Str::contains($message, ['is my supervisor free']);
    }

    private function isMeetingRecommendationIntent(string $message): bool
    {
        return Str::contains($message, [
            'suggest meeting',
            'recommend meeting',
            'suitable time',
            'best time to meet',
            'meeting slot',
            'when should i meet',
        ]);
    }

    private function isCheckpointIntent(string $message): bool
    {
        return Str::contains($message, ['next checkpoint', 'checkpoint due', 'when is checkpoint']);
    }

    private function isDurationIntent(string $message): bool
    {
        return Str::contains($message, [
            'how much time',
            'time before graduation',
            'when will i graduate',
            'graduation',
            'duration',
            'time left',
        ]);
    }

    private function isIdealPeriodIntent(string $message): bool
    {
        return Str::contains($message, [
            'ideal period',
            'current checkpoint',
            'what should i focus',
            'current stage',
            'what should i do now',
        ]);
    }

    private function isRecommendationIntent(string $message): bool
    {
        return Str::contains($message, ['recommend', 'suggest', 'reading', 'topic', 'article', 'resource']);
    }

    private function isNewsIntent(string $message): bool
    {
        return Str::contains($message, ['ips', 'university news', 'announcement', 'latest updates', 'usm news']);
    }

    private function isIpsVivaVoceDownloadsIntent(string $message): bool
    {
        if (! Str::contains($message, ['download', 'viva', 'voce', 'borang', 'form'])) {
            return false;
        }

        // Keep it IPS-scoped to avoid hijacking generic "download" requests.
        return Str::contains($message, ['ips', 'viva', 'voce']);
    }

    private function isHelpIntent(string $message): bool
    {
        return Str::contains($message, ['help', 'what can you do', 'how can you help', 'what can i ask']);
    }

    private function buildReminderResponse(User $student): string
    {
        $html = '<div class="usmg-reminder-container">';
        $html .= '<div class="usmg-reminder-heading">📋 Reminder Summary</div>';
        $html .= '<div class="usmg-reminder-grid">';

        // Next Checkpoint Card
        $next = $this->nextCheckpoint($student);
        if ($next) {
            $statusClass = str_contains(strtolower($next['relative']), 'overdue') ? 'usmg-status-alert' : '';
            $html .= '<div class="usmg-reminder-card ' . $statusClass . '">';
            $html .= '<div class="usmg-reminder-card-icon">🎯</div>';
            $html .= '<div class="usmg-reminder-card-content">';
            $html .= '<div class="usmg-reminder-card-label">Next Checkpoint</div>';
            $html .= '<div class="usmg-reminder-card-title">' . htmlspecialchars($next['title']) . '</div>';
            $html .= '<div class="usmg-reminder-card-meta">' . htmlspecialchars($next['date_label']) . '</div>';
            $html .= '<div class="usmg-reminder-card-status">' . htmlspecialchars($next['relative']) . '</div>';
            $html .= '</div></div>';
        } else {
            $html .= '<div class="usmg-reminder-card">';
            $html .= '<div class="usmg-reminder-card-icon">✓</div>';
            $html .= '<div class="usmg-reminder-card-content">';
            $html .= '<div class="usmg-reminder-card-label">Checkpoints</div>';
            $html .= '<div class="usmg-reminder-card-title">No pending checkpoint</div>';
            $html .= '<div class="usmg-reminder-card-meta">Keep your checkpoint record updated</div>';
            $html .= '</div></div>';
        }

        // Progress Snapshot Card
        $snapshot = $this->progressSnapshot($student);
        if ($snapshot) {
            $riskClass = $snapshot['risk_status'] === 'high_risk' ? 'usmg-status-alert' : 'usmg-status-ok';
            $html .= '<div class="usmg-reminder-card ' . $riskClass . '">';
            $html .= '<div class="usmg-reminder-card-icon">📈</div>';
            $html .= '<div class="usmg-reminder-card-content">';
            $html .= '<div class="usmg-reminder-card-label">Progress</div>';
            $html .= '<div class="usmg-reminder-card-title">Semester ' . htmlspecialchars($snapshot['current_semester']) . '</div>';
            $html .= '<div class="usmg-reminder-card-meta">Stage: ' . htmlspecialchars($snapshot['stage_title']) . '</div>';
            $html .= '<div class="usmg-reminder-card-status">Risk: ' . htmlspecialchars($snapshot['risk_status']) . '</div>';
            $html .= '</div></div>';
        }

        // Next Meeting Card
        $nextMeeting = $this->nextApprovedMeeting($student);
        if ($nextMeeting) {
            $html .= '<div class="usmg-reminder-card">';
            $html .= '<div class="usmg-reminder-card-icon">📅</div>';
            $html .= '<div class="usmg-reminder-card-content">';
            $html .= '<div class="usmg-reminder-card-label">Next Meeting</div>';
            $html .= '<div class="usmg-reminder-card-title">' . htmlspecialchars($nextMeeting->topic) . '</div>';
            $html .= '<div class="usmg-reminder-card-meta">' . $nextMeeting->requested_start->format('d M Y H:i') . '</div>';
            $html .= '</div></div>';
        } else if (! $this->hasRecentSupervisorInteraction($student)) {
            $html .= '<div class="usmg-reminder-card usmg-status-alert">';
            $html .= '<div class="usmg-reminder-card-icon">⚠️</div>';
            $html .= '<div class="usmg-reminder-card-content">';
            $html .= '<div class="usmg-reminder-card-label">Follow-up</div>';
            $html .= '<div class="usmg-reminder-card-title">No meeting this month</div>';
            $html .= '<div class="usmg-reminder-card-meta">Please apply for a new meeting slot</div>';
            $html .= '</div></div>';
        }

        // Suggested Topic Card
        $topic = $this->pickRotatingSuggestion((string) $student->cluster);
        if ($topic) {
            $html .= '<div class="usmg-reminder-card">';
            $html .= '<div class="usmg-reminder-card-icon">💡</div>';
            $html .= '<div class="usmg-reminder-card-content">';
            $html .= '<div class="usmg-reminder-card-label">Suggested Topic</div>';
            $html .= '<div class="usmg-reminder-card-title">' . htmlspecialchars($topic['title']) . '</div>';
            $html .= '<div class="usmg-reminder-card-link"><a href="' . htmlspecialchars($topic['url']) . '" target="_blank" rel="noopener noreferrer">View Resource →</a></div>';
            $html .= '</div></div>';
        }

        // Notification Status
        try {
            $sent = $this->overdueNotificationService->dispatchForStudent($student->fresh('supervisor'));
            if ($sent > 0) {
                $html .= '<div class="usmg-reminder-card usmg-status-info">';
                $html .= '<div class="usmg-reminder-card-icon">🔔</div>';
                $html .= '<div class="usmg-reminder-card-content">';
                $html .= '<div class="usmg-reminder-card-label">Notifications</div>';
                $html .= '<div class="usmg-reminder-card-title">Reminders Sent</div>';
                $html .= '<div class="usmg-reminder-card-meta">' . $sent . ' overdue checkpoint reminder(s) sent</div>';
                $html .= '</div></div>';
            }
        } catch (\Throwable) {
            // Ignore notification dispatch failures in chatbot flow.
        }

        $html .= '</div>';
        $html .= '<div class="usmg-reminder-footer">💬 You can also ask: "is my supervisor free this week" or "show latest IPS news"</div>';
        $html .= '</div>';

        return $html;
    }

    private function buildSupervisorAvailabilityResponse(User $student): string
    {
        $supervisor = $student->supervisor;

        if (! $supervisor || ! $supervisor->hasRole('lecturer')) {
            return 'I cannot check availability yet because your supervisor is not assigned in the system.';
        }

        $start = CarbonImmutable::today();
        $end = $start->addDays(6)->endOfDay();
        $events = collect();
        $source = 'Google Calendar';

        try {
            $events = collect($this->googleCalendarService->getEventsForUser(
                $supervisor,
                $start->toDateString(),
                $end->toDateString()
            ));
        } catch (\Throwable) {
            $source = 'local schedule';
            $events = Schedule::query()
                ->where('user_id', $supervisor->id)
                ->whereBetween('start_time', [$start->toDateTimeString(), $end->toDateTimeString()])
                ->orderBy('start_time')
                ->get()
                ->map(function (Schedule $schedule) {
                    return [
                        'title' => $schedule->title,
                        'start' => $schedule->start_time,
                        'end' => $schedule->end_time,
                    ];
                });
        }

        $busyDays = $events
            ->map(fn (array $event) => $this->safeParseDate((string) ($event['start'] ?? '')))
            ->filter()
            ->map(fn (CarbonImmutable $date) => $date->toDateString())
            ->unique()
            ->values();

        $windowDays = collect(range(0, 6))->map(fn (int $offset) => $start->addDays($offset));

        $freeDays = $windowDays
            ->filter(fn (CarbonImmutable $day) => ! $busyDays->contains($day->toDateString()))
            ->map(fn (CarbonImmutable $day) => $day->format('D, d M'))
            ->values();

        $html = '<div class="usmg-schedule-container">';
        $html .= '<div class="usmg-schedule-heading">📅 Supervisor Availability - ' . htmlspecialchars($supervisor->name) . '</div>';
        $html .= '<div class="usmg-schedule-source">Based on ' . $source . ' (next 7 days)</div>';

        if (! $freeDays->isEmpty()) {
            $html .= '<div class="usmg-schedule-section">';
            $html .= '<div class="usmg-schedule-section-title">✓ Free Days:</div>';
            $html .= '<div class="usmg-schedule-chips">';
            foreach ($freeDays->take(4) as $day) {
                $html .= '<span class="usmg-schedule-chip usmg-chip-free">' . htmlspecialchars($day) . '</span>';
            }
            $html .= '</div></div>';
        } else {
            $html .= '<div class="usmg-schedule-alert">No fully free days detected in the next 7 days.</div>';
        }

        $busyPreview = $events->take(3);
        if ($busyPreview->isNotEmpty()) {
            $html .= '<div class="usmg-schedule-section">';
            $html .= '<div class="usmg-schedule-section-title">⏱️ Busy Slots:</div>';
            foreach ($busyPreview as $event) {
                $startDate = $this->safeParseDate((string) ($event['start'] ?? ''));
                $label = $startDate ? $startDate->format('D, d M H:i') : 'Unknown time';
                $html .= '<div class="usmg-schedule-event">';
                $html .= '<div class="usmg-schedule-event-title">' . htmlspecialchars((string) ($event['title'] ?? 'Untitled')) . '</div>';
                $html .= '<div class="usmg-schedule-event-time">' . htmlspecialchars($label) . '</div>';
                $html .= '</div>';
            }
            $html .= '</div>';
        }

        $html .= '<div class="usmg-schedule-footer">For exact booking, please confirm with your supervisor directly.</div>';
        $html .= '</div>';

        return $html;
    }

    private function buildMeetingRecommendationResponse(User $student): string
    {
        $supervisor = $student->supervisor;

        if (! $supervisor || ! $supervisor->hasRole('lecturer')) {
            return 'I cannot suggest meeting slots yet because your supervisor is not assigned in the system.';
        }

        $slots = $this->meetingRequestService->recommendSlotsForStudent($student, daysAhead: 14, maxSlots: 4);

        if ($slots === []) {
            return 'I could not find clear slots in the next 14 days. Please check the schedule page and try applying for another time.';
        }

        $html = '<div class="usmg-slots-container">';
        $html .= '<div class="usmg-slots-heading">⏰ Suggested Meeting Slots</div>';
        $html .= '<div class="usmg-slots-subtext">With ' . htmlspecialchars($supervisor->name) . ' (next 14 days)</div>';
        $html .= '<div class="usmg-slots-grid">';

        foreach ($slots as $slot) {
            $html .= '<div class="usmg-slot-card">';
            $html .= '<div class="usmg-slot-label">' . htmlspecialchars($slot['label'] ?? 'Suggested slot') . '</div>';
            $html .= '</div>';
        }

        $html .= '</div>';

        if (! $this->hasRecentSupervisorInteraction($student)) {
            $html .= '<div class="usmg-slots-reminder">⚠️ Reminder: No approved meeting in the last 30 days. Please submit one of these slots.</div>';
        }

        $html .= '<div class="usmg-slots-footer">Apply from the Schedule page by selecting a slot on the calendar.</div>';
        $html .= '</div>';

        return $html;
    }

    private function buildCheckpointResponse(User $student): string
    {
        $next = $this->nextCheckpoint($student);

        if (! $next) {
            return '<div class="usmg-info-box"><i class="fas fa-info-circle"></i> I cannot compute your next checkpoint yet. Please ensure start date is set, then update your checkpoint records.</div>';
        }

        $statusClass = str_contains(strtolower($next['relative']), 'overdue') ? 'usmg-status-alert' : '';
        $html = '<div class="usmg-checkpoint-container">';
        $html .= '<div class="usmg-checkpoint-card ' . $statusClass . '">';
        $html .= '<div class="usmg-checkpoint-icon">🎯</div>';
        $html .= '<div class="usmg-checkpoint-content">';
        $html .= '<div class="usmg-checkpoint-label">Next Checkpoint</div>';
        $html .= '<div class="usmg-checkpoint-title">' . htmlspecialchars($next['title']) . '</div>';
        $html .= '<div class="usmg-checkpoint-date">📅 ' . htmlspecialchars($next['date_label']) . '</div>';
        $html .= '<div class="usmg-checkpoint-status">' . htmlspecialchars($next['relative']) . '</div>';
        $html .= '</div></div>';
        $html .= '</div>';
        return $html;
    }

    private function buildDurationResponse(User $student): string
    {
        if (! $student->start_date) {
            return '<div class="usmg-info-box"><i class="fas fa-info-circle"></i> I need your start date to estimate time before graduation.</div>';
        }

        try {
            $mode = (string) ($student->study_mode ?: 'full_time');
            $rules = $this->timelineService->rulesForStudyMode($mode);
            $timeline = $this->timelineService->buildSemesterTimeline($student->start_date, $mode);
            $currentSemester = max(1, $this->timelineService->currentSemesterNumber($student->start_date, $mode));

            $expectedIndex = max(0, ((int) $rules['expected_semesters']) - 1);
            $maxIndex = max(0, ((int) $rules['max_semesters']) - 1);

            $expectedEnd = CarbonImmutable::instance($timeline[$expectedIndex]['end']);
            $maxEnd = CarbonImmutable::instance($timeline[$maxIndex]['end']);

            $remainingExpected = max(0, (int) $rules['expected_semesters'] - $currentSemester);
            $remainingMax = max(0, (int) $rules['max_semesters'] - $currentSemester);

            $html = '<div class="usmg-timeline-container">';
            $html .= '<div class="usmg-timeline-heading">⏳ Candidature Timeline</div>';
            $html .= '<div class="usmg-timeline-card"><div class="usmg-timeline-card-icon">📍</div><div class="usmg-timeline-card-content">';
            $html .= '<div class="usmg-timeline-card-label">Current</div>';
            $html .= '<div class="usmg-timeline-card-value">Semester ' . $currentSemester . '</div>';
            $html .= '</div></div>';
            $html .= '<div class="usmg-timeline-card"><div class="usmg-timeline-card-icon">🎓</div><div class="usmg-timeline-card-content">';
            $html .= '<div class="usmg-timeline-card-label">Expected Graduation</div>';
            $html .= '<div class="usmg-timeline-card-value">' . $expectedEnd->format('d M Y') . '</div>';
            $html .= '<div class="usmg-timeline-card-meta">' . $remainingExpected . ' semester(s) remaining</div>';
            $html .= '</div></div>';
            $html .= '<div class="usmg-timeline-card"><div class="usmg-timeline-card-icon">⏰</div><div class="usmg-timeline-card-content">';
            $html .= '<div class="usmg-timeline-card-label">Maximum Candidature</div>';
            $html .= '<div class="usmg-timeline-card-value">' . $maxEnd->format('d M Y') . '</div>';
            $html .= '<div class="usmg-timeline-card-meta">' . $remainingMax . ' semester(s) left before overdue</div>';
            $html .= '</div></div></div>';
            return $html;
        } catch (\Throwable) {
            return '<div class="usmg-info-box usmg-error"><i class="fas fa-exclamation-triangle"></i> I could not compute graduation duration right now. Please check that your profile and timeline data are complete.</div>';
        }
    }

    private function buildIdealPeriodResponse(User $student): string
    {
        $snapshot = $this->progressSnapshot($student);
        $next = $this->nextCheckpoint($student);

        if (! $snapshot) {
            return '<div class="usmg-info-box"><i class="fas fa-info-circle"></i> I need your start date before I can identify your ideal checkpoint period.</div>';
        }

        $riskClass = $snapshot['risk_status'] === 'high_risk' ? 'usmg-status-alert' : 'usmg-status-ok';
        $html = '<div class="usmg-period-container"><div class="usmg-period-heading">📍 Your Current Focus</div>';
        $html .= '<div class="usmg-period-card ' . $riskClass . '"><div class="usmg-period-card-icon">📈</div><div class="usmg-period-card-content">';
        $html .= '<div class="usmg-period-card-label">Stage</div>';
        $html .= '<div class="usmg-period-card-title">' . htmlspecialchars($snapshot['stage_title']) . '</div>';
        $html .= '<div class="usmg-period-card-meta">Semester ' . $snapshot['current_semester'] . ' · Risk: ' . htmlspecialchars($snapshot['risk_status']) . '</div>';
        $html .= '</div></div>';
        if ($next) {
            $html .= '<div class="usmg-period-card"><div class="usmg-period-card-icon">🎯</div><div class="usmg-period-card-content">';
            $html .= '<div class="usmg-period-card-label">Recommended Focus</div>';
            $html .= '<div class="usmg-period-card-title">' . htmlspecialchars($next['title']) . '</div>';
            $html .= '<div class="usmg-period-card-meta">Due by ' . htmlspecialchars($next['date_label']) . '</div>';
            $html .= '</div></div>';
        }
        $html .= '</div>';
        return $html;
    }

    private function buildRecommendationResponse(User $student): string
    {
        $cluster = (string) ($student->cluster ?? 'general');
        $suggestions = collect();

        try {
            $suggestions = collect($this->paperRecommendationService->recommendForStudent($student, 3));
        } catch (\Throwable) {
            $suggestions = collect();
        }

        if ($suggestions->isEmpty()) {
            $suggestions = $this->clusterSuggestions($cluster)->take(3);
        }

        $html = '<div class="usmg-resources-container"><div class="usmg-resources-heading">📚 Suggested Readings</div>';
        $html .= '<div class="usmg-resources-cluster">Cluster: <strong>' . htmlspecialchars($cluster) . '</strong></div>';
        $html .= '<div class="usmg-resources-grid">';
        foreach ($suggestions as $suggestion) {
            $title = (string) ($suggestion['title'] ?? 'Reading');
            $url = (string) ($suggestion['url'] ?? '');
            if ($url === '') {
                continue;
            }
            $html .= '<div class="usmg-resource-card"><div class="usmg-resource-title">' . htmlspecialchars($title) . '</div>';
            $html .= '<div class="usmg-resource-link"><a href="' . htmlspecialchars($url) . '" target="_blank" rel="noopener noreferrer">Read More →</a></div></div>';
        }
        $html .= '</div><div class="usmg-resources-tip">💡 Ask "reminder" anytime and I will include one rotating topic suggestion.</div></div>';
        return $html;
    }

    private function buildNewsResponse(): string
    {
        try {
            $items = $this->ipsService->latestNews(5);
        } catch (\Throwable $e) {
            return 'I cannot fetch IPS news at the moment. Please try again shortly. (' . $e->getMessage() . ')';
        }

        $html = '<div class="ips-news-heading">Latest announcements from IPS USM:</div>';
        $html .= '<div class="ips-news-grid">';

        foreach ($items as $item) {
            $title = e($item['title']);
            $url = e($item['url']);
            $image = e($item['image'] ?? '');
            $date = e($item['date'] ?? '');

            $html .= '<div class="ips-news-card">';
            if ($image !== '') {
                $html .= '<a href="' . $url . '" target="_blank" rel="noopener noreferrer"><img src="' . $image . '" alt="' . $title . '" class="ips-news-img" loading="lazy"></a>';
            }
            $html .= '<div class="ips-news-body">';
            $html .= '<a href="' . $url . '" target="_blank" rel="noopener noreferrer" class="ips-news-title">' . $title . '</a>';
            if ($date !== '') {
                $html .= '<small class="ips-news-date">' . $date . '</small>';
            }
            $html .= '</div></div>';
        }

        $html .= '</div>';

        return $html;
    }

    private function buildVivaVoceDownloadsResponse(): string
    {
        try {
            $items = $this->ipsService->vivaVoceDownloads(10);
        } catch (\Throwable $e) {
            return 'I cannot fetch IPS Viva Voce downloads at the moment. Please try again shortly. (' . $e->getMessage() . ')';
        }

        $html = '<div class="ips-news-heading">Latest Viva Voce downloads (IPS USM):</div>';
        $html .= '<div class="ips-news-grid">';

        foreach ($items as $item) {
            $title = e($item['title']);
            $sourceUrl = (string) ($item['url'] ?? '');
            $section = e((string) ($item['section'] ?? ''));

            $encoded = $this->base64UrlEncode($sourceUrl);
            $preferredName = $this->base64UrlEncode((string) ($item['title'] ?? 'ips-download'));

            $downloadUrl = URL::temporarySignedRoute(
                'ips.download',
                now()->addMinutes(10),
                ['u' => $encoded, 'n' => $preferredName],
                false
            );

            $html .= '<div class="ips-news-card">';
            $html .= '<div class="ips-news-body">';
            $html .= '<a href="' . e($downloadUrl) . '" class="ips-news-title">' . $title . '</a>';
            if ($section !== '') {
                $html .= '<small class="ips-news-date">' . $section . '</small>';
            }
            $html .= '<small class="ips-news-date"><a href="' . e($downloadUrl) . '">Download</a> &nbsp;|&nbsp; <a href="' . e($sourceUrl) . '" target="_blank" rel="noopener noreferrer">Open</a></small>';
            $html .= '</div></div>';
        }

        $html .= '</div>';

        return $html;
    }

    private function base64UrlEncode(string $value): string
    {
        $b64 = base64_encode($value);
        return rtrim(strtr($b64, '+/', '-_'), '=');
    }

    private function helpText(): string
    {
        $html = '<div class="usmg-help-container"><div class="usmg-help-heading">💬 What I Can Help You With</div><div class="usmg-help-grid">';
        $items = [
            ['icon' => '📅', 'title' => 'Supervisor Schedule', 'text' => 'Is my supervisor free this week?'],
            ['icon' => '⏰', 'title' => 'Meeting Slots', 'text' => 'Suggest suitable meeting times with my supervisor.'],
            ['icon' => '🎯', 'title' => 'Next Checkpoint', 'text' => 'When is my next checkpoint?'],
            ['icon' => '⏳', 'title' => 'Graduation Timeline', 'text' => 'How much time before graduation?'],
            ['icon' => '📈', 'title' => 'My Progress', 'text' => 'What is my ideal period/current stage now?'],
            ['icon' => '📚', 'title' => 'Resources', 'text' => 'Recommend reading topics for my cluster.'],
            ['icon' => '📰', 'title' => 'Updates', 'text' => 'Show latest IPS USM announcements.'],
            ['icon' => '📥', 'title' => 'Viva Voce Files', 'text' => 'IPS viva voce download'],
            ['icon' => '🔔', 'title' => 'Reminders', 'text' => 'Reminder'],
        ];
        foreach ($items as $item) {
            $html .= '<div class="usmg-help-item"><div class="usmg-help-item-icon">' . $item['icon'] . '</div><div class="usmg-help-item-content">';
            $html .= '<div class="usmg-help-item-title">' . htmlspecialchars($item['title']) . '</div>';
            $html .= '<div class="usmg-help-item-text">"' . htmlspecialchars($item['text']) . '"</div></div></div>';
        }
        $html .= '</div></div>';
        return $html;
    }

    /**
     * @return array{title:string,date_label:string,relative:string}|null
     */
    private function nextCheckpoint(User $student): ?array
    {
        if (! $student->start_date) {
            return null;
        }

        $checkpoint = $student->checkpoint ?: StudentCheckpoint::firstOrCreate(['user_id' => $student->id]);

        try {
            $targets = $this->timelineService->defaultProfessionalCheckpoints($student->start_date, $student->study_mode ?: 'full_time');
        } catch (\Throwable) {
            return null;
        }

        $today = CarbonImmutable::today();

        foreach ($targets as $target) {
            $field = (string) ($target['field'] ?? '');
            if ($field === '') {
                continue;
            }

            if ($this->isCheckpointCompleted($checkpoint, $field)) {
                continue;
            }

            $eventDateRaw = $target['event_date'] ?? null;
            $eventDate = $eventDateRaw instanceof CarbonInterface
                ? CarbonImmutable::instance($eventDateRaw)
                : CarbonImmutable::parse((string) $eventDateRaw);

            $diffDays = (int) floor($today->diffInDays($eventDate, false));

                if ($diffDays === 0) {
                    $relative = 'Due today';
                } elseif ($diffDays < 0) {
                    $overdueDays = abs($diffDays);

                    $relative = $overdueDays === 1
                        ? '1 day overdue'
                        : $overdueDays . ' days overdue';
                } elseif ($diffDays < 30) {
                    $relative = $diffDays === 1
                        ? 'Due in 1 day'
                        : 'Due in ' . $diffDays . ' days';
                } elseif ($diffDays < 365) {
                    $months = (int) floor($diffDays / 30);

                    $relative = $months === 1
                        ? 'Due in about 1 month'
                        : 'Due in about ' . $months . ' months';
                } else {
                    $years = (int) floor($diffDays / 365);
                    $remainingMonths = (int) floor(($diffDays % 365) / 30);

                    $relative = $years === 1
                        ? 'Due in about 1 year'
                        : 'Due in about ' . $years . ' years';

                    if ($remainingMonths > 0) {
                        $relative .= $remainingMonths === 1
                            ? ' 1 month'
                            : ' ' . $remainingMonths . ' months';
                    }
                }
            return [
                'title' => (string) ($target['title'] ?? 'Checkpoint'),
                'date_label' => $eventDate->format('d M Y'),
                'relative' => $relative,
            ];
        }

        return null;
    }

    private function isCheckpointCompleted(StudentCheckpoint $checkpoint, string $field): bool
    {
        if ($field === 'ekc500_admin_approved') {
            return (bool) $checkpoint->ekc500_admin_approved;
        }

        return ! empty($checkpoint->{$field});
    }

    /**
     * @return array{current_semester:int,stage_title:string,risk_status:string}|null
     */
    private function progressSnapshot(User $student): ?array
    {
        if (! $student->start_date) {
            return null;
        }

        try {
            $snapshot = $this->timelineService->progressSnapshot(
                $student->start_date,
                $student->study_mode ?: 'full_time',
                null,
                $student->checkpoint
            );

            return [
                'current_semester' => (int) ($snapshot['current_semester'] ?? 0),
                'stage_title' => (string) ($snapshot['stage_title'] ?? 'Not available'),
                'risk_status' => (string) ($snapshot['risk_status'] ?? 'unknown'),
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return Collection<int, array{title:string,url:string}>
     */
    private function clusterSuggestions(string $cluster): Collection
    {
        $name = Str::lower(trim($cluster));

        $catalog = [
            'data_to_knowledge' => [
                [
                    'title' => 'Systematic literature review for data science research',
                    'url' => 'https://scholar.google.com/scholar?q=systematic+literature+review+data+science+research',
                ],
                [
                    'title' => 'Knowledge discovery process (KDD) and CRISP-DM',
                    'url' => 'https://scholar.google.com/scholar?q=KDD+CRISP-DM+research+methodology',
                ],
                [
                    'title' => 'Explainable AI for postgraduate thesis work',
                    'url' => 'https://scholar.google.com/scholar?q=explainable+ai+graduate+research',
                ],
                [
                    'title' => 'Automated knowledge graph construction using large language models',
                    'url' => 'https://scholar.google.com/scholar?q=automated+knowledge+graph+construction+LLM+scientific+literature',
                ],
                [
                    'title' => 'Federated learning for privacy-preserving predictive analytics',
                    'url' => 'https://scholar.google.com/scholar?q=federated+learning+privacy+preserving+healthcare+analytics',
                ],
                [
                    'title' => 'Causal inference frameworks for temporal data streams',
                    'url' => 'https://scholar.google.com/scholar?q=causal+inference+temporal+data+streams+financial+markets',
                ],
                [
                    'title' => 'Semantic data integration across heterogeneous IoT ecosystems',
                    'url' => 'https://scholar.google.com/scholar?q=semantic+data+integration+heterogeneous+IoT',
                ],
                [
                    'title' => 'Graph neural networks for relational reasoning in knowledge bases',
                    'url' => 'https://scholar.google.com/scholar?q=graph+neural+networks+relational+reasoning+knowledge+base',
                ],
                [
                    'title' => 'Transfer learning strategies for low-resource NLP tasks',
                    'url' => 'https://scholar.google.com/scholar?q=transfer+learning+low+resource+NLP',
                ],
            ],

            'service_computing' => [
                [
                    'title' => 'Adaptive microservices orchestration using reinforcement learning',
                    'url' => 'https://scholar.google.com/scholar?q=adaptive+microservices+orchestration+reinforcement+learning+cloud',
                ],
                [
                    'title' => 'QoS-aware web service composition under dynamic conditions',
                    'url' => 'https://scholar.google.com/scholar?q=QoS+aware+web+service+composition+dynamic+uncertain',
                ],
                [
                    'title' => 'Serverless computing resource allocation with SLA guarantees',
                    'url' => 'https://scholar.google.com/scholar?q=serverless+computing+resource+allocation+SLA+deep+learning',
                ],
                [
                    'title' => 'Trust and reputation management in multi-cloud API ecosystems',
                    'url' => 'https://scholar.google.com/scholar?q=trust+reputation+management+multi+cloud+API+ecosystem',
                ],
                [
                    'title' => 'Edge-cloud continuum service scheduling for latency-sensitive applications',
                    'url' => 'https://scholar.google.com/scholar?q=edge+cloud+continuum+service+scheduling+latency+sensitive',
                ],
                [
                    'title' => 'Blockchain-based SLA enforcement in distributed cloud systems',
                    'url' => 'https://scholar.google.com/scholar?q=blockchain+service+level+agreement+enforcement+cloud',
                ],
                [
                    'title' => 'API security and threat modeling for cloud microservices architectures',
                    'url' => 'https://scholar.google.com/scholar?q=API+security+threat+modeling+cloud+microservices',
                ],
                [
                    'title' => 'Autonomous service fault detection and recovery in IoT-cloud pipelines',
                    'url' => 'https://scholar.google.com/scholar?q=autonomous+service+fault+detection+recovery+IoT+cloud',
                ],
                [
                    'title' => 'Cybersecurity risk assessment methods for cloud service organizations',
                    'url' => 'https://scholar.google.com/scholar?q=cybersecurity+risk+assessment+methods+survey',
                ],
            ],

            'enabling_technologies_&_infrastructres' => [
                [
                    'title' => 'Energy-efficient neural network inference on heterogeneous edge hardware',
                    'url' => 'https://scholar.google.com/scholar?q=energy+efficient+neural+network+inference+edge+hardware',
                ],
                [
                    'title' => 'Quantum-resilient cryptographic protocols for distributed ledger systems',
                    'url' => 'https://scholar.google.com/scholar?q=quantum+resilient+cryptographic+protocols+distributed+ledger',
                ],
                [
                    'title' => 'Self-healing network architectures for autonomous fault detection and recovery',
                    'url' => 'https://scholar.google.com/scholar?q=self+healing+network+architecture+autonomous+fault+detection',
                ],
                [
                    'title' => 'High-performance in-memory computing frameworks for graph-based workloads',
                    'url' => 'https://scholar.google.com/scholar?q=high+performance+in+memory+computing+graph+workloads',
                ],
                [
                    'title' => 'Hardware-software co-design for real-time AI acceleration in embedded systems',
                    'url' => 'https://scholar.google.com/scholar?q=hardware+software+co+design+real+time+AI+acceleration+embedded',
                ],
                [
                    'title' => '5G network slicing for ultra-low latency industrial IoT applications',
                    'url' => 'https://scholar.google.com/scholar?q=5G+network+slicing+ultra+low+latency+industrial+IoT',
                ],
                [
                    'title' => 'Containerization and orchestration security in Kubernetes-based deployments',
                    'url' => 'https://scholar.google.com/scholar?q=containerization+orchestration+security+kubernetes',
                ],
                [
                    'title' => 'Software-defined networking for adaptive cyber-physical system management',
                    'url' => 'https://scholar.google.com/scholar?q=software+defined+networking+cyber+physical+system+management',
                ],
                [
                    'title' => 'Green computing strategies for sustainable data center operations',
                    'url' => 'https://scholar.google.com/scholar?q=green+computing+sustainable+data+center+operations',
                ],
            ],

            'default' => [
                [
                    'title' => 'Research methodology for postgraduate students',
                    'url' => 'https://scholar.google.com/scholar?q=postgraduate+research+methodology',
                ],
                [
                    'title' => 'How to write a strong literature review',
                    'url' => 'https://scholar.google.com/scholar?q=how+to+write+literature+review+thesis',
                ],
                [
                    'title' => 'Academic writing and thesis structuring',
                    'url' => 'https://scholar.google.com/scholar?q=thesis+writing+structure+graduate+students',
                ],
                [
                    'title' => 'Choosing a research topic in computer science',
                    'url' => 'https://scholar.google.com/scholar?q=choosing+research+topic+computer+science+phd',
                ],
                [
                    'title' => 'How to prepare for a PhD viva voce examination',
                    'url' => 'https://scholar.google.com/scholar?q=phd+viva+voce+preparation+computer+science',
                ],
                [
                    'title' => 'Ethical considerations in computer science research',
                    'url' => 'https://scholar.google.com/scholar?q=ethical+considerations+computer+science+research',
                ],
            ],
        ];

        $key = Str::contains($name, ['data to knowledge', 'data_to_knowledge', 'dtk'])
            ? 'data_to_knowledge'
            : 'default';

        return collect($catalog[$key]);
    }

    /**
     * @return array{title:string,url:string}|null
     */
    private function pickRotatingSuggestion(string $cluster): ?array
    {
        $suggestions = $this->clusterSuggestions($cluster)->values();

        if ($suggestions->isEmpty()) {
            return null;
        }

        $index = now()->dayOfYear % $suggestions->count();

        return $suggestions->get($index);
    }

    private function hasRecentSupervisorInteraction(User $student): bool
    {
        return MeetingRequest::query()
            ->where('student_id', $student->id)
            ->where('status', MeetingRequest::STATUS_APPROVED)
            ->where('requested_start', '>=', now()->subDays(30))
            ->exists();
    }

    private function nextApprovedMeeting(User $student): ?MeetingRequest
    {
        return MeetingRequest::query()
            ->where('student_id', $student->id)
            ->where('status', MeetingRequest::STATUS_APPROVED)
            ->where('requested_start', '>=', now())
            ->orderBy('requested_start')
            ->first();
    }

    private function safeParseDate(string $value): ?CarbonImmutable
    {
        if ($value === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
