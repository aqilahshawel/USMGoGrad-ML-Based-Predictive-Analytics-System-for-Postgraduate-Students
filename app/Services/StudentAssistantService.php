<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\StudentCheckpoint;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class StudentAssistantService
{
    public function __construct(
        private readonly CandidatureTimelineService $timelineService,
        private readonly GoogleCalendarService $googleCalendarService,
        private readonly OverdueCheckpointNotificationService $overdueNotificationService,
        private readonly IpsNewsService $ipsNewsService,
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

    private function isCheckpointIntent(string $message): bool
    {
        return Str::contains($message, ['next checkpoint', 'checkpoint due', 'when is checkpoint', 'next milestone']);
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

    private function isHelpIntent(string $message): bool
    {
        return Str::contains($message, ['help', 'what can you do', 'how can you help', 'what can i ask']);
    }

    private function buildReminderResponse(User $student): string
    {
        $parts = [];

        $parts[] = 'Reminder summary for you:';

        $next = $this->nextCheckpoint($student);
        if ($next) {
            $parts[] = '- Next checkpoint: ' . $next['title'] . ' on ' . $next['date_label'] . ' (' . $next['relative'] . ').';
        } else {
            $parts[] = '- No pending checkpoint is detected yet. Keep your checkpoint record updated.';
        }

        $snapshot = $this->progressSnapshot($student);
        if ($snapshot) {
            $parts[] = '- Current progress: Semester ' . $snapshot['current_semester'] . ', stage: ' . $snapshot['stage_title'] . ', risk: ' . $snapshot['risk_status'] . '.';
        }

        $topic = $this->pickRotatingSuggestion((string) $student->cluster);
        if ($topic) {
            $parts[] = '- Suggested topic this time: ' . $topic['title'] . ' (' . $topic['url'] . ').';
        }

        try {
            $sent = $this->overdueNotificationService->dispatchForStudent($student->fresh('supervisor'));
            if ($sent > 0) {
                $parts[] = '- I also sent ' . $sent . ' overdue checkpoint reminder notification(s).';
            }
        } catch (\Throwable) {
            // Ignore notification dispatch failures in chatbot flow.
        }

        $parts[] = 'You can also ask: "is my supervisor free this week" or "show latest IPS news".';

        return implode("\n", $parts);
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

        $lines = [];
        $lines[] = 'Based on ' . $source . ' for your supervisor (' . $supervisor->name . ') this week:';

        if ($freeDays->isEmpty()) {
            $lines[] = '- No fully free day detected in the next 7 days.';
        } else {
            $lines[] = '- Likely free day(s): ' . $freeDays->take(4)->implode(', ') . '.';
        }

        $busyPreview = $events
            ->take(3)
            ->map(function (array $event) {
                $startDate = $this->safeParseDate((string) ($event['start'] ?? ''));
                $label = $startDate ? $startDate->format('D, d M H:i') : 'Unknown time';

                return '- Busy slot: ' . ((string) ($event['title'] ?? 'Untitled event')) . ' (' . $label . ')';
            })
            ->values();

        if ($busyPreview->isNotEmpty()) {
            foreach ($busyPreview as $line) {
                $lines[] = $line;
            }
        }

        $lines[] = 'For exact booking, please still confirm with your supervisor directly.';

        return implode("\n", $lines);
    }

    private function buildCheckpointResponse(User $student): string
    {
        $next = $this->nextCheckpoint($student);

        if (! $next) {
            return 'I cannot compute your next checkpoint yet. Please ensure start date and study mode are set, then update your checkpoint records.';
        }

        return 'Your next checkpoint is ' . $next['title'] . ' on ' . $next['date_label'] . ' (' . $next['relative'] . ').';
    }

    private function buildDurationResponse(User $student): string
    {
        if (! $student->start_date || ! $student->study_mode) {
            return 'I need your start date and study mode to estimate time before graduation.';
        }

        try {
            $rules = $this->timelineService->rulesForStudyMode((string) $student->study_mode);
            $timeline = $this->timelineService->buildSemesterTimeline($student->start_date, (string) $student->study_mode);
            $currentSemester = max(1, $this->timelineService->currentSemesterNumber($student->start_date, (string) $student->study_mode));

            $expectedIndex = max(0, ((int) $rules['expected_semesters']) - 1);
            $maxIndex = max(0, ((int) $rules['max_semesters']) - 1);

            $expectedEnd = CarbonImmutable::instance($timeline[$expectedIndex]['end']);
            $maxEnd = CarbonImmutable::instance($timeline[$maxIndex]['end']);

            $remainingExpected = max(0, (int) $rules['expected_semesters'] - $currentSemester);
            $remainingMax = max(0, (int) $rules['max_semesters'] - $currentSemester);

            return implode("\n", [
                'Estimated candidature timeline:',
                '- Current semester: ' . $currentSemester . '.',
                '- Expected graduation window end: ' . $expectedEnd->format('d M Y') . ' (about ' . $remainingExpected . ' semester(s) remaining).',
                '- Maximum candidature end: ' . $maxEnd->format('d M Y') . ' (' . $remainingMax . ' semester(s) left before overdue).',
            ]);
        } catch (\Throwable) {
            return 'I could not compute graduation duration right now. Please check that your profile and timeline data are complete.';
        }
    }

    private function buildIdealPeriodResponse(User $student): string
    {
        $snapshot = $this->progressSnapshot($student);
        $next = $this->nextCheckpoint($student);

        if (! $snapshot) {
            return 'I need your start date and study mode before I can identify your ideal checkpoint period.';
        }

        $lines = [];
        $lines[] = 'Your current ideal progress focus:';
        $lines[] = '- Stage: ' . $snapshot['stage_title'] . ' (Semester ' . $snapshot['current_semester'] . ').';
        $lines[] = '- Risk status: ' . $snapshot['risk_status'] . '.';

        if ($next) {
            $lines[] = '- Recommended checkpoint focus now: ' . $next['title'] . ' by ' . $next['date_label'] . '.';
        }

        return implode("\n", $lines);
    }

    private function buildRecommendationResponse(User $student): string
    {
        $cluster = (string) ($student->cluster ?? 'general');
        $suggestions = $this->clusterSuggestions($cluster)->take(3);

        $lines = [];
        $lines[] = 'Suggested readings/topics for your cluster (' . $cluster . '):';

        foreach ($suggestions as $suggestion) {
            $lines[] = '- ' . $suggestion['title'] . ': ' . $suggestion['url'];
        }

        $lines[] = 'Ask "reminder" anytime and I will also include one rotating topic suggestion.';

        return implode("\n", $lines);
    }

    private function buildNewsResponse(): string
    {
        try {
            $items = $this->ipsNewsService->latest(5);
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

    private function helpText(): string
    {
        return implode("\n", [
            'You can ask me things like:',
            '- Is my supervisor free this week?',
            '- When is my next checkpoint?',
            '- How much time before graduation?',
            '- What is my ideal period/current stage now?',
            '- Recommend reading topics for my cluster.',
            '- Show latest IPS USM announcements.',
            '- Reminder',
        ]);
    }

    /**
     * @return array{title:string,date_label:string,relative:string}|null
     */
    private function nextCheckpoint(User $student): ?array
    {
        if (! $student->start_date || ! $student->study_mode) {
            return null;
        }

        $checkpoint = $student->checkpoint ?: StudentCheckpoint::firstOrCreate(['user_id' => $student->id]);

        try {
            $targets = $this->timelineService->defaultProfessionalCheckpoints($student->start_date, (string) $student->study_mode);
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

            $diffDays = $today->diffInDays($eventDate, false);
            $relative = $diffDays === 0
                ? 'due today'
                : ($diffDays > 0 ? 'due in ' . $diffDays . ' day(s)' : abs($diffDays) . ' day(s) overdue');

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
        if (! $student->start_date || ! $student->study_mode) {
            return null;
        }

        try {
            $snapshot = $this->timelineService->progressSnapshot($student->start_date, (string) $student->study_mode);

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
